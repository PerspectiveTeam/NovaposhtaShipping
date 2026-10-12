define([
    'ko',
    'uiComponent',
    'Magento_Checkout/js/model/quote',
    'postbox',
    'mage/translate'
], function (ko, Component, quote, postbox, $t) {
    'use strict';

    return Component.extend({
        defaults: {
            template: 'Perspective_NovaposhtaShipping/checkout/shipping/address/private_house',
            isPrivateHouse: false
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
            var method = quote.shippingMethod();
            return method ? method.method_code : false;
        }
    });
});
