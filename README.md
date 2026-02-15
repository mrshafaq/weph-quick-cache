# WePH Quick Cache

A comprehensive WordPress performance optimization plugin designed to speed up WordPress and Elementor websites through Gzip compression, CSS/JS/HTML minification, browser caching, and more.

## Features

### Core Optimization Features

1. **Gzip Compression**
   - Automatically compresses HTML, CSS, and JavaScript files
   - Reduces bandwidth usage by up to 70%
   - Improves page load times significantly

2. **File Minification**
   - **HTML Minification**: Removes whitespace and comments from HTML output
   - **CSS Minification**: Compresses CSS files by removing unnecessary characters
   - **JavaScript Minification**: Reduces JS file sizes while preserving functionality
   - Smart caching system to avoid re-minifying unchanged files

3. **Browser Caching**
   - Sets optimal cache headers for static resources
   - Configures cache expiration for different file types
   - Leverages browser caching to reduce server requests

4. **Image Optimization**
   - Lazy loading for images
   - Loads images only when they enter the viewport
   - Improves initial page load time
   - **WebP Conversion**: Automatic JPG/PNG to WebP conversion (25-35% smaller!)
   - Browser detection for WebP support

5. **Font Optimization** ⭐ NEW
   - **Local Google Fonts**: Download and host Google Fonts locally
   - Eliminates external requests to Google servers
   - Improves GDPR compliance
   - Faster font loading (no DNS lookup, no external connection)
   - Automatic caching (30 days)
   - Works with any theme or page builder

6. **JavaScript Optimization**
   - Defers non-critical JavaScript
   - Excludes jQuery and critical scripts from deferring
   - Reduces render-blocking resources

6. **Advanced Optimizations**
   - Remove query strings from static resources
   - Disable WordPress emoji scripts
   - Clean up unnecessary HTTP requests
   - Optimized for Elementor websites

## Installation

### Method 1: Manual Installation

1. Download the plugin files
2. Upload the `weph-quick-cache` folder to `/wp-content/plugins/`
3. Activate the plugin through the 'Plugins' menu in WordPress
4. Go to 'Quick Cache' in the admin menu to configure settings

### Method 2: Upload via WordPress Admin

1. Go to WordPress Admin → Plugins → Add New
2. Click 'Upload Plugin'
3. Choose the `weph-quick-cache.zip` file
4. Click 'Install Now'
5. Activate the plugin

## Configuration

### Initial Setup

1. Navigate to **Quick Cache** in your WordPress admin menu
2. Review the default settings (all optimizations are enabled by default)
3. Adjust settings based on your needs
4. Click **Save Settings**
5. Click **Clear All Cache** to start fresh

### Recommended Settings

For most WordPress and Elementor sites, we recommend:

- ✅ Enable Gzip Compression: **ON**
- ✅ Browser Caching: **ON**
- ✅ Lazy Load Images: **ON**
- ✅ WebP Conversion: **ON** ⭐
- ✅ Local Google Fonts: **ON** ⭐
- ✅ Enable Minification: **ON**
- ✅ Minify HTML: **ON**
- ✅ Minify CSS: **ON**
- ✅ Minify JavaScript: **ON**
- ✅ Defer JavaScript Loading: **ON**
- ✅ Remove Query Strings: **ON**
- ✅ Disable WordPress Emojis: **ON**
- ✅ DNS Prefetch: **ON**

### For Elementor Users

If you're using Elementor, make sure to:

1. Go to **Elementor → Settings → Features**
2. Disable **"Improved Asset Loading"** (to prevent conflicts)
3. Clear Elementor cache after configuring WePH Quick Cache

## Usage

### Clearing Cache

After making changes to your site or updating content:

1. Go to **Quick Cache** in admin menu
2. Click the **Clear All Cache** button
3. This will delete all minified files and force regeneration

### Monitoring Performance

The admin dashboard shows:
- **Cached Files**: Total number of minified files
- **Cache Size**: Total disk space used by cache
- **CSS Files**: Number of minified CSS files
- **JS Files**: Number of minified JavaScript files

