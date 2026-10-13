define([
    'jquery', 'mage/utils/wrapper', 'Magento_Checkout/js/model/quote',
    'Perspective_NovaposhtaShipping/js/model/checkout-address-state'
], function ($, wrapper, quote, addressState) {
    'use strict';
    return function (extendPayload) {
        return wrapper.wrap(extendPayload, function (original, payload) {
            var result = original(payload), method = quote.shippingMethod();
            if (!method || method.carrier_code !== 'novaposhtashipping') {
                var address = result.addressInformation.shipping_address,
                    attributes = address.extension_attributes || {},
                    clean = $.extend({}, attributes), changed = false;
                Object.keys(clean).forEach(function (key) {
                    if (key.indexOf('perspective_novaposhta_shipping_') === 0) {
                        delete clean[key];
                        changed = true;
                    }
                });
                if (changed) result.addressInformation.shipping_address = $.extend({}, address, {extension_attributes: clean});
                return result;
            }
            if (!addressState.ready()) return result;
            var data = addressState.fromForm(), street,
                destination = addressState.quoteAddress(),
                information = result.addressInformation;
            if (method.method_code === 'c2w' || method.method_code === 'w2w') {
                if (data.novaposhta_warehouse_label) street = [data.novaposhta_warehouse_label];
            } else if (data.novaposhta_street_label) {
                street = [data.novaposhta_street_label, data.novaposhta_building,
                    data.novaposhta_is_private_house ? '' : data.novaposhta_flat];
            }
            information.shipping_address = $.extend({}, information.shipping_address, {
                city: data.city || information.shipping_address.city,
                regionId: destination.regionId,
                regionCode: destination.regionCode,
                region: destination.region,
                street: street || information.shipping_address.street
            });
            return result;
        });
    };
});
