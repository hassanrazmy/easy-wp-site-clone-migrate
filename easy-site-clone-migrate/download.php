<?php
/**
 * Download handler for exported files
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Verify nonce
if (!isset($_GET['nonce']) || !wp_verify_nonce($_GET['nonce'], 'escm_download_nonce')) {
    wp_die(__('Security check failed', 'easy-site-clone-migrate'));
}

// Check user capability
if (!current_user_can('manage_options')) {
    wp_die(__('Permission denied', 'easy-site-clone-migrate'));
}

// Get filename
if (!isset($_GET['file'])) {
    wp_die(__('File not specified', 'easy-site-clone-migrate'));
}

$filename = sanitize_file_name($_GET['file']);
$upload_dir = wp_upload_dir();
$backup_dir = $upload_dir['basedir'] . '/easy-site-clone-migrate';
$filepath = $backup_dir . '/' . $filename;

// Verify file exists and is within backup directory
if (!file_exists($filepath) || strpos(realpath($filepath), realpath($backup_dir)) !== 0) {
    wp_die(__('File not found', 'easy-site-clone-migrate'));
}

// Determine MIME type
$finfo = new finfo(FILEINFO_MIME_TYPE);
$mimeType = $finfo->file($filepath);

// Send download headers
header('Content-Type: ' . $mimeType);
header('Content-Disposition: attachment; filename="' . basename($filepath) . '"');
header('Content-Length: ' . filesize($filepath));
header('Cache-Control: no-cache, must-revalidate');
header('Pragma: no-cache');

// Output file
readfile($filepath);
exit;
