define([], function () {
    'use strict';
    return function (Component) {
        return Component.extend({
            getCustomAttributeLabel: function (attribute) {
                if (attribute && attribute.attribute_code === 'novaposhta_shipping_data') return '';
                return this._super(attribute);
            }
        });
    };
});
