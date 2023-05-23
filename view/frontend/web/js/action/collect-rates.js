define(
    [
        'jquery',
        'Magento_Checkout/js/model/quote',
        'Magento_Checkout/js/model/shipping-rate-processor/new-address',
        'Magento_Checkout/js/model/shipping-rate-processor/customer-address',
        'Magento_Checkout/js/model/shipping-rate-registry'
    ],
    function ($, quote, defaultProcessor, customerAddressProcessor, rateRegistry) {

        return function () {
            var processors = [];
            processors.default = defaultProcessor;
            processors['customer-address'] = customerAddressProcessor;
            var type = quote.shippingAddress().getType();
            rateRegistry.set(quote.shippingAddress().getCacheKey(), null)

            if (processors[type]) {
                processors[type].getRates(quote.shippingAddress());
            } else {
                processors.default.getRates(quote.shippingAddress());
            }
        }
    }
);
