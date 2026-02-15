# WePH Quick Cache v1.4.0 - FIXED VERSION

## 🎯 What's Fixed in Version 1.4.0

### Critical Fixes
✅ **HTML Minification** - Completely rewritten, no longer breaks sites  
✅ **Auto Clear Cache Options** - Now available in UI (Cache Management tab)  
✅ **Cache Lifespan** - Now configurable with automatic old file cleanup  
✅ **WebP on Nginx** - Full Nginx configuration support added  
✅ **Page Exclusions** - Complete exclusion system for pages, URLs, scripts, CSS  

## 🚀 Installation Instructions

### New Installation
1. Delete the old plugin (if installed): WP Admin → Plugins → Deactivate & Delete
2. Upload `weph-quick-cache-v1_4_0-FIXED.zip` via WordPress admin
3. Activate the plugin
4. Go to Settings → WePH Quick Cache
5. Configure your settings (see recommended settings below)

### Upgrade from 1.3.8
1. **IMPORTANT: Backup your site first!**
2. Deactivate the old plugin (don't delete yet)
3. Upload and activate the new version
4. Visit Settings → WePH Quick Cache
5. Click "Save Settings" to ensure all new options are initialized
6. Clear all caches (button at top of settings page)
7. Test your site thoroughly
8. Delete the old plugin files if everything works

## ⚙️ Recommended Settings

### For Most Sites
**Cache Management Tab:**
- ✅ Enable Auto Clear Cache (every 7 days)
- Cache Lifespan: 30 days
- ✅ Clear Cache on Post Publish

**Minification Tab:**
- ✅ Minify HTML (now safe!)
- ✅ Minify CSS
- ✅ Minify JS
- ✅ Defer JavaScript

**Exclusions Tab:**
- Leave empty unless you have issues
- If problems occur, add the problematic script/page

### For WooCommerce Sites
**Advanced Tab:**
- ✅ Exclude WooCommerce Pages (keep enabled!)

**Exclusions Tab:**
- Excluded URLs: Add these lines:
  ```
  /checkout/*
  /cart/*
  /my-account/*
  ```
- Excluded Scripts: Add these lines:
  ```
  stripe
  paypal
  woocommerce
  ```

### For Nginx Servers
1. Go to **Server Config** tab
2. Copy the Nginx configuration
3. Add to your Nginx config file
4. Reload Nginx: `sudo systemctl reload nginx`
5. Test WebP images are working

## 📋 New Features Explained

### 1. Cache Management (NEW TAB)
Automatically manage your cache lifecycle:
- **Auto Clear**: Clears entire cache on schedule (daily check)
- **Cache Lifespan**: Deletes files older than X days
- **Clear on Publish**: Auto-clear when you publish/update content
- **Manual Clear Old**: Button to immediately clear old files

### 2. Exclusions (NEW TAB)
Granular control over what gets optimized:
- **Page IDs**: Exclude specific pages (e.g., `12, 45, 89`)
- **URL Patterns**: Use wildcards (e.g., `/checkout/*`, `/api/*`)
- **Script Exclusions**: Prevent breaking payment gateways, analytics
- **CSS Exclusions**: Don't minify files that break when minified

### 3. Server Configuration (NEW TAB)
- Auto-detects your server type (Apache/Nginx/LiteSpeed)
- Provides server-specific configuration
- **Nginx users**: Get complete config with WebP support
- **Apache users**: Verify required modules are enabled

### 4. Improved HTML Minification
The minifier is now much smarter:
- Preserves SVG tags, code blocks, textareas
- Protects JSON-LD and data scripts
- Only removes excessive whitespace
- Won't break modern frameworks (React, Vue, Gutenberg)
- Safe for Elementor and page builders

## 🔧 Troubleshooting

### If your site breaks after activation:
1. Log into WordPress admin
2. Go to Settings → WePH Quick Cache
3. Disable "Minify HTML" temporarily
4. Save settings
5. Test each optimization one by one
6. Use Exclusions tab to exclude problematic scripts/pages

### If checkout/payment doesn't work:
1. Go to Exclusions tab
2. Add checkout URLs:
   ```
   /checkout/*
   /cart/*
   ```
3. Add payment scripts (Excluded Scripts):
   ```
   stripe
   paypal
   square
   ```
4. Save and test

### If images don't load or look wrong:
1. Disable "WebP Conversion" temporarily
2. Clear cache
3. Check if your server supports WebP
4. For Nginx: Apply the config from Server Config tab

### If analytics/tracking doesn't work:
1. Go to Exclusions tab
2. Add to Excluded Scripts:
   ```
   google-analytics
   gtag
   facebook
   pixel
   ```
3. Save settings

## 📊 Performance Tips

### Best Practices:
1. **Start Conservative**: Enable optimizations one by one
2. **Test Thoroughly**: Check all site functionality after each change
3. **Use Exclusions**: Better to exclude than break functionality
4. **Clear Regularly**: Enable auto-clear to prevent stale cache
5. **Monitor**: Check cache size periodically

### What to Optimize First:
1. ✅ Gzip Compression (safe)
2. ✅ Browser Caching (safe)
3. ✅ Lazy Loading (safe)
4. ✅ Remove Query Strings (safe)
5. ✅ Defer JavaScript (test carefully)
6. ✅ Minify CSS (usually safe)
7. ⚠️ Minify JS (test carefully)
8. ⚠️ Minify HTML (now safer, but still test)

### What to Test After Enabling:
- ✅ Forms submission
- ✅ Checkout process
- ✅ Login/registration
- ✅ Page builder functionality
- ✅ Third-party integrations
- ✅ Mobile display
- ✅ Different browsers

## 🆘 Common Issues & Solutions

### Issue: "Changes not showing on site"
**Solution**: Multiple cache layers exist!
1. Clear plugin cache (button in settings)
2. Clear hosting cache (cPanel/Plesk)
3. Clear CDN cache (Cloudflare, etc.)
4. Clear browser cache (Ctrl+Shift+R)
5. Test in incognito mode

### Issue: "JavaScript errors in console"
**Solution**: Some scripts are being deferred that shouldn't be
1. Go to Exclusions → Excluded Scripts
2. Add the script name causing errors
3. Or disable "Defer JavaScript" entirely

### Issue: "Page layout broken or CSS missing"
**Solution**: CSS minification breaking your styles
1. Disable "Minify CSS"
2. Or add problematic CSS to Exclusions → Excluded CSS
3. Save and clear cache

### Issue: "WooCommerce checkout not working"
**Solution**: Follow WooCommerce recommended settings above
1. Keep "Exclude WooCommerce Pages" enabled
2. Add exclusions for checkout, cart, my-account
3. Exclude payment gateway scripts

## 📝 Changelog

See CHANGELOG.md for complete version history and detailed changes.

## 🔐 Security

This plugin:
- ✅ Uses WordPress nonces for all AJAX requests
- ✅ Checks user capabilities (manage_options)
- ✅ Sanitizes all user inputs
- ✅ Escapes all outputs
- ✅ No external API calls
- ✅ No tracking or data collection

## 🤝 Support

For support:
1. Check this README first
2. Review the CHANGELOG
3. Check the WordPress admin notices
4. Contact your developer or hosting provider

## 📄 License

GPL v2 or later

## 👥 Credits

- Original Plugin: MrShafaQ, Patrick Hofman
- Version 1.4.0: Critical fixes and enhancements
- Testing: Various WordPress installations and themes

---

**Version**: 1.4.0  
**Last Updated**: February 15, 2026  
**Tested up to**: WordPress 6.4  
**Requires PHP**: 7.4+
