<?php
/**
 * Admin Class
 * Handles admin interface and functionality
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class ESCM_Admin {
    
    /**
     * Constructor
     */
    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_assets'));
    }
    
    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        add_submenu_page(
            'tools.php',
            __('Easy Site Clone & Migrate', 'easy-site-clone-migrate'),
            __('Site Clone & Migrate', 'easy-site-clone-migrate'),
            'manage_options',
            'easy-site-clone-migrate',
            array($this, 'render_admin_page')
        );
    }
    
    /**
     * Enqueue admin assets
     */
    public function enqueue_assets($hook) {
        if ($hook !== 'tools_page_easy-site-clone-migrate') {
            return;
        }
        
        wp_enqueue_style(
            'escm-admin-style',
            ESCM_PLUGIN_URL . 'assets/css/admin.css',
            array(),
            ESCM_VERSION
        );
        
        wp_enqueue_script(
            'escm-admin-script',
            ESCM_PLUGIN_URL . 'assets/js/admin.js',
            array('jquery'),
            ESCM_VERSION,
            true
        );
        
        wp_localize_script('escm-admin-script', 'escm_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'export_nonce' => wp_create_nonce('escm_export_nonce'),
            'import_nonce' => wp_create_nonce('escm_import_nonce'),
            'download_nonce' => wp_create_nonce('escm_download_nonce'),
            'strings' => array(
                'confirm_export' => __('Are you sure you want to export the site? This may take a while for large sites.', 'easy-site-clone-migrate'),
                'confirm_import' => __('WARNING: This will overwrite your current site data! Make sure you have a backup. Continue?', 'easy-site-clone-migrate'),
                'exporting' => __('Exporting...', 'easy-site-clone-migrate'),
                'importing' => __('Importing...', 'easy-site-clone-migrate'),
                'uploading' => __('Uploading...', 'easy-site-clone-migrate'),
                'success' => __('Success!', 'easy-site-clone-migrate'),
                'error' => __('Error', 'easy-site-clone-migrate')
            )
        ));
    }
    
    /**
     * Render admin page
     */
    public function render_admin_page() {
        ?>
        <div class="wrap escm-admin-wrap">
            <h1><?php echo esc_html__('Easy Site Clone & Migrate', 'easy-site-clone-migrate'); ?></h1>
            
            <div class="escm-dashboard">
                <!-- Export Section -->
                <div class="escm-card escm-export-section">
                    <h2><?php echo esc_html__('Export Site', 'easy-site-clone-migrate'); ?></h2>
                    <p class="description"><?php echo esc_html__('Create a complete backup of your WordPress site including database and files.', 'easy-site-clone-migrate'); ?></p>
                    
                    <div class="escm-export-options">
                        <button type="button" class="button button-primary button-large" id="escm-export-full">
                            <span class="dashicons dashicons-download"></span>
                            <?php echo esc_html__('Export Full Site', 'easy-site-clone-migrate'); ?>
                        </button>
                        
                        <button type="button" class="button button-secondary" id="escm-export-db">
                            <span class="dashicons dashicons-database"></span>
                            <?php echo esc_html__('Export Database Only', 'easy-site-clone-migrate'); ?>
                        </button>
                        
                        <button type="button" class="button button-secondary" id="escm-export-files">
                            <span class="dashicons dashicons-media-default"></span>
                            <?php echo esc_html__('Export Files Only', 'easy-site-clone-migrate'); ?>
                        </button>
                    </div>
                    
                    <div id="escm-export-progress" class="escm-progress" style="display:none;">
                        <div class="escm-progress-bar"></div>
                        <p class="escm-progress-text"></p>
                    </div>
                    
                    <div id="escm-export-result" class="escm-result" style="display:none;"></div>
                </div>
                
                <!-- Import Section -->
                <div class="escm-card escm-import-section">
                    <h2><?php echo esc_html__('Import Site', 'easy-site-clone-migrate'); ?></h2>
                    <p class="description"><?php echo esc_html__('Import a previously exported site backup. Warning: This will overwrite existing data!', 'easy-site-clone-migrate'); ?></p>
                    
                    <div class="escm-import-form">
                        <form id="escm-import-upload-form" enctype="multipart/form-data">
                            <input type="file" name="import_file" id="escm-import-file" accept=".zip,.sql" />
                            <button type="submit" class="button button-primary" id="escm-upload-file">
                                <span class="dashicons dashicons-upload"></span>
                                <?php echo esc_html__('Upload File', 'easy-site-clone-migrate'); ?>
                            </button>
                        </form>
                        
                        <div id="escm-import-progress" class="escm-progress" style="display:none;">
                            <div class="escm-progress-bar"></div>
                            <p class="escm-progress-text"></p>
                        </div>
                        
                        <div id="escm-import-actions" style="display:none;">
                            <p><strong><?php echo esc_html__('File uploaded:', 'easy-site-clone-migrate'); ?></strong> <span id="escm-uploaded-filename"></span></p>
                            
                            <div class="escm-import-options">
                                <button type="button" class="button button-primary button-large" id="escm-import-full">
                                    <span class="dashicons dashicons-upload"></span>
                                    <?php echo esc_html__('Import Full Site', 'easy-site-clone-migrate'); ?>
                                </button>
                                
                                <button type="button" class="button button-secondary" id="escm-import-db">
                                    <span class="dashicons dashicons-database"></span>
                                    <?php echo esc_html__('Import Database Only', 'easy-site-clone-migrate'); ?>
                                </button>
                                
                                <button type="button" class="button button-secondary" id="escm-import-files">
                                    <span class="dashicons dashicons-media-default"></span>
                                    <?php echo esc_html__('Import Files Only', 'easy-site-clone-migrate'); ?>
                                </button>
                            </div>
                        </div>
                        
                        <div id="escm-import-result" class="escm-result" style="display:none;"></div>
                    </div>
                </div>
                
                <!-- Information Section -->
                <div class="escm-card escm-info-section">
                    <h2><?php echo esc_html__('Site Information', 'easy-site-clone-migrate'); ?></h2>
                    
                    <table class="widefat">
                        <tbody>
                            <tr>
                                <th><?php echo esc_html__('Site Name', 'easy-site-clone-migrate'); ?></th>
                                <td><?php echo esc_html(get_bloginfo('name')); ?></td>
                            </tr>
                            <tr>
                                <th><?php echo esc_html__('Site URL', 'easy-site-clone-migrate'); ?></th>
                                <td><?php echo esc_html(get_bloginfo('url')); ?></td>
                            </tr>
                            <tr>
                                <th><?php echo esc_html__('WordPress Version', 'easy-site-clone-migrate'); ?></th>
                                <td><?php echo esc_html(get_bloginfo('version')); ?></td>
                            </tr>
                            <tr>
                                <th><?php echo esc_html__('PHP Version', 'easy-site-clone-migrate'); ?></th>
                                <td><?php echo esc_html(PHP_VERSION); ?></td>
                            </tr>
                            <tr>
                                <th><?php echo esc_html__('Active Plugins', 'easy-site-clone-migrate'); ?></th>
                                <td><?php echo count(get_option('active_plugins')); ?></td>
                            </tr>
                            <tr>
                                <th><?php echo esc_html__('Theme', 'easy-site-clone-migrate'); ?></th>
                                <td><?php echo esc_html(get_option('stylesheet')); ?></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                
                <!-- Help Section -->
                <div class="escm-card escm-help-section">
                    <h2><?php echo esc_html__('How to Use', 'easy-site-clone-migrate'); ?></h2>
                    
                    <h3><?php echo esc_html__('To Export Your Site:', 'easy-site-clone-migrate'); ?></h3>
                    <ol>
                        <li><?php echo esc_html__('Click "Export Full Site" to create a complete backup.', 'easy-site-clone-migrate'); ?></li>
                        <li><?php echo esc_html__('Download the generated ZIP file when ready.', 'easy-site-clone-migrate'); ?></li>
                        <li><?php echo esc_html__('Store the backup file in a safe location.', 'easy-site-clone-migrate'); ?></li>
                    </ol>
                    
                    <h3><?php echo esc_html__('To Import/Clone Your Site:', 'easy-site-clone-migrate'); ?></h3>
                    <ol>
                        <li><?php echo esc_html__('Upload your previously exported ZIP file.', 'easy-site-clone-migrate'); ?></li>
                        <li><?php echo esc_html__('Choose "Import Full Site" to restore everything.', 'easy-site-clone-migrate'); ?></li>
                        <li><?php echo esc_html__('Wait for the process to complete.', 'easy-site-clone-migrate'); ?></li>
                        <li><?php echo esc_html__('You may need to log in again after import.', 'easy-site-clone-migrate'); ?></li>
                    </ol>
                    
                    <div class="escm-warning">
                        <strong><?php echo esc_html__('Important:', 'easy-site-clone-migrate'); ?></strong>
                        <ul>
                            <li><?php echo esc_html__('Always backup your current site before importing.', 'easy-site-clone-migrate'); ?></li>
                            <li><?php echo esc_html__('Import operations cannot be undone.', 'easy-site-clone-migrate'); ?></li>
                            <li><?php echo esc_html__('Large sites may take several minutes to process.', 'easy-site-clone-migrate'); ?></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }
}
