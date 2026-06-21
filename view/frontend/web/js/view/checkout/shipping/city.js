define([
    'jquery',
    'ko',
    'Magento_Ui/js/form/element/select',
    'mage/url',
    'postbox',
    'Magento_Checkout/js/action/set-shipping-information',
    'Magento_Checkout/js/model/quote',
    'mage/translate',
    'Perspective_NovaposhtaShipping/js/lib/select2/select2'
], function ($, ko, Select, url, postbox, setShippingInformationAction, quote) {
    'use strict';

    var NP_METHODS = ['c2w', 'w2w', 'w2c', 'c2c'];

    return Select.extend({

        defaults: {
            template: 'Perspective_NovaposhtaShipping/checkout/shipping/city',
            cityName: '',
            postcode: '',
            city_fast: [],
            areaRef: '',
            allowShippingUpdate: true,
            exports: {
                "cityName": "checkout.steps.shipping-step.shippingAddress.shipping-address-fieldset.city:value",
                "cityDefaultVisible": "checkout.steps.shipping-step.shippingAddress.shipping-address-fieldset.city:visible"
            }
        },

        initialize: function () {
            this._super();
            this.cityName(this.getPreview());

            // Reset city on area(region) update
            postbox.subscribe('selectedAreaPost', function (areaRef) {
                this.allowShippingUpdate = false;
                if (!!this.areaRef() && this.areaRef() !== areaRef) {
                    var select = $('[name="' + this.inputName + '"]');
                    select.find('option').prop('selected', false);
                    if (select.data('select2')) {
                        select.find('option[value!="0"]').remove();
                        select.val(null).trigger('change');
                        select.select2('destroy');
                        this.select2(select[0]);
                    }
                    this.value('');
                }
                this.allowShippingUpdate = true;
                this.areaRef(areaRef)
            }, this);

            return this;
        },

        initObservable: function () {
            this._super();
            var self = this;

            this.observe('cityName');
            this.observe('postcode');
            this.observe('cityDefaultVisible');
            this.observe('areaRef');
            this.isNpMethod = ko.pureComputed(function () {
                var method = quote.shippingMethod();
                var code = method ? method.method_code : null;
                var isNp = NP_METHODS.indexOf(code) !== -1;
                this.cityDefaultVisible(!isNp);
                this.visible(isNp);
                return code;
            }, this);
            this.areaRef.subscribe(function (areaRef) {
                self.disabled(!areaRef);
            });
            this.disabled(!this.areaRef());

            return this;
        },

        select2: function (element) {
            var self = this;
            var lang = $('html').attr('lang') === 'uk' ? 'uk' : 'ru';
            var isDisabled = !self.areaRef();

            $(element).select2({
                placeholder: isDisabled
                    ? $.mage.__('Choose region first')
                    : $.mage.__('Choose city'),
                dropdownAutoWidth: true,
                width: '100%',
                minimumInputLength: 2,
                language: lang,
                disabled: isDisabled,
                data: [{id: 0, text: isDisabled ? $.mage.__('Choose region first') : $.mage.__('Choose city')}],
                ajax: {
                    url: url.build('rest/V1/novaposhtashipping/city'),
                    type: "POST",
                    dataType: 'json',
                    contentType: "application/json",
                    delay: 1000,
                    data: function (params) {
                        return JSON.stringify({
                            term: params.term,
                            areaRef: self.areaRef()
                        });
                    },
                    processResults: function (data) {
                        return {
                            results: JSON.parse(data)
                        };
                    }
                }
            });
        },

        getPreview: function () {
            return this.hasValue() ? $('[name="' + this.inputName + '"] option:selected').text() : '';
        },

        getCityName: function () {
            return this.cityName();
        },

        hasValue: function() {
            return this.value() !== '0' && !!this.value();
        },

        setDifferedFromDefault: function () {
            this._super();
            var preview = this.getPreview();
            this.cityName(this.hasValue() ? preview : '');
            postbox.publish('selectedCityPost', this.value());
            if (this.allowShippingUpdate && this.hasValue()) {
                try {
                    setShippingInformationAction();
                } catch (e) {
                    console.log(e);
                }
            }
        }
    });
});
