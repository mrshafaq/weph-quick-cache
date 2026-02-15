<?php
/**
 * Plugin Name: WePH Quick Cache
 * Plugin URI: https://mrshafaq.com/weph-quick-cache
 * Description: Complete performance optimization plugin with Gzip compression, CSS/JS/HTML minification, WebP conversion, Local Google Fonts, browser caching, and more for WordPress and Elementor websites.
 * Version: 1.4.1
 * Author: MrShafaQ, Patrick Hofman
 * Author URI: https://mrshafaq.com
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: weph-quick-cache
 * Requires at least: 5.0
 * Requires PHP: 7.4
 * Contributors: MrShafaQ (https://mrshafaq.com), Patrick Hofman (https://weph.de)
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('WEPH_QC_VERSION', '1.4.1');
define('WEPH_QC_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('WEPH_QC_PLUGIN_URL', plugin_dir_url(__FILE__));
define('WEPH_QC_CACHE_DIR', WP_CONTENT_DIR . '/cache/weph-quick-cache/');

// ============================================================================
// GitHub Auto-Update Integration
// ============================================================================
// Load the Plugin Update Checker library
require WEPH_QC_PLUGIN_DIR . 'plugin-update-checker/plugin-update-checker.php';
use YahnisElsts\PluginUpdateChecker\v5\PucFactory;

// Initialize the update checker
$wephQcUpdateChecker = PucFactory::buildUpdateChecker(
    'https://github.com/mrshafaq/weph-quick-cache/', // TODO
    __FILE__,
    'weph-quick-cache'
);

// Set the branch to track (default is 'main')
$wephQcUpdateChecker->setBranch('main');

// Optional: Enable release assets (if you want to upload ZIP files manually)
// $wephQcUpdateChecker->getVcsApi()->enableReleaseAssets();

// For PRIVATE repositories only: Uncomment and add your GitHub token
// You can store the token in wp-config.php for security:
// define('WEPH_QC_GITHUB_TOKEN', 'ghp_yourPersonalAccessTokenHere');
// if (defined('WEPH_QC_GITHUB_TOKEN')) {
//     $wephQcUpdateChecker->setAuthentication(WEPH_QC_GITHUB_TOKEN);
// }
// ============================================================================

/**
 * Main WePH Quick Cache Class
 */
class WePH_Quick_Cache {
    
    private static $instance = null;
    
    /**
     * Get singleton instance
     */
    public static function get_instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Constructor
     */
    private function __construct() {
        $this->init_hooks();
        $this->load_dependencies();
    }
    
