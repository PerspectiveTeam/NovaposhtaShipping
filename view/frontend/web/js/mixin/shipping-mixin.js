define([
    'Magento_Checkout/js/model/quote',
    'uiRegistry',
    'mage/translate',
    'Magento_Checkout/js/checkout-data',
    'Magento_Checkout/js/model/address-converter',
    'Magento_Checkout/js/action/select-shipping-address',
    'Magento_Checkout/js/model/shipping-service'
], function (quote, registry, $t, checkoutData, addressConverter, selectShippingAddress, shippingService) {
    'use strict';

    var NP_METHODS = ['c2w', 'w2w', 'w2c', 'c2c'];
    var NP_WAREHOUSE_METHODS = ['c2w', 'w2w'];
    var NP_ADDRESS_METHODS = ['c2c', 'w2c'];

    return function (Component) {
        return Component.extend({
            initialize: function () {
                this._super();
                if (!this.isFormInline || quote.isVirtual() ||
                    window.checkoutConfig.activeCarriers.indexOf('novaposhtashipping') === -1) {
                    return this;
                }

                var fieldset = 'checkout.steps.shipping-step.shippingAddress.shipping-address-fieldset.';
                registry.get([
                    'checkoutProvider', fieldset + 'country_id', fieldset + 'region_id',
                    fieldset + 'city', fieldset + 'area_novaposhta_field', fieldset + 'city_novaposhta_field'
                ], function (provider) {
                    var subscription,
                        hasNpDraft = provider.get('shippingAddress').country_id === 'UA';
                    var saveDraft = function (address) {
                        hasNpDraft = hasNpDraft || address.country_id === 'UA';
                        if (hasNpDraft) {
                            checkoutData.setShippingAddressFromData(address);
                        }
                    };
                    provider.on('shippingAddress', saveDraft);
                    saveDraft(provider.get('shippingAddress'));

                    var reconcileCountry = function () {
                        var form = provider.get('shippingAddress'),
                            address = quote.shippingAddress();

                        if (form.country_id === 'UA' && address && address.countryId !== form.country_id) {
                            if ((checkoutData.getSelectedShippingRate() || '').indexOf('novaposhtashipping_') === 0) {
                                quote.shippingMethod(null);
                            }
                            selectShippingAddress(addressConverter.formAddressDataToQuoteAddress(form));
                        }
                    };
                    if (shippingService.isLoading()) {
                        subscription = shippingService.isLoading.subscribe(function (loading) {
                            if (!loading) {
                                subscription.dispose();
                                reconcileCountry();
                            }
                        });
                    } else {
                        reconcileCountry();
                    }
                });
                return this;
            },

            validateShippingInformation: function () {
                var method = quote.shippingMethod();
                var methodCode = method ? method.method_code : null;

                if (method && method.carrier_code === 'novaposhtashipping' && NP_METHODS.indexOf(methodCode) !== -1) {
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
