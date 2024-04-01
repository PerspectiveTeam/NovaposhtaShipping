define([
    'jquery',
    'Magento_Ui/js/form/element/select',
    'Magento_Checkout/js/model/quote',
    'mage/translate',
    'Perspective_NovaposhtaShipping/js/lib/select2/select2',
    'mage/url',
    'postbox',
    'Magento_Checkout/js/action/set-shipping-information',
], function (
    $,
    Select,
    quote,
    $t,
    select2Component,
    url,
    postbox,
    setShippingInformationAction
) {
    'use strict';

    return Select.extend({

        defaults: {
            address: '',
            placeholder: $t('Enter address'),
            exports: {
                "address": "checkout.steps.shipping-step.shippingAddress.shipping-address-fieldset.street.0:value"
            },
            imports: {
                "novaposhtaNewAddressDoor": "checkout.steps.shipping-step.shippingAddress.shipping-address-fieldset.perspective_novaposhtashipping_warehouse_house_street:value",
                "cityValue": 'checkout.steps.shipping-step.shippingAddress.shipping-address-fieldset.city_novaposhta_field:value'
            }
        },
        streets: {},
        cityRef: '',

        initialize: function () {
            this._super();
            this.address('');
            return this;
        },

        initObservable: function () {
            this._super();
            this.observe('address');
            this.observe('cityValue');
            this.observe('novaposhtaNewAddressDoor');
            return this;
        },

        onUpdate: function () {
            this._super();
            this.address(this.getPreview());
            if (this.value() !== 'none') {
                postbox.publish('selectedStreetPost', this.value());
                postbox.publish('selectedWarehousePost', '');
                if (this.getPreview()) {
                    try {
                        setShippingInformationAction();
                    } catch (e) {
                        console.log(e);
                        // если будет эксепшен, то еще не выбран шиппинг и керриер метод
                    }
                }
            }
        },

        selectedMethodCode: function () {
            var method = quote.shippingMethod();
            var selectedMethodCode = method != null ? method.method_code : false;
            if (selectedMethodCode === 'c2c' || selectedMethodCode === 'w2c') {
                if (!this.isLoading) {
                    this.isLoading = true;
                    try {
                        if (this.cityValue()) {
                            this.getCityStreets(this.cityValue(), this);
                        }
                    } catch (e) {
                        console.log(e);
                        this.isLoading = false;
                    } finally {
                        this.isLoading = false;
                    }
                }
            }
            return selectedMethodCode;
        },
        getCityStreets: function (cityValue, vm) {
            let cityTerm = JSON.stringify({cityRef: cityValue ? cityValue : ''});
            $.ajax({
                url: url.build('rest/V1/novaposhtashipping/streets-formatted'),
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
                    let currentStreet = vm.value();
                    window.perspective_novaposhta.street.react = false;
                    vm.setOptions(data);
                    window.perspective_novaposhta.street.react = true;
                    if (currentStreet && currentStreet !== 'none') {
                        vm.value(currentStreet);
                    }
                    vm.isLoading = false;
                }
            });
        },

        select2: function (element) {
            $(element).select2({
                placeholder: $.mage.__('Enter address'),
                dropdownAutoWidth: true,
                width: $(element).parent().parent().width().toString() + 'px'
            });
        },

        setStreets: function (data) {
            this.clear();
            this.streets = data;
            this.setOptions(this.streets);

            if (addressList().length > 0) {
                var street = quote.shippingAddress().street[0];
                if (street != '' && street != undefined) {
                    $("[name='warehouse_novaposhta_id'] option:contains(" + street + ")").attr('selected', 'selected');
                }
            }
        },

        getCityRef: function () {
            return this.cityRef;
        },

        setCityRef: function (cityRef) {
            this.cityRef = cityRef;
        }
    });
});
