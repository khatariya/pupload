define([
    'jquery',
    'Magento_Customer/js/customer-data'
], function ($, customerData) {
    'use strict';
    return function (config) {
        $(document).on('click', '.qty-btn', function () {
            var $wrapper = $(this).closest('.qty-selector-wrapper');
            var $input = $wrapper.find('.qty-input');
            var qty = parseInt($input.val(), 10) || config.minQty;
            var maxQty = config.maxQty;
            var minQty = config.minQty;
            if ($(this).hasClass('qty-increase')) {
                qty++;
            } else {
                qty--;
            }
            if (qty > maxQty) {
                qty = maxQty;
                showMaxMsg($wrapper, maxQty);
            } else if (qty < minQty) {
                qty = minQty;
            } else {
                hideMaxMsg($wrapper);
            }
            $input.val(qty).trigger('change');
        });

        $(document).on('input', '.qty-input', function () {
            var $input = $(this);
            var $wrapper = $input.closest('.qty-selector-wrapper');
            var qty = parseInt($input.val(), 10) || config.minQty;
            var maxQty = config.maxQty;
            var minQty = config.minQty;
            if (qty > maxQty) {
                qty = maxQty;
                showMaxMsg($wrapper, maxQty);
            } else if (qty < minQty) {
                qty = minQty;
            } else {
                hideMaxMsg($wrapper);
            }
            $input.val(qty);
            updateCartQty($wrapper, qty);
        });

        function showMaxMsg($wrapper, maxQty) {
            $wrapper.find('.qty-max-message').text('Maximum allowed quantity is ' + maxQty).show();
        }
        function hideMaxMsg($wrapper) {
            $wrapper.find('.qty-max-message').hide();
        }
        function updateCartQty($wrapper, qty) {
            var itemId = $wrapper.data('cart-item-id');
            var formKey = $wrapper.find('.qty-form-key').val();
            var data = {
                form_key: formKey || (window.FORM_KEY ? window.FORM_KEY : ''),
                cart: {}
            };
            data.cart[itemId] = {qty: qty};
            $.ajax({
                url: '/checkout/cart/updateItemQty/',
                type: 'POST',
                data: data,
                success: function (response) {
                    // Reload cart summary, coupon, shipping, etc.
                    customerData.reload(['cart', 'checkout-data'], true);
                },
                error: function (xhr) {
                    // Optionally show error message
                }
            });
        }
    };
}); 