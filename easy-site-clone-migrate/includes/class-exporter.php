<?php
/**
 * Exporter Class
 * Handles site export functionality including database and files
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class ESCM_Exporter {
    
    /**
     * Constructor
     */
    public function __construct() {
        add_action('wp_ajax_escm_export_site', array($this, 'export_site'));
        add_action('wp_ajax_escm_export_database', array($this, 'export_database'));
        add_action('wp_ajax_escm_export_files', array($this, 'export_files'));
    }
    
    /**
     * Export entire site (database + files)
     */
    public function export_site() {
        check_ajax_referer('escm_export_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Permission denied', 'easy-site-clone-migrate')));
        }
        
        try {
            $upload_dir = wp_upload_dir();
            $backup_dir = $upload_dir['basedir'] . '/easy-site-clone-migrate';
            
            if (!file_exists($backup_dir)) {
                wp_mkdir_p($backup_dir);
            }
            
            $timestamp = current_time('Ymd_His');
            $site_name = sanitize_title(get_bloginfo('name'));
            $export_file = $backup_dir . "/{$site_name}_full_{$timestamp}.zip";
            
            // Export database
            $db_file = $this->export_database_to_file($backup_dir, $timestamp);
            
            // Create export info file
            $info_file = $this->create_export_info($backup_dir, $timestamp);
            
            // Create ZIP archive
            $zip = new ZipArchive();
            if ($zip->open($export_file, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
                throw new Exception(__('Failed to create ZIP archive', 'easy-site-clone-migrate'));
            }
            
            // Add database export
            $zip->addFile($db_file, 'database.sql');
            
            // Add export info
            $zip->addFile($info_file, 'export-info.json');
            
            // Add wp-content directory (optional - can be large)
            $wp_content_dir = WP_CONTENT_DIR;
            $this->add_directory_to_zip($zip, $wp_content_dir, 'wp-content');
            
            $zip->close();
            
            // Cleanup temporary files
            unlink($db_file);
            unlink($info_file);
            
            $download_url = ESCM_PLUGIN_URL . 'download.php?file=' . basename($export_file) . '&nonce=' . wp_create_nonce('escm_download_nonce');
            
            wp_send_json_success(array(
                'message' => __('Site exported successfully!', 'easy-site-clone-migrate'),
                'download_url' => $download_url,
                'file_size' => size_format(filesize($export_file))
            ));
            
        } catch (Exception $e) {
            wp_send_json_error(array('message' => $e->getMessage()));
        }
    }
    
    /**
     * Export database only
     */
    public function export_database() {
        check_ajax_referer('escm_export_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Permission denied', 'easy-site-clone-migrate')));
        }
        
        try {
            $sql_content = $this->get_database_export();
            
            header('Content-Type: application/sql');
            header('Content-Disposition: attachment; filename="' . sanitize_title(get_bloginfo('name')) . '_database_' . current_time('Ymd_His') . '.sql"');
            header('Content-Length: ' . strlen($sql_content));
            
            echo $sql_content;
            exit;
            
        } catch (Exception $e) {
            wp_send_json_error(array('message' => $e->getMessage()));
        }
    }
    
    /**
     * Export files only (wp-content)
     */
    public function export_files() {
        check_ajax_referer('escm_export_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Permission denied', 'easy-site-clone-migrate')));
        }
        
        try {
            $upload_dir = wp_upload_dir();
            $backup_dir = $upload_dir['basedir'] . '/easy-site-clone-migrate';
            
            if (!file_exists($backup_dir)) {
                wp_mkdir_p($backup_dir);
            }
            
            $timestamp = current_time('Ymd_His');
            $site_name = sanitize_title(get_bloginfo('name'));
            $export_file = $backup_dir . "/{$site_name}_files_{$timestamp}.zip";
            
            $zip = new ZipArchive();
            if ($zip->open($export_file, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
                throw new Exception(__('Failed to create ZIP archive', 'easy-site-clone-migrate'));
            }
            
            $wp_content_dir = WP_CONTENT_DIR;
            $this->add_directory_to_zip($zip, $wp_content_dir, 'wp-content');
            
            $zip->close();
            
            $download_url = ESCM_PLUGIN_URL . 'download.php?file=' . basename($export_file) . '&nonce=' . wp_create_nonce('escm_download_nonce');
            
            wp_send_json_success(array(
                'message' => __('Files exported successfully!', 'easy-site-clone-migrate'),
                'download_url' => $download_url,
                'file_size' => size_format(filesize($export_file))
            ));
            
        } catch (Exception $e) {
            wp_send_json_error(array('message' => $e->getMessage()));
        }
    }
    
    /**
     * Get database export as SQL string
     */
    private function get_database_export() {
        global $wpdb;
        
        $tables = $wpdb->get_results('SHOW TABLES', ARRAY_N);
        $sql = "-- WordPress Database Export\n";
        $sql .= "-- Site: " . get_bloginfo('name') . "\n";
        $sql .= "-- URL: " . get_bloginfo('url') . "\n";
        $sql .= "-- Generated: " . current_time('mysql') . "\n\n";
        
        foreach ($tables as $table) {
            $table_name = $table[0];
            
            // Get table structure
            $create_table = $wpdb->get_row("SHOW CREATE TABLE `$table_name`", ARRAY_A);
            $sql .= "DROP TABLE IF EXISTS `$table_name`;\n";
            $sql .= $create_table['Create Table'] . ";\n\n";
            
            // Get table data
            $rows = $wpdb->get_results("SELECT * FROM `$table_name`", ARRAY_A);
            
            if (!empty($rows)) {
                foreach ($rows as $row) {
                    $columns = array_keys($row);
                    $values = array_map(array($this, 'escape_value'), array_values($row));
                    
                    $sql .= "INSERT INTO `$table_name` (`" . implode('`, `', $columns) . "`) VALUES (" . implode(', ', $values) . ");\n";
                }
                $sql .= "\n";
            }
        }
        
        return $sql;
    }
    
    /**
     * Export database to file
     */
    private function export_database_to_file($backup_dir, $timestamp) {
        $sql_content = $this->get_database_export();
        $db_file = $backup_dir . "/database_{$timestamp}.sql";
        
        file_put_contents($db_file, $sql_content);
        
        return $db_file;
    }
    
    /**
     * Create export info file
     */
    private function create_export_info($backup_dir, $timestamp) {
        $info = array(
            'site_name' => get_bloginfo('name'),
            'site_url' => get_bloginfo('url'),
            'admin_email' => get_bloginfo('admin_email'),
            'wordpress_version' => get_bloginfo('version'),
            'php_version' => PHP_VERSION,
            'export_date' => current_time('mysql'),
            'plugins' => get_option('active_plugins'),
            'theme' => get_option('stylesheet')
        );
        
        $info_file = $backup_dir . "/export-info_{$timestamp}.json";
        file_put_contents($info_file, json_encode($info, JSON_PRETTY_PRINT));
        
        return $info_file;
    }
    
    /**
     * Add directory to ZIP archive
     */
    private function add_directory_to_zip($zip, $source, $dest) {
        if (is_dir($source)) {
            $dir = dir($source);
            while (($file = $dir->read()) !== false) {
                if ($file == '.' || $file == '..') {
                    continue;
                }
                
                $path = $source . '/' . $file;
                $zip_path = $dest . '/' . $file;
                
                if (is_dir($path)) {
                    $this->add_directory_to_zip($zip, $path, $zip_path);
                } else {
                    // Skip very large files or specific file types if needed
                    $max_file_size = 100 * 1024 * 1024; // 100MB limit per file
                    if (filesize($path) < $max_file_size) {
                        $zip->addFile($path, $zip_path);
                    }
                }
            }
            $dir->close();
        }
    }
    
    /**
     * Escape value for SQL insertion
     */
    private function escape_value($value) {
        if (is_null($value)) {
            return 'NULL';
        } elseif (is_numeric($value)) {
            return $value;
        } else {
            global $wpdb;
            return "'" . $wpdb->_real_escape($value) . "'";
        }
    }
}
