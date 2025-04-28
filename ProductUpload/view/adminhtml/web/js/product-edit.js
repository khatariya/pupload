define([
    'jquery',
    'mage/url',
    'domReady!'
], function ($, urlBuilder) {
    'use strict';

    var setCookieUrl = window.location.origin + '/admin/productupload/product/setcookie';
    var formKey = $('meta[name="form_key"]').attr('content');

    console.log('Product Edit Cookie Script Initialized');
    console.log('Cookie URL:', setCookieUrl);
    console.log('Form Key:', formKey);

    $.ajax({
        url: setCookieUrl,
        type: 'POST',
        data: {form_key: formKey},
        dataType: 'json',
        showLoader: true,
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        },
        success: function(response) {
            console.log('Cookie Response:', response);
            if (response.success) {
                console.log('Cloudflare cookie set successfully');
            } else {
                console.error('Failed to set Cloudflare cookie:', response.message);
            }
        },
        error: function(xhr, status, error) {
            console.error('Error setting Cloudflare cookie:', error);
            console.error('XHR Status:', status);
            console.error('XHR Response:', xhr.responseText);
        }
    });
}); 