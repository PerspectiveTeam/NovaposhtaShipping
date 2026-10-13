define([
    'jquery',
    'Magento_Ui/js/form/element/select',
    'Magento_Checkout/js/model/quote',
    'mage/url',
    'postbox',
    'Magento_Checkout/js/action/set-shipping-information',
    'mage/translate',
    'Magento_Checkout/js/checkout-data',
    'Perspective_NovaposhtaShipping/js/model/checkout-address-state',
    'Perspective_NovaposhtaShipping/js/lib/select2/select2'
], function ($, Select, quote, url, postbox, setShippingInformationAction, $t, checkoutData, addressState) {
    'use strict';
    return Select.extend(
        {
            defaults: {
                warehouseName: undefined,
                allowShippingUpdate: true,
                exports: {
                    warehouseName: 'checkout.steps.shipping-step.shippingAddress.shipping-address-fieldset.street.0:value'
                },
                imports: {
                    cityValue: 'checkout.steps.shipping-step.shippingAddress.shipping-address-fieldset.city_novaposhta_field:value'
                }
            },
            initialize: function (config) {
                this._initializing = true;
                this._super();
                this._initializing = false;
                return this;
            },
            select2: function (element) {
                var self = this;
                var preselect = [{id: '', text: $.mage.__('Choose warehouse')}];
                if (self.options() && self.options().length) {
                    var currentWarehouse = self.value();
                    if (currentWarehouse && currentWarehouse !== 'none') {
                        var opt = self.options().find(function (o) { return o.value === currentWarehouse; });
                        if (opt) {
                            preselect = [{id: opt.value, text: opt.label}];
                        }
                    }
                }
                $(element).select2({
                    placeholder: $.mage.__('Choose warehouse'),
                    dropdownAutoWidth: true,
                    width: $(element).parent().parent().width().toString() + 'px',
                    data: preselect,
                    ajax: {
                        url: url.build('rest/V1/novaposhtashipping/warehouses'),
                        type: 'POST',
                        dataType: 'json',
                        contentType: 'application/json',
                        delay: 300,
                        data: function () { return JSON.stringify({term: self.cityValue() || ''}); },
                        processResults: function (data, params) {
                            var term = (params.term || '').toLocaleLowerCase();
                            return {results: JSON.parse(data).filter(function (item) {
                                return item.label.toLocaleLowerCase().indexOf(term) !== -1;
                            }).map(function (item) { return {id: item.value, text: item.label}; })};
                        }
                    }
                }).on('select2:select', function (event) {
                    self.setOptions([{value: event.params.data.id, label: event.params.data.text}]);
                });
            },
            initObservable: function () {
                var saved = checkoutData.getShippingAddressFromData() || {};
                if (saved.country_id === 'UA' && saved.novaposhta_warehouse && saved.novaposhta_warehouse_label) {
                    this.options = [{value: saved.novaposhta_warehouse, label: saved.novaposhta_warehouse_label}];
                }
                this._super();
                this.observe('warehouseName');
                this.observe('cityValue');

                var self = this;
                var updateRequired = function () {
                    var code = addressState.methodCode();
                    self.required(code === 'c2w' || code === 'w2w');
                };
                addressState.methodCode.subscribe(updateRequired);
                updateRequired();
                postbox.subscribe('selectedCityPost', function (cityRef) {
                    this.allowShippingUpdate = false;
                    this.warehouseName('');
                    this.value('');
                    this.allowShippingUpdate = true;
                }, this);
                return this;
            },
            setDifferedFromDefault: function () {
                this._super();
                var method = quote.shippingMethod();
                if (this._initializing || addressState.restoring || !addressState.isNp() || !method ||
                    ['c2w', 'w2w'].indexOf(method.method_code) === -1) {
                    return;
                }
                this.warehouseName(this.getPreview());
                this.source.set(this.dataScope + '_label', this.getPreview());
                postbox.publish('selectedWarehousePost', this.value());
                postbox.publish('selectedStreetPost', '');
                postbox.publish('selectedStreetNumPost', '');
                postbox.publish('selectedApartNumPost', '');
                if (this.getPreview()) {
                    try {
                        setShippingInformationAction();
                    } catch (e) {
                        console.log(e);
                        // если будет эксепшен, то еще не выбран шиппинг и керриер метод
                    }
                }
            },
            selectedMethod: function () {
                return addressState.methodCode();
            },
            getPreview: function () {
                var value = this.value(), option = this.indexedOptions[value];
                if (!value || value === '0' || value === 'none') return '';
                return option ? option.label : $('[name="' + this.inputName + '"] option:selected').text();
            }
        },
    );
});
