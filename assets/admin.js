/**
 * WePH Quick Cache Admin JavaScript
 */

jQuery(document).ready(function($) {
    
    // Tab switching
    $('.weph-qc-tab-button').on('click', function() {
        var tab = $(this).data('tab');
        
        // Update buttons
        $('.weph-qc-tab-button').removeClass('active');
        $(this).addClass('active');
        
        // Update content
        $('.weph-qc-tab-content').removeClass('active');
        $('#tab-' + tab).addClass('active');
    });
    
    // Clear cache button
    $('.weph-qc-clear-cache').on('click', function() {
        var $button = $(this);
        var $message = $('.weph-qc-cache-message');
        
        // Disable button
        $button.addClass('loading').prop('disabled', true);
        $message.removeClass('success error').hide();
        
        // Send AJAX request
        $.ajax({
            url: wephQC.ajax_url,
            type: 'POST',
            data: {
                action: 'weph_qc_clear_cache',
                nonce: wephQC.nonce
            },
            success: function(response) {
                $button.removeClass('loading').prop('disabled', false);
                
                if (response.success) {
                    $message.addClass('success').text(response.data).fadeIn();
                    
                    // Reload stats
                    location.reload();
                } else {
                    $message.addClass('error').text(response.data).fadeIn();
                }
            },
            error: function() {
                $button.removeClass('loading').prop('disabled', false);
                $message.addClass('error').text('An error occurred. Please try again.').fadeIn();
            }
        });
    });
    
    // Auto-hide messages after 5 seconds
    setTimeout(function() {
        $('.notice').fadeOut();
    }, 5000);
    
    // Confirm before leaving with unsaved changes - IMPROVED
    var initialFormData = '';
    var formChangeTimeout = null;
    
    // Capture initial form state after everything is loaded
    $(window).on('load', function() {
        setTimeout(function() {
            initialFormData = $('.weph-qc-settings-form').serialize();
            console.log('Initial form state captured');
        }, 1500); // Increased delay to ensure all fields are initialized
    });
    
    // Check if form actually changed by comparing serialized data
    $('.weph-qc-settings-form').on('change', 'input, select, textarea', function() {
        // Debounce the check
        clearTimeout(formChangeTimeout);
        formChangeTimeout = setTimeout(function() {
            // Only check if initial state has been captured
            if (initialFormData !== '') {
                var currentFormData = $('.weph-qc-settings-form').serialize();
                console.log('Form changed check:', currentFormData !== initialFormData);
            }
        }, 200);
    });
    
    // On form submit, disable warning
    $('.weph-qc-settings-form').on('submit', function() {
        console.log('Form submitted - removing beforeunload');
        $(window).off('beforeunload');
        return true;
    });
    
    // Beforeunload warning - only if form actually changed
    $(window).on('beforeunload', function(e) {
        // Only check if initial state was captured
        if (initialFormData === '') {
            return undefined; // No warning if not initialized
        }
        
        var currentFormData = $('.weph-qc-settings-form').serialize();
        if (currentFormData !== initialFormData) {
            var message = 'You have unsaved changes. Are you sure you want to leave?';
            e.returnValue = message;
            return message;
        }
        return undefined; // No warning
    });
    
    // Enable/disable dependent options
    $('#weph_qc_enable_minify').on('change', function() {
        var isChecked = $(this).is(':checked');
        
        $('#weph_qc_minify_html, #weph_qc_minify_css, #weph_qc_minify_js')
            .prop('disabled', !isChecked)
            .closest('tr')
            .css('opacity', isChecked ? 1 : 0.5);
    }).trigger('change');
    
    // Bulk update metadata button
    $('.weph-qc-bulk-update-metadata').on('click', function() {
        var $button = $(this);
        var $message = $('.weph-qc-metadata-message');
        
        if (!confirm('This will update metadata for all existing images in your media library. This may take a few moments. Continue?')) {
            return;
        }
        
        // Disable button
        $button.addClass('loading').prop('disabled', true);
        $message.removeClass('success error').hide();
        
        // Send AJAX request
        $.ajax({
            url: wephQC.ajax_url,
            type: 'POST',
            data: {
                action: 'weph_qc_bulk_update_metadata',
                nonce: wephQC.nonce
            },
            success: function(response) {
                $button.removeClass('loading').prop('disabled', false);
                
                if (response.success) {
                    $message.addClass('success').text(response.data).fadeIn();
                } else {
                    $message.addClass('error').text(response.data).fadeIn();
                }
            },
            error: function() {
                $button.removeClass('loading').prop('disabled', false);
                $message.addClass('error').text('An error occurred. Please try again.').fadeIn();
            }
        });
    });
    
    // Clear old cache button
    $('.weph-qc-clear-old-cache').on('click', function() {
        var $button = $(this);
        var $message = $('.weph-qc-old-cache-message');
        
        if (!confirm('This will delete cached files older than the specified lifespan. Continue?')) {
            return;
        }
        
        // Disable button
        $button.addClass('loading').prop('disabled', true);
        $message.removeClass('success error').hide();
        
        // Send AJAX request
        $.ajax({
            url: wephQC.ajax_url,
            type: 'POST',
            data: {
                action: 'weph_qc_clear_old_cache',
                nonce: wephQC.nonce
            },
            success: function(response) {
                $button.removeClass('loading').prop('disabled', false);
                
                if (response.success) {
                    $message.addClass('success').text(response.data).fadeIn();
                    
                    // Optionally reload stats
                    setTimeout(function() {
                        location.reload();
                    }, 1500);
                } else {
                    $message.addClass('error').text(response.data).fadeIn();
                }
            },
            error: function() {
                $button.removeClass('loading').prop('disabled', false);
                $message.addClass('error').text('An error occurred. Please try again.').fadeIn();
            }
        });
    });
    
    // Tooltips
    $('[data-tooltip]').on('mouseenter', function() {
        var tooltip = $(this).data('tooltip');
        var $tooltip = $('<div class="weph-qc-tooltip">' + tooltip + '</div>');
        
        $('body').append($tooltip);
        
        var offset = $(this).offset();
        $tooltip.css({
            top: offset.top - $tooltip.outerHeight() - 10,
            left: offset.left + ($(this).outerWidth() / 2) - ($tooltip.outerWidth() / 2)
        });
    }).on('mouseleave', function() {
        $('.weph-qc-tooltip').remove();
    });
});
