<?php
// If uninstall not called from WordPress, exit
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

// Delete plugin options
delete_option('digicontent_anthropic_key');
delete_option('digicontent_openai_key');
delete_option('digicontent_settings');
delete_option('digicontent_debug_enabled');

// Drop custom tables
global $wpdb;
$tables = [
    $wpdb->prefix . 'digicontent_templates',
    $wpdb->prefix . 'digicontent_logs'
];

foreach ($tables as $table) {
    $wpdb->query("DROP TABLE IF EXISTS {$table}");
}

// Clear any scheduled hooks
wp_clear_scheduled_hook('digicontent_cleanup_logs');

// Remove plugin files and directories that may have been created
$upload_dir = wp_upload_dir();
$plugin_upload_dir = $upload_dir['basedir'] . '/digicontent';
if (is_dir($plugin_upload_dir)) {
    require_once(ABSPATH . 'wp-admin/includes/class-wp-filesystem-base.php');
    require_once(ABSPATH . 'wp-admin/includes/class-wp-filesystem-direct.php');
    $filesystem = new WP_Filesystem_Direct(null);
    $filesystem->rmdir($plugin_upload_dir, true);
} 