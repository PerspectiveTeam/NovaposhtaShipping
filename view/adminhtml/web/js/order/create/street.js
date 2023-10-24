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
            template: 'Perspective_NovaposhtaShipping/order/create/warehouse',
            imports: {
                cityBilling: 'cityInputAutocompleteBilling:value',
                cityShipping: 'cityInputAutocompleteShipping:value',
                cityOrphan: 'cityInputAutocompleteOrphan:value'
            },
            backendRestURL: '',
            inputCustomName: '',
            classCustomName: '',
            preselectedLabel: '',
            preselectedValue: '',
            isOrphan: false
        },

        initialize: function (config) {
            this._super();
            this.backendRestURL(config.cityBackendUrl);
            this.inputCustomName(config.inputName);
            return this;
        },

        initObservable: function () {
            this._super();
            this.observe('backendRestURL');
            this.observe('inputCustomName');
            this.observe('classCustomName');
            this.observe('cityBilling');
            this.observe('cityShipping');
            this.observe('cityOrphan');
            this.observe('isOrphan');
            this.observe('preselectedLabel');
            this.observe('preselectedValue');
            return this;
        },

        select2: function (element) {
            if (this.inputCustomName()) {
                //такое нужно чтобы не пропадал name после изменения инпута
                this.inputName = element.name = this.inputCustomName();
            }
            var preselectObject = {};
            if (this.preselectedLabel() && this.preselectedValue()) {
                preselectObject = {id: this.preselectedValue(), text: $.mage.__(this.preselectedLabel())};
            } else {
                preselectObject = {id: 0, text: $.mage.__('Choose street')};
            }
            var lang = "ru";
            if ($('html').attr('lang') == "uk") {
                lang = "uk";
            }
            $(element).select2({
                name: this.inputCustomName(),
                placeholder: $.mage.__(''),
                dropdownAutoWidth: true,
                width: '100%',
                minimumInputLength: 0,
                language: lang,
                data: [preselectObject],
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
                        var cityRef = '';
                        let ko = require('ko');
                        if ($('#order-shipping_same_as_billing').is(":checked")) {
                            cityRef = ko.dataFor(this[0]).cityBilling();
                        } else {
                            cityRef = ko.dataFor(this[0]).cityShipping();
                        }
                        if (!cityRef) {
                            cityRef = ko.dataFor(this[0]).cityOrphan();
                            ko.dataFor(this[0]).isOrphan(true);
                        }
                        var query = JSON.stringify({
                            cityRef: cityRef,
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
            if (this.index == 'streetInputAutocompleteShipping') {
                this.exportValue(value, $('[name="order[shipping_address][street][0]"]'));
            }
            if (this.index == 'streetInputAutocompleteBilling') {
                this.exportValue(value, $('[name="order[billing_address][street][0]"]'));
            }
            if ($('#order-shipping_same_as_billing').is(":checked")) {
                this.exportValue(value, $('[name="order[billing_address][street][0]"]'));
                this.exportValue(value, $('[name="order[shipping_address][street][0]"]'));
            }
            $('[name="order[shipping_address][street][0]"]').change();
            $('[name="order[billing_address][street][0]"]').change();
            if (this.isOrphan()) {
                this.exportValue(value, $('[name="street[0]"]'));
                $('[name="street[0]"]').change();
            }
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
