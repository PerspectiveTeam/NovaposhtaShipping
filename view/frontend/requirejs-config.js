var config = {
    config: {
        mixins: {
            'Magento_Checkout/js/action/set-shipping-information': {
                'Perspective_NovaposhtaShipping/js/mixin/set-shipping-information-mixin': true
            },
            'Magento_Checkout/js/model/shipping-save-processor/payload-extender': {
                'Perspective_NovaposhtaShipping/js/mixin/payload-extender-mixin': true
            }
        }
    },
    map: {
        '*': {
            postbox:
                'Perspective_NovaposhtaShipping/js/lib/knockout-postbox-min'
        }
    }
};
