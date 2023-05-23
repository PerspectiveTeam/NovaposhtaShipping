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
            template: 'Perspective_NovaposhtaShipping/order/create/street',
            backendRestURL: '',
            inputCustomName: '',
            classCustomName: '',
            backendStreetValue: '',
            backendStreetName: '',
            cityValue: '',
            imports: {
                "cityValue": 'cityInputAutocompleteShipping:value'
            }
        },

        initialize: function (config) {
            this._super();
            this.backendRestURL(config.cityBackendUrl);
            this.inputCustomName(config.inputName);
            this.backendStreetName(config.streetLabel);
            this.backendStreetValue(config.streetValue);
            return this;
        },

        initObservable: function () {
            this._super();
            this.observe('backendRestURL');
            this.observe('inputCustomName');
            this.observe('classCustomName');
            this.observe('backendStreetValue');
            this.observe('backendStreetName');
            this.observe('cityValue');
            return this;
        },

        select2: function (element) {
            if (this.inputCustomName()) {
                //такое нужно чтобы не пропадал name после изменения инпута
                this.inputName = element.name = this.inputCustomName();
            }
            var lang = "ru";
            if ($('html').attr('lang') == "uk") {
                lang = "uk";
            }
            let initialData = [];
            if (this.backendStreetValue() != '' && this.backendStreetName() != '') {
                initialData = [{id: this.backendStreetValue(), text: this.backendStreetName()}];
            } else {
                initialData = [{id: 0, text: $.mage.__('Choose street')}];
            }
            $(element, this).select2({
                name: this.inputCustomName(),
                placeholder: $.mage.__(''),
                dropdownAutoWidth: true,
                width: '100%',
                minimumInputLength: 0,
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
                        let ko = require('ko');
                        var cityRef = ko.dataFor(this[0]).cityValue();
                        var query = JSON.stringify({
                            cityRef: cityRef,
                            term: params.term,
                            form_key: window.FORM_KEY
                        })
                        return query;
                    },
                    processResults: function (data) {
                        let result = data.map(function (item) {
                                return {
                                    id: item.value,
                                    text: item.label
                                }
                            }
                        );
                        return {
                            results: result
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

        },
        exportCityValue: function (value) {
            if ($('#order-shipping_same_as_billing').is(":checked")) {
                $('[name="order[shipping_address][novaposhta_street]"]').select2({
                    data: $('[name="order[billing_address][novaposhta_street]"]').select2("data"),
                    initSelection: function (element, callback) {
                        callback($('[name="order[billing_address][novaposhta_street]"]').select2("data"));
                    }
                });
                $('[name="order[shipping_address][novaposhta_street]"]').val($('[name="order[billing_address][novaposhta_street]"]').val());
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
