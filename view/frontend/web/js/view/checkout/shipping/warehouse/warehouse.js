define([
    'jquery',
    'Magento_Ui/js/form/element/select',
    'Magento_Checkout/js/model/quote',
    'mage/url',
    'postbox',
    'Magento_Checkout/js/action/set-shipping-information',
    'mage/translate',
    'Perspective_NovaposhtaShipping/js/lib/select2/select2'
], function ($, Select, quote, url, postbox, setShippingInformationAction) {
    'use strict';
    return Select.extend(
        {
            defaults: {
                warehouseName: '',
                exports: {
                    warehouseName: 'checkout.steps.shipping-step.shippingAddress.shipping-address-fieldset.street.0:value'
                },
                imports: {
                    cityValue: 'checkout.steps.shipping-step.shippingAddress.shipping-address-fieldset.city_novaposhta_field:value'
                }
            },
            isLoading: false,
            warehouses: {},
            initialize: function (config) {
                this._super();
                var self = this;
                this.setOptions(this.warehouses);
                this.warehouseName(this.getPreview());
                return this;
            },
            select2: function (element) {
                $(element).select2({
                    placeholder: $.mage.__('Choose the warehouse...'),
                    dropdownAutoWidth: true,
                    width: $(element).parent().parent().width().toString() + 'px'
                });
            },
            initObservable: function () {
                this._super();
                this.observe('warehouseName');
                this.observe('cityValue');
                return this;
            },
            setDifferedFromDefault: function () {
                this._super();
                this.warehouseName(this.getPreview());
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
                        }
                        vm.isLoading = false;
                    }
                });
            }
        },
    );
});
