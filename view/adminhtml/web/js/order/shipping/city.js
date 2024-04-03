define([
    'jquery',
    'ko',
    'Magento_Ui/js/form/element/select',
    'mage/url',
    'postbox',
    'mage/translate',
    'Perspective_NovaposhtaShipping/js/lib/select2/select2'
], function ($, ko, Select, url, postbox, setShippingInformationAction) {
    'use strict';
    return Select.extend({

        defaults: {
            template: 'Perspective_NovaposhtaShipping/order/create/city',
            backendRestURL: '',
            inputCustomName: '',
            classCustomName: '',
            backendCityValue: '',
            backendCityName: ''
        },

        initialize: function (config) {
            this._super();
            this.backendRestURL(config.cityBackendUrl);
            this.inputCustomName(config.inputName);
            this.backendCityName(config.cityLabel);
            this.backendCityValue(config.cityValue);
            return this;
        },

        initObservable: function () {
            this._super();
            this.observe('backendRestURL');
            this.observe('inputCustomName');
            this.observe('classCustomName');
            this.observe('backendCityValue');
            this.observe('backendCityName');
            return this;
        },

        select2: function (element) {
            if (this.inputCustomName()) {
                //такое нужно чтобы не пропадал name после изменения инпута
                this.inputName = element.name = this.inputCustomName();
            }
            var lang = "uk";
            if ($('html').attr('lang') == "ru") {
                lang = "ru";
            }
            let initialData = [];
            if (this.backendCityValue() != '' && this.backendCityName() != '') {
                initialData = [{id: this.backendCityValue(), text: this.backendCityName()}];
            } else {
                initialData = [{id: 0, text: $.mage.__('Choose city')}];
            }
            $(element).select2({
                name: this.inputCustomName(),
                placeholder: $.mage.__(''),
                dropdownAutoWidth: true,
                width: '100%',
                minimumInputLength: 2,
                language: lang,
                data: initialData,
                ajax: {
                    url: this.backendRestURL(),
                    type: "post",
                    dataType: 'json',
                    contentType: "application/json",
                    delay: 1000,
                    beforeSend: function (xhr, ajax) {
                        //Empty to remove magento's default handler
                    },
                    data: function (params) {
                        var query = JSON.stringify({
                            term: params.term,
                            form_key: window.FORM_KEY
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
        getPreview: function () {
            return $('[id="' + this.uid + '"] option:selected').text();
        },
        setDifferedFromDefault: function (a, b, c) {
            this._super();
            this.exportCityName(this.getPreview());
            this.exportCityValue(this.value());
        },
        exportCityName: function (value) {
            if (this.index == 'cityInputAutocompleteShipping') {
                this.exportValue(value, $('[name="order[shipping_address][city]"]'));
            }
            if (this.index == 'cityInputAutocompleteBilling') {
                this.exportValue(value, $('[name="order[billing_address][city]"]'));
            }
            if ($('#order-shipping_same_as_billing').is(":checked")) {
                this.exportValue(value, $('[name="order[billing_address][city]"]'));
                this.exportValue(value, $('[name="order[shipping_address][city]"]'));
            }
            $('[name="order[shipping_address][city]"]').change();
            $('[name="order[billing_address][city]"]').change();
        },
        exportCityValue: function (value) {
            if ($('#order-shipping_same_as_billing').is(":checked")) {
                $('[name="order[shipping_address][novaposhta_city]"]').select2({
                    data: $('[name="order[billing_address][novaposhta_city]"]').select2("data"),
                    initSelection: function (element, callback) {
                        callback($('[name="order[billing_address][novaposhta_city]"]').select2("data"));
                    }
                });
                $('[name="order[shipping_address][novaposhta_city]"]').val($('[name="order[billing_address][novaposhta_city]"]').val());
            }
        },
        exportValue: function (value, control) {
            control.val(value);
        },
        exportLabel: function (value, control) {
            control.text(value);
            this.exportValue(value, control);
        },
    });
});
