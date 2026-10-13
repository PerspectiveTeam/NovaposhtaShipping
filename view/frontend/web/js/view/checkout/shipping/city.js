define([
    'jquery',
    'ko',
    'Magento_Ui/js/form/element/select',
    'mage/url',
    'postbox',
    'Magento_Checkout/js/model/quote',
    'Magento_Checkout/js/checkout-data',
    'Perspective_NovaposhtaShipping/js/model/checkout-address-state',
    'mage/translate',
    'Perspective_NovaposhtaShipping/js/lib/select2/select2'
], function ($, ko, Select, url, postbox, quote, checkoutData, addressState) {
    'use strict';

    var NP_METHODS = ['c2w', 'w2w', 'w2c', 'c2c'];

    return Select.extend({

        defaults: {
            template: 'Perspective_NovaposhtaShipping/checkout/shipping/city',
            cityName: undefined,
            areaRef: '',
            exports: {
                "cityName": "checkout.steps.shipping-step.shippingAddress.shipping-address-fieldset.city:value",
                "cityDefaultVisible": "checkout.steps.shipping-step.shippingAddress.shipping-address-fieldset.city:visible"
            }
        },

        initialize: function () {
            this._initializing = true;
            this._super();
            this._initializing = false;

            // Reset city on area(region) update
            postbox.subscribe('selectedAreaPost', function (areaRef) {
                if (this.areaRef() && this.areaRef() !== areaRef && this.hasValue()) {
                    var select = $('[name="' + this.inputName + '"]');
                    select.find('option').prop('selected', false);
                    if (select.data('select2')) {
                        select.find('option[value!="0"]').remove();
                        select.val(null).trigger('change');
                    }
                    this.value('');
                }
                this.areaRef(areaRef);
            }, this);

            return this;
        },

        initObservable: function () {
            var saved = checkoutData.getShippingAddressFromData() || {};
            if (saved.country_id === 'UA' && saved.city_novaposhta_field &&
                saved.city_novaposhta_field !== '0' && saved.city) {
                this.options = [{value: saved.city_novaposhta_field, label: saved.city}];
                this.areaRef = saved.area_novaposhta_field || '';
            }
            this._super();

            this.observe('cityName');
            this.observe('cityDefaultVisible');
            this.observe('areaRef');
            this.isNpMethod = ko.pureComputed(function () {
                var method = quote.shippingMethod();
                var code = method ? method.method_code : null;
                var isNp = addressState.isNp() && NP_METHODS.indexOf(code) !== -1;
                this.cityDefaultVisible(!isNp);
                this.visible(isNp);
                return isNp;
            }, this);
            this.disabled(false);

            return this;
        },

        select2: function (element) {
            var self = this;
            var lang = $('html').attr('lang') === 'uk' ? 'uk' : 'ru';
            $(element).select2({
                placeholder: $.mage.__('Choose city'),
                dropdownAutoWidth: true,
                width: '100%',
                minimumInputLength: 2,
                language: lang,
                data: [{id: 0, text: $.mage.__('Choose city')}],
                templateResult: function (data) {
                    return data.areaLabel ? data.text + ' (' + data.areaLabel + ')' : data.text;
                },
                ajax: {
                    url: url.build('rest/V1/novaposhtashipping/city'),
                    type: "POST",
                    dataType: 'json',
                    contentType: "application/json",
                    delay: 1000,
                    data: function (params) {
                        return JSON.stringify({
                            term: params.term,
                            areaRef: '',
                            includeArea: true
                        });
                    },
                    processResults: function (data) {
                        return {
                            results: JSON.parse(data)
                        };
                    }
                }
            }).on('select2:select', function (event) {
                var city = event.params.data;

                self.setOptions([{value: city.id, label: city.text}]);

                if (city.areaRef) {
                    self.areaRef(city.areaRef);
                    postbox.publish('selectedCityAreaPost', {areaRef: city.areaRef, label: city.areaLabel});
                }
            });
        },

        setRestoredOptions: function (options) {
            var saved = checkoutData.getShippingAddressFromData() || {};
            if (!saved.country_id || (saved.country_id === 'UA' && saved.city_novaposhta_field === undefined)) {
                this.setOptions(options);
            }
        },

        getPreview: function () {
            var option = this.indexedOptions && this.indexedOptions[this.value()];
            return this.hasValue() ? (option ? option.label : $('[name="' + this.inputName + '"] option:selected').text()) : '';
        },

        getCityName: function () {
            return this.cityName();
        },

        hasValue: function() {
            return this.value() !== '0' && !!this.value();
        },

        setDifferedFromDefault: function () {
            this._super();
            if (this._initializing || !this.isNpMethod()) {
                return;
            }
            var preview = this.getPreview();
            this.cityName(this.hasValue() ? preview : '');
            postbox.publish('selectedCityPost', this.value());
        }
    });
});
