<?php
/**
 * Admin Page Template
 */

if (!defined('ABSPATH')) {
    exit;
}

// Get cache stats
$cache_manager = new WePH_QC_Cache_Manager();
$cache_stats = $cache_manager->get_cache_stats();

// Handle form submission
if (isset($_POST['weph_qc_save_settings'])) {
    check_admin_referer('weph_qc_settings_nonce');
    
    // Update options
    update_option('weph_qc_enable_gzip', isset($_POST['weph_qc_enable_gzip']) ? 1 : 0);
    update_option('weph_qc_enable_minify', isset($_POST['weph_qc_enable_minify']) ? 1 : 0);
    update_option('weph_qc_enable_browser_cache', isset($_POST['weph_qc_enable_browser_cache']) ? 1 : 0);
    update_option('weph_qc_enable_lazy_load', isset($_POST['weph_qc_enable_lazy_load']) ? 1 : 0);
    update_option('weph_qc_enable_webp', isset($_POST['weph_qc_enable_webp']) ? 1 : 0);
    update_option('weph_qc_enable_local_fonts', isset($_POST['weph_qc_enable_local_fonts']) ? 1 : 0);
    update_option('weph_qc_minify_html', isset($_POST['weph_qc_minify_html']) ? 1 : 0);
    update_option('weph_qc_minify_css', isset($_POST['weph_qc_minify_css']) ? 1 : 0);
    update_option('weph_qc_minify_js', isset($_POST['weph_qc_minify_js']) ? 1 : 0);
    update_option('weph_qc_defer_js', isset($_POST['weph_qc_defer_js']) ? 1 : 0);
    update_option('weph_qc_remove_query_strings', isset($_POST['weph_qc_remove_query_strings']) ? 1 : 0);
    update_option('weph_qc_disable_emojis', isset($_POST['weph_qc_disable_emojis']) ? 1 : 0);
    update_option('weph_qc_dns_prefetch', isset($_POST['weph_qc_dns_prefetch']) ? 1 : 0);
    update_option('weph_qc_enable_image_metadata', isset($_POST['weph_qc_enable_image_metadata']) ? 1 : 0);
    update_option('weph_qc_image_alt_text', sanitize_text_field($_POST['weph_qc_image_alt_text']));
    update_option('weph_qc_image_title_text', sanitize_text_field($_POST['weph_qc_image_title_text']));
    update_option('weph_qc_image_caption', sanitize_textarea_field($_POST['weph_qc_image_caption']));
    update_option('weph_qc_image_description', sanitize_textarea_field($_POST['weph_qc_image_description']));
    update_option('weph_qc_bust_browser_cache', isset($_POST['weph_qc_bust_browser_cache']) ? 1 : 0);
    update_option('weph_qc_exclude_woocommerce', isset($_POST['weph_qc_exclude_woocommerce']) ? 1 : 0);
    
    // New cache management options
    update_option('weph_qc_auto_clear_enabled', isset($_POST['weph_qc_auto_clear_enabled']) ? 1 : 0);
    update_option('weph_qc_auto_clear_days', intval($_POST['weph_qc_auto_clear_days']));
    update_option('weph_qc_clear_on_publish', isset($_POST['weph_qc_clear_on_publish']) ? 1 : 0);
    update_option('weph_qc_cache_lifespan_days', intval($_POST['weph_qc_cache_lifespan_days']));
    
    // New exclusion options
    update_option('weph_qc_excluded_pages', sanitize_text_field($_POST['weph_qc_excluded_pages']));
    update_option('weph_qc_excluded_urls', sanitize_textarea_field($_POST['weph_qc_excluded_urls']));
    update_option('weph_qc_excluded_scripts', sanitize_textarea_field($_POST['weph_qc_excluded_scripts']));
    update_option('weph_qc_excluded_css', sanitize_textarea_field($_POST['weph_qc_excluded_css']));
    
    // Clear all caches and flush rewrite rules to ensure changes take effect
    wp_cache_flush();
    $cache_manager = new WePH_QC_Cache_Manager();
    $cache_manager->clear_all();
    flush_rewrite_rules();
    
    // Set no-cache headers
    header("Cache-Control: no-cache, no-store, must-revalidate");
    header("Pragma: no-cache");
    header("Expires: 0");
    
    echo '<div class="notice notice-success"><p>Settings saved successfully! Cache cleared.</p></div>';
    echo '<script>
        jQuery(document).ready(function($) {
            // Force reset form state completely
            if (typeof formChanged !== "undefined") { 
                formChanged = false; 
            }
            // Remove beforeunload event completely
            $(window).off("beforeunload");
            // Capture new initial state after page DOM is ready
            setTimeout(function() {
                if (typeof initialFormData !== "undefined") {
                    initialFormData = $(".weph-qc-settings-form").serialize();
                }
                // Re-bind with new state
                $(window).off("beforeunload").on("beforeunload", function(e) {
                    var currentData = $(".weph-qc-settings-form").serialize();
                    if (currentData !== initialFormData) {
                        var message = "You have unsaved changes. Are you sure you want to leave?";
                        e.returnValue = message;
                        return message;
                    }
                });
            }, 1000);
        });
    </script>';
}
?>

