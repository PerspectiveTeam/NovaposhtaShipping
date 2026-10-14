define([
    'jquery',
    'Magento_Ui/js/form/element/textarea',
    'Magento_Checkout/js/model/quote',
    'mage/translate',
    'postbox',
    'Perspective_NovaposhtaShipping/js/model/checkout-address-state',
    'Magento_Checkout/js/action/set-shipping-information',
], function (
    $,
    Input,
    quote,
    $t,
    postbox,
    addressState,
    setShippingInformationAction
) {
    'use strict';

    return Input.extend({

        defaults: {
            address: '',
            buildingNum: undefined,
            novaposhtaNewAddressDoor: '',
            placeholder: $t('House number'),
            allowShippingUpdate: true,
            exports: {
                "buildingNum": "checkout.steps.shipping-step.shippingAddress.shipping-address-fieldset.street.1:value"
            },
            imports: {
                "novaposhtaNewAddressDoor": "checkout.steps.shipping-step.shippingAddress.shipping-address-fieldset.perspective_novaposhtashipping_warehouse_house_build_num:value"
            }
        },

        initialize: function () {
            this._initializing = true;
            this._super();
            this._initializing = false;
            this.address('');
            return this;
        },

        initObservable: function () {
            this._super();
            this.observe('address');
            this.observe('buildingNum');
            this.observe('novaposhtaNewAddressDoor');

            postbox.subscribe('selectedCityPost', function (cityRef) {
                this.allowShippingUpdate = false;
                this.buildingNum('');
                this.value('');
                this.allowShippingUpdate = true;
            }, this);

            return this;
        },

        onUpdate: function () {
            this._super();
            var method = quote.shippingMethod();
            if (this._initializing || addressState.restoring || !addressState.isNp() || !method ||
                ['c2c', 'w2c'].indexOf(method.method_code) === -1) {
                return;
            }
            this.buildingNum(this.getPreview());
            postbox.publish('selectedStreetNumPost',this.getPreview());
            if (this.allowShippingUpdate && this.getPreview()) {
                try {
                    setShippingInformationAction();
                } catch (e) {
                    console.log(e);
                    // если будет эксепшен, то еще не выбран шиппинг и керриер метод
                }
            }
        },

        selectedMethodCode: function () {
            return addressState.methodCode();
        }
    });
});
