/*jshint browser:true jquery:true*/
/*global alert*/
define([
    'jquery',
    'mage/utils/wrapper',
    'Magento_Checkout/js/model/quote',
    'ko',
    'postbox'
], function ($, wrapper, quote, ko, postbox) {
    'use strict';
    return function (payloadExtender) {
        return wrapper.wrap(payloadExtender, function (originalAction, payload) {
            if (payload.addressInformation['extension_attributes'] === undefined) {
                payload.addressInformation['extension_attributes'] = {};
            } else {
                return payload;
            }
        });
    };
});
