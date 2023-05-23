define([
    'jquery',
    'Magento_Ui/js/form/element/textarea',
    'Magento_Checkout/js/model/quote',
    'mage/translate',
    'postbox',
    'Magento_Checkout/js/action/set-shipping-information',
], function (
    $,
    Input,
    quote,
    $t,
    postbox,
    setShippingInformationAction
) {
    'use strict';

    return Input.extend({

        defaults: {
            address: '',
            buildingNum: '',
            novaposhtaNewAddressDoor: '',
            placeholder: $t('House number'),
            exports: {
                "buildingNum": "checkout.steps.shipping-step.shippingAddress.shipping-address-fieldset.street.1:value"
            },
            imports: {
                "novaposhtaNewAddressDoor": "checkout.steps.shipping-step.shippingAddress.shipping-address-fieldset.perspective_novaposhtashipping_warehouse_house_build_num:value"
            }
        },

        initialize: function () {
            this._super();
            this.address('');
            return this;
        },

        initObservable: function () {
            this._super();
            this.observe('address');
            this.observe('buildingNum');
            this.observe('novaposhtaNewAddressDoor');
            return this;
        },

        onUpdate: function () {
            this._super();
            this.buildingNum(this.getPreview());
            postbox.publish('selectedStreetNumPost',this.getPreview());
            if (this.getPreview()) {
                try {
                    setShippingInformationAction();
                } catch (e) {
                    console.log(e);
                    // если будет эксепшен, то еще не выбран шиппинг и керриер метод
                }
            }
        },

        selectedMethodCode: function () {
            var method = quote.shippingMethod();
            var selectedMethodCode = method != null ? method.method_code : false;
            return selectedMethodCode;
        }
    });
});
