<?php
/**
 * Importer Class
 * Handles site import functionality including database and files
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class ESCM_Importer {
    
    /**
     * Constructor
     */
    public function __construct() {
        add_action('wp_ajax_escm_import_site', array($this, 'import_site'));
        add_action('wp_ajax_escm_import_database', array($this, 'import_database'));
        add_action('wp_ajax_escm_import_files', array($this, 'import_files'));
        add_action('wp_ajax_escm_upload_file', array($this, 'handle_file_upload'));
    }
    
    /**
     * Handle file upload
     */
    public function handle_file_upload() {
        check_ajax_referer('escm_import_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Permission denied', 'easy-site-clone-migrate')));
        }
        
        if (empty($_FILES['import_file'])) {
            wp_send_json_error(array('message' => __('No file uploaded', 'easy-site-clone-migrate')));
        }
        
        $file = $_FILES['import_file'];
        $allowed_types = array('application/zip', 'application/x-zip-compressed', 'application/sql', 'text/plain');
        
        if (!in_array($file['type'], $allowed_types) && !preg_match('/\.(zip|sql)$/i', $file['name'])) {
            wp_send_json_error(array('message' => __('Invalid file type. Please upload a ZIP or SQL file.', 'easy-site-clone-migrate')));
        }
        
        $upload_dir = wp_upload_dir();
        $import_dir = $upload_dir['basedir'] . '/easy-site-clone-migrate/import';
        
        if (!file_exists($import_dir)) {
            wp_mkdir_p($import_dir);
        }
        
        $filename = sanitize_file_name($file['name']);
        $filepath = $import_dir . '/' . $filename;
        
        if (move_uploaded_file($file['tmp_name'], $filepath)) {
            wp_send_json_success(array(
                'message' => __('File uploaded successfully', 'easy-site-clone-migrate'),
                'filepath' => $filepath,
                'filename' => $filename
            ));
        } else {
            wp_send_json_error(array('message' => __('Failed to upload file', 'easy-site-clone-migrate')));
        }
    }
    
    /**
     * Import entire site from ZIP file
     */
    public function import_site() {
        check_ajax_referer('escm_import_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Permission denied', 'easy-site-clone-migrate')));
        }
        
        $filepath = isset($_POST['filepath']) ? sanitize_text_field($_POST['filepath']) : '';
        
        if (empty($filepath) || !file_exists($filepath)) {
            wp_send_json_error(array('message' => __('Import file not found', 'easy-site-clone-migrate')));
        }
        
        try {
            $upload_dir = wp_upload_dir();
            $import_dir = $upload_dir['basedir'] . '/easy-site-clone-migrate/import';
            
            // Extract ZIP file
            $zip = new ZipArchive();
            if ($zip->open($filepath) !== true) {
                throw new Exception(__('Failed to open ZIP file', 'easy-site-clone-migrate'));
            }
            
            // Extract to temporary directory
            $temp_dir = $import_dir . '/temp_' . time();
            if (!file_exists($temp_dir)) {
                wp_mkdir_p($temp_dir);
            }
            
            $zip->extractTo($temp_dir);
            $zip->close();
            
            // Import database
            $db_file = $temp_dir . '/database.sql';
            if (file_exists($db_file)) {
                $this->import_database_from_file($db_file);
            }
            
            // Import files (wp-content)
            $wp_content_dir = $temp_dir . '/wp-content';
            if (is_dir($wp_content_dir)) {
                $this->copy_files($wp_content_dir, WP_CONTENT_DIR);
            }
            
            // Cleanup
            $this->delete_directory($temp_dir);
            
            // Clear cache
            wp_cache_flush();
            
            wp_send_json_success(array(
                'message' => __('Site imported successfully! You may need to log in again.', 'easy-site-clone-migrate'),
                'redirect' => admin_url()
            ));
            
        } catch (Exception $e) {
            wp_send_json_error(array('message' => $e->getMessage()));
        }
    }
    
    /**
     * Import database from SQL file
     */
    public function import_database() {
        check_ajax_referer('escm_import_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Permission denied', 'easy-site-clone-migrate')));
        }
        
        $filepath = isset($_POST['filepath']) ? sanitize_text_field($_POST['filepath']) : '';
        
        if (empty($filepath) || !file_exists($filepath)) {
            wp_send_json_error(array('message' => __('SQL file not found', 'easy-site-clone-migrate')));
        }
        
        try {
            $this->import_database_from_file($filepath);
            
            wp_cache_flush();
            
            wp_send_json_success(array(
                'message' => __('Database imported successfully!', 'easy-site-clone-migrate')
            ));
            
        } catch (Exception $e) {
            wp_send_json_error(array('message' => $e->getMessage()));
        }
    }
    
    /**
     * Import files from ZIP
     */
    public function import_files() {
        check_ajax_referer('escm_import_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Permission denied', 'easy-site-clone-migrate')));
        }
        
        $filepath = isset($_POST['filepath']) ? sanitize_text_field($_POST['filepath']) : '';
        
        if (empty($filepath) || !file_exists($filepath)) {
            wp_send_json_error(array('message' => __('ZIP file not found', 'easy-site-clone-migrate')));
        }
        
        try {
            $upload_dir = wp_upload_dir();
            $import_dir = $upload_dir['basedir'] . '/easy-site-clone-migrate/import';
            
            // Extract ZIP file
            $zip = new ZipArchive();
            if ($zip->open($filepath) !== true) {
                throw new Exception(__('Failed to open ZIP file', 'easy-site-clone-migrate'));
            }
            
            $temp_dir = $import_dir . '/temp_' . time();
            if (!file_exists($temp_dir)) {
                wp_mkdir_p($temp_dir);
            }
            
            $zip->extractTo($temp_dir);
            $zip->close();
            
            // Import files
            $wp_content_dir = $temp_dir . '/wp-content';
            if (is_dir($wp_content_dir)) {
                $this->copy_files($wp_content_dir, WP_CONTENT_DIR);
            }
            
            // Cleanup
            $this->delete_directory($temp_dir);
            
            wp_send_json_success(array(
                'message' => __('Files imported successfully!', 'easy-site-clone-migrate')
            ));
            
        } catch (Exception $e) {
            wp_send_json_error(array('message' => $e->getMessage()));
        }
    }
    
    /**
     * Import database from SQL file
     */
    private function import_database_from_file($sql_file) {
        global $wpdb;
        
        if (!file_exists($sql_file)) {
            throw new Exception(__('SQL file not found', 'easy-site-clone-migrate'));
        }
        
        $sql_content = file_get_contents($sql_file);
        
        if (empty($sql_content)) {
            throw new Exception(__('SQL file is empty', 'easy-site-clone-migrate'));
        }
        
        // Split SQL into individual queries
        $queries = $this->parse_sql_queries($sql_content);
        
        $wpdb->query('SET FOREIGN_KEY_CHECKS=0');
        
        foreach ($queries as $query) {
            $query = trim($query);
            if (!empty($query) && strpos($query, '--') !== 0) {
                $result = $wpdb->query($query);
                if ($result === false && $wpdb->last_error) {
                    // Log error but continue (some errors are expected, like DROP TABLE on non-existent tables)
                    error_log('ESCM Import Error: ' . $wpdb->last_error . ' - Query: ' . substr($query, 0, 100));
                }
            }
        }
        
        $wpdb->query('SET FOREIGN_KEY_CHECKS=1');
        
        // Update site URL if different
        $old_url = $this->extract_old_url($sql_content);
        if ($old_url && $old_url !== get_bloginfo('url')) {
            $new_url = get_bloginfo('url');
            $wpdb->query($wpdb->prepare("UPDATE {$wpdb->options} SET option_value = %s WHERE option_name = 'siteurl'", $new_url));
            $wpdb->query($wpdb->prepare("UPDATE {$wpdb->options} SET option_value = %s WHERE option_name = 'home'", $new_url));
            
            // Update GUIDs and content URLs
            $wpdb->query($wpdb->prepare("UPDATE {$wpdb->posts} SET guid = REPLACE(guid, %s, %s)", $old_url, $new_url));
            $wpdb->query($wpdb->prepare("UPDATE {$wpdb->posts} SET post_content = REPLACE(post_content, %s, %s)", $old_url, $new_url));
            $wpdb->query($wpdb->prepare("UPDATE {$wpdb->postmeta} SET meta_value = REPLACE(meta_value, %s, %s)", $old_url, $new_url));
        }
    }
    
    /**
     * Parse SQL content into individual queries
     */
    private function parse_sql_queries($sql) {
        $queries = array();
        $query = '';
        
        for ($i = 0; $i < strlen($sql); $i++) {
            $char = $sql[$i];
            $query .= $char;
            
            if ($char === ';') {
                $queries[] = $query;
                $query = '';
            }
        }
        
        if (!empty(trim($query))) {
            $queries[] = $query;
        }
        
        return $queries;
    }
    
    /**
     * Extract old site URL from SQL
     */
    private function extract_old_url($sql_content) {
        preg_match('/-- URL: (.+)/', $sql_content, $matches);
        if (isset($matches[1])) {
            return trim($matches[1]);
        }
        return null;
    }
    
    /**
     * Copy files recursively
     */
    private function copy_files($source, $dest) {
        if (!is_dir($source)) {
            return;
        }
        
        $dir = dir($source);
        while (($file = $dir->read()) !== false) {
            if ($file == '.' || $file == '..') {
                continue;
            }
            
            $src_path = $source . '/' . $file;
            $dest_path = $dest . '/' . $file;
            
            if (is_dir($src_path)) {
                if (!file_exists($dest_path)) {
                    wp_mkdir_p($dest_path);
                }
                $this->copy_files($src_path, $dest_path);
            } else {
                // Skip wp-config.php and other critical files
                if ($file === 'wp-config.php') {
                    continue;
                }
                copy($src_path, $dest_path);
            }
        }
        $dir->close();
    }
    
    /**
     * Delete directory recursively
     */
    private function delete_directory($dir) {
        if (!file_exists($dir)) {
            return;
        }
        
        $files = array_diff(scandir($dir), array('.', '..'));
        foreach ($files as $file) {
            $path = $dir . '/' . $file;
            if (is_dir($path)) {
                $this->delete_directory($path);
            } else {
                unlink($path);
            }
        }
        rmdir($dir);
    }
}
