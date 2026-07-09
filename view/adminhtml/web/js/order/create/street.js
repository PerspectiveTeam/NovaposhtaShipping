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

            var self = this;
            var sameAsBilling = $('#order-shipping_same_as_billing').is(':checked');

            if (!this.preselectedValue() || sameAsBilling) {
                this.disabled(true);
            }

            // Reset street selection after city updated
            postbox.subscribe('selectedCityPost', function (data) {
                var addressType = data?.addressType ?? null;
                var cityRef = (data?.cityRef && data.cityRef !== '0') ? data.cityRef : null;

                // Perform changes only for component matching addressType
                if (cityRef !== null && addressType !== null && addressType === self.addressType) {
                    var streetSelect = $('[name="order[' + addressType + '_address][novaposhta_street]"]');
                    streetSelect.data('cityRef', cityRef);
                    this.disabled(!cityRef);

                    var sameAsBilling = $('#order-shipping_same_as_billing').is(':checked');
                    if (sameAsBilling && addressType === 'billing') {
                        var shippingSelect = $('[name="order[shipping_address][novaposhta_street]"]');
                        shippingSelect.data('cityRef', cityRef);
                    }
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
            this.observe('cityBilling');
            this.observe('cityShipping');
            this.observe('cityOrphan');
            this.observe('isOrphan');
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

            var hasCity = !!($(element).data('cityRef') || this.preselectedValue());
            var preselectObject = {};
            var lang = $('html').attr('lang') === 'uk' ? 'uk' : 'ru';

            if (this.preselectedLabel() && this.preselectedValue()) {
                preselectObject = {id: this.preselectedValue(), text: $.mage.__(this.preselectedLabel())};
            } else {
                preselectObject = {id: 0, text: hasCity ? $.mage.__('Choose street') : $.mage.__('Choose city first')};
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
                        var cityRef = $(element).data('cityRef') || '';
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
            }).on('select2:select', function (e) {
                var value = e.params.data.id;
                var sameAsBilling = $('#order-shipping_same_as_billing').is(':checked');
                self._propagateToStreetInput(self.hasValue() ? self.getPreview() : '', sameAsBilling);
                if (sameAsBilling) {
                    self._syncShippingStreetFromBilling();
                }
                postbox.publish('selectedStreetPost', {streetRef: value, addressType: self.addressType});
            });
        },

        getPreview: function () {
            return $('[id="' + this.uid + '"] option:selected').text();
        },

        _propagateToStreetInput: function (value, sameAsBilling) {
            var shippingSelect = $('[name="order[shipping_address][street][0]"]');
            var billingSelect = $('[name="order[billing_address][street][0]"]');
            var orphanSelect = $('[name="street[0]"]');

            if (sameAsBilling) {
                this.exportValue(value, billingSelect);
                this.exportValue(value, shippingSelect);
            } else if (this.index === 'streetInputAutocompleteShipping') {
                this.exportValue(value, shippingSelect);
            } else if (this.index === 'streetInputAutocompleteBilling') {
                this.exportValue(value, billingSelect);
            } else if (this.isOrphan() && orphanSelect.length) {
                this.exportValue(value, orphanSelect);
                orphanSelect.change();
                return;
            }

            shippingSelect.change();
            billingSelect.change();
        },

        _syncShippingStreetFromBilling: function () {
            var billingSelect = $('[name="order[billing_address][novaposhta_street]"]');
            var shippingSelect = $('[name="order[shipping_address][novaposhta_street]"]');
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
            this._propagateToStreetInput('', $('#order-shipping_same_as_billing').is(':checked'));
        }
    });
});
