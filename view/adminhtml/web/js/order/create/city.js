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
            preselectedLabel: '',
            preselectedValue: ''
        },

        initialize: function (config) {
            this._super();
            this.backendRestURL(config.cityBackendUrl);
            this.inputCustomName(config.inputName);

            var self = this;
            var sameAsBilling = $('#order-shipping_same_as_billing').is(':checked');

            if (!this.preselectedValue() || sameAsBilling) {
                this.disabled(true);
            }

            // Reset city selection after area updated
            postbox.subscribe('selectedAreaPost', function (data) {
                var areaRef = data?.areaRef ?? null;
                var addressType = data?.addressType ?? null;

                // Perform changes only for component matching addressType
                if (areaRef !== null && addressType !== null && addressType === self.addressType) {
                    var citySelect = $('[name="order[' + addressType + '_address][novaposhta_city]"]');
                    citySelect.data('areaRef', areaRef);
                    this.disabled(!areaRef);

                    var sameAsBilling = $('#order-shipping_same_as_billing').is(':checked');
                    if (sameAsBilling && addressType === 'billing') {
                        var shippingSelect = $('[name="order[shipping_address][novaposhta_city]"]');
                        shippingSelect.data('areaRef', areaRef);
                    }
                    postbox.publish('selectedCityPost', {cityRef: '', addressType: self.addressType});
                    self._resetSelect();
                }
            }, this);

            return this;
        },

        initObservable: function () {
            this._super();
            this.observe('backendRestURL');
            this.observe('inputCustomName');
            this.observe('classCustomName');
            this.observe('preselectedLabel');
            this.observe('preselectedValue');
            return this;
        },

        select2: function (element) {
            var self = this;

            if (this.inputCustomName()) {
                // Reassign name to prevent it from being lost after DOM updates
                this.inputName = element.name = this.inputCustomName();
            }

            var lang = $('html').attr('lang') === 'uk' ? 'uk' : 'ru';
            var hasArea = !!($(element).data('areaRef') || this.preselectedValue());
            var preselectObject = {};

            if (this.preselectedLabel() && this.preselectedValue()) {
                preselectObject = {id: this.preselectedValue(), text: $.mage.__(this.preselectedLabel())};
            } else {
                preselectObject = {id: 0, text: hasArea ? $.mage.__('Choose city') : $.mage.__('Choose region first')};
            }

            $(element).select2({
                name: this.inputCustomName(),
                placeholder: $.mage.__(''),
                dropdownAutoWidth: true,
                width: '100%',
                minimumInputLength: 2,
                language: lang,
                data: [preselectObject],
                ajax: {
                    url: this.backendRestURL(),
                    type: "post",
                    dataType: 'json',
                    contentType: "application/json",
                    delay: 1000,
                    beforeSend: function (xhr, ajax) {
                        // Empty to remove magento's default handler
                    },
                    data: function (params) {
                        var areaRef = $(element).data('areaRef') || '';
                        return JSON.stringify({
                            term: params.term,
                            areaRef: areaRef,
                            form_key: window.FORM_KEY
                        });
                    },
                    processResults: function (data) {
                        return {
                            results: JSON.parse(data)
                        };
                    }
                }
            }).on('select2:select', function (e) {
                var value = e.params.data.id;
                var sameAsBilling = $('#order-shipping_same_as_billing').is(':checked');
                self._propagateToCityInput(self.getPreview(), sameAsBilling);
                if (sameAsBilling) {
                    self._syncShippingCityFromBilling();
                }
                postbox.publish('selectedCityPost', {cityRef: value, addressType: self.addressType});
            });
        },

        getPreview: function () {
            return $('[id="' + this.uid + '"] option:selected').text();
        },

        _propagateToCityInput: function (value, sameAsBilling) {
            var shippingSelect = $('[name="order[shipping_address][city]"]');
            var billingSelect = $('[name="order[billing_address][city]"]');
            var orphanSelect = $('[name="city"]');

            if (sameAsBilling) {
                this.exportValue(value, billingSelect);
                this.exportValue(value, shippingSelect);
            } else if (this.index === 'cityInputAutocompleteShipping') {
                this.exportValue(value, shippingSelect);
            } else if (this.index === 'cityInputAutocompleteBilling') {
                this.exportValue(value, billingSelect);
            } else if (this.index === 'cityInputAutocompleteOrphan' && orphanSelect.length) {
                this.exportValue(value, orphanSelect);
                orphanSelect.change();
                return;
            }

            shippingSelect.change();
            billingSelect.change();
        },

        _syncShippingCityFromBilling: function () {
            var billingSelect = $('[name="order[billing_address][novaposhta_city]"]');
            var shippingSelect = $('[name="order[shipping_address][novaposhta_city]"]');
            var selectedData = billingSelect.select2('data');
            if (selectedData && selectedData.length) {
                var option = new Option(selectedData[0].text, selectedData[0].id, true, true);
                shippingSelect.append(option).trigger('change');
            }
        },

        exportValue: function (value, control) {
            control.val(value);
        },

        exportLabel: function (value, control) {
            control.text(value);
            this.exportValue(value, control);
        },

        hasValue: function () {
            return !!(this.value() !== '0' && this.value());
        },

        _resetSelect: function () {
            var select = $('[name="' + this.inputName + '"]');
            if (select.data('select2')) {
                $(select[0]).empty();
                this.select2(select[0]);
            }
            this.value('');
            this._propagateToCityInput('', $('#order-shipping_same_as_billing').is(':checked'));
        }
    });
});
