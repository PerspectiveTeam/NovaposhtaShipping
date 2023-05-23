/*jshint browser:true jquery:true*/
/*global alert*/
define([
    'jquery',
    'mage/utils/wrapper',
    'Magento_Checkout/js/model/quote',
    'ko',
    'postbox'
], function ($, wrapper, quote, ko, postbox) {
    'use strict';
    var perspective_novaposhta_shipping_city_val = '';
    var perspective_novaposhta_shipping_warehouse_val = '';
    var perspective_novaposhta_shipping_street_val = '';
    var perspective_novaposhta_shipping_building_val = '';
    var perspective_novaposhta_shipping_flat_val = '';
    postbox.subscribe("selectedCityPost", function (value) {
        perspective_novaposhta_shipping_city_val = value;
    });
    postbox.subscribe("selectedWarehousePost", function (value) {
        perspective_novaposhta_shipping_warehouse_val = value;
    });
    postbox.subscribe("selectedStreetPost", function (value) {
        perspective_novaposhta_shipping_street_val = value;
    });
    postbox.subscribe("selectedStreetNumPost", function (value) {
        perspective_novaposhta_shipping_building_val = value;
    });
    postbox.subscribe("selectedApartNumPost", function (value) {
        perspective_novaposhta_shipping_flat_val = value;
    });
    return function (setShippingInformationAction) {
        return wrapper.wrap(setShippingInformationAction, function (originalAction) {
            var shippingAddress = quote.shippingAddress();
            if (!shippingAddress || shippingAddress['extension_attributes'] === undefined) {
                if (shippingAddress === null) {
                    shippingAddress = [];
                }
                shippingAddress['extension_attributes'] = {};
            }
            shippingAddress['extension_attributes']['perspective_novaposhta_shipping_city'] =
                perspective_novaposhta_shipping_city_val;
            shippingAddress['extension_attributes']['perspective_novaposhta_shipping_warehouse'] =
                perspective_novaposhta_shipping_warehouse_val;
            shippingAddress['extension_attributes']['perspective_novaposhta_shipping_street'] =
                perspective_novaposhta_shipping_street_val;
            shippingAddress['extension_attributes']['perspective_novaposhta_shipping_building'] =
                perspective_novaposhta_shipping_building_val;
            shippingAddress['extension_attributes']['perspective_novaposhta_shipping_flat'] =
                perspective_novaposhta_shipping_flat_val;

            var billingAddress = quote.billingAddress();
            if (!billingAddress || billingAddress['extension_attributes'] === undefined) {
                if (billingAddress === null) {
                    billingAddress = {};
                }
                billingAddress['extension_attributes'] = {};
                //fix for M2.4.4 bug - absent the street arr
                if (billingAddress.street === undefined || !Array.isArray(billingAddress.street)) {
                    billingAddress.street = [];
                    var getCacheKeyFunc = function () {
                        return quote.shippingAddress().getCacheKey();
                    }
                    billingAddress.getCacheKey = getCacheKeyFunc;
                    quote.billingAddress(billingAddress);
                }
            }
            billingAddress['extension_attributes']['perspective_novaposhta_shipping_city'] =
                perspective_novaposhta_shipping_city_val;
            billingAddress['extension_attributes']['perspective_novaposhta_shipping_warehouse'] =
                perspective_novaposhta_shipping_warehouse_val;
            billingAddress['extension_attributes']['perspective_novaposhta_shipping_street'] =
                perspective_novaposhta_shipping_street_val;
            billingAddress['extension_attributes']['perspective_novaposhta_shipping_building'] =
                perspective_novaposhta_shipping_building_val;
            billingAddress['extension_attributes']['perspective_novaposhta_shipping_flat'] =
                perspective_novaposhta_shipping_flat_val;
            return originalAction();
        });
    };
});
