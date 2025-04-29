define([
    'jquery',
    'Magento_Customer/js/customer-data'
], function ($, customerData) {
    'use strict';
    
    return function (config) {
        config = $.extend({
            minQty: 1,
            maxQty: 10000
        }, config);
        
        // Helper to get min/max for a given input
        function getQtyLimits($input) {
            var min = 1, max = 10000;
            var validate = $input.attr('data-validate');
            if (validate) {
                try {
                    var validateObj = JSON.parse(validate.replace(/'/g, '"'));
                    if (validateObj['validate-item-quantity']) {
                        if (typeof validateObj['validate-item-quantity'].minAllowed !== 'undefined') {
                            min = parseFloat(validateObj['validate-item-quantity'].minAllowed);
                        }
                        if (typeof validateObj['validate-item-quantity'].maxAllowed !== 'undefined') {
                            max = parseFloat(validateObj['validate-item-quantity'].maxAllowed);
                        }
                    }
                } catch (e) {}
            }
            // Fallback to min/max attributes if present
            if ($input.attr('min')) {
                min = parseFloat($input.attr('min'));
            }
            if ($input.attr('max')) {
                max = parseFloat($input.attr('max'));
            }
            return {min: min, max: max};
        }
        
        // Require Magento's update-shopping-cart widget
        require(['Magento_Checkout/js/action/update-shopping-cart'], function(updateShoppingCart) {
            // On +/- button click
            $(document).on('click', '.qty-btn', function () {
                var $wrapper = $(this).closest('.qty-selector-wrapper');
                var $input = $wrapper.find('[data-role="cart-item-qty"]');
                var limits = getQtyLimits($input);
                var currentQty = parseInt($input.val(), 10) || limits.min;
                var newQty = currentQty;
                
                if ($(this).hasClass('qty-increase')) {
                    newQty = currentQty + 1;
                } else if ($(this).hasClass('qty-decrease')) {
                    newQty = currentQty - 1;
                }
                
                // Validate quantity
                if (newQty > limits.max) {
                    newQty = limits.max;
                    showMaxMsg($wrapper, limits.max);
                } else if (newQty < limits.min) {
                    newQty = limits.min;
                } else {
                    hideMaxMsg($wrapper);
                }
                
                if (newQty !== currentQty) {
                    $input.val(newQty);
                    triggerCartUpdate();
                }
            });
            
            // On manual input (real-time)
            $(document).on('input', '[data-role="cart-item-qty"]', function () {
                var $input = $(this);
                var $wrapper = $input.closest('.qty-selector-wrapper');
                var limits = getQtyLimits($input);
                var qty = parseInt($input.val(), 10) || limits.min;
                
                // Validate quantity
                if (qty > limits.max) {
                    qty = limits.max;
                    $input.val(qty);
                    showMaxMsg($wrapper, limits.max);
                } else if (qty < limits.min) {
                    qty = limits.min;
                    $input.val(qty);
                } else {
                    hideMaxMsg($wrapper);
                }
                
                triggerCartUpdate();
            });
            
            // On change (for compatibility)
            $(document).on('change', '[data-role="cart-item-qty"]', function () {
                triggerCartUpdate();
            });
            
            function triggerCartUpdate() {
                // Submit the cart form via Magento's widget for AJAX update
                var $form = $('#form-validate');
                if ($form.length) {
                    $form.submit();
                }
            }
            
            function showMaxMsg($wrapper, maxQty) {
                $wrapper.find('.qty-max-message').text('Maximum allowed quantity is ' + maxQty).show();
            }
            
            function hideMaxMsg($wrapper) {
                $wrapper.find('.qty-max-message').hide();
            }
        });
    };
});