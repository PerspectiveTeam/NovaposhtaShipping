/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

/* global AdminOrder */
define([
    'jquery',
    'Magento_Sales/order/create/scripts'
], function ($) {
    'use strict';

    AdminOrder.prototype.setCustomerAfter = function () {
        this.customerSelectorHide();
        if (this.storeId) {
            $(this.getAreaId('data')).callback = 'dataLoaded';
            this.loadArea(['data'], true);
            this.reInitNovaposhtaCitySelect();
            this.reInitNovaposhtaWarehouseSelect();
        } else {
            this.storeSelectorShow();
        }
    }
    AdminOrder.prototype.setShippingAsBilling = function (flag) {
        var data,
            areasToLoad = ['billing_method', 'shipping_address', 'shipping_method', 'totals', 'giftmessage'];
        this.disableShippingAddress(flag);
        data = this.serializeData(flag ? this.billingAddressContainer : this.shippingAddressContainer);
        data = data.toObject();
        data['shipping_as_billing'] = flag ? 1 : 0;
        data['reset_shipping'] = 1;
        this.loadArea(areasToLoad, true, data);
        this.reInitNovaposhtaCitySelect();
        this.reInitNovaposhtaWarehouseSelect();
    }
    AdminOrder.prototype.reInitNovaposhtaCitySelect = function () {
        setTimeout(function () {
            try {
                $('.cityInputAutocompleteShippingClass').applyBindings();
            } catch (e) {
                console.info('Not bug');
                console.info(e);
            }
        }, 1000);
        setTimeout(function () {
            try {
                $('.cityInputAutocompleteBillingClass').applyBindings();
            } catch (e) {
                console.info('Not bug');
                console.info(e);
            }
        }, 1000);
    }
    AdminOrder.prototype.reInitNovaposhtaWarehouseSelect = function () {
        setTimeout(function () {
            try {
                $('.warehouseInputAutocompleteShippingClass').applyBindings();
            } catch (e) {
                console.info('Not bug');
                console.info(e);
            }
        }, 1000);
        setTimeout(function () {
            try {
                $('.warehouseInputAutocompleteBillingClass').applyBindings();
            } catch (e) {
                console.info('Not bug');
                console.info(e);
            }
        }, 1000);
    }
});
