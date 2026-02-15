<?php
/**
 * Minifier Class
 * Handles minification of CSS, JS, and HTML
 */

if (!defined('ABSPATH')) {
    exit;
}

class WePH_QC_Minifier {
    
    /**
     * Minify CSS content
     */
    public function minify_css($css) {
        // Remove comments
        $css = preg_replace('!/\*[^*]*\*+([^/][^*]*\*+)*/!', '', $css);
        
        // Remove whitespace
        $css = str_replace(array("\r\n", "\r", "\n", "\t"), '', $css);
        $css = preg_replace('/\s+/', ' ', $css);
        
        // Remove spaces around colons, semicolons, braces
        $css = preg_replace('/\s*:\s*/', ':', $css);
        $css = preg_replace('/\s*;\s*/', ';', $css);
        $css = preg_replace('/\s*{\s*/', '{', $css);
        $css = preg_replace('/\s*}\s*/', '}', $css);
        $css = preg_replace('/;\s*}/', '}', $css);
        
        // Remove last semicolon before closing brace
        $css = str_replace(';}', '}', $css);
        
        return trim($css);
    }
    
    /**
     * Minify CSS file
     */
    public function minify_css_file($file_url) {
        // Convert URL to file path
        $file_path = $this->url_to_path($file_url);
        
        if (!$file_path || !file_exists($file_path)) {
            return false;
        }
        
        // Check if already minified
        if (strpos($file_path, '.min.css') !== false) {
            return $file_url;
        }
        
        // Generate cache file path
        $cache_file = $this->get_cache_file_path($file_path, 'css');
        
        // Check if cache exists and is fresh
        if (file_exists($cache_file) && filemtime($cache_file) >= filemtime($file_path)) {
            return $this->path_to_url($cache_file);
        }
        
        // Read and minify CSS
        $css = file_get_contents($file_path);
        $minified = $this->minify_css($css);
        
        // Save to cache
        file_put_contents($cache_file, $minified);
        
        return $this->path_to_url($cache_file);
    }
    
    /**
     * Minify JavaScript content
     */
    public function minify_js($js) {
        // Remove single-line comments (but preserve URLs)
        $js = preg_replace('#(?<!:)//[^\n]*#', '', $js);
        
        // Remove multi-line comments
        $js = preg_replace('#/\*.*?\*/#s', '', $js);
        
        // Remove whitespace
        $js = preg_replace('/\s+/', ' ', $js);
        
        // Remove spaces around operators
        $js = preg_replace('/\s*([=+\-*\/%<>!&|,;:{}()\[\]])\s*/', '$1', $js);
        
        return trim($js);
    }
    
    /**
     * Minify JavaScript file
     */
    public function minify_js_file($file_url) {
        // Convert URL to file path
        $file_path = $this->url_to_path($file_url);
        
        if (!$file_path || !file_exists($file_path)) {
            return false;
        }
        
        // Check if already minified
        if (strpos($file_path, '.min.js') !== false) {
            return $file_url;
        }
        
        // Generate cache file path
        $cache_file = $this->get_cache_file_path($file_path, 'js');
        
        // Check if cache exists and is fresh
        if (file_exists($cache_file) && filemtime($cache_file) >= filemtime($file_path)) {
            return $this->path_to_url($cache_file);
        }
        
        // Read and minify JS
        $js = file_get_contents($file_path);
        $minified = $this->minify_js($js);
        
        // Save to cache
        file_put_contents($cache_file, $minified);
        
        return $this->path_to_url($cache_file);
    }
    
    /**
     * Minify HTML content - SAFER VERSION
     */
    public function minify_html($html) {
        // Skip if HTML is too small
        if (strlen($html) < 100) {
            return $html;
        }
        
        // Preserve pre, code, textarea, script, style, and svg tags
        $preserve = array();
        
        // Preserve script tags (including JSON-LD and other data scripts)
        $html = preg_replace_callback('/<script\b[^>]*>(.*?)<\/script>/is', function($match) use (&$preserve) {
            $key = '<!--PRESERVE_SCRIPT_' . count($preserve) . '-->';
            $preserve[$key] = $match[0];
            return $key;
        }, $html);
        
        // Preserve style tags
        $html = preg_replace_callback('/<style\b[^>]*>(.*?)<\/style>/is', function($match) use (&$preserve) {
            $key = '<!--PRESERVE_STYLE_' . count($preserve) . '-->';
            $preserve[$key] = $match[0];
            return $key;
        }, $html);
        
        // Preserve pre tags
        $html = preg_replace_callback('/<pre\b[^>]*>(.*?)<\/pre>/is', function($match) use (&$preserve) {
            $key = '<!--PRESERVE_PRE_' . count($preserve) . '-->';
            $preserve[$key] = $match[0];
            return $key;
        }, $html);
        
        // Preserve code tags
        $html = preg_replace_callback('/<code\b[^>]*>(.*?)<\/code>/is', function($match) use (&$preserve) {
            $key = '<!--PRESERVE_CODE_' . count($preserve) . '-->';
            $preserve[$key] = $match[0];
            return $key;
        }, $html);
        
        // Preserve textarea tags
        $html = preg_replace_callback('/<textarea\b[^>]*>(.*?)<\/textarea>/is', function($match) use (&$preserve) {
            $key = '<!--PRESERVE_TEXTAREA_' . count($preserve) . '-->';
            $preserve[$key] = $match[0];
            return $key;
        }, $html);
        
        // Preserve svg tags
        $html = preg_replace_callback('/<svg\b[^>]*>(.*?)<\/svg>/is', function($match) use (&$preserve) {
            $key = '<!--PRESERVE_SVG_' . count($preserve) . '-->';
            $preserve[$key] = $match[0];
            return $key;
        }, $html);
        
        // Remove HTML comments (except IE conditional comments and noscript)
        $html = preg_replace('/<!--(?!\s*(?:\[if [^\]]+]|<!|>|<noscript))(?:(?!-->).)*-->/s', '', $html);
        
        // SAFER: Only remove excessive whitespace between tags (2+ spaces)
        $html = preg_replace('/>\s{2,}</', '><', $html);
        
        // SAFER: Only collapse multiple spaces into one, don't remove all spaces
        $html = preg_replace('/\s{2,}/', ' ', $html);
        
        // Remove whitespace around block-level elements only
        $html = preg_replace('/\s*(<\/?(div|section|article|header|footer|nav|main|aside|ul|ol|li|p|h1|h2|h3|h4|h5|h6|table|tr|td|th)[^>]*>)\s*/i', '$1', $html);
        
        // Restore preserved content
        foreach ($preserve as $key => $value) {
            $html = str_replace($key, $value, $html);
        }
        
        return trim($html);
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
        
        // Try with site URL
        $site_url = site_url();
        $abspath = ABSPATH;
        
        if (strpos($url, $site_url) === 0) {
            return str_replace($site_url, $abspath, $url);
        }
        
        // Handle relative URLs
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
     * Get cache file path
     */
    private function get_cache_file_path($original_file, $type) {
        $cache_dir = WEPH_QC_CACHE_DIR . $type . '/';
        
        if (!file_exists($cache_dir)) {
            wp_mkdir_p($cache_dir);
        }
        
        $filename = basename($original_file);
        $hash = md5($original_file);
        
        return $cache_dir . $hash . '-' . $filename;
    }
}
