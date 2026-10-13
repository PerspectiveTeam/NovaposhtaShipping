/*jshint browser:true jquery:true*/
/*global alert*/
define([
    'jquery',
    'mage/utils/wrapper',
    'Magento_Checkout/js/model/quote',
    'uiRegistry',
    'postbox',
    'Magento_Checkout/js/model/shipping-address/form-popup-state'
], function ($, wrapper, quote, registry, postbox, popup) {
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

    function currentValue(component, fallback) {
        if (!component || typeof component.value !== 'function') {
            return fallback;
        }
        var value = component.value();
        return value && value !== '0' && value !== 'none' ? value : '';
    }

    return function (setShippingInformationAction) {
        return wrapper.wrap(setShippingInformationAction, function (originalAction) {
            var method = quote.shippingMethod();

            if (!method || method.carrier_code !== 'novaposhtashipping') {
                return originalAction();
            }
            if (popup && popup.isVisible()) {
                return $.Deferred().resolve().promise();
            }

            var shippingAddress = quote.shippingAddress();
            if (!shippingAddress) {
                return originalAction();
            }
            var fieldset = 'checkout.steps.shipping-step.shippingAddress.shipping-address-fieldset.',
                additional = 'checkout.steps.shipping-step.shippingAddress.shippingAdditional.',
                area = registry.get(fieldset + 'area_novaposhta_field'),
                city = registry.get(fieldset + 'city_novaposhta_field'),
                warehouse = registry.get(additional + 'perspective_novaposhtashipping_warehouse'),
                street = registry.get(additional + 'perspective_novaposhtashipping_warehouse_house_street'),
                building = registry.get(additional + 'perspective_novaposhtashipping_warehouse_house_build_num'),
                flat = registry.get(additional + 'perspective_novaposhtashipping_house_door_num'),
                privateHouse = registry.get(additional + 'perspective_novaposhtashipping_private_house'),
                isAddressDelivery = ['c2c', 'w2c'].indexOf(method.method_code) !== -1;

            if ((!shippingAddress.getType || shippingAddress.getType() !== 'customer-address') &&
                city && city.hasValue() && city.getPreview()) {
                shippingAddress.city = city.getPreview();
            }
            shippingAddress.extension_attributes = shippingAddress.extension_attributes || {};
            shippingAddress['extension_attributes']['perspective_novaposhta_shipping_area'] =
                area ? area.areaRef() : perspective_novaposhta_shipping_area_val;
            shippingAddress['extension_attributes']['perspective_novaposhta_shipping_city'] =
                city ? (city.hasValue() ? city.value() : '') : perspective_novaposhta_shipping_city_val;
            if (window.perspective_novaposhta.warehouse.react) {
                shippingAddress['extension_attributes']['perspective_novaposhta_shipping_warehouse'] =
                    isAddressDelivery ? '' : currentValue(warehouse, perspective_novaposhta_shipping_warehouse_val);
            }
            if (window.perspective_novaposhta.street.react) {
                shippingAddress['extension_attributes']['perspective_novaposhta_shipping_street'] =
                    isAddressDelivery ? currentValue(street, perspective_novaposhta_shipping_street_val) : '';
            }
            shippingAddress['extension_attributes']['perspective_novaposhta_shipping_building'] =
                isAddressDelivery ? currentValue(building, perspective_novaposhta_shipping_building_val) : '';
            shippingAddress['extension_attributes']['perspective_novaposhta_shipping_flat'] =
                isAddressDelivery ? currentValue(flat, perspective_novaposhta_shipping_flat_val) : '';
            shippingAddress['extension_attributes']['perspective_novaposhta_shipping_is_private_house'] =
                isAddressDelivery && (privateHouse && typeof privateHouse.isPrivateHouse === 'function'
                    ? privateHouse.isPrivateHouse() : perspective_novaposhta_shipping_is_private_house_val);

            return originalAction();
        });
    };
});
