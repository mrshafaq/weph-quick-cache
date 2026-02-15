<?php
/**
 * Uninstall WePH Quick Cache
 * 
 * This file is executed when the plugin is deleted via WordPress admin
 */

// Exit if not called from WordPress
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

// Define cache directory
$cache_dir = WP_CONTENT_DIR . '/cache/weph-quick-cache/';

// Delete cache directory and all contents
function weph_qc_delete_cache_directory($dir) {
    if (!is_dir($dir)) {
        return false;
    }
    
    $files = array_diff(scandir($dir), array('.', '..'));
    
    foreach ($files as $file) {
        $path = $dir . '/' . $file;
        
        if (is_dir($path)) {
            weph_qc_delete_cache_directory($path);
            rmdir($path);
        } else {
            unlink($path);
        }
    }
    
    rmdir($dir);
    
    return true;
}

// Delete cache directory
weph_qc_delete_cache_directory($cache_dir);

// Delete plugin options
delete_option('weph_qc_enable_gzip');
delete_option('weph_qc_enable_minify');
delete_option('weph_qc_enable_browser_cache');
delete_option('weph_qc_enable_lazy_load');
delete_option('weph_qc_minify_html');
delete_option('weph_qc_minify_css');
delete_option('weph_qc_minify_js');
delete_option('weph_qc_combine_css');
delete_option('weph_qc_combine_js');
delete_option('weph_qc_defer_js');
delete_option('weph_qc_remove_query_strings');
delete_option('weph_qc_disable_emojis');

// Remove .htaccess rules
function weph_qc_remove_htaccess_on_uninstall() {
    $htaccess_file = ABSPATH . '.htaccess';
    
    if (!is_writable($htaccess_file)) {
        return false;
    }
    
    $htaccess_content = file_get_contents($htaccess_file);
    $htaccess_content = preg_replace('/# BEGIN WePH Quick Cache.*?# END WePH Quick Cache\n*/s', '', $htaccess_content);
    
    file_put_contents($htaccess_file, $htaccess_content);
    
    return true;
}

// Remove .htaccess rules
weph_qc_remove_htaccess_on_uninstall();

// Clear any remaining transients
global $wpdb;
$wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_weph_qc_%'");
$wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_timeout_weph_qc_%'");
