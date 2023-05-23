define([
    'jquery',
    'Magento_Ui/js/form/element/select',
    'mage/url',
    'postbox',
    'Magento_Checkout/js/action/set-shipping-information',
    'Magento_Checkout/js/model/quote',
    'mage/translate',
    'Perspective_NovaposhtaShipping/js/lib/select2/select2'
], function ($, Select, url, postbox, setShippingInformationAction, quote) {
    'use strict';
    return Select.extend({

        defaults: {
            template: 'Perspective_NovaposhtaShipping/checkout/shipping/city',
            cityName: '',
            postcode: '',
            city_fast: [],
            exports: {
                "cityName": "checkout.steps.shipping-step.shippingAddress.shipping-address-fieldset.city:value",
                "cityDefaultVisible": "checkout.steps.shipping-step.shippingAddress.shipping-address-fieldset.city:visible"
            }
        },

        initialize: function () {
            this._super();
            this.cityName(this.getPreview());
            return this;
        },

        initObservable: function () {
            this._super();
            this.observe('cityName');
            this.observe('postcode');
            this.observe('cityDefaultVisible');
            return this;
        },

        select2: function (element) {
            var lang = "ru";
            if ($('html').attr('lang') == "uk") {
                lang = "uk";
            }
            $(element).select2({
                placeholder: $.mage.__(''),
                dropdownAutoWidth: true,
                width: '100%',
                minimumInputLength: 2,
                language: lang,
                data: [{id: 0, text: $.mage.__('Choose city')}],
                ajax: {
                    url: url.build('rest/V1/novaposhtashipping/city'),
                    type: "POST",
                    dataType: 'json',
                    contentType: "application/json",
                    delay: 1000,
                    data: function (params) {
                        var query = JSON.stringify({
                            term: params.term
                        })
                        return query;
                    },
                    processResults: function (data) {
                        return {
                            results: JSON.parse(data)
                        };
                    }
                }
            });
        },
        selectedMethod: function () {
            var method = quote.shippingMethod();
            var selectedMethodCode = method != null ? method.method_code : false;
            if (selectedMethodCode === 'c2w' || selectedMethodCode === 'w2w' || selectedMethodCode === 'w2c' || selectedMethodCode === 'c2c') {
                this.cityDefaultVisible(false);
                this.visible(true);
            } else {
                this.cityDefaultVisible(true);
                this.visible(false);
            }
            return selectedMethodCode;
        },
        getPreview: function () {
            return $('[name="' + this.inputName + '"] option:selected').text();
        },

        getCityName: function () {
            return this.cityName();
        },

        setDifferedFromDefault: function () {
            this._super();
            this.cityName(this.getPreview());
            postbox.publish('selectedCityPost', this.value());
            if (this.getPreview()) {
                try {
                    setShippingInformationAction();
                } catch (e) {
                    console.log(e);
                    // если будет эксепшен, то еще не выбран шиппинг и керриер метод
                }
            }
        },
    });
});
