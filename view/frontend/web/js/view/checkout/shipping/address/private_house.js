define([
    'ko',
    'uiComponent',
    'Magento_Checkout/js/model/quote',
    'postbox',
    'mage/translate',
    'Perspective_NovaposhtaShipping/js/model/checkout-address-state'
], function (ko, Component, quote, postbox, $t, addressState) {
    'use strict';

    return Component.extend({
        defaults: {
            template: 'Perspective_NovaposhtaShipping/checkout/shipping/address/private_house',
            isPrivateHouse: false,
            links: {
                isPrivateHouse: 'checkoutProvider:shippingAddress.novaposhta_is_private_house'
            }
        },

        initialize: function () {
            this._super();
            postbox.subscribe('selectedCityPost', function () {
                this.isPrivateHouse(false);
                postbox.publish('privateHousePost', false);
            }, this);
            return this;
        },

        initObservable: function () {
            this._super();
            this.observe('isPrivateHouse');
            this.isPrivateHouse.subscribe(function (newVal) {
                postbox.publish('privateHousePost', newVal);
            });
            return this;
        },

        selectedMethodCode: function () {
            return addressState.methodCode();
        }
    });
});
