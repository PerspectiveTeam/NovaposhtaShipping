var config = {
    config: {
        mixins: {
            'mage/menu': {
                'Perspective_NovaposhtaShipping/js/lib/mage/menu-mixin': true
            },
            'mage/backend/validation': {
                'Perspective_NovaposhtaShipping/js/mixin/city-validation-mixin': true
            }
        }
    },
    map: {
        '*': {
            AddressShippingFormComponent:
                'Perspective_NovaposhtaShipping/js/order/shipping/addressShippingFormComponent',
            WarehouseShippingFormComponent:
                'Perspective_NovaposhtaShipping/js/order/shipping/warehouseShippingFormComponent',
            warehouseOnChange:
                'Perspective_NovaposhtaShipping/js/order/address/change/warehouse',
            addressOnChange:
                'Perspective_NovaposhtaShipping/js/order/address/change/address',
            postbox:
                'Perspective_NovaposhtaShipping/js/lib/knockout-postbox-min',
            validation: "mage/backend/validation"
        }
    }
};
