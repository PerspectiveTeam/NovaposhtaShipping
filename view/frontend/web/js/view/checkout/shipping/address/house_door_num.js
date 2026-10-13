define([
    'jquery',
    'Magento_Ui/js/form/element/textarea',
    'Magento_Checkout/js/model/quote',
    'mage/translate',
    'postbox',
    'Magento_Checkout/js/action/set-shipping-information'
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
            apartNum: undefined,
            novaposhtaNewAddressDoorNum: '',
            placeholder: $t('House door number'),
            allowShippingUpdate: true,
            isPrivateHouse: false,
            exports: {
                "apartNum": "checkout.steps.shipping-step.shippingAddress.shipping-address-fieldset.street.2:value"
            },
            imports: {
                isPrivateHouse: 'checkoutProvider:shippingAddress.novaposhta_is_private_house',
                "novaposhtaNewAddressDoorNum": "checkout.steps.shipping-step.shippingAddress.shipping-address-fieldset.perspective_novaposhtashipping_house_door_num:value"
            }
        },

        initialize: function () {
            this._initializing = true;
            this._super();
            this._initializing = false;
            return this;
        },

        initObservable: function () {
            this._super();
            this.observe('novaposhtaNewAddressDoorNum');
            this.observe('apartNum');
            this.observe('isPrivateHouse');

            postbox.subscribe('selectedCityPost', function () {
                this.allowShippingUpdate = false;
                this.apartNum('');
                this.value('');
                this.isPrivateHouse(false);
                this.allowShippingUpdate = true;
            }, this);

            postbox.subscribe('privateHousePost', function (isPrivate) {
                this.isPrivateHouse(isPrivate);
                if (isPrivate) {
                    this.value('');
                }
            }, this);

            return this;
        },

        onUpdate: function () {
            this._super();
            var method = quote.shippingMethod();
            if (this._initializing || !method || method.carrier_code !== 'novaposhtashipping' ||
                ['c2c', 'w2c'].indexOf(method.method_code) === -1) {
                return;
            }
            this.apartNum(this.getPreview());
            postbox.publish('selectedApartNumPost',this.getPreview());
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
            var method = quote.shippingMethod();
            var selectedMethodCode = method != null ? method.method_code : false;
            return selectedMethodCode;
        }
    });
});