<div class="wrap weph-qc-admin">
    <h1>
        <span class="dashicons dashicons-performance"></span>
        WePH Quick Cache
        <span class="weph-qc-version">v<?php echo WEPH_QC_VERSION; ?></span>
    </h1>
    
    <div class="weph-qc-container">
        <!-- Stats Section -->
        <div class="weph-qc-stats">
            <div class="weph-qc-stat-box">
                <div class="weph-qc-stat-icon">
                    <span class="dashicons dashicons-media-document"></span>
                </div>
                <div class="weph-qc-stat-content">
                    <h3><?php echo number_format($cache_stats['total_files']); ?></h3>
                    <p>Cached Files</p>
                </div>
            </div>
            
            <div class="weph-qc-stat-box">
                <div class="weph-qc-stat-icon">
                    <span class="dashicons dashicons-database"></span>
                </div>
                <div class="weph-qc-stat-content">
                    <h3><?php echo $cache_manager->format_bytes($cache_stats['total_size']); ?></h3>
                    <p>Cache Size</p>
                </div>
            </div>
            
            <div class="weph-qc-stat-box">
                <div class="weph-qc-stat-icon">
                    <span class="dashicons dashicons-media-code"></span>
                </div>
                <div class="weph-qc-stat-content">
                    <h3><?php echo number_format($cache_stats['css_files']); ?></h3>
                    <p>CSS Files</p>
                </div>
            </div>
            
            <div class="weph-qc-stat-box">
                <div class="weph-qc-stat-icon">
                    <span class="dashicons dashicons-media-code"></span>
                </div>
                <div class="weph-qc-stat-content">
                    <h3><?php echo number_format($cache_stats['js_files']); ?></h3>
                    <p>JS Files</p>
                </div>
            </div>
        </div>
        
        <!-- Clear Cache Button -->
        <div class="weph-qc-actions">
            <button type="button" class="button button-primary button-large weph-qc-clear-cache">
                <span class="dashicons dashicons-trash"></span>
                Clear All Cache
            </button>
            <span class="weph-qc-cache-message"></span>
        </div>
        
        <div class="weph-qc-info-box" style="margin-bottom: 20px; background: #fff3cd; border-left-color: #ffc107;">
            <h3><span class="dashicons dashicons-warning"></span> Important: Multiple Cache Layers</h3>
            <p><strong>If changes aren't appearing on your site, you may have multiple caching layers:</strong></p>
            <ul>
                <li><strong>Server Cache:</strong> Contact your hosting provider (cPanel, Cloudflare, etc.)</li>
                <li><strong>CDN Cache:</strong> Purge Cloudflare, Cloudways, or other CDN caches</li>
                <li><strong>Other Plugins:</strong> Clear WP Rocket, W3 Total Cache, LiteSpeed Cache, etc.</li>
                <li><strong>Browser Cache:</strong> Hard refresh (Ctrl+Shift+R or Cmd+Shift+R)</li>
                <li><strong>Object Cache:</strong> Redis or Memcached may need manual flush</li>
            </ul>
            <p><strong>Pro Tip:</strong> When logged in, you see fresh content. Test in an incognito/private window to see cached version.</p>
        </div>
        
        <!-- Settings Form -->
        <form method="post" action="" class="weph-qc-settings-form">
            <?php wp_nonce_field('weph_qc_settings_nonce'); ?>
            
            <div class="weph-qc-tabs">
                <div class="weph-qc-tab-nav">
                    <button type="button" class="weph-qc-tab-button active" data-tab="general">
                        <span class="dashicons dashicons-admin-generic"></span>
                        General
                    </button>
                    <button type="button" class="weph-qc-tab-button" data-tab="cache-management">
                        <span class="dashicons dashicons-database"></span>
                        Cache Management
                    </button>
                    <button type="button" class="weph-qc-tab-button" data-tab="minification">
                        <span class="dashicons dashicons-performance"></span>
                        Minification
                    </button>
                    <button type="button" class="weph-qc-tab-button" data-tab="exclusions">
                        <span class="dashicons dashicons-dismiss"></span>
                        Exclusions
                    </button>
                    <button type="button" class="weph-qc-tab-button" data-tab="advanced">
                        <span class="dashicons dashicons-admin-tools"></span>
                        Advanced
                    </button>
                    <button type="button" class="weph-qc-tab-button" data-tab="image-metadata">
                        <span class="dashicons dashicons-format-image"></span>
                        Image Metadata
                    </button>
                    <button type="button" class="weph-qc-tab-button" data-tab="server-config">
                        <span class="dashicons dashicons-admin-settings"></span>
                        Server Config
                    </button>
                </div>
                
                <!-- General Tab -->
                <div class="weph-qc-tab-content active" id="tab-general">
                    <h2>General Settings</h2>
                    
                    <table class="form-table">
                        <tr>
                            <th scope="row">
                                <label for="weph_qc_enable_gzip">Enable Gzip Compression</label>
                            </th>
                            <td>
                                <label class="weph-qc-switch">
                                    <input type="checkbox" name="weph_qc_enable_gzip" id="weph_qc_enable_gzip" value="1" <?php checked(get_option('weph_qc_enable_gzip', 1), 1); ?>>
                                    <span class="weph-qc-slider"></span>
                                </label>
                                <p class="description">Compress HTML, CSS, and JavaScript files using Gzip to reduce file sizes.</p>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">
                                <label for="weph_qc_enable_browser_cache">Browser Caching</label>
                            </th>
                            <td>
                                <label class="weph-qc-switch">
                                    <input type="checkbox" name="weph_qc_enable_browser_cache" id="weph_qc_enable_browser_cache" value="1" <?php checked(get_option('weph_qc_enable_browser_cache', 1), 1); ?>>
                                    <span class="weph-qc-slider"></span>
                                </label>
                                <p class="description">Set browser caching headers to store static files in visitor's browsers.</p>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">
                                <label for="weph_qc_enable_lazy_load">Lazy Load Images</label>
                            </th>
                            <td>
                                <label class="weph-qc-switch">
                                    <input type="checkbox" name="weph_qc_enable_lazy_load" id="weph_qc_enable_lazy_load" value="1" <?php checked(get_option('weph_qc_enable_lazy_load', 1), 1); ?>>
                                    <span class="weph-qc-slider"></span>
                                </label>
                                <p class="description">Load images only when they're about to enter the viewport.</p>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">
                                <label for="weph_qc_enable_webp">WebP Conversion</label>
                            </th>
                            <td>
                                <label class="weph-qc-switch">
                                    <input type="checkbox" name="weph_qc_enable_webp" id="weph_qc_enable_webp" value="1" <?php checked(get_option('weph_qc_enable_webp', 1), 1); ?>>
                                    <span class="weph-qc-slider"></span>
                                </label>
                                <p class="description">Automatically convert JPG/PNG images to WebP format and serve to supported browsers. <strong>Reduces image size by 25-35%!</strong></p>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">
                                <label for="weph_qc_dns_prefetch">DNS Prefetch</label>
                            </th>
                            <td>
                                <label class="weph-qc-switch">
                                    <input type="checkbox" name="weph_qc_dns_prefetch" id="weph_qc_dns_prefetch" value="1" <?php checked(get_option('weph_qc_dns_prefetch', 1), 1); ?>>
                                    <span class="weph-qc-slider"></span>
                                </label>
                                <p class="description">Resolve DNS for external resources earlier to improve loading speed.</p>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">
                                <label for="weph_qc_enable_local_fonts">Local Google Fonts</label>
                            </th>
                            <td>
                                <label class="weph-qc-switch">
                                    <input type="checkbox" name="weph_qc_enable_local_fonts" id="weph_qc_enable_local_fonts" value="1" <?php checked(get_option('weph_qc_enable_local_fonts', 1), 1); ?>>
                                    <span class="weph-qc-slider"></span>
                                </label>
                                <p class="description"><strong>Download and host Google Fonts locally.</strong> Eliminates external requests, improves GDPR compliance, and speeds up font loading. <strong>Highly recommended!</strong></p>
                            </td>
                        </tr>
                    </table>
                </div>
                
                <!-- Minification Tab -->
                <div class="weph-qc-tab-content" id="tab-minification">
                    <h2>Minification Settings</h2>
                    
                    <table class="form-table">
                        <tr>
                            <th scope="row">
                                <label for="weph_qc_enable_minify">Enable Minification</label>
                            </th>
                            <td>
                                <label class="weph-qc-switch">
                                    <input type="checkbox" name="weph_qc_enable_minify" id="weph_qc_enable_minify" value="1" <?php checked(get_option('weph_qc_enable_minify', 1), 1); ?>>
                                    <span class="weph-qc-slider"></span>
                                </label>
                                <p class="description">Enable file minification (required for options below).</p>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">
                                <label for="weph_qc_minify_html">Minify HTML</label>
                            </th>
                            <td>
                                <label class="weph-qc-switch">
                                    <input type="checkbox" name="weph_qc_minify_html" id="weph_qc_minify_html" value="1" <?php checked(get_option('weph_qc_minify_html', 1), 1); ?>>
                                    <span class="weph-qc-slider"></span>
                                </label>
                                <p class="description">Remove whitespace and comments from HTML output.</p>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">
                                <label for="weph_qc_minify_css">Minify CSS</label>
                            </th>
                            <td>
                                <label class="weph-qc-switch">
                                    <input type="checkbox" name="weph_qc_minify_css" id="weph_qc_minify_css" value="1" <?php checked(get_option('weph_qc_minify_css', 1), 1); ?>>
                                    <span class="weph-qc-slider"></span>
                                </label>
                                <p class="description">Compress CSS files to reduce file sizes.</p>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">
                                <label for="weph_qc_minify_js">Minify JavaScript</label>
                            </th>
                            <td>
                                <label class="weph-qc-switch">
                                    <input type="checkbox" name="weph_qc_minify_js" id="weph_qc_minify_js" value="1" <?php checked(get_option('weph_qc_minify_js', 1), 1); ?>>
                                    <span class="weph-qc-slider"></span>
                                </label>
                                <p class="description">Compress JavaScript files to reduce file sizes.</p>
                            </td>
                        </tr>
                    </table>
                </div>
                
                <!-- Advanced Tab -->
                <div class="weph-qc-tab-content" id="tab-advanced">
                    <h2>Advanced Settings</h2>
                    
                    <table class="form-table">
                        <tr>
                            <th scope="row">
                                <label for="weph_qc_defer_js">Defer JavaScript Loading</label>
                            </th>
                            <td>
                                <label class="weph-qc-switch">
                                    <input type="checkbox" name="weph_qc_defer_js" id="weph_qc_defer_js" value="1" <?php checked(get_option('weph_qc_defer_js', 1), 1); ?>>
                                    <span class="weph-qc-slider"></span>
                                </label>
                                <p class="description">Defer JavaScript files to load after page content (excludes jQuery).</p>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">
                                <label for="weph_qc_remove_query_strings">Remove Query Strings</label>
                            </th>
                            <td>
                                <label class="weph-qc-switch">
                                    <input type="checkbox" name="weph_qc_remove_query_strings" id="weph_qc_remove_query_strings" value="1" <?php checked(get_option('weph_qc_remove_query_strings', 1), 1); ?>>
                                    <span class="weph-qc-slider"></span>
                                </label>
                                <p class="description">Remove version query strings from static resources for better caching.</p>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">
                                <label for="weph_qc_disable_emojis">Disable WordPress Emojis</label>
                            </th>
                            <td>
                                <label class="weph-qc-switch">
                                    <input type="checkbox" name="weph_qc_disable_emojis" id="weph_qc_disable_emojis" value="1" <?php checked(get_option('weph_qc_disable_emojis', 1), 1); ?>>
                                    <span class="weph-qc-slider"></span>
                                </label>
                                <p class="description">Remove emoji scripts and styles to reduce HTTP requests.</p>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">
                                <label for="weph_qc_exclude_woocommerce">Exclude WooCommerce Pages</label>
                            </th>
                            <td>
                                <label class="weph-qc-switch">
                                    <input type="checkbox" name="weph_qc_exclude_woocommerce" id="weph_qc_exclude_woocommerce" value="1" <?php checked(get_option('weph_qc_exclude_woocommerce', 1), 1); ?>>
                                    <span class="weph-qc-slider"></span>
                                </label>
                                <p class="description"><strong>Highly Recommended for WooCommerce sites!</strong> Excludes cart, checkout, product pages, and WooCommerce AJAX from optimization to prevent conflicts. Keeps your store running smoothly.</p>
                            </td>
                        </tr>
                    </table>
                    
                    <div class="weph-qc-info-box">
                        <h3><span class="dashicons dashicons-info"></span> Important Information</h3>
                        <ul>
                            <li>After changing settings, clear your cache for changes to take effect.</li>
                            <li>Test your website after enabling minification to ensure compatibility.</li>
                            <li>Some themes or plugins may conflict with certain optimizations.</li>
                            <li>For Elementor websites, ensure "Improved Asset Loading" is disabled in Elementor settings.</li>
                            <li><strong>WebP Conversion:</strong> First-time conversion may take a few moments. WebP images are cached for faster subsequent loads.</li>
                            <li><strong>Local Google Fonts:</strong> Fonts are downloaded once and cached for 30 days. Improves speed and GDPR compliance!</li>
                            <li><strong>Performance Tip:</strong> Disable minification for logged-in users to improve admin experience.</li>
                        </ul>
                    </div>
                </div>
                
                <!-- Cache Management Tab -->
                <div class="weph-qc-tab-content" id="tab-cache-management">
                    <h2>Cache Management Settings</h2>
                    
                    <table class="form-table">
                        <tr>
                            <th scope="row">
                                <label for="weph_qc_auto_clear_enabled">Enable Auto Clear Cache</label>
                            </th>
                            <td>
                                <label class="weph-qc-switch">
                                    <input type="checkbox" name="weph_qc_auto_clear_enabled" id="weph_qc_auto_clear_enabled" value="1" <?php checked(get_option('weph_qc_auto_clear_enabled', 0), 1); ?>>
                                    <span class="weph-qc-slider"></span>
                                </label>
                                <p class="description">Automatically clear cache on a schedule to keep content fresh.</p>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">
                                <label for="weph_qc_auto_clear_days">Auto Clear Cache Every (Days)</label>
                            </th>
                            <td>
                                <input type="number" name="weph_qc_auto_clear_days" id="weph_qc_auto_clear_days" value="<?php echo esc_attr(get_option('weph_qc_auto_clear_days', 7)); ?>" min="1" max="90" class="small-text">
                                <p class="description">Clear entire cache automatically every X days. Default: 7 days</p>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">
                                <label for="weph_qc_cache_lifespan_days">Cache File Lifespan (Days)</label>
                            </th>
                            <td>
                                <input type="number" name="weph_qc_cache_lifespan_days" id="weph_qc_cache_lifespan_days" value="<?php echo esc_attr(get_option('weph_qc_cache_lifespan_days', 30)); ?>" min="1" max="365" class="small-text">
                                <p class="description">Delete cached files older than X days. Default: 30 days</p>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">
                                <label for="weph_qc_clear_on_publish">Clear Cache on Post Publish</label>
                            </th>
                            <td>
                                <label class="weph-qc-switch">
                                    <input type="checkbox" name="weph_qc_clear_on_publish" id="weph_qc_clear_on_publish" value="1" <?php checked(get_option('weph_qc_clear_on_publish', 1), 1); ?>>
                                    <span class="weph-qc-slider"></span>
                                </label>
                                <p class="description">Automatically clear cache when publishing or updating posts/pages.</p>
                            </td>
                        </tr>
                    </table>
                    
                    <div class="weph-qc-actions" style="margin-top: 20px;">
                        <button type="button" class="button button-secondary button-large weph-qc-clear-old-cache">
                            <span class="dashicons dashicons-clock"></span>
                            Clear Old Cache Files Now
                        </button>
                        <span class="weph-qc-old-cache-message"></span>
                        <p class="description" style="margin-top: 10px;">Manually clear cache files older than the specified lifespan.</p>
                    </div>
                    
                    <div class="weph-qc-info-box" style="margin-top: 20px;">
                        <h3><span class="dashicons dashicons-info"></span> Cache Management Tips</h3>
                        <ul>
                            <li><strong>Auto Clear:</strong> Useful for sites with frequently updated content</li>
                            <li><strong>Lifespan:</strong> Prevents old cached files from accumulating and taking up disk space</li>
                            <li><strong>On Publish:</strong> Ensures visitors see your latest content immediately</li>
                            <li><strong>Manual Clear:</strong> Use the buttons above for immediate cache clearing</li>
                        </ul>
                    </div>
                </div>
                
                <!-- Exclusions Tab -->
                <div class="weph-qc-tab-content" id="tab-exclusions">
                    <h2>Exclusion Settings</h2>
                    <p>Exclude specific pages, URLs, scripts, and CSS from optimization.</p>
                    
                    <table class="form-table">
                        <tr>
                            <th scope="row">
                                <label for="weph_qc_excluded_pages">Excluded Page IDs</label>
                            </th>
                            <td>
                                <input type="text" name="weph_qc_excluded_pages" id="weph_qc_excluded_pages" value="<?php echo esc_attr(get_option('weph_qc_excluded_pages', '')); ?>" class="regular-text" placeholder="e.g., 12, 45, 89">
                                <p class="description">Comma-separated list of page/post IDs to exclude from ALL optimizations.</p>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">
                                <label for="weph_qc_excluded_urls">Excluded URL Patterns</label>
                            </th>
                            <td>
                                <textarea name="weph_qc_excluded_urls" id="weph_qc_excluded_urls" rows="5" class="large-text" placeholder="/checkout/*&#10;/my-account/*&#10;/cart/"><?php echo esc_textarea(get_option('weph_qc_excluded_urls', '')); ?></textarea>
                                <p class="description">One URL pattern per line. Use * as wildcard. Examples:<br>
                                <code>/checkout/*</code> - Excludes all checkout pages<br>
                                <code>/my-special-page</code> - Excludes exact URL</p>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">
                                <label for="weph_qc_excluded_scripts">Excluded JavaScript Files</label>
                            </th>
                            <td>
                                <textarea name="weph_qc_excluded_scripts" id="weph_qc_excluded_scripts" rows="5" class="large-text" placeholder="google-analytics&#10;stripe&#10;paypal&#10;recaptcha"><?php echo esc_textarea(get_option('weph_qc_excluded_scripts', '')); ?></textarea>
                                <p class="description">One script identifier per line. Scripts containing these strings will NOT be deferred.<br>
                                Examples: <code>google-analytics</code>, <code>stripe</code>, <code>paypal</code>, <code>recaptcha</code><br>
                                Note: jQuery and admin-bar are automatically excluded.</p>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">
                                <label for="weph_qc_excluded_css">Excluded CSS Files</label>
                            </th>
                            <td>
                                <textarea name="weph_qc_excluded_css" id="weph_qc_excluded_css" rows="5" class="large-text" placeholder="elementor&#10;custom-theme&#10;admin-bar"><?php echo esc_textarea(get_option('weph_qc_excluded_css', '')); ?></textarea>
                                <p class="description">One CSS identifier per line. CSS files containing these strings will NOT be minified.<br>
                                Use this if certain stylesheets break when minified.</p>
                            </td>
                        </tr>
                    </table>
                    
                    <div class="weph-qc-info-box">
                        <h3><span class="dashicons dashicons-info"></span> Exclusion Tips</h3>
                        <ul>
                            <li><strong>Page IDs:</strong> Find page ID in WordPress admin (hover over page title in list view)</li>
                            <li><strong>URL Patterns:</strong> Use relative URLs starting with /. Wildcards (*) match any characters</li>
                            <li><strong>Scripts:</strong> Exclude payment gateways, analytics, and any JS that breaks when deferred</li>
                            <li><strong>CSS:</strong> Exclude page builders and theme files if they cause visual issues</li>
                            <li><strong>Testing:</strong> After excluding, test your site in incognito mode to verify functionality</li>
                        </ul>
                    </div>
                </div>
                
                <!-- Server Configuration Tab -->
                <div class="weph-qc-tab-content" id="tab-server-config">
                    <h2>Server Configuration</h2>
                    
                    <?php 
                    $plugin_instance = WePH_Quick_Cache::get_instance();
                    $server_type = $plugin_instance->get_server_type();
                    ?>
                    
                    <div class="weph-qc-info-box" style="background: #e7f5fe; border-left-color: #00a0d2;">
                        <h3><span class="dashicons dashicons-admin-settings"></span> Detected Server: <?php echo strtoupper($server_type); ?></h3>
                        <p>Your server type has been detected. Follow the appropriate configuration below.</p>
                    </div>
                    
                    <?php if ($server_type === 'nginx'): ?>
                    <div class="weph-qc-server-config">
                        <h3>Nginx Configuration</h3>
                        <p><strong>For WebP and caching to work properly on Nginx, add this configuration to your server block:</strong></p>
                        <pre style="background: #23282d; color: #f0f0f1; padding: 15px; overflow-x: auto; border-radius: 4px;"><code><?php echo esc_html($plugin_instance->get_nginx_config()); ?></code></pre>
                        <button type="button" class="button button-secondary" onclick="navigator.clipboard.writeText(this.previousElementSibling.querySelector('code').textContent); this.textContent='Copied!'; setTimeout(() => this.textContent='Copy Configuration', 2000);">
                            <span class="dashicons dashicons-clipboard"></span> Copy Configuration
                        </button>
                        <div class="weph-qc-info-box" style="margin-top: 15px;">
                            <h4>How to Apply This Configuration:</h4>
                            <ol>
                                <li>Copy the configuration above</li>
                                <li>SSH into your server</li>
                                <li>Edit your Nginx config: <code>sudo nano /etc/nginx/sites-available/your-site</code></li>
                                <li>Add the configuration inside your <code>server {}</code> block</li>
                                <li>Test configuration: <code>sudo nginx -t</code></li>
                                <li>Reload Nginx: <code>sudo systemctl reload nginx</code></li>
                            </ol>
                            <p><strong>Need help?</strong> Contact your hosting provider or system administrator.</p>
                        </div>
                    </div>
                    <?php elseif ($server_type === 'apache'): ?>
                    <div class="weph-qc-server-config">
                        <h3>Apache Configuration</h3>
                        <p><strong>Good news!</strong> Apache configuration is handled automatically via .htaccess file.</p>
                        <p>The plugin has already created the necessary rules in your WordPress .htaccess file.</p>
                        <div class="weph-qc-info-box" style="margin-top: 15px;">
                            <h4>Verify Apache Modules:</h4>
                            <p>Make sure these Apache modules are enabled:</p>
                            <ul>
                                <li><code>mod_rewrite</code> - For URL rewriting</li>
                                <li><code>mod_deflate</code> - For Gzip compression</li>
                                <li><code>mod_expires</code> - For browser caching</li>
                                <li><code>mod_headers</code> - For cache headers</li>
                            </ul>
                            <p>Contact your hosting provider if you need help enabling these modules.</p>
                        </div>
                    </div>
                    <?php else: ?>
                    <div class="weph-qc-server-config">
                        <h3>Unknown Server Type</h3>
                        <p>Your server type could not be detected. This plugin works best with Apache or Nginx.</p>
                        <p>Contact your hosting provider for server-specific optimization recommendations.</p>
                    </div>
                    <?php endif; ?>
                </div>
                
                <!-- Image Metadata Tab -->
                <div class="weph-qc-tab-content" id="tab-image-metadata">
                    <h2>Image Metadata Auto-Fill Settings</h2>
                    
                    <table class="form-table">
                        <tr>
                            <th scope="row">
                                <label for="weph_qc_enable_image_metadata">Enable Image Metadata Auto-Fill</label>
                            </th>
                            <td>
                                <label class="weph-qc-switch">
                                    <input type="checkbox" name="weph_qc_enable_image_metadata" id="weph_qc_enable_image_metadata" value="1" <?php checked(get_option('weph_qc_enable_image_metadata', 0), 1); ?>>
                                    <span class="weph-qc-slider"></span>
                                </label>
                                <p class="description">Automatically fill image metadata for all uploaded and existing images.</p>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">
                                <label for="weph_qc_bust_browser_cache">Force Browser Cache Refresh</label>
                            </th>
                            <td>
                                <label class="weph-qc-switch">
                                    <input type="checkbox" name="weph_qc_bust_browser_cache" id="weph_qc_bust_browser_cache" value="1" <?php checked(get_option('weph_qc_bust_browser_cache', 1), 1); ?>>
                                    <span class="weph-qc-slider"></span>
                                </label>
                                <p class="description"><strong>Highly Recommended!</strong> Adds version parameters to image URLs to force browsers to load updated metadata. When metadata changes, the image URL changes, forcing browser cache refresh.</p>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">
                                <label for="weph_qc_image_alt_text">Alt Text</label>
                            </th>
                            <td>
                                <input type="text" name="weph_qc_image_alt_text" id="weph_qc_image_alt_text" value="<?php echo esc_attr(get_option('weph_qc_image_alt_text', '')); ?>" class="regular-text" placeholder="e.g., {filename} - {site_name}">
                                <p class="description">Alt text for images. Supports variables: {filename}, {title}, {site_name}, {site_description}</p>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">
                                <label for="weph_qc_image_title_text">Title Text</label>
                            </th>
                            <td>
                                <input type="text" name="weph_qc_image_title_text" id="weph_qc_image_title_text" value="<?php echo esc_attr(get_option('weph_qc_image_title_text', '')); ?>" class="regular-text" placeholder="e.g., {filename}">
                                <p class="description">Title attribute for images. Supports same variables as above.</p>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">
                                <label for="weph_qc_image_caption">Caption</label>
                            </th>
                            <td>
                                <textarea name="weph_qc_image_caption" id="weph_qc_image_caption" rows="3" class="large-text" placeholder="e.g., Image from {site_name}"><?php echo esc_textarea(get_option('weph_qc_image_caption', '')); ?></textarea>
                                <p class="description">Caption for images. Supports same variables as above.</p>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">
                                <label for="weph_qc_image_description">Description</label>
                            </th>
                            <td>
                                <textarea name="weph_qc_image_description" id="weph_qc_image_description" rows="4" class="large-text" placeholder="e.g., {filename} from {site_name}"><?php echo esc_textarea(get_option('weph_qc_image_description', '')); ?></textarea>
                                <p class="description">Description for images. Supports same variables as above.</p>
                            </td>
                        </tr>
                    </table>
                    
                    <div class="weph-qc-actions" style="margin-top: 20px;">
                        <button type="button" class="button button-secondary button-large weph-qc-bulk-update-metadata">
                            <span class="dashicons dashicons-update"></span>
                            Update All Existing Images
                        </button>
                        <span class="weph-qc-metadata-message"></span>
                        <p class="description" style="margin-top: 10px;">Click this button to apply the metadata settings to all existing images in your media library. New uploads will be automatically updated if the feature is enabled.</p>
                    </div>
                    
                    <div class="weph-qc-info-box" style="margin-top: 20px;">
                        <h3><span class="dashicons dashicons-info"></span> Available Variables</h3>
                        <ul>
                            <li><strong>{filename}</strong> - The image filename (cleaned and capitalized)</li>
                            <li><strong>{title}</strong> - The image title</li>
                            <li><strong>{site_name}</strong> - Your website name</li>
                            <li><strong>{site_description}</strong> - Your website tagline/description</li>
                        </ul>
                        <p><strong>Example:</strong> If you set Alt Text to "{filename} - {site_name}" and upload an image named "sunset-beach.jpg", the alt text will be "Sunset Beach - Your Site Name"</p>
                        <hr>
                        <h4><span class="dashicons dashicons-update"></span> Browser Cache Busting</h4>
                        <p>When "Force Browser Cache Refresh" is enabled, image URLs will include version parameters (e.g., image.jpg?ver=1234567890). This ensures:</p>
                        <ul>
                            <li>✅ Updated metadata appears immediately on your website</li>
                            <li>✅ Visitors' browsers load the latest version</li>
                            <li>✅ No need to clear browser cache manually</li>
                            <li>✅ Changes are visible instantly after updating metadata</li>
                        </ul>
                    </div>
                </div>
            </div>
            
            <p class="submit">
                <button type="submit" name="weph_qc_save_settings" class="button button-primary button-large">
                    <span class="dashicons dashicons-yes"></span>
                    Save Settings
                </button>
            </p>
        </form>
        
        <!-- Footer -->
        <div class="weph-qc-footer">
            <p>
                <strong>WePH Quick Cache</strong> - Performance Optimization Plugin for WordPress & Elementor
                <br>
                Need help? <a href="https://example.com/support" target="_blank">Visit Support</a> | 
                <a href="https://example.com/docs" target="_blank">Documentation</a>
            </p>
        </div>
    </div>
</div>
