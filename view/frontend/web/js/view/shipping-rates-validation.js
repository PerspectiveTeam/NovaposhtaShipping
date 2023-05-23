define(
    [
        'uiComponent',
        'Magento_Checkout/js/model/shipping-rates-validator',
        'Magento_Checkout/js/model/shipping-rates-validation-rules',
        'Perspective_NovaposhtaShipping/js/model/shipping-rates-validator',
        'Perspective_NovaposhtaShipping/js/model/shipping-rates-validation-rules'
    ],
    function (
        Component,
        defaultShippingRatesValidator,
        defaultShippingRatesValidationRules,
        shippingRatesValidator,
        shippingRatesValidationRules
    ) {
        'use strict';
        defaultShippingRatesValidator.registerValidator('novaposhtashipping', shippingRatesValidator);
        defaultShippingRatesValidationRules.registerRules('novaposhtashipping', shippingRatesValidationRules);
        return Component;
    }
);
