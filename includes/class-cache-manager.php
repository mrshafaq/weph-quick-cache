<?php
/**
 * Cache Manager Class
 * Handles cache operations
 */

if (!defined('ABSPATH')) {
    exit;
}

class WePH_QC_Cache_Manager {
    
    /**
     * Clear all cache
     */
    public function clear_all() {
        $this->clear_minified_cache();
        $this->clear_page_cache();
        $this->clear_object_cache();
        
        return true;
    }
    
    /**
     * Clear minified files cache
     */
    public function clear_minified_cache() {
        $cache_dir = WEPH_QC_CACHE_DIR;
        
        if (!file_exists($cache_dir)) {
            return false;
        }
        
        $this->delete_directory_contents($cache_dir);
        
        return true;
    }
    
    /**
     * Clear page cache
     */
    public function clear_page_cache() {
        // Clear WordPress object cache
        wp_cache_flush();
        
        // Clear transients
        global $wpdb;
        $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_%'");
        
        return true;
    }
    
    /**
     * Clear object cache
     */
    public function clear_object_cache() {
        if (function_exists('wp_cache_flush')) {
            wp_cache_flush();
        }
        
        return true;
    }
    
    /**
     * Delete directory contents recursively
     */
    private function delete_directory_contents($dir) {
        if (!is_dir($dir)) {
            return false;
        }
        
        $files = array_diff(scandir($dir), array('.', '..'));
        
        foreach ($files as $file) {
            $path = $dir . '/' . $file;
            
            if (is_dir($path)) {
                $this->delete_directory_contents($path);
                rmdir($path);
            } else {
                unlink($path);
            }
        }
        
        return true;
    }
    
    /**
     * Get cache size
     */
    public function get_cache_size() {
        $cache_dir = WEPH_QC_CACHE_DIR;
        
        if (!file_exists($cache_dir)) {
            return 0;
        }
        
        return $this->get_directory_size($cache_dir);
    }
    
    /**
     * Get directory size recursively
     */
    private function get_directory_size($dir) {
        $size = 0;
        
        if (!is_dir($dir)) {
            return 0;
        }
        
        $files = array_diff(scandir($dir), array('.', '..'));
        
        foreach ($files as $file) {
            $path = $dir . '/' . $file;
            
            if (is_dir($path)) {
                $size += $this->get_directory_size($path);
            } else {
                $size += filesize($path);
            }
        }
        
        return $size;
    }
    
    /**
     * Format bytes to human readable
     */
    public function format_bytes($bytes, $precision = 2) {
        $units = array('B', 'KB', 'MB', 'GB', 'TB');
        
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        
        $bytes /= pow(1024, $pow);
        
        return round($bytes, $precision) . ' ' . $units[$pow];
    }
    
    /**
     * Get cache statistics
     */
    public function get_cache_stats() {
        $cache_dir = WEPH_QC_CACHE_DIR;
        $stats = array(
            'total_files' => 0,
            'total_size' => 0,
            'css_files' => 0,
            'js_files' => 0,
            'html_files' => 0
        );
        
        if (!file_exists($cache_dir)) {
            return $stats;
        }
        
        $stats['total_files'] = $this->count_files($cache_dir);
        $stats['total_size'] = $this->get_directory_size($cache_dir);
        $stats['css_files'] = $this->count_files($cache_dir . 'css/');
        $stats['js_files'] = $this->count_files($cache_dir . 'js/');
        
        return $stats;
    }
    
    /**
     * Count files in directory
     */
    private function count_files($dir) {
        if (!is_dir($dir)) {
            return 0;
        }
        
        $count = 0;
        $files = array_diff(scandir($dir), array('.', '..'));
        
        foreach ($files as $file) {
            $path = $dir . '/' . $file;
            
            if (is_dir($path)) {
                $count += $this->count_files($path);
            } else {
                $count++;
            }
        }
        
        return $count;
    }
    
    /**
     * Clear cache files older than specified days
     */
    public function clear_old_cache($days = 7) {
        $cache_dir = WEPH_QC_CACHE_DIR;
        
        if (!file_exists($cache_dir)) {
            return 0;
        }
        
        $cutoff_time = time() - ($days * 24 * 60 * 60);
        $deleted_count = 0;
        
        $deleted_count = $this->delete_old_files($cache_dir, $cutoff_time);
        
        return $deleted_count;
    }
    
    /**
     * Delete files older than cutoff time recursively
     */
    private function delete_old_files($dir, $cutoff_time) {
        if (!is_dir($dir)) {
            return 0;
        }
        
        $deleted = 0;
        $files = array_diff(scandir($dir), array('.', '..'));
        
        foreach ($files as $file) {
            $path = $dir . '/' . $file;
            
            if (is_dir($path)) {
                $deleted += $this->delete_old_files($path, $cutoff_time);
                // Remove empty directories
                if (count(scandir($path)) == 2) {
                    rmdir($path);
                }
            } else {
                if (filemtime($path) < $cutoff_time) {
                    if (unlink($path)) {
                        $deleted++;
                    }
                }
            }
        }
        
        return $deleted;
    }
}
