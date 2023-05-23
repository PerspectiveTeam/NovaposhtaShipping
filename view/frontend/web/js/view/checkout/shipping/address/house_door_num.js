define([
    'jquery',
    'ko',
    'Magento_Ui/js/form/element/textarea',
    'Magento_Checkout/js/model/quote',
    'mage/translate',
    'postbox',
    'Magento_Checkout/js/action/set-shipping-information'
], function (
    $,
    ko,
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
            apartNum: ko.observable(''),
            novaposhtaNewAddressDoorNum: '',
            placeholder: $t('House door number'),
            exports: {
                "apartNum": "checkout.steps.shipping-step.shippingAddress.shipping-address-fieldset.street.2:value"
            },
            imports: {
                "novaposhtaNewAddressDoorNum": "checkout.steps.shipping-step.shippingAddress.shipping-address-fieldset.perspective_novaposhtashipping_house_door_num:value"
            }
        },


        initialize: function () {
            this._super();
            return this;
        },

        initObservable: function () {
            this._super();
            this.observe('novaposhtaNewAddressDoorNum');
            return this;
        },

        onUpdate: function () {
            this._super();
            this.apartNum(this.getPreview());
            postbox.publish('selectedApartNumPost',this.getPreview());
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
