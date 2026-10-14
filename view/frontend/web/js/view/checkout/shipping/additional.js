define(['uiComponent', 'Perspective_NovaposhtaShipping/js/model/checkout-address-state', 'uiRegistry'], function (Component, state, registry) {
    'use strict';
    return Component.extend({
        defaults: {template: 'Perspective_NovaposhtaShipping/checkout/shipping/additional'},
        addressState: state,
        openNewAddress: function () {
            registry.get('checkout.steps.shipping-step.shippingAddress').showFormPopUp();
        },
        novaposhtaElements: function () {
            return this.elems().filter(function (element) {
                return element.name.indexOf('.perspective_novaposhtashipping_') !== -1;
            });
        },
        otherElements: function () {
            return this.elems().filter(function (element) {
                return element.name.indexOf('.perspective_novaposhtashipping_') === -1;
            });
        }
    });
});
