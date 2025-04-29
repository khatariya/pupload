var config = {
    map: {
        '*': {
            'Brainvire_QtySelector/js/qty-selector': 'Brainvire_QtySelector/js/qty-selector'
        }
    },
    shim: {
        'Brainvire_QtySelector/js/qty-selector': {
            deps: ['jquery', 'Magento_Customer/js/customer-data']
        }
    }
};