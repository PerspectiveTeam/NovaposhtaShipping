define([
    'jquery',
    'Magento_Ui/js/form/element/select',
    'Magento_Checkout/js/model/quote',
    'mage/url',
    'postbox',
    'Magento_Checkout/js/action/set-shipping-information',
    'mage/translate',
    'Magento_Checkout/js/checkout-data',
    'Perspective_NovaposhtaShipping/js/lib/select2/select2'
], function ($, Select, quote, url, postbox, setShippingInformationAction, $t, checkoutData) {
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
            isLoading: false,
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
                    data: preselect
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
                    var method = quote.shippingMethod();
                    var code = method ? method.method_code : null;
                    self.required(code === 'c2w' || code === 'w2w');
                };
                quote.shippingMethod.subscribe(updateRequired);
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
                if (this._initializing || !method || method.carrier_code !== 'novaposhtashipping' ||
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
                var method = quote.shippingMethod();
                var selectedMethodCode = method != null ? method.method_code : false;
                if (selectedMethodCode === 'c2w' || selectedMethodCode === 'w2w') {
                    if (!this.isLoading) {
                        this.isLoading = true;
                        this.getCityWarehouses(this.cityValue(), this);
                    }
                }
                return selectedMethodCode;
            },
            getCityWarehouses: function (cityValue, vm) {
                let cityTerm = JSON.stringify({term: cityValue});
                $.ajax({
                    url: url.build('rest/V1/novaposhtashipping/warehouses'),
                    data: cityTerm,
                    showLoader: true,
                    contentType: "application/json",
                    type: "post",
                    dataType: 'json',
                    error: function (data) {
                        console.log(data.responseText);
                        vm.isLoading = false;
                    },
                    success: function (data) {
                        let currentWarehouse = vm.value();
                        var items = JSON.parse(data);
                        window.perspective_novaposhta.warehouse.react = false;
                        vm.setOptions(items);
                        window.perspective_novaposhta.warehouse.react = true;
                        if (currentWarehouse && currentWarehouse !== 'none') {
                            vm.value(currentWarehouse);
                        } else {
                            vm.value('');
                        }
                        vm.isLoading = false;
                    }
                });
            }
        },
    );
});
