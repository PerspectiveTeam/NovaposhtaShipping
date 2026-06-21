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

    var NP_METHOD_PREFIX = 'novaposhtashipping_';
    var NP_FIELDS_SELECTOR = '.field-novaposhtashipping_area, .field-novaposhtashipping_warehouse';

    function isNovaposhtaMethod(value) {
        return typeof value === 'string' && value.indexOf(NP_METHOD_PREFIX) === 0;
    }

    function toggleNovaposhtaFields() {
        var selected = $('input[name="order[shipping_method]"]:checked').val();
        if (isNovaposhtaMethod(selected)) {
            $(NP_FIELDS_SELECTOR).show();
        } else {
            $(NP_FIELDS_SELECTOR).hide();
        }
    }

    $(document).on('change', 'input[name="order[shipping_method]"]', function () {
        toggleNovaposhtaFields();
    });

    $(document).on('ajaxComplete', function (event, xhr, settings) {
        if (settings.url && settings.url.indexOf('sales/order_create') !== -1) {
            setTimeout(toggleNovaposhtaFields, 300);
        }
    });

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
