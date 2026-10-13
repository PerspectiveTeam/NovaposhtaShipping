define([
    'jquery',
    'Magento_Ui/js/form/element/select',
    'Magento_Checkout/js/model/quote',
    'mage/translate',
    'Perspective_NovaposhtaShipping/js/lib/select2/select2',
    'mage/url',
    'postbox',
    'Magento_Checkout/js/action/set-shipping-information',
    'Magento_Checkout/js/checkout-data'
], function (
    $,
    Select,
    quote,
    $t,
    select2Component,
    url,
    postbox,
    setShippingInformationAction,
    checkoutData
) {
    'use strict';

    return Select.extend({

        defaults: {
            address: undefined,
            cityRef: '',
            placeholder: $t('Enter address'),
            allowShippingUpdate: true,
            exports: {
                "address": "checkout.steps.shipping-step.shippingAddress.shipping-address-fieldset.street.0:value"
            },
            imports: {
                "novaposhtaNewAddressDoor": "checkout.steps.shipping-step.shippingAddress.shipping-address-fieldset.perspective_novaposhtashipping_warehouse_house_street:value",
                "cityRef": 'checkout.steps.shipping-step.shippingAddress.shipping-address-fieldset.city_novaposhta_field:value'
            }
        },

        initialize: function () {
            this._initializing = true;
            this._super();
            this._initializing = false;
            return this;
        },

        initObservable: function () {
            var saved = checkoutData.getShippingAddressFromData() || {};
            if (saved.country_id === 'UA' && saved.novaposhta_street && saved.novaposhta_street_label) {
                this.options = [{value: saved.novaposhta_street, label: saved.novaposhta_street_label}];
            }
            this._super();
            this.observe('address');
            this.observe('cityRef');
            this.observe('novaposhtaNewAddressDoor');

            var self = this;
            var updateRequired = function () {
                var method = quote.shippingMethod();
                var code = method ? method.method_code : null;
                self.required(code === 'c2c' || code === 'w2c');
            };
            quote.shippingMethod.subscribe(updateRequired);
            updateRequired();

            postbox.subscribe('selectedCityPost', function (cityRef) {
                this.allowShippingUpdate = false;
                this.cityRef(cityRef);
                this.value('');
                this.allowShippingUpdate = true;
            }, this);

            return this;
        },

        onUpdate: function () {
            this._super();
            var method = quote.shippingMethod();
            if (this._initializing || !method || method.carrier_code !== 'novaposhtashipping' ||
                ['c2c', 'w2c'].indexOf(method.method_code) === -1) {
                return;
            }
            this.address(this.getPreview());
            if (this.allowShippingUpdate && this.value() !== 'none') {
                postbox.publish('selectedStreetPost', this.value());
                postbox.publish('selectedWarehousePost', '');
                if (this.getPreview()) {
                    try {
                        setShippingInformationAction();
                    } catch (e) {
                        console.log(e);
                        // если будет эксепшен, то еще не выбран шиппинг и керриер метод
                    }
                }
            }
        },

        selectedMethodCode: function () {
            var method = quote.shippingMethod();
            return method ? method.method_code : false;
        },

        select2: function (element) {
            var ko = require('ko');
            $(element).on('select2:select', function (e) {
                var data = e.params.data,
                    component = ko.dataFor(e.currentTarget);
                quote.shippingAddress().street[0] = data.text;
                component.address(data.text);
                component.source.set(component.dataScope + '_label', data.text);
            });
            $(element).select2({
                placeholder: $.mage.__('Enter address'),
                dropdownAutoWidth: true,
                width: $(element).parent().parent().width().toString() + 'px',
                ajax: {
                    url: url.build('rest/V1/novaposhtashipping/streets-formatted'),
                    type: "POST",
                    dataType: 'json',
                    contentType: "application/json",
                    delay: 1000,
                    data: function (params) {

                        var query = JSON.stringify({
                            term: params.term,
                            cityRef: ko.dataFor(this.get(0)).cityRef()
                        })
                        return query;
                    },
                    processResults: function (data) {
                        // ko.dataFor(this.$element.get(0)).setOptions(data);
                        let processedData = [];
                        for (let i = 0; i < data.length; i++) {
                            processedData.push({id: data[i].value, text: data[i].label});
                        }
                        return {
                            results: processedData
                        };
                    }
                }
            });
        },

        hasValue: function() {
            return this.value() !== '0' && !!this.value() && this.value() !== 'none';
        }
    });
});
