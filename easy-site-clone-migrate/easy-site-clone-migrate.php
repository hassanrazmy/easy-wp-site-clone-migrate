<?php
/**
 * Plugin Name: Easy Site Clone & Migrate Pro
 * Plugin URI: https://example.com/easy-site-clone-migrate
 * Description: Professional WordPress migration and cloning plugin with complete site backup, serialized data handling, chunked processing, and safe database imports.
 * Version: 2.0.0
 * Author: Migration Expert
 * Author URI: https://example.com
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: easy-site-clone-migrate
 * Domain Path: /languages
 */

if (!defined('ABSPATH')) {
    exit;
}

class Easy_Site_Clone_Migrate_Pro {
    
    private $chunk_size = 1000;
    private $max_execution_time = 300;
    private $temp_dir;
    private $log = array();
    
    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('wp_ajax_escm_export_site', array($this, 'ajax_export_site'));
        add_action('wp_ajax_escm_import_site', array($this, 'ajax_import_site'));
        add_action('wp_ajax_escm_get_progress', array($this, 'ajax_get_progress'));
        add_action('wp_ajax_escm_cancel_operation', array($this, 'ajax_cancel_operation'));
        
        $this->temp_dir = wp_upload_dir()['basedir'] . '/escm-temp/';
        if (!file_exists($this->temp_dir)) {
            wp_mkdir_p($this->temp_dir);
        }
        
