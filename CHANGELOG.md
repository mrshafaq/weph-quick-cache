# WePH Quick Cache - Changelog

## Version 1.4.0 - February 15, 2026

### 🔴 CRITICAL FIXES
- **FIXED: HTML Minification Breaking Sites** - Completely rewrote HTML minification logic to be much safer
  - Now preserves SVG tags, code blocks, and proper whitespace
  - Only removes excessive whitespace (2+ spaces) instead of all whitespace
  - Protects JSON-LD scripts and other critical inline data
  - Prevents breaking modern JavaScript frameworks
  - Fixed issue where minification would break Gutenberg blocks and Elementor widgets

### ✨ NEW FEATURES

#### Cache Management Tab (NEW)
- **Auto Clear Cache** - Automatically clear cache on a schedule
  - Enable/disable automatic cache clearing
  - Set interval in days (1-90 days)
  - Runs daily via WordPress cron
- **Cache Lifespan Control** - Set how long cached files are kept
  - Configure cache file lifespan (1-365 days)
  - Automatically delete old cache files
  - "Clear Old Cache Files Now" button for manual cleanup
- **Clear on Publish** - Automatically clear cache when publishing/updating posts or pages
  - Ensures visitors see fresh content immediately
  - Works with posts, pages, and custom post types

#### Exclusions Tab (NEW)
- **Page Exclusions** - Exclude specific pages by ID
  - Enter comma-separated page/post IDs
  - Excluded pages skip ALL optimizations
- **URL Pattern Exclusions** - Exclude URLs by pattern
  - One pattern per line
  - Supports wildcards (*)
  - Examples: `/checkout/*`, `/my-account/*`
- **Script Exclusions** - Exclude JavaScript files from defer/minification
  - Add payment gateways (Stripe, PayPal)
  - Add analytics scripts
  - Add reCAPTCHA and other third-party scripts
  - One script identifier per line
- **CSS Exclusions** - Exclude CSS files from minification
  - Add page builder CSS (Elementor, Divi)
  - Add theme CSS that breaks when minified
  - One CSS identifier per line

#### Server Configuration Tab (NEW)
- **Auto-detect Server Type** - Detects if you're using Apache, Nginx, or LiteSpeed
- **Nginx Configuration Generator** - For Nginx users
  - Automatically generates proper Nginx configuration
  - Includes Gzip, browser caching, and WebP support
  - Copy-to-clipboard button
  - Step-by-step instructions for applying config
  - **FIXES WebP conversion on Nginx servers**
- **Apache Verification** - Shows required Apache modules
  - Lists necessary modules: mod_rewrite, mod_deflate, mod_expires, mod_headers
  - Confirms .htaccess rules are in place

### 🐛 BUG FIXES
- Fixed WebP conversion not working on Nginx servers
- Fixed script defer breaking payment gateways and analytics
- Fixed missing UI options for cache management features
- Fixed cache files accumulating indefinitely without cleanup
- Fixed inability to exclude specific pages or scripts
- Improved WooCommerce compatibility with better exclusions

### 🔧 IMPROVEMENTS
- Safer HTML minification that won't break sites
- Better error handling in cache operations
- Improved admin UI with clearer organization
- Added helpful tooltips and descriptions
- Better cron scheduling for auto-clear
- Enhanced page exclusion logic
- More granular control over optimizations

### 📚 DOCUMENTATION
- Added inline help for all new features
- Server-specific configuration instructions
- Clear examples for exclusion patterns
- Tips and best practices in each tab

### ⚡ PERFORMANCE
- Optimized cache file lookup
- Improved minification speed
- Better handling of large cache directories
- Reduced database queries

---

## Version 1.3.8 - Previous Release
- Original release with basic caching and minification
- Gzip compression
- CSS/JS/HTML minification
- WebP conversion
- Lazy loading
- Local Google Fonts
- Image metadata auto-fill
- WooCommerce exclusions

---

## Upgrade Notes

### From 1.3.8 to 1.4.0:
1. **Backup your site** before upgrading
2. After upgrade, **clear all caches**
3. Review new **Exclusions** tab and add any necessary exclusions
4. If on Nginx, check **Server Config** tab and apply the configuration
5. Configure **Cache Management** settings as needed
6. Test your site thoroughly, especially:
   - Checkout process (if using WooCommerce)
   - Contact forms
   - Payment gateways
   - Third-party integrations

### Recommended Settings for Most Sites:
- **Cache Management:**
  - Auto Clear: Enabled (every 7 days)
  - Cache Lifespan: 30 days
  - Clear on Publish: Enabled
  
- **Exclusions:**
  - Add checkout, cart, my-account URLs
  - Add payment gateway scripts (stripe, paypal)
  - Add analytics scripts (if they break when deferred)

### For WooCommerce Sites:
- Keep "Exclude WooCommerce Pages" enabled (in Advanced tab)
- Add to Excluded Scripts: `stripe`, `paypal`, `woocommerce`
- Test checkout process thoroughly

### For Nginx Servers:
- Go to **Server Config** tab
- Copy the Nginx configuration
- Apply to your server (or ask your host)
- Test WebP images are loading correctly

---

## Support
- Documentation: https://mrshafaq.com/weph-quick-cache
- Support: Contact your developer or hosting provider
- Issues: Check exclusions first, then clear all caches

---

## Credits
- Version 1.4.0 improvements and fixes
- Enhanced HTML minification algorithm
- Complete Nginx support
- Advanced exclusion system
- Automatic cache lifecycle management
