define([
    'Magento_Checkout/js/model/quote',
    'uiRegistry',
    'mage/translate'
], function (quote, registry, $t) {
    'use strict';

    var NP_METHODS = ['c2w', 'w2w', 'w2c', 'c2c'];
    var NP_WAREHOUSE_METHODS = ['c2w', 'w2w'];
    var NP_ADDRESS_METHODS = ['c2c', 'w2c'];

    return function (Component) {
        return Component.extend({
            validateShippingInformation: function () {
                var method = quote.shippingMethod();
                var methodCode = method ? method.method_code : null;

                if (NP_METHODS.indexOf(methodCode) !== -1) {
                    var areaValidationResult = this.validateNpAreaField(),
                        cityValidationResult = this.validateNpCityField();

                    if (!areaValidationResult || !cityValidationResult) {
                        return false;
                    }

                    if (NP_WAREHOUSE_METHODS.indexOf(methodCode) !== -1) {
                        if (!this.validateNpWarehouseField()) {
                            return false;
                        }
                    }

                    if (NP_ADDRESS_METHODS.indexOf(methodCode) !== -1) {
                        var isPrivateHouse = this._isPrivateHouseChecked();
                        if (!this.validateNpStreetField()) {
                            return false;
                        }
                        if (!this.validateNpBuildNumField()) {
                            return false;
                        }
                        if (!isPrivateHouse && !this.validateNpHouseDoorNumField()) {
                            return false;
                        }
                    }
                }

                return this._super();
            },

            validateNpAreaField: function () {
                var npAreaComponent = registry.get(
                    'checkout.steps.shipping-step.shippingAddress.shipping-address-fieldset.area_novaposhta_field'
                );
                return this.validateField(npAreaComponent);
            },

            validateNpCityField: function () {
                var npCityComponent = registry.get(
                    'checkout.steps.shipping-step.shippingAddress.shipping-address-fieldset.city_novaposhta_field'
                );
                return this.validateField(npCityComponent);
            },

            validateNpWarehouseField: function () {
                var component = registry.get(
                    'checkout.steps.shipping-step.shippingAddress.shippingAdditional.perspective_novaposhtashipping_warehouse'
                );
                return this.validateField(component);
            },

            validateNpStreetField: function () {
                var component = registry.get(
                    'checkout.steps.shipping-step.shippingAddress.shippingAdditional.perspective_novaposhtashipping_warehouse_house_street'
                );
                return this.validateField(component, ['none']);
            },

            validateNpBuildNumField: function () {
                var component = registry.get(
                    'checkout.steps.shipping-step.shippingAddress.shippingAdditional.perspective_novaposhtashipping_warehouse_house_build_num'
                );
                return this.validateField(component);
            },

            validateNpHouseDoorNumField: function () {
                var component = registry.get(
                    'checkout.steps.shipping-step.shippingAddress.shippingAdditional.perspective_novaposhtashipping_house_door_num'
                );
                return this.validateField(component);
            },

            _isPrivateHouseChecked: function () {
                var component = registry.get(
                    'checkout.steps.shipping-step.shippingAddress.shippingAdditional.perspective_novaposhtashipping_private_house'
                );
                return !!(component && component.isPrivateHouse && component.isPrivateHouse());
            },

            validateField: function (component, invalidValues) {
                if (!component) {
                    return true;
                }

                var val = component.value();
                var empty = !val || val === '0';
                var invalid = empty || (invalidValues && invalidValues.indexOf(val) !== -1);

                if (invalid) {
                    component.error($t('This is a required field.'));
                    if (!component.disabled()) {
                        component.focused(true);
                    }
                    return false;
                }

                return true;
            }
        });
    };
});
