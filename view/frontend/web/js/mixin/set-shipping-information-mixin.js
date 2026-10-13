/*jshint browser:true jquery:true*/
/*global alert*/
define([
    'mage/utils/wrapper',
    'Magento_Checkout/js/model/quote',
    'uiRegistry',
    'postbox'
], function (wrapper, quote, registry, postbox) {
    'use strict';
    var perspective_novaposhta_shipping_area_val = '';
    var perspective_novaposhta_shipping_city_val = '';
    var perspective_novaposhta_shipping_warehouse_val = '';
    var perspective_novaposhta_shipping_street_val = '';
    var perspective_novaposhta_shipping_building_val = '';
    var perspective_novaposhta_shipping_flat_val = '';
    var perspective_novaposhta_shipping_is_private_house_val = false;
    window.perspective_novaposhta = window.perspective_novaposhta || {
        warehouse: {
            react: true
        },
        street: {
            react: true
        }
    };
    postbox.subscribe("selectedAreaPost", function (value) {
        perspective_novaposhta_shipping_area_val = value;
    });
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
    postbox.subscribe("privateHousePost", function (value) {
        perspective_novaposhta_shipping_is_private_house_val = value;
    });
    return function (setShippingInformationAction) {
        return wrapper.wrap(setShippingInformationAction, function (originalAction) {
            var method = quote.shippingMethod();

            if (!method || method.carrier_code !== 'novaposhtashipping') {
                return originalAction();
            }

            var shippingAddress = quote.shippingAddress();
            if (!shippingAddress) {
                return originalAction();
            }
            var fieldset = 'checkout.steps.shipping-step.shippingAddress.shipping-address-fieldset.',
                area = registry.get(fieldset + 'area_novaposhta_field'),
                city = registry.get(fieldset + 'city_novaposhta_field');

            if (city && city.hasValue() && city.getPreview()) {
                shippingAddress.city = city.getPreview();
            }
            shippingAddress.extension_attributes = shippingAddress.extension_attributes || {};
            shippingAddress['extension_attributes']['perspective_novaposhta_shipping_area'] =
                area ? area.areaRef() : perspective_novaposhta_shipping_area_val;
            shippingAddress['extension_attributes']['perspective_novaposhta_shipping_city'] =
                city ? (city.hasValue() ? city.value() : '') : perspective_novaposhta_shipping_city_val;
            if (window.perspective_novaposhta.warehouse.react) {
                shippingAddress['extension_attributes']['perspective_novaposhta_shipping_warehouse'] =
                    perspective_novaposhta_shipping_warehouse_val;
            }
            if (window.perspective_novaposhta.street.react) {
                shippingAddress['extension_attributes']['perspective_novaposhta_shipping_street'] =
                    perspective_novaposhta_shipping_street_val;
            }
            shippingAddress['extension_attributes']['perspective_novaposhta_shipping_building'] =
                perspective_novaposhta_shipping_building_val;
            shippingAddress['extension_attributes']['perspective_novaposhta_shipping_flat'] =
                perspective_novaposhta_shipping_flat_val;
            shippingAddress['extension_attributes']['perspective_novaposhta_shipping_is_private_house'] =
                perspective_novaposhta_shipping_is_private_house_val;

            return originalAction();
        });
    };
});
