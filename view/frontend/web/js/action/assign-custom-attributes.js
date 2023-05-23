define(
    [
        'jquery',
        'ko',
        'Magento_Checkout/js/model/quote',
        'Magento_Checkout/js/model/shipping-rate-processor/new-address',
        'Magento_Checkout/js/model/shipping-rate-processor/customer-address',
        'Magento_Checkout/js/model/shipping-rate-registry',
        'uiRegistry',
        'underscore'
    ],
    function (
        $,
        ko,
        quote,
        defaultProcessor,
        customerAddressProcessor,
        rateRegistry,
        uiRegistry,
        _
    ) {
        'use strict';
        return function (name, value) {
            let address = quote.shippingAddress();
            rateRegistry.set(address.getKey(), null);
            rateRegistry.set(address.getCacheKey(), null);
            if (address.customAttributes == undefined) {
                address.customAttributes = {}
            }
            address.customAttributes = _.extend(address.customAttributes, [{
                "attribute_code": name,
                "value": value
            }]);
            uiRegistry.set('shippingAddress', address);
            quote.shippingAddress();
            //этот метод кладет страницу на лопатки
        }
    }
);