### Testing Your Site

After enabling optimizations:

1. Clear all caches (plugin + browser cache)
2. Test your website thoroughly
3. Check all interactive elements and forms
4. Verify Elementor widgets work correctly
5. Test on different browsers and devices

## File Structure

```
weph-quick-cache/
├── weph-quick-cache.php          # Main plugin file
├── includes/
│   ├── class-minifier.php        # Minification logic
│   ├── class-cache-manager.php   # Cache management
│   └── admin-page.php            # Admin interface
├── assets/
│   ├── admin.css                 # Admin styles
│   └── admin.js                  # Admin scripts
└── README.md                     # Documentation
```

## Cache Directory

Cached files are stored in:
```
/wp-content/cache/weph-quick-cache/
├── css/          # Minified CSS files
├── js/           # Minified JavaScript files
├── fonts/        # Local Google Fonts (CSS + font files)
└── html/         # Cached HTML pages
```

## .htaccess Rules

The plugin automatically adds optimization rules to your `.htaccess` file:

- Gzip compression for text-based files
- Browser caching headers for static resources
- Cache expiration times for different file types

These rules are automatically removed when the plugin is deactivated.

## Performance Tips

1. **Use a CDN**: Combine WePH Quick Cache with a CDN for maximum performance
2. **Optimize Images**: Use image optimization plugins alongside this plugin
3. **Database Optimization**: Regularly clean up your database
4. **Choose Good Hosting**: Fast hosting is essential for good performance
5. **Keep WordPress Updated**: Always use the latest WordPress version
6. **Limit Plugins**: Only use necessary plugins

## Troubleshooting

### JavaScript Not Working

If JavaScript breaks after enabling minification:

1. Disable **"Minify JavaScript"** temporarily
2. Identify the problematic script
3. Report the issue or exclude specific scripts

### CSS Styling Issues

If styles appear broken:

1. Clear all caches (plugin, browser, CDN)
2. Disable **"Minify CSS"** to test
3. Check for conflicts with other optimization plugins

### Elementor Editor Issues

If Elementor editor has problems:

1. Clear Elementor cache: **Elementor → Tools → Regenerate CSS & Data**
2. Ensure "Improved Asset Loading" is disabled in Elementor settings
3. Clear WePH Quick Cache

### Site Performance Not Improving

1. Test with a performance tool (GTmetrix, PageSpeed Insights)
2. Check if .htaccess rules were added correctly
3. Ensure Gzip is supported by your server
4. Verify browser caching is working

## Compatibility

- **WordPress**: 5.0 or higher
- **PHP**: 7.4 or higher
- **Servers**: Apache, Nginx (with proper configuration)
- **Page Builders**: Elementor, Gutenberg, and most popular builders
- **Themes**: Compatible with most WordPress themes

## Best Practices

1. **Backup First**: Always backup your site before installing
2. **Test Changes**: Test on staging before production
3. **Clear Cache**: Clear cache after every change
4. **Monitor**: Keep an eye on cache size
5. **Update Regularly**: Keep the plugin updated

## Support

For support, feature requests, or bug reports:

- Documentation: https://example.com/docs
- Support Forum: https://example.com/support
- Email: support@example.com

## Changelog

### Version 1.2.0
- Added Local Google Fonts hosting
- Improved performance for font loading
- GDPR compliance for Google Fonts
- Better caching strategy
- Enhanced WebP conversion

### Version 1.1.0
- Fixed performance degradation issue
- Added WebP automatic conversion
- Added smart caching system
- Added DNS prefetch
- Improved minification strategy
- Skip processing for logged-in users

### Version 1.0.0
- Initial release
- Gzip compression
- HTML/CSS/JS minification
- Browser caching
- Lazy loading
- JavaScript deferring
- Query string removal
- Emoji script removal

## License

This plugin is licensed under the GPL v2 or later.

## Credits

Developed by WePH Team

---

**Note**: Always test the plugin on a staging site before deploying to production. While we've tested extensively, every WordPress site is unique and may react differently to optimization.
