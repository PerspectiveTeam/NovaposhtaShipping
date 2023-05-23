var config = {
    config: {
        mixins: {
            'mage/menu': {
                'Perspective_NovaposhtaShipping/js/lib/mage/menu-mixin': true
            },
            'mage/validation': {
                'Perspective_NovaposhtaShipping/js/mixin/city-validation-mixin': true
            }
        }
    },
    map: {
        '*': {
            warehouseOnChange:
                'Perspective_NovaposhtaShipping/js/order/address/change/warehouse',
            addressOnChange:
                'Perspective_NovaposhtaShipping/js/order/address/change/address',
            postbox:
                'Perspective_NovaposhtaShipping/js/lib/knockout-postbox-min',
            validation: "mage/validation/validation"
        }
    }
};