    /**
     * Initialize hooks
     */
    private function init_hooks() {
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
        
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'register_settings'));
        add_action('admin_enqueue_scripts', array($this, 'admin_scripts'));
        
        // Performance optimization hooks
        if (get_option('weph_qc_enable_gzip', 1)) {
            add_action('init', array($this, 'enable_gzip'));
        }
        
        // Only run optimizations on frontend
        if (!is_admin()) {
            if (get_option('weph_qc_enable_minify', 1)) {
                add_action('template_redirect', array($this, 'start_html_buffer'), 1);
            }
            
            if (get_option('weph_qc_enable_lazy_load', 1)) {
                add_filter('the_content', array($this, 'add_lazy_load'), 99);
                add_filter('post_thumbnail_html', array($this, 'add_lazy_load'), 99);
                add_filter('wp_get_attachment_image_attributes', array($this, 'add_lazy_load_to_img_attr'), 10, 2);
            }
            
            if (get_option('weph_qc_enable_webp', 1)) {
                add_filter('the_content', array($this, 'convert_images_to_webp'), 100);
                add_filter('post_thumbnail_html', array($this, 'convert_images_to_webp'), 100);
            }
            
            if (get_option('weph_qc_enable_local_fonts', 1)) {
                add_action('wp_head', array($this, 'replace_google_fonts_with_local'), 1);
                add_filter('style_loader_tag', array($this, 'intercept_google_fonts'), 10, 2);
            }
        }
        
        // Disable emojis
        if (get_option('weph_qc_disable_emojis', 1)) {
            $this->disable_emojis();
        }
        
        // DNS Prefetch
        if (get_option('weph_qc_dns_prefetch', 1)) {
            add_action('wp_head', array($this, 'add_dns_prefetch'), 0);
        }
        
        // Image metadata auto-fill
        if (get_option('weph_qc_enable_image_metadata', 0)) {
            add_filter('wp_generate_attachment_metadata', array($this, 'auto_fill_image_metadata'), 10, 2);
            add_filter('attachment_fields_to_save', array($this, 'update_image_metadata_on_save'), 10, 2);
        }
        
        // Browser cache busting for images
        if (!is_admin() && get_option('weph_qc_bust_browser_cache', 1)) {
            add_filter('wp_get_attachment_image_src', array($this, 'add_cache_busting_to_images'), 10, 4);
            add_filter('wp_calculate_image_srcset', array($this, 'add_cache_busting_to_srcset'), 10, 5);
        }
        
        // Auto clear cache on publish
        if (get_option('weph_qc_clear_on_publish', 1)) {
            add_action('publish_post', array($this, 'clear_cache_on_publish'));
            add_action('publish_page', array($this, 'clear_cache_on_publish'));
        }
        
        // Schedule auto cache clear
        if (get_option('weph_qc_auto_clear_enabled', 0)) {
            if (!wp_next_scheduled('weph_qc_scheduled_cache_clear')) {
                wp_schedule_event(time(), 'daily', 'weph_qc_scheduled_cache_clear');
            }
            add_action('weph_qc_scheduled_cache_clear', array($this, 'scheduled_cache_clear'));
        }
        
        // AJAX handlers
        add_action('wp_ajax_weph_qc_clear_cache', array($this, 'ajax_clear_cache'));
        add_action('wp_ajax_weph_qc_bulk_update_metadata', array($this, 'ajax_bulk_update_metadata'));
        add_action('wp_ajax_weph_qc_clear_old_cache', array($this, 'ajax_clear_old_cache'));
    }
    
    /**
     * Load dependencies
     */
    private function load_dependencies() {
        require_once WEPH_QC_PLUGIN_DIR . 'includes/class-minifier.php';
        require_once WEPH_QC_PLUGIN_DIR . 'includes/class-cache-manager.php';
    }
    
    /**
     * Check if current page is WooCommerce and should be excluded
     */
    private function should_exclude_woocommerce() {
        // If WooCommerce exclusion is disabled, don't exclude
        if (!get_option('weph_qc_exclude_woocommerce', 1)) {
            return false;
        }
        
        // Check if WooCommerce is active
        if (!function_exists('is_woocommerce')) {
            return false;
        }
        
        // Exclude WooCommerce pages
        if (is_woocommerce() || is_cart() || is_checkout() || is_account_page()) {
            return true;
        }
        
        // Exclude AJAX requests for WooCommerce
        if (defined('DOING_AJAX') && DOING_AJAX) {
            if (isset($_REQUEST['action']) && strpos($_REQUEST['action'], 'woocommerce') !== false) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Activate plugin
     */
    public function activate() {
        // Create cache directory
        if (!file_exists(WEPH_QC_CACHE_DIR)) {
            wp_mkdir_p(WEPH_QC_CACHE_DIR);
        }
        
        // Set default options
        add_option('weph_qc_enable_gzip', 1);
        add_option('weph_qc_enable_minify', 1);
        add_option('weph_qc_enable_browser_cache', 1);
        add_option('weph_qc_enable_lazy_load', 1);
        add_option('weph_qc_enable_webp', 1);
        add_option('weph_qc_enable_local_fonts', 1);
        add_option('weph_qc_minify_html', 1);
        add_option('weph_qc_minify_css', 1);
        add_option('weph_qc_minify_js', 1);
        add_option('weph_qc_combine_css', 0);
        add_option('weph_qc_combine_js', 0);
        add_option('weph_qc_defer_js', 1);
        add_option('weph_qc_remove_query_strings', 1);
        add_option('weph_qc_disable_emojis', 1);
        add_option('weph_qc_preload_fonts', 0);
        add_option('weph_qc_dns_prefetch', 1);
        add_option('weph_qc_enable_image_metadata', 0);
        add_option('weph_qc_image_alt_text', '');
        add_option('weph_qc_image_title_text', '');
        add_option('weph_qc_image_caption', '');
        add_option('weph_qc_image_description', '');
        add_option('weph_qc_bust_browser_cache', 1);
        add_option('weph_qc_exclude_woocommerce', 1); // WooCommerce compatibility - ON by default
        
        // New cache management options
        add_option('weph_qc_auto_clear_enabled', 0);
        add_option('weph_qc_auto_clear_days', 7);
        add_option('weph_qc_clear_on_publish', 1);
        add_option('weph_qc_cache_lifespan_days', 30);
        
        // New exclusion options
        add_option('weph_qc_excluded_pages', '');
        add_option('weph_qc_excluded_urls', '');
        add_option('weph_qc_excluded_scripts', '');
        add_option('weph_qc_excluded_css', '');
        
        // Create .htaccess rules
        $this->update_htaccess();
        
        flush_rewrite_rules();
    }
    
    /**
     * Deactivate plugin
     */
    public function deactivate() {
        // Clear cache
        $this->clear_cache();
        
        // Remove .htaccess rules
        $this->remove_htaccess_rules();
        
        flush_rewrite_rules();
    }
    
    /**
     * Enable Gzip compression
     */
    public function enable_gzip() {
        if (!headers_sent() && extension_loaded('zlib') && !ini_get('zlib.output_compression')) {
            if (strpos($_SERVER['HTTP_ACCEPT_ENCODING'], 'gzip') !== false) {
                ob_start('ob_gzhandler');
            }
        }
    }
    
    /**
     * Start HTML output buffering for minification
     */
    public function start_html_buffer() {
        if (get_option('weph_qc_minify_html', 1)) {
            ob_start(array($this, 'minify_html_callback'));
        }
    }
    
    /**
     * Minify HTML callback with proper caching
     */
    public function minify_html_callback($html) {
        // Skip minification for excluded pages
        if ($this->is_page_excluded()) {
            return $html;
        }
        
        // Skip minification for WooCommerce pages if exclusion is enabled
        if ($this->should_exclude_woocommerce()) {
            return $html;
        }
        
        // Skip minification for logged-in users and send no-cache headers
        if (is_user_logged_in()) {
            if (!headers_sent()) {
                header("Cache-Control: no-cache, no-store, must-revalidate, max-age=0");
                header("Pragma: no-cache");
                header("Expires: Thu, 01 Jan 1970 00:00:00 GMT");
            }
            return $html;
        }
        
        // Skip if admin, customizer, or cache bypass parameter
        if (is_admin() || is_customize_preview() || isset($_GET['nocache'])) {
            return $html;
        }
        
        $minifier = new WePH_QC_Minifier();
        
        // Only minify if enabled
        if (get_option('weph_qc_minify_html', 1)) {
            $html = $minifier->minify_html($html);
        }
        
        // Minify inline CSS
        if (get_option('weph_qc_minify_css', 1)) {
            $html = preg_replace_callback('/<style[^>]*>(.*?)<\/style>/is', function($match) use ($minifier) {
                return '<style>' . $minifier->minify_css($match[1]) . '</style>';
            }, $html);
        }
        
        // Minify inline JS
        if (get_option('weph_qc_minify_js', 1)) {
            $html = preg_replace_callback('/<script[^>]*>(.*?)<\/script>/is', function($match) use ($minifier) {
                // Skip if external source
                if (preg_match('/src=/', $match[0])) {
                    return $match[0];
                }
                return '<script>' . $minifier->minify_js($match[1]) . '</script>';
            }, $html);
        }
        
        // Defer JavaScript
        if (get_option('weph_qc_defer_js', 1)) {
            $html = $this->defer_javascript($html);
        }
        
        // Remove query strings
        if (get_option('weph_qc_remove_query_strings', 1)) {
            $html = preg_replace('/(.css|.js)\?ver=[^"\']*/', '$1', $html);
        }
        
        return $html;
    }
    
    /**
     * Defer JavaScript in HTML
     */
    private function defer_javascript($html) {
        // Default excludes
        $default_excludes = array('jquery', 'admin-bar');
        
        // Get user-defined excluded scripts
        $custom_excludes_str = get_option('weph_qc_excluded_scripts', '');
        $custom_excludes = array();
        if (!empty($custom_excludes_str)) {
            $custom_excludes = explode("\n", $custom_excludes_str);
            $custom_excludes = array_map('trim', $custom_excludes);
            $custom_excludes = array_filter($custom_excludes);
        }
        
        // Merge all excludes
        $excludes = array_merge($default_excludes, $custom_excludes);
        
        $html = preg_replace_callback('/<script([^>]*)src=(["\'])([^"\']+)(["\'])([^>]*)>/i', 
            function($matches) use ($excludes) {
                $script = $matches[0];
                $src = $matches[3];
                
                // Check if should be excluded
                foreach ($excludes as $exclude) {
                    if (stripos($src, $exclude) !== false) {
                        return $script;
                    }
                }
                
                // Add defer if not present
                if (strpos($script, 'defer') === false && strpos($script, 'async') === false) {
                    return str_replace('<script', '<script defer', $script);
                }
                
                return $script;
            }, 
        $html);
        
        return $html;
    }
    
    /**
     * Disable WordPress emojis
     */
    private function disable_emojis() {
        remove_action('wp_head', 'print_emoji_detection_script', 7);
        remove_action('admin_print_scripts', 'print_emoji_detection_script');
        remove_action('wp_print_styles', 'print_emoji_styles');
        remove_action('admin_print_styles', 'print_emoji_styles');
        remove_filter('the_content_feed', 'wp_staticize_emoji');
        remove_filter('comment_text_rss', 'wp_staticize_emoji');
        remove_filter('wp_mail', 'wp_staticize_emoji_for_email');
    }
    
    /**
     * Add browser caching headers
     */
    public function browser_cache_headers() {
        if (!is_admin()) {
            $expires = 31536000; // 1 year
            header('Cache-Control: public, max-age=' . $expires);
            header('Expires: ' . gmdate('D, d M Y H:i:s', time() + $expires) . ' GMT');
            header('Pragma: public');
        }
    }
    
    /**
     * Add lazy loading to images
     */
    public function add_lazy_load($content) {
        if (is_admin() || is_feed() || is_preview()) {
            return $content;
        }
        
        $content = preg_replace_callback('/<img([^>]+?)src=/i', function($matches) {
            if (strpos($matches[1], 'loading=') === false) {
                return '<img' . $matches[1] . 'loading="lazy" src=';
            }
            return $matches[0];
        }, $content);
        
        return $content;
    }
    
    /**
     * Add lazy load to image attributes
     */
    public function add_lazy_load_to_img_attr($attr, $attachment) {
        if (!isset($attr['loading'])) {
            $attr['loading'] = 'lazy';
        }
        return $attr;
    }
    
    /**
     * Convert images to WebP and serve WebP versions
     */
    public function convert_images_to_webp($content) {
        if (is_admin() || is_feed() || is_preview()) {
            return $content;
        }
        
        // Check if browser supports WebP
        if (!$this->browser_supports_webp()) {
            return $content;
        }
        
        // Find all img tags
        $content = preg_replace_callback(
            '/<img[^>]+src=["\']([^"\']+\.(jpg|jpeg|png))["\']([^>]*)>/i',
            array($this, 'replace_with_webp'),
            $content
        );
        
        return $content;
    }
    
    /**
     * Replace image with WebP version
     */
    private function replace_with_webp($matches) {
        $img_tag = $matches[0];
        $img_url = $matches[1];
        
        // Generate WebP version
        $webp_url = $this->generate_webp_image($img_url);
        
        if ($webp_url) {
            // Replace src with WebP
            $img_tag = str_replace($img_url, $webp_url, $img_tag);
        }
        
        return $img_tag;
    }
    
    /**
     * Generate WebP image from original
     */
    private function generate_webp_image($image_url) {
        // Convert URL to file path
        $file_path = $this->url_to_path($image_url);
        
        if (!$file_path || !file_exists($file_path)) {
            return false;
        }
        
        // Check if WebP already exists
        $webp_path = preg_replace('/\.(jpg|jpeg|png)$/i', '.webp', $file_path);
        
        // If WebP exists and is newer than original, use it
        if (file_exists($webp_path) && filemtime($webp_path) >= filemtime($file_path)) {
            return $this->path_to_url($webp_path);
        }
        
        // Create WebP directory if needed
        $webp_dir = dirname($webp_path);
        if (!file_exists($webp_dir)) {
            wp_mkdir_p($webp_dir);
        }
        
        // Convert to WebP
        if ($this->create_webp($file_path, $webp_path)) {
            return $this->path_to_url($webp_path);
        }
        
        return false;
    }
    
    /**
     * Create WebP image
     */
    private function create_webp($source, $destination) {
        // Check if GD library supports WebP
        if (!function_exists('imagewebp')) {
            return false;
        }
        
        $image_info = @getimagesize($source);
        if (!$image_info) {
            return false;
        }
        
        $image = false;
        
        // Create image resource based on type
        switch ($image_info['mime']) {
            case 'image/jpeg':
                $image = @imagecreatefromjpeg($source);
                break;
            case 'image/png':
                $image = @imagecreatefrompng($source);
                // Preserve transparency
                imagealphablending($image, true);
                imagesavealpha($image, true);
                break;
            default:
                return false;
        }
        
        if (!$image) {
            return false;
        }
        
        // Convert to WebP with quality 85
        $result = @imagewebp($image, $destination, 85);
        
        // Free memory
        imagedestroy($image);
        
        return $result;
    }
    
    /**
     * Check if browser supports WebP
     */
    private function browser_supports_webp() {
        if (!isset($_SERVER['HTTP_ACCEPT'])) {
            return false;
        }
        
        return strpos($_SERVER['HTTP_ACCEPT'], 'image/webp') !== false;
    }
    
    /**
     * Convert URL to file path
     */
    private function url_to_path($url) {
        $wp_content_url = content_url();
        $wp_content_dir = WP_CONTENT_DIR;
        
        if (strpos($url, $wp_content_url) === 0) {
            return str_replace($wp_content_url, $wp_content_dir, $url);
        }
        
        $site_url = site_url();
        $abspath = ABSPATH;
        
        if (strpos($url, $site_url) === 0) {
            return str_replace($site_url, $abspath, $url);
        }
        
        if (strpos($url, '/') === 0) {
            return ABSPATH . ltrim($url, '/');
        }
        
        return false;
    }
    
    /**
     * Convert file path to URL
     */
    private function path_to_url($path) {
        $wp_content_dir = WP_CONTENT_DIR;
        $wp_content_url = content_url();
        
        if (strpos($path, $wp_content_dir) === 0) {
            return str_replace($wp_content_dir, $wp_content_url, $path);
        }
        
        return false;
    }
    
    /**
     * Add DNS prefetch
     */
    public function add_dns_prefetch() {
        $domains = array(
            'fonts.googleapis.com',
            'fonts.gstatic.com',
            'ajax.googleapis.com',
            'cdn.jsdelivr.net'
        );
        
        foreach ($domains as $domain) {
            echo '<link rel="dns-prefetch" href="//' . esc_attr($domain) . '">' . "\n";
        }
    }
    
    /**
     * Replace Google Fonts with local versions
     */
    public function replace_google_fonts_with_local() {
        // Start output buffering to capture and modify Google Fonts links
        ob_start(array($this, 'process_google_fonts_in_html'));
    }
    
    /**
     * Process HTML to replace Google Fonts with local versions
     */
    public function process_google_fonts_in_html($html) {
        // Find all Google Fonts links
        preg_match_all('/<link[^>]*href=[\'"]([^\'"]*fonts\.googleapis\.com[^\'"]*)[\'"][^>]*>/i', $html, $matches);
        
        if (empty($matches[0])) {
            return $html;
        }
        
        foreach ($matches[1] as $index => $font_url) {
            $local_css = $this->download_and_host_google_font($font_url);
            
            if ($local_css) {
                // Replace Google Fonts link with local version
                $original_tag = $matches[0][$index];
                $local_tag = '<link rel="stylesheet" href="' . esc_url($local_css) . '">';
                $html = str_replace($original_tag, $local_tag, $html);
            }
        }
        
        return $html;
    }
    
    /**
     * Intercept Google Fonts enqueued via WordPress
     */
    public function intercept_google_fonts($tag, $handle) {
        // Check if this is a Google Fonts stylesheet
        if (strpos($tag, 'fonts.googleapis.com') !== false) {
            preg_match('/href=[\'"]([^\'"]+)[\'"]/', $tag, $matches);
            
            if (!empty($matches[1])) {
                $font_url = $matches[1];
                $local_css = $this->download_and_host_google_font($font_url);
                
                if ($local_css) {
                    return '<link rel="stylesheet" href="' . esc_url($local_css) . '" id="' . esc_attr($handle) . '-css">';
                }
            }
        }
        
        return $tag;
    }
    
    /**
     * Download Google Font and host locally
     */
    private function download_and_host_google_font($font_url) {
        // Create fonts directory
        $fonts_dir = WEPH_QC_CACHE_DIR . 'fonts/';
        if (!file_exists($fonts_dir)) {
            wp_mkdir_p($fonts_dir);
        }
        
        // Generate unique filename based on URL
        $font_hash = md5($font_url);
        $css_file = $fonts_dir . $font_hash . '.css';
        
        // Check if already downloaded and cached
        if (file_exists($css_file)) {
            // Check if cache is fresh (30 days)
            if (time() - filemtime($css_file) < 2592000) {
                return $this->path_to_url($css_file);
            }
        }
        
        // Download the CSS from Google Fonts
        $response = wp_remote_get($font_url, array(
            'timeout' => 30,
            'user-agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36'
        ));
        
        if (is_wp_error($response)) {
            return false;
        }
        
        $css_content = wp_remote_retrieve_body($response);
        
        if (empty($css_content)) {
            return false;
        }
        
        // Download font files referenced in CSS
        $css_content = $this->download_font_files($css_content, $fonts_dir);
        
        // Save the modified CSS
        file_put_contents($css_file, $css_content);
        
        return $this->path_to_url($css_file);
    }
    
    /**
     * Download font files referenced in CSS
     */
    private function download_font_files($css_content, $fonts_dir) {
        // Find all font URLs in the CSS
        preg_match_all('/url\(([^)]+)\)/', $css_content, $matches);
        
        if (empty($matches[1])) {
            return $css_content;
        }
        
        foreach ($matches[1] as $font_url) {
            // Clean up the URL
            $font_url = trim($font_url, '\'" ');
            
            // Skip if already local
            if (strpos($font_url, 'http') !== 0) {
                continue;
            }
            
            // Generate filename from URL
            $font_filename = basename(parse_url($font_url, PHP_URL_PATH));
            $font_file = $fonts_dir . $font_filename;
            
            // Download if not exists
            if (!file_exists($font_file)) {
                $font_response = wp_remote_get($font_url, array(
                    'timeout' => 30
                ));
                
                if (!is_wp_error($font_response)) {
                    $font_data = wp_remote_retrieve_body($font_response);
                    file_put_contents($font_file, $font_data);
                }
            }
            
            // Replace URL in CSS with local path
            if (file_exists($font_file)) {
                $local_font_url = $this->path_to_url($font_file);
                $css_content = str_replace($font_url, $local_font_url, $css_content);
            }
        }
        
        return $css_content;
    }
    
    /**
     * Check if URL is external
     */
    private function is_external_url($url) {
        $site_url = site_url();
        return strpos($url, 'http') === 0 && strpos($url, $site_url) === false;
    }
    
    /**
     * Update .htaccess with caching rules
     */
    private function update_htaccess() {
        $htaccess_file = ABSPATH . '.htaccess';
        
        if (!is_writable($htaccess_file)) {
            return false;
        }
        
        $rules = $this->get_htaccess_rules();
        
        $htaccess_content = file_get_contents($htaccess_file);
        
        // Remove old rules if exist
        $htaccess_content = preg_replace('/# BEGIN WePH Quick Cache.*?# END WePH Quick Cache\n*/s', '', $htaccess_content);
        
        // Add new rules
        $htaccess_content = $rules . "\n" . $htaccess_content;
        
        file_put_contents($htaccess_file, $htaccess_content);
        
        return true;
    }
    
    /**
     * Get .htaccess rules
     */
    private function get_htaccess_rules() {
        return <<<HTACCESS
# BEGIN WePH Quick Cache

# Gzip Compression
<IfModule mod_deflate.c>
    AddOutputFilterByType DEFLATE text/html text/plain text/xml text/css text/javascript application/javascript application/x-javascript application/json application/xml application/rss+xml application/atom+xml image/svg+xml
</IfModule>

# Browser Caching
<IfModule mod_expires.c>
    ExpiresActive On
    ExpiresByType image/jpg "access plus 1 year"
    ExpiresByType image/jpeg "access plus 1 year"
    ExpiresByType image/gif "access plus 1 year"
    ExpiresByType image/png "access plus 1 year"
    ExpiresByType image/webp "access plus 1 year"
    ExpiresByType image/svg+xml "access plus 1 year"
    ExpiresByType image/x-icon "access plus 1 year"
    ExpiresByType text/css "access plus 1 month"
    ExpiresByType application/javascript "access plus 1 month"
    ExpiresByType text/javascript "access plus 1 month"
    ExpiresByType application/pdf "access plus 1 month"
    ExpiresByType application/x-shockwave-flash "access plus 1 month"
    ExpiresByType font/woff "access plus 1 year"
    ExpiresByType font/woff2 "access plus 1 year"
    ExpiresByType application/x-font-woff "access plus 1 year"
    ExpiresByType application/vnd.ms-fontobject "access plus 1 year"
    ExpiresByType application/x-font-ttf "access plus 1 year"
</IfModule>

# Leverage Browser Caching
<IfModule mod_headers.c>
    <FilesMatch "\.(ico|jpg|jpeg|png|gif|svg|webp|css|js|woff|woff2|ttf|eot)$">
        Header set Cache-Control "max-age=31536000, public"
    </FilesMatch>
</IfModule>

# END WePH Quick Cache

HTACCESS;
    }
    
    /**
     * Remove .htaccess rules
     */
    private function remove_htaccess_rules() {
        $htaccess_file = ABSPATH . '.htaccess';
        
        if (!is_writable($htaccess_file)) {
            return false;
        }
        
        $htaccess_content = file_get_contents($htaccess_file);
        $htaccess_content = preg_replace('/# BEGIN WePH Quick Cache.*?# END WePH Quick Cache\n*/s', '', $htaccess_content);
        
        file_put_contents($htaccess_file, $htaccess_content);
        
        return true;
    }
    
    /**
     * Clear cache
     */
    public function clear_cache() {
        // Clear plugin cache
        $cache_manager = new WePH_QC_Cache_Manager();
        $result = $cache_manager->clear_all();
        
        // Clear WordPress object cache
        wp_cache_flush();
        
        // Clear all transients
        global $wpdb;
        $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '%_transient_%'");
        
        // Clear output buffer if exists
        if (ob_get_level() > 0) {
            ob_end_clean();
        }
        
        // Flush rewrite rules
        flush_rewrite_rules();
        
        // Clear opcache if available
        if (function_exists('opcache_reset')) {
            @opcache_reset();
        }
        
        return $result;
    }
    
    /**
     * AJAX: Clear cache
     */
    public function ajax_clear_cache() {
        check_ajax_referer('weph_qc_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized');
        }
        
        $result = $this->clear_cache();
        
        // Try to clear other common caching plugins
        $cleared_plugins = array();
        
        // WP Super Cache
        if (function_exists('wp_cache_clear_cache')) {
            wp_cache_clear_cache();
            $cleared_plugins[] = 'WP Super Cache';
        }
        
        // W3 Total Cache
        if (function_exists('w3tc_flush_all')) {
            w3tc_flush_all();
            $cleared_plugins[] = 'W3 Total Cache';
        }
        
        // WP Rocket
        if (function_exists('rocket_clean_domain')) {
            rocket_clean_domain();
            $cleared_plugins[] = 'WP Rocket';
        }
        
        // LiteSpeed Cache
        if (class_exists('LiteSpeed_Cache_API') && method_exists('LiteSpeed_Cache_API', 'purge_all')) {
            LiteSpeed_Cache_API::purge_all();
            $cleared_plugins[] = 'LiteSpeed Cache';
        }
        
        // WP Fastest Cache
        if (class_exists('WpFastestCache')) {
            global $wp_fastest_cache;
            if (method_exists($wp_fastest_cache, 'deleteCache')) {
                $wp_fastest_cache->deleteCache(true);
                $cleared_plugins[] = 'WP Fastest Cache';
            }
        }
        
        // Autoptimize
        if (class_exists('autoptimizeCache')) {
            autoptimizeCache::clearall();
            $cleared_plugins[] = 'Autoptimize';
        }
        
        $message = 'Cache cleared successfully!';
        if (!empty($cleared_plugins)) {
            $message .= ' Also cleared: ' . implode(', ', $cleared_plugins);
        }
        
        if ($result) {
            wp_send_json_success($message);
        } else {
            wp_send_json_error('Failed to clear cache');
        }
    }
    
    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        add_menu_page(
            'WePH Quick Cache',
            'WePh Cache',
            'manage_options',
            'weph-quick-cache',
            array($this, 'admin_page'),
            'dashicons-performance',
            100
        );
    }
    
    /**
     * Register settings
     */
    public function register_settings() {
        register_setting('weph_qc_settings', 'weph_qc_enable_gzip');
        register_setting('weph_qc_settings', 'weph_qc_enable_minify');
        register_setting('weph_qc_settings', 'weph_qc_enable_browser_cache');
        register_setting('weph_qc_settings', 'weph_qc_enable_lazy_load');
        register_setting('weph_qc_settings', 'weph_qc_enable_webp');
        register_setting('weph_qc_settings', 'weph_qc_enable_local_fonts');
        register_setting('weph_qc_settings', 'weph_qc_minify_html');
        register_setting('weph_qc_settings', 'weph_qc_minify_css');
        register_setting('weph_qc_settings', 'weph_qc_minify_js');
        register_setting('weph_qc_settings', 'weph_qc_combine_css');
        register_setting('weph_qc_settings', 'weph_qc_combine_js');
        register_setting('weph_qc_settings', 'weph_qc_defer_js');
        register_setting('weph_qc_settings', 'weph_qc_remove_query_strings');
        register_setting('weph_qc_settings', 'weph_qc_disable_emojis');
        register_setting('weph_qc_settings', 'weph_qc_dns_prefetch');
        register_setting('weph_qc_settings', 'weph_qc_enable_image_metadata');
        register_setting('weph_qc_settings', 'weph_qc_image_alt_text');
        register_setting('weph_qc_settings', 'weph_qc_image_title_text');
        register_setting('weph_qc_settings', 'weph_qc_image_caption');
        register_setting('weph_qc_settings', 'weph_qc_image_description');
        register_setting('weph_qc_settings', 'weph_qc_bust_browser_cache');
        register_setting('weph_qc_settings', 'weph_qc_exclude_woocommerce');
        
        // New cache management settings
        register_setting('weph_qc_settings', 'weph_qc_auto_clear_enabled');
        register_setting('weph_qc_settings', 'weph_qc_auto_clear_days');
        register_setting('weph_qc_settings', 'weph_qc_clear_on_publish');
        register_setting('weph_qc_settings', 'weph_qc_cache_lifespan_days');
        
        // New exclusion settings
        register_setting('weph_qc_settings', 'weph_qc_excluded_pages');
        register_setting('weph_qc_settings', 'weph_qc_excluded_urls');
        register_setting('weph_qc_settings', 'weph_qc_excluded_scripts');
        register_setting('weph_qc_settings', 'weph_qc_excluded_css');
    }
    
    /**
     * Admin scripts
     */
    public function admin_scripts($hook) {
        if ($hook !== 'toplevel_page_weph-quick-cache') {
            return;
        }
        
        wp_enqueue_style('weph-qc-admin', WEPH_QC_PLUGIN_URL . 'assets/admin.css', array(), WEPH_QC_VERSION);
        wp_enqueue_script('weph-qc-admin', WEPH_QC_PLUGIN_URL . 'assets/admin.js', array('jquery'), WEPH_QC_VERSION, true);
        
        wp_localize_script('weph-qc-admin', 'wephQC', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('weph_qc_nonce')
        ));
    }
    
    /**
     * Auto-fill image metadata on upload
     */
    public function auto_fill_image_metadata($metadata, $attachment_id) {
        if (!get_option('weph_qc_enable_image_metadata', 0)) {
            return $metadata;
        }
        
        $this->update_attachment_metadata($attachment_id);
        
        return $metadata;
    }
    
    /**
     * Update image metadata on save/update
     */
    public function update_image_metadata_on_save($post, $attachment) {
        if (!get_option('weph_qc_enable_image_metadata', 0)) {
            return $post;
        }
        
        $this->update_attachment_metadata($post['ID']);
        
        return $post;
    }
    
    /**
     * Update attachment metadata with configured values
     */
    private function update_attachment_metadata($attachment_id) {
        // Get configured metadata
        $alt_text = get_option('weph_qc_image_alt_text', '');
        $title_text = get_option('weph_qc_image_title_text', '');
        $caption = get_option('weph_qc_image_caption', '');
        $description = get_option('weph_qc_image_description', '');
        
        // Get attachment post
        $attachment = get_post($attachment_id);
        if (!$attachment || $attachment->post_type !== 'attachment') {
            return;
        }
        
        // Get filename without extension for replacements
        $filename = pathinfo($attachment->guid, PATHINFO_FILENAME);
        $filename_clean = str_replace(array('-', '_'), ' ', $filename);
        $filename_clean = ucwords($filename_clean);
        
        // Replacement variables
        $replacements = array(
            '{filename}' => $filename_clean,
            '{title}' => get_the_title($attachment_id),
            '{site_name}' => get_bloginfo('name'),
            '{site_description}' => get_bloginfo('description'),
        );
        
        // Update alt text
        if (!empty($alt_text)) {
            $alt_value = str_replace(array_keys($replacements), array_values($replacements), $alt_text);
            update_post_meta($attachment_id, '_wp_attachment_image_alt', $alt_value);
        }
        
        // Update post data (title, caption, description)
        $post_data = array('ID' => $attachment_id);
        
        if (!empty($title_text)) {
            $title_value = str_replace(array_keys($replacements), array_values($replacements), $title_text);
            $post_data['post_title'] = $title_value;
        }
        
        if (!empty($caption)) {
            $caption_value = str_replace(array_keys($replacements), array_values($replacements), $caption);
            $post_data['post_excerpt'] = $caption_value;
        }
        
        if (!empty($description)) {
            $description_value = str_replace(array_keys($replacements), array_values($replacements), $description);
            $post_data['post_content'] = $description_value;
        }
        
        // Update post if we have data to update
        if (count($post_data) > 1) {
            wp_update_post($post_data);
            
            // Touch the post to update modified time for cache busting
            wp_update_post(array(
                'ID' => $attachment_id,
                'post_modified' => current_time('mysql'),
                'post_modified_gmt' => current_time('mysql', 1)
            ));
        }
        
        // Clear WordPress object cache for this attachment
        clean_post_cache($attachment_id);
        wp_cache_delete($attachment_id, 'post_meta');
    }
    
    /**
     * Add cache busting parameter to image URLs
     */
    public function add_cache_busting_to_images($image, $attachment_id, $size, $icon) {
        if ($image && is_array($image) && isset($image[0])) {
            // Get the last modified time of the attachment
            $modified_time = get_post_modified_time('U', false, $attachment_id);
            
            // Add version parameter to bust browser cache
            $separator = (strpos($image[0], '?') !== false) ? '&' : '?';
            $image[0] = $image[0] . $separator . 'ver=' . $modified_time;
        }
        return $image;
    }
    
    /**
     * Add cache busting to srcset
     */
    public function add_cache_busting_to_srcset($sources, $size_array, $image_src, $image_meta, $attachment_id) {
        if (!empty($sources) && is_array($sources)) {
            $modified_time = get_post_modified_time('U', false, $attachment_id);
            
            foreach ($sources as $width => $source) {
                if (isset($source['url'])) {
                    $separator = (strpos($source['url'], '?') !== false) ? '&' : '?';
                    $sources[$width]['url'] = $source['url'] . $separator . 'ver=' . $modified_time;
                }
            }
        }
        return $sources;
    }
    
    /**
     * AJAX: Bulk update all existing images metadata
     */
    public function ajax_bulk_update_metadata() {
        check_ajax_referer('weph_qc_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized');
        }
        
        // Get all image attachments
        $args = array(
            'post_type' => 'attachment',
            'post_mime_type' => 'image',
            'post_status' => 'inherit',
            'posts_per_page' => -1,
        );
        
        $attachments = get_posts($args);
        $updated_count = 0;
        
        foreach ($attachments as $attachment) {
            $this->update_attachment_metadata($attachment->ID);
            $updated_count++;
        }
        
        // Clear all caches after bulk update
        wp_cache_flush();
        $this->clear_cache();
        flush_rewrite_rules();
        
        wp_send_json_success(sprintf('Successfully updated metadata for %d images! Cache cleared.', $updated_count));
    }
    
    /**
     * Admin page
     */
    public function admin_page() {
        include WEPH_QC_PLUGIN_DIR . 'includes/admin-page.php';
    }
    
    /**
     * Check if current page should be excluded from optimization
     */
    private function is_page_excluded() {
        // Check excluded URLs
        $excluded_urls = get_option('weph_qc_excluded_urls', '');
        if (!empty($excluded_urls)) {
            $current_url = $_SERVER['REQUEST_URI'];
            $url_patterns = explode("\n", $excluded_urls);
            
            foreach ($url_patterns as $pattern) {
                $pattern = trim($pattern);
                if (empty($pattern)) continue;
                
                // Support wildcards
                $pattern = str_replace('*', '.*', preg_quote($pattern, '/'));
                if (preg_match('/^' . $pattern . '$/i', $current_url)) {
                    return true;
                }
            }
        }
        
        // Check excluded page IDs
        $excluded_pages = get_option('weph_qc_excluded_pages', '');
        if (!empty($excluded_pages) && is_singular()) {
            $page_ids = array_map('trim', explode(',', $excluded_pages));
            if (in_array(get_the_ID(), $page_ids)) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Clear cache on post publish
     */
    public function clear_cache_on_publish($post_id) {
        if (wp_is_post_revision($post_id)) {
            return;
        }
        $this->clear_cache();
    }
    
    /**
     * Scheduled cache clear (runs daily if enabled)
     */
    public function scheduled_cache_clear() {
        $days = intval(get_option('weph_qc_auto_clear_days', 7));
        if ($days > 0) {
            $cache_manager = new WePH_QC_Cache_Manager();
            $cache_manager->clear_old_cache($days);
        }
    }
    
    /**
     * AJAX: Clear old cache files
     */
    public function ajax_clear_old_cache() {
        check_ajax_referer('weph_qc_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized');
        }
        
        $days = intval(get_option('weph_qc_cache_lifespan_days', 30));
        $cache_manager = new WePH_QC_Cache_Manager();
        $deleted = $cache_manager->clear_old_cache($days);
        
        wp_send_json_success(sprintf('Cleared %d old cache files (older than %d days).', $deleted, $days));
    }
    
    /**
     * Get server type for configuration display
     */
    public function get_server_type() {
        $server = strtolower($_SERVER['SERVER_SOFTWARE'] ?? '');
        
        if (strpos($server, 'nginx') !== false) {
            return 'nginx';
        } elseif (strpos($server, 'apache') !== false) {
            return 'apache';
        } elseif (strpos($server, 'litespeed') !== false) {
            return 'litespeed';
        }
        
        return 'unknown';
    }
    
    /**
     * Generate Nginx configuration
     */
    public function get_nginx_config() {
        $config = "# WePH Quick Cache - Nginx Configuration\n";
        $config .= "# Add this to your nginx server block\n\n";
        
        // Gzip
        if (get_option('weph_qc_enable_gzip', 1)) {
            $config .= "# Gzip Compression\n";
            $config .= "gzip on;\n";
            $config .= "gzip_vary on;\n";
            $config .= "gzip_proxied any;\n";
            $config .= "gzip_comp_level 6;\n";
            $config .= "gzip_types text/plain text/css text/xml text/javascript application/x-javascript application/javascript application/xml+rss application/json;\n\n";
        }
        
        // Browser Cache
        if (get_option('weph_qc_enable_browser_cache', 1)) {
            $config .= "# Browser Caching\n";
            $config .= "location ~* \\.(jpg|jpeg|png|gif|ico|css|js|svg|woff|woff2|ttf|otf|eot)$ {\n";
            $config .= "    expires 1y;\n";
            $config .= "    add_header Cache-Control \"public, immutable\";\n";
            $config .= "}\n\n";
        }
        
        // WebP Support
        if (get_option('weph_qc_enable_webp', 1)) {
            $config .= "# WebP Support with fallback\n";
            $config .= "location ~* \\.(png|jpg|jpeg)$ {\n";
            $config .= "    add_header Vary Accept;\n";
            $config .= "    set \$webp_suffix \"\";\n";
            $config .= "    if (\$http_accept ~* \"webp\") {\n";
            $config .= "        set \$webp_suffix \".webp\";\n";
            $config .= "    }\n";
            $config .= "    # Try WebP version first, then original\n";
            $config .= "    try_files \$uri\$webp_suffix \$uri =404;\n";
            $config .= "}\n\n";
        }
        
        $config .= "# After adding this config, run: nginx -t && systemctl reload nginx\n";
        
        return $config;
    }
}

// Initialize plugin
WePH_Quick_Cache::get_instance();