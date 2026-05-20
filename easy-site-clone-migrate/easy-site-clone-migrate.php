<?php
/**
 * Plugin Name: Easy Site Clone & Migrate
 * Plugin URI: https://example.com/easy-site-clone-migrate
 * Description: The best easy WordPress migration and cloning plugin. Export, import, and clone sites with ease. Supports full site backup, database export, and seamless migration.
 * Version: 1.0.0
 * Author: Your Name
 * Author URI: https://example.com
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: easy-site-clone-migrate
 * Domain Path: /languages
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('ESCM_VERSION', '1.0.0');
define('ESCM_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('ESCM_PLUGIN_URL', plugin_dir_url(__FILE__));
define('ESCM_PLUGIN_BASENAME', plugin_basename(__FILE__));

// Include required files
require_once ESCM_PLUGIN_DIR . 'includes/class-exporter.php';
require_once ESCM_PLUGIN_DIR . 'includes/class-importer.php';
require_once ESCM_PLUGIN_DIR . 'includes/class-admin.php';

/**
 * Initialize the plugin
 */
function escm_init() {
    load_plugin_textdomain('easy-site-clone-migrate', false, dirname(ESCM_PLUGIN_BASENAME) . '/languages');
    
    new ESCM_Exporter();
    new ESCM_Importer();
    new ESCM_Admin();
}
add_action('plugins_loaded', 'escm_init');

/**
 * Activation hook
 */
function escm_activate() {
    // Create necessary directories
    $upload_dir = wp_upload_dir();
    $escm_dir = $upload_dir['basedir'] . '/easy-site-clone-migrate';
    
    if (!file_exists($escm_dir)) {
        wp_mkdir_p($escm_dir);
    }
    
    // Add .htaccess to protect directory
    $htaccess_file = $escm_dir . '/.htaccess';
    if (!file_exists($htaccess_file)) {
        file_put_contents($htaccess_file, 'deny from all');
    }
    
    flush_rewrite_rules();
}
register_activation_hook(__FILE__, 'escm_activate');

/**
 * Deactivation hook
 */
function escm_deactivate() {
    flush_rewrite_rules();
}
register_deactivation_hook(__FILE__, 'escm_deactivate');

/**
 * Add plugin action links
 */
function escm_plugin_action_links($links) {
    $settings_link = '<a href="' . admin_url('tools.php?page=easy-site-clone-migrate') . '">' . __('Settings', 'easy-site-clone-migrate') . '</a>';
    array_unshift($links, $settings_link);
    return $links;
}
add_filter('plugin_action_links_' . ESCM_PLUGIN_BASENAME, 'escm_plugin_action_links');
