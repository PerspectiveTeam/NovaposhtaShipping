define([
    'jquery',
    'ko',
    'Magento_Ui/js/form/element/select',
    'mage/url',
    'postbox',
    'mage/translate',
    'Perspective_NovaposhtaShipping/js/order/create/address-state',
    'Perspective_NovaposhtaShipping/js/lib/select2/select2'
], function ($, ko, Select, url, postbox, translate, refreshAddressState) {
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

            if (!this.preselectedValue() || (this.addressType === 'shipping' && sameAsBilling)) {
                this.disabled(true);
            }

            // Reset street selection after city updated
            postbox.subscribe('selectedCityPost', function (data) {
                var addressType = data?.addressType ?? null;
                var cityRef = (data?.cityRef && data.cityRef !== '0') ? data.cityRef : null;

                // Perform changes only for component matching addressType
                if (addressType !== null && addressType === self.addressType) {
                    var streetSelect = $('[name="order[' + addressType + '_address][novaposhta_warehouse]"]');
                    streetSelect.data('cityRef', cityRef);
                    this.disabled(!cityRef);

                    var sameAsBilling = $('#order-shipping_same_as_billing').is(':checked');
                    if (sameAsBilling && addressType === 'billing') {
                        var shippingSelect = $('[name="order[shipping_address][novaposhta_warehouse]"]');
                        shippingSelect.data('cityRef', cityRef);
                    }
                    if (!data.preserveAddress) {
                        self._resetSelect();
                    } else {
                        refreshAddressState();
                    }
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

            refreshAddressState();

            var hasCity = !!($(element).data('cityRef') || this.preselectedValue());
            var preselectObject = {};
            var lang = $('html').attr('lang') === 'uk' ? 'uk' : 'ru';

            if (this.preselectedLabel() && this.preselectedValue()) {
                preselectObject = {id: this.preselectedValue(), text: $.mage.__(this.preselectedLabel())};
            } else {
                preselectObject = {id: 0, text: hasCity ? $.mage.__('Choose warehouse') : $.mage.__('Choose city first')};
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
                            cityRef = self.cityOrphan() || '';
                            self.isOrphan(true);
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
            }).off('select2:select.novaposhta').on('select2:select.novaposhta', function (e) {
                var value = e.params.data.id;
                self.value(value);
                self.preselectedValue(value);
                self.preselectedLabel(e.params.data.text);
                var sameAsBilling = $('#order-shipping_same_as_billing').is(':checked');
                self._propagateToWarehouseInput(self.hasValue() ? self.getPreview() : '', sameAsBilling);
                if (sameAsBilling) {
                    self._syncShippingWarehouseFromBilling();
                }
                postbox.publish('selectedStreetPost', {streetRef: value, addressType: self.addressType});
            });
            refreshAddressState();
        },

        getPreview: function () {
            return $('[id="' + this.uid + '"] option:selected').text();
        },

        _propagateToWarehouseInput: function (value, sameAsBilling) {
            if (this.addressType && !refreshAddressState.isActive(this.addressType)) {
                return;
            }

            var shippingSelect = $('[name="order[shipping_address][street][0]"]');
            var billingSelect = $('[name="order[billing_address][street][0]"]');
            var orphanSelect = $('[name="street[0]"]');

            if (sameAsBilling) {
                this.exportValue(value, billingSelect);
                this.exportValue(value, shippingSelect);
            } else if (this.index === 'warehouseInputAutocompleteShipping') {
                this.exportValue(value, shippingSelect);
            } else if (this.index === 'warehouseInputAutocompleteBilling') {
                this.exportValue(value, billingSelect);
            } else if (this.isOrphan() && orphanSelect.length) {
                this.exportValue(value, orphanSelect);
                orphanSelect.change();
                return;
            }

        },

        _syncShippingWarehouseFromBilling: function () {
            var billingSelect = $('[name="order[billing_address][novaposhta_warehouse]"]');
            var shippingSelect = $('[name="order[shipping_address][novaposhta_warehouse]"]');
            var selectedData = billingSelect.select2('data');
            if (selectedData && selectedData.length) {
                var option = new Option(selectedData[0].text, selectedData[0].id, true, true);
                shippingSelect.append(option).trigger('change');
            }
        },

        exportValue: function (value, control) {
            if (control.val() !== value) {
                control.val(value).trigger('change');
            }
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
            this.preselectedValue('');
            this.preselectedLabel('');
            this.value('');
            select.empty().append(new Option($.mage.__('Choose warehouse'), '', true, true)).trigger('change');
            refreshAddressState();
            this._propagateToWarehouseInput('', $('#order-shipping_same_as_billing').is(':checked'));
        }
    });
});