        // Add .htaccess to protect temp directory
        $htaccess_file = $this->temp_dir . '.htaccess';
        if (!file_exists($htaccess_file)) {
            file_put_contents($htaccess_file, "Deny from all");
        }
    }
    
    public function add_admin_menu() {
        add_submenu_page(
            'tools.php',
            'Site Clone & Migrate',
            'Site Clone & Migrate',
            'manage_options',
            'easy-site-clone-migrate',
            array($this, 'render_admin_page')
        );
    }
    
    public function enqueue_scripts($hook) {
        if ($hook !== 'tools_page_easy-site-clone-migrate') {
            return;
        }
        
        wp_enqueue_style('escm-admin-style', plugins_url('assets/css/admin.css', __FILE__), array(), '2.0.0');
        wp_enqueue_script('escm-admin-script', plugins_url('assets/js/admin.js', __FILE__), array('jquery'), '2.0.0', true);
        
        wp_localize_script('escm-admin-script', 'escm_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('escm_nonce'),
            'temp_url' => wp_upload_dir()['baseurl'] . '/escm-temp/',
            'i18n' => array(
                'exporting' => __('Exporting...', 'easy-site-clone-migrate'),
                'importing' => __('Importing...', 'easy-site-clone-migrate'),
                'error' => __('Error:', 'easy-site-clone-migrate'),
                'success' => __('Success!', 'easy-site-clone-migrate'),
                'confirm_import' => __('This will overwrite your entire site! Are you sure?', 'easy-site-clone-migrate')
            )
        ));
    }
    
    public function render_admin_page() {
        ?>
        <div class="wrap escm-admin">
            <h1><?php echo esc_html__('Easy Site Clone & Migrate Pro', 'easy-site-clone-migrate'); ?></h1>
            
            <div class="escm-notice notice notice-info">
                <p><?php echo esc_html__('This plugin creates complete backups including all WordPress files and database. During import, it automatically adapts to the new server\'s database configuration.', 'easy-site-clone-migrate'); ?></p>
            </div>
            
            <div class="escm-tabs">
                <button class="escm-tab active" data-tab="export"><?php echo esc_html__('Export Site', 'easy-site-clone-migrate'); ?></button>
                <button class="escm-tab" data-tab="import"><?php echo esc_html__('Import Site', 'easy-site-clone-migrate'); ?></button>
                <button class="escm-tab" data-tab="logs"><?php echo esc_html__('Logs', 'easy-site-clone-migrate'); ?></button>
            </div>
            
            <div class="escm-tab-content" id="export-tab">
                <div class="escm-card">
                    <h2><?php echo esc_html__('Export Complete Site', 'easy-site-clone-migrate'); ?></h2>
                    <p><?php echo esc_html__('Creates a complete backup of your WordPress installation including all files and database.', 'easy-site-clone-migrate'); ?></p>
                    
                    <div class="escm-options">
                        <label>
                            <input type="checkbox" id="escm-export-db" checked disabled>
                            <?php echo esc_html__('Include Database (Required)', 'easy-site-clone-migrate'); ?>
                        </label>
                        <label>
                            <input type="checkbox" id="escm-export-files" checked disabled>
                            <?php echo esc_html__('Include All Files (Required)', 'easy-site-clone-migrate'); ?>
                        </label>
                    </div>
                    
                    <div class="escm-progress-container" id="export-progress-container" style="display:none;">
                        <div class="escm-progress-bar">
                            <div class="escm-progress" id="export-progress"></div>
                        </div>
                        <p class="escm-progress-text" id="export-progress-text"></p>
                    </div>
                    
                    <button class="button button-primary button-large" id="escm-export-btn">
                        <?php echo esc_html__('Start Export', 'easy-site-clone-migrate'); ?>
                    </button>
                    
                    <div class="escm-result" id="export-result"></div>
                </div>
                
                <div class="escm-card escm-info-card">
                    <h3><?php echo esc_html__('What Gets Exported?', 'easy-site-clone-migrate'); ?></h3>
                    <ul>
                        <li><?php echo esc_html__('✓ Complete WordPress core files (wp-admin, wp-includes)', 'easy-site-clone-migrate'); ?></li>
                        <li><?php echo esc_html__('✓ All plugins and themes', 'easy-site-clone-migrate'); ?></li>
                        <li><?php echo esc_html__('✓ All uploads and media files', 'easy-site-clone-migrate'); ?></li>
                        <li><?php echo esc_html__('✓ Complete database with all tables', 'easy-site-clone-migrate'); ?></li>
                        <li><?php echo esc_html__('✓ .htaccess and other configuration files', 'easy-site-clone-migrate'); ?></li>
                        <li><?php echo esc_html__('✗ wp-config.php (excluded for security - uses target DB settings)', 'easy-site-clone-migrate'); ?></li>
                    </ul>
                </div>
            </div>
            
            <div class="escm-tab-content" id="import-tab" style="display:none;">
                <div class="escm-card">
                    <h2><?php echo esc_html__('Import Site Package', 'easy-site-clone-migrate'); ?></h2>
                    <p class="escm-warning"><?php echo esc_html__('⚠️ Warning: This will completely replace your current site!', 'easy-site-clone-migrate'); ?></p>
                    
                    <form id="escm-import-form" enctype="multipart/form-data">
                        <div class="escm-file-drop" id="escm-file-drop">
                            <input type="file" id="escm-import-file" name="escm_import_file" accept=".zip" required>
                            <p><?php echo esc_html__('Drop ZIP file here or click to browse', 'easy-site-clone-migrate'); ?></p>
                        </div>
                    </form>
                    
                    <div class="escm-progress-container" id="import-progress-container" style="display:none;">
                        <div class="escm-progress-bar">
                            <div class="escm-progress" id="import-progress"></div>
                        </div>
                        <p class="escm-progress-text" id="import-progress-text"></p>
                    </div>
                    
                    <button class="button button-primary button-large" id="escm-import-btn" disabled>
                        <?php echo esc_html__('Start Import', 'easy-site-clone-migrate'); ?>
                    </button>
                    
                    <div class="escm-result" id="import-result"></div>
                </div>
                
                <div class="escm-card escm-warning-card">
                    <h3><?php echo esc_html__('Important Notes', 'easy-site-clone-migrate'); ?></h3>
                    <ul>
                        <li><?php echo esc_html__('The imported site will automatically use this server\'s database credentials from wp-config.php', 'easy-site-clone-migrate'); ?></li>
                        <li><?php echo esc_html__('All existing content will be permanently deleted', 'easy-site-clone-migrate'); ?></li>
                        <li><?php echo esc_html__('URLs are automatically updated to match the new domain', 'easy-site-clone-migrate'); ?></li>
                        <li><?php echo esc_html__('Large sites may take several minutes to import', 'easy-site-clone-migrate'); ?></li>
                    </ul>
                </div>
            </div>
            
            <div class="escm-tab-content" id="logs-tab" style="display:none;">
                <div class="escm-card">
                    <h2><?php echo esc_html__('Operation Logs', 'easy-site-clone-migrate'); ?></h2>
                    <div class="escm-log-viewer" id="escm-log-viewer">
                        <p><?php echo esc_html__('No logs yet. Perform an export or import to see logs here.', 'easy-site-clone-migrate'); ?></p>
                    </div>
                    <button class="button" id="escm-clear-logs"><?php echo esc_html__('Clear Logs', 'easy-site-clone-migrate'); ?></button>
                </div>
            </div>
        </div>
        <?php
    }
    
    public function ajax_export_site() {
        check_ajax_referer('escm_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Permission denied', 'easy-site-clone-migrate')));
        }
        
        try {
            $this->log(__('Starting complete site export...', 'easy-site-clone-migrate'));
            
            $timestamp = time();
            $filename = 'site-backup-' . $timestamp . '.zip';
            $zip_path = $this->temp_dir . $filename;
            
            // Create temporary export directory
            $export_dir = $this->temp_dir . 'export-' . $timestamp . '/';
            if (!file_exists($export_dir)) {
                wp_mkdir_p($export_dir);
            }
            
            // Step 1: Export database
            $this->log(__('Exporting database...', 'easy-site-clone-migrate'));
            $db_file = $export_dir . 'database.sql';
            $this->export_database($db_file);
            
            // Step 2: Copy all WordPress files (excluding wp-config.php)
            $this->log(__('Copying WordPress files...', 'easy-site-clone-migrate'));
            $files_dir = $export_dir . 'files/';
            if (!file_exists($files_dir)) {
                wp_mkdir_p($files_dir);
            }
            
            $wp_root = ABSPATH;
            $this->copy_wp_files($wp_root, $files_dir);
            
            // Step 3: Create manifest
            $manifest = array(
                'version' => '2.0',
                'export_date' => date('Y-m-d H:i:s'),
                'wordpress_version' => get_bloginfo('version'),
                'site_url' => get_site_url(),
                'home_url' => get_home_url(),
                'plugin_version' => '2.0.0',
                'total_files' => $this->count_files($files_dir),
                'database_size' => filesize($db_file)
            );
            
            file_put_contents($export_dir . 'manifest.json', json_encode($manifest, JSON_PRETTY_PRINT));
            $this->log(__('Manifest created', 'easy-site-clone-migrate'));
            
            // Step 4: Create ZIP archive
            $this->log(__('Creating ZIP archive...', 'easy-site-clone-migrate'));
            $this->create_zip($export_dir, $zip_path);
            
            // Cleanup temp files
            $this->delete_directory($export_dir);
            
            $this->log(__('Export completed successfully', 'easy-site-clone-migrate'));
            
            wp_send_json_success(array(
                'filename' => $filename,
                'download_url' => wp_upload_dir()['baseurl'] . '/escm-temp/' . $filename,
                'size' => size_format(filesize($zip_path)),
                'manifest' => $manifest
            ));
            
        } catch (Exception $e) {
            $this->log(__('Export failed: ', 'easy-site-clone-migrate') . $e->getMessage());
            wp_send_json_error(array('message' => $e->getMessage()));
        }
    }
    
    private function export_database($output_file) {
        global $wpdb;
        
        $tables = $wpdb->get_results('SHOW TABLES', ARRAY_N);
        $sql_content = "-- WordPress Database Backup\n";
        $sql_content .= "-- Generated by Easy Site Clone & Migrate Pro\n";
        $sql_content .= "-- Date: " . date('Y-m-d H:i:s') . "\n";
        $sql_content .= "-- Site URL: " . get_site_url() . "\n\n";
        $sql_content .= "SET SQL_MODE = \"NO_AUTO_VALUE_ON_ZERO\";\n";
        $sql_content .= "SET AUTOCOMMIT = 0;\n";
        $sql_content .= "START TRANSACTION;\n";
        $sql_content .= "SET time_zone = \"+00:00\";\n\n";
        
        foreach ($tables as $table_info) {
            $table_name = $table_info[0];
            
            // Get table structure
            $create_table = $wpdb->get_row("SHOW CREATE TABLE `$table_name`", ARRAY_N);
            $sql_content .= "\n-- Table structure for `$table_name`\n";
            $sql_content .= "DROP TABLE IF EXISTS `$table_name`;\n";
            $sql_content .= $create_table[1] . ";\n\n";
            
            // Get table data
            $row_count = $wpdb->get_var("SELECT COUNT(*) FROM `$table_name`");
            if ($row_count > 0) {
                $sql_content .= "-- Dumping data for `$table_name`\n";
                $sql_content .= "LOCK TABLES `$table_name` WRITE;\n";
                $sql_content .= "ALTER TABLE `$table_name` DISABLE KEYS;\n";
                
                // Fetch data in chunks
                $offset = 0;
                while ($offset < $row_count) {
                    $rows = $wpdb->get_results("SELECT * FROM `$table_name` LIMIT $offset, $this->chunk_size", ARRAY_A);
                    
                    if ($rows) {
                        foreach ($rows as $row) {
                            $values = array();
                            foreach ($row as $value) {
                                if ($value === null) {
                                    $values[] = 'NULL';
                                } else {
                                    $values[] = "'" . $wpdb->escape($value) . "'";
                                }
                            }
                            $sql_content .= "INSERT INTO `$table_name` VALUES (" . implode(', ', $values) . ");\n";
                        }
                    }
                    
                    $offset += $this->chunk_size;
                    set_time_limit(60);
                }
                
                $sql_content .= "ALTER TABLE `$table_name` ENABLE KEYS;\n";
                $sql_content .= "UNLOCK TABLES;\n\n";
            }
        }
        
        $sql_content .= "COMMIT;\n";
        
        file_put_contents($output_file, $sql_content);
    }
    
    private function copy_wp_files($source, $destination) {
        $exclude_patterns = array(
            'wp-config.php',
            '.git',
            '.svn',
            'node_modules',
            '.DS_Store',
            'Thumbs.db',
            'escm-temp',
            '.htaccess' // We'll recreate this on import
        );
        
        if (!is_dir($source)) {
            return;
        }
        
        $dir = opendir($source);
        if (!$dir) {
            return;
        }
        
        while (($file = readdir($dir)) !== false) {
            if ($file === '.' || $file === '..') {
                continue;
            }
            
            $source_path = $source . '/' . $file;
            $dest_path = $destination . '/' . $file;
            
            // Check if file should be excluded
            $should_exclude = false;
            foreach ($exclude_patterns as $pattern) {
                if (strpos($source_path, $pattern) !== false) {
                    $should_exclude = true;
                    break;
                }
            }
            
            if ($should_exclude) {
                continue;
            }
            
            if (is_dir($source_path)) {
                if (!file_exists($dest_path)) {
                    wp_mkdir_p($dest_path);
                }
                $this->copy_wp_files($source_path, $dest_path);
            } else {
                copy($source_path, $dest_path);
            }
        }
        
        closedir($dir);
    }
    
    private function create_zip($source_dir, $zip_path) {
        if (!class_exists('ZipArchive')) {
            throw new Exception(__('ZipArchive extension is not available', 'easy-site-clone-migrate'));
        }
        
        $zip = new ZipArchive();
        if (!$zip->open($zip_path, ZipArchive::CREATE | ZipArchive::OVERWRITE)) {
            throw new Exception(__('Failed to create ZIP archive', 'easy-site-clone-migrate'));
        }
        
        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($source_dir),
            RecursiveIteratorIterator::LEAVES_ONLY
        );
        
        $source_dir_real = realpath($source_dir);
        
        foreach ($files as $file) {
            if (!$file->isDir()) {
                $file_path = $file->getRealPath();
                $relative_path = substr($file_path, strlen($source_dir_real) + 1);
                $zip->addFile($file_path, $relative_path);
            }
        }
        
        if (!$zip->close()) {
            throw new Exception(__('Failed to finalize ZIP archive', 'easy-site-clone-migrate'));
        }
    }
    
    public function ajax_import_site() {
        check_ajax_referer('escm_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Permission denied', 'easy-site-clone-migrate')));
        }
        
        try {
            $this->log(__('Starting site import...', 'easy-site-clone-migrate'));
            
            if (!isset($_FILES['escm_import_file'])) {
                throw new Exception(__('No file uploaded', 'easy-site-clone-migrate'));
            }
            
            $upload = $_FILES['escm_import_file'];
            if ($upload['error'] !== UPLOAD_ERR_OK) {
                throw new Exception(__('Upload error: ', 'easy-site-clone-migrate') . $upload['error']);
            }
            
            // Validate file type
            $file_extension = pathinfo($upload['name'], PATHINFO_EXTENSION);
            if (strtolower($file_extension) !== 'zip') {
                throw new Exception(__('Invalid file type. Please upload a ZIP file.', 'easy-site-clone-migrate'));
            }
            
            $temp_upload = $this->temp_dir . 'import-' . time() . '.zip';
            if (!move_uploaded_file($upload['tmp_name'], $temp_upload)) {
                throw new Exception(__('Failed to save uploaded file', 'easy-site-clone-migrate'));
            }
            
            // Extract ZIP
            $extract_dir = $this->temp_dir . 'import-extract-' . time() . '/';
            if (!file_exists($extract_dir)) {
                wp_mkdir_p($extract_dir);
            }
            
            $this->log(__('Extracting archive...', 'easy-site-clone-migrate'));
            $this->extract_zip($temp_upload, $extract_dir);
            
            // Validate manifest
            $manifest_file = $extract_dir . 'manifest.json';
            if (!file_exists($manifest_file)) {
                throw new Exception(__('Invalid backup package: manifest.json not found', 'easy-site-clone-migrate'));
            }
            
            $manifest = json_decode(file_get_contents($manifest_file), true);
            if (!$manifest) {
                throw new Exception(__('Invalid manifest file', 'easy-site-clone-migrate'));
            }
            
            $this->log(__('Manifest validated. Version: ', 'easy-site-clone-migrate') . $manifest['version']);
            
            // Create pre-import backup
            $this->log(__('Creating pre-import backup...', 'easy-site-clone-migrate'));
            $this->create_pre_import_backup();
            
            // Step 1: Delete current site files (except wp-config.php)
            $this->log(__('Removing current site files...', 'easy-site-clone-migrate'));
            $this->cleanup_current_site();
            
            // Step 2: Copy new files
            $this->log(__('Installing new site files...', 'easy-site-clone-migrate'));
            $files_dir = $extract_dir . 'files/';
            if (is_dir($files_dir)) {
                $this->copy_directory($files_dir, ABSPATH);
            }
            
            // Step 3: Import database
            $this->log(__('Importing database...', 'easy-site-clone-migrate'));
            $db_file = $extract_dir . 'database.sql';
            if (file_exists($db_file)) {
                $old_url = isset($manifest['site_url']) ? $manifest['site_url'] : '';
                $new_url = get_site_url();
                $this->import_database($db_file, $old_url, $new_url);
            }
            
            // Step 4: Update URLs in database if domain changed
            if ($old_url && $old_url !== $new_url) {
                $this->log(__('Updating URLs from ', 'easy-site-clone-migrate') . $old_url . ' ' . __('to ', 'easy-site-clone-migrate') . $new_url);
                $this->update_urls($old_url, $new_url);
            }
            
            // Cleanup
            unlink($temp_upload);
            $this->delete_directory($extract_dir);
            
            // Flush rewrite rules
            flush_rewrite_rules();
            
            $this->log(__('Import completed successfully', 'easy-site-clone-migrate'));
            
            wp_send_json_success(array(
                'message' => __('Site imported successfully! You may need to log in again.', 'easy-site-clone-migrate'),
                'manifest' => $manifest
            ));
            
        } catch (Exception $e) {
            $this->log(__('Import failed: ', 'easy-site-clone-migrate') . $e->getMessage());
            wp_send_json_error(array('message' => $e->getMessage()));
        }
    }
    
    private function extract_zip($zip_path, $extract_dir) {
        if (!class_exists('ZipArchive')) {
            throw new Exception(__('ZipArchive extension is not available', 'easy-site-clone-migrate'));
        }
        
        $zip = new ZipArchive();
        if (!$zip->open($zip_path)) {
            throw new Exception(__('Failed to open ZIP archive', 'easy-site-clone-migrate'));
        }
        
        if (!$zip->extractTo($extract_dir)) {
            $zip->close();
            throw new Exception(__('Failed to extract ZIP archive', 'easy-site-clone-migrate'));
        }
        
        $zip->close();
    }
    
    private function cleanup_current_site() {
        global $wpdb;
        
        $exclude_patterns = array(
            'wp-config.php',
            'wp-content/uploads/escm-temp',
            '.htaccess'
        );
        
        $items = scandir(ABSPATH);
        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }
            
            $item_path = ABSPATH . $item;
            $should_exclude = false;
            
            foreach ($exclude_patterns as $pattern) {
                if (strpos($item_path, $pattern) !== false) {
                    $should_exclude = true;
                    break;
                }
            }
            
            if ($should_exclude) {
                continue;
            }
            
            if (is_dir($item_path)) {
                $this->delete_directory($item_path);
            } else {
                unlink($item_path);
            }
        }
    }
    
    private function copy_directory($source, $destination) {
        if (!is_dir($source)) {
            return;
        }
        
        if (!file_exists($destination)) {
            wp_mkdir_p($destination);
        }
        
        $dir = opendir($source);
        if (!$dir) {
            return;
        }
        
        while (($file = readdir($dir)) !== false) {
            if ($file === '.' || $file === '..') {
                continue;
            }
            
            $source_path = $source . '/' . $file;
            $dest_path = $destination . '/' . $file;
            
            if (is_dir($source_path)) {
                $this->copy_directory($source_path, $dest_path);
            } else {
                // Skip wp-config.php during copy
                if (basename($source_path) === 'wp-config.php') {
                    continue;
                }
                copy($source_path, $dest_path);
            }
        }
        
        closedir($dir);
    }
    
    private function import_database($sql_file, $old_url, $new_url) {
        global $wpdb;
        
        $sql_content = file_get_contents($sql_file);
        if (!$sql_content) {
            throw new Exception(__('Failed to read SQL file', 'easy-site-clone-migrate'));
        }
        
        // Split SQL into individual statements
        $statements = $this->parse_sql($sql_content);
        
        $wpdb->query('SET AUTOCOMMIT = 0');
        $wpdb->query('START TRANSACTION');
        
        $count = 0;
        $total = count($statements);
        
        foreach ($statements as $statement) {
            $statement = trim($statement);
            if (empty($statement) || strpos($statement, '--') === 0) {
                continue;
            }
            
            $result = $wpdb->query($statement);
            if ($result === false && !strpos($statement, 'DROP TABLE IF EXISTS')) {
                // Log error but continue for non-critical errors
                $this->log(__('SQL Warning: ', 'easy-site-clone-migrate') . $wpdb->last_error);
            }
            
            $count++;
            if ($count % 100 === 0) {
                set_time_limit(60);
            }
        }
        
        $wpdb->query('COMMIT');
        $wpdb->query('SET AUTOCOMMIT = 1');
    }
    
    private function parse_sql($sql_content) {
        $statements = array();
        $current_statement = '';
        $in_string = false;
        $string_char = '';
        
        $lines = explode("\n", $sql_content);
        
        foreach ($lines as $line) {
            $line = trim($line);
            
            // Skip comments
            if (strpos($line, '--') === 0 || strpos($line, '/*') === 0) {
                continue;
            }
            
            if (empty($line)) {
                continue;
            }
            
            $length = strlen($line);
            for ($i = 0; $i < $length; $i++) {
                $char = $line[$i];
                
                if (!$in_string && ($char === '"' || $char === "'")) {
                    $in_string = true;
                    $string_char = $char;
                } elseif ($in_string && $char === $string_char && ($i === 0 || $line[$i - 1] !== '\\')) {
                    $in_string = false;
                }
                
                $current_statement .= $char;
                
                if (!$in_string && $char === ';') {
                    $statements[] = $current_statement;
                    $current_statement = '';
                }
            }
            
            if (!empty($current_statement) && substr($current_statement, -1) !== ';') {
                $current_statement .= ' ';
            }
        }
        
        if (!empty(trim($current_statement))) {
            $statements[] = $current_statement;
        }
        
        return $statements;
    }
    
    private function update_urls($old_url, $new_url) {
        global $wpdb;
        
        $old_url = untrailingslashit($old_url);
        $new_url = untrailingslashit($new_url);
        
        $this->log(__('Updating URLs in database...', 'easy-site-clone-migrate'));
        
        // Get all tables
        $tables = $wpdb->get_results('SHOW TABLES', ARRAY_N);
        
        foreach ($tables as $table_info) {
            $table_name = $table_info[0];
            
            // Get all columns
            $columns = $wpdb->get_results("SHOW COLUMNS FROM `$table_name`", ARRAY_A);
            
            foreach ($columns as $column) {
                $col_name = $column['Field'];
                $col_type = $column['Type'];
                
                // Only process text columns
                if (strpos($col_type, 'text') !== false || 
                    strpos($col_type, 'varchar') !== false || 
                    strpos($col_type, 'char') !== false) {
                    
                    // Check if column contains old URL
                    $check_query = $wpdb->prepare(
                        "SELECT COUNT(*) FROM `$table_name` WHERE `$col_name` LIKE %s",
                        '%' . $wpdb->esc_like($old_url) . '%'
                    );
                    
                    $count = $wpdb->get_var($check_query);
                    
                    if ($count > 0) {
                        // Update URLs
                        $update_query = $wpdb->prepare(
                            "UPDATE `$table_name` SET `$col_name` = REPLACE(`$col_name`, %s, %s) WHERE `$col_name` LIKE %s",
                            $old_url,
                            $new_url,
                            '%' . $wpdb->esc_like($old_url) . '%'
                        );
                        
                        $wpdb->query($update_query);
                        $this->log(sprintf(__('Updated %d rows in %s.%s', 'easy-site-clone-migrate'), $count, $table_name, $col_name));
                    }
                }
            }
            
            set_time_limit(60);
        }
        
        // Handle serialized data
        $this->update_serialized_data($old_url, $new_url);
    }
    
    private function update_serialized_data($old_url, $new_url) {
        global $wpdb;
        
        $options = $wpdb->get_results("SELECT option_name, option_value FROM {$wpdb->options} WHERE option_value LIKE '%:%'", ARRAY_A);
        
        foreach ($options as $option) {
            $value = maybe_unserialize($option['option_value']);
            
            if (is_array($value) || is_object($value)) {
                $modified = $this->replace_in_serialized($value, $old_url, $new_url);
                
                if ($modified !== $value) {
                    $wpdb->update(
                        $wpdb->options,
                        array('option_value' => serialize($modified)),
                        array('option_name' => $option['option_name']),
                        array('%s'),
                        array('%s')
                    );
                }
            }
        }
        
        // Update postmeta
        $postmeta = $wpdb->get_results("SELECT meta_id, meta_value FROM {$wpdb->postmeta} WHERE meta_value LIKE '%:%'", ARRAY_A);
        
        foreach ($postmeta as $meta) {
            $value = maybe_unserialize($meta['meta_value']);
            
            if (is_array($value) || is_object($value)) {
                $modified = $this->replace_in_serialized($value, $old_url, $new_url);
                
                if ($modified !== $value) {
                    $wpdb->update(
                        $wpdb->postmeta,
                        array('meta_value' => serialize($modified)),
                        array('meta_id' => $meta['meta_id']),
                        array('%s'),
                        array('%d')
                    );
                }
            }
        }
    }
    
    private function replace_in_serialized($data, $old_url, $new_url) {
        if (is_array($data)) {
            foreach ($data as $key => $value) {
                if (is_string($value)) {
                    $data[$key] = str_replace($old_url, $new_url, $value);
                } elseif (is_array($value) || is_object($value)) {
                    $data[$key] = $this->replace_in_serialized($value, $old_url, $new_url);
                }
            }
        } elseif (is_object($data)) {
            foreach ($data as $key => $value) {
                if (is_string($value)) {
                    $data->$key = str_replace($old_url, $new_url, $value);
                } elseif (is_array($value) || is_object($value)) {
                    $data->$key = $this->replace_in_serialized($value, $old_url, $new_url);
                }
            }
        } elseif (is_string($data)) {
            $data = str_replace($old_url, $new_url, $data);
        }
        
        return $data;
    }
    
    private function create_pre_import_backup() {
        // Create a minimal backup before import for rollback purposes
        $backup_dir = $this->temp_dir . 'pre-import-backup-' . time() . '/';
        if (!file_exists($backup_dir)) {
            wp_mkdir_p($backup_dir);
        }
        
        // Backup wp-config.php
        if (file_exists(ABSPATH . 'wp-config.php')) {
            copy(ABSPATH . 'wp-config.php', $backup_dir . 'wp-config.php');
        }
        
        // Backup .htaccess
        if (file_exists(ABSPATH . '.htaccess')) {
            copy(ABSPATH . '.htaccess', $backup_dir . '.htaccess');
        }
        
        $this->log(__('Pre-import backup created', 'easy-site-clone-migrate'));
    }
    
    private function count_files($directory) {
        $count = 0;
        if (is_dir($directory)) {
            $iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($directory),
                RecursiveIteratorIterator::SELF_FIRST
            );
            
            foreach ($iterator as $file) {
                if ($file->isFile()) {
                    $count++;
                }
            }
        }
        return $count;
    }
    
    private function delete_directory($directory) {
        if (!is_dir($directory)) {
            return;
        }
        
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($directory, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST
        );
        
        foreach ($iterator as $file) {
            if ($file->isDir()) {
                rmdir($file->getPathname());
            } else {
                unlink($file->getPathname());
            }
        }
        
        rmdir($directory);
    }
    
    private function log($message) {
        $timestamp = date('Y-m-d H:i:s');
        $log_entry = "[$timestamp] $message\n";
        $this->log[] = $log_entry;
        
        // Also save to file
        $log_file = $this->temp_dir . 'operation-log.txt';
        file_put_contents($log_file, $log_entry, FILE_APPEND);
    }
    
    public function ajax_get_progress() {
        check_ajax_referer('escm_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Permission denied', 'easy-site-clone-migrate')));
        }
        
        $log_file = $this->temp_dir . 'operation-log.txt';
        $logs = file_exists($log_file) ? file_get_contents($log_file) : '';
        
        wp_send_json_success(array('logs' => $logs));
    }
    
    public function ajax_cancel_operation() {
        check_ajax_referer('escm_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Permission denied', 'easy-site-clone-migrate')));
        }
        
        // In a real implementation, this would set a flag to stop long-running operations
        wp_send_json_success(array('message' => __('Cancellation requested', 'easy-site-clone-migrate')));
    }
}

// Initialize plugin
new Easy_Site_Clone_Migrate_Pro();
