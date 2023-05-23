define(
    [
        'jquery',
        'Magento_Customer/js/customer-data',
        'Magento_Checkout/js/checkout-data'
    ],
    function ($, customerData, checkoutData) {
        'use strict';

        var cacheKey = 'perspective-novaposhtashipping-checkout-data';

        /**
         * Retrieve checkout data from storage
         *
         * @returns {Object}
         */
        var getData = function () {
            return customerData.get(cacheKey)();
        };

        /**
         * Save checkout data to storage
         *
         * @param {Object} data
         */
        var saveData = function (data) {
            customerData.set(cacheKey, data);
        };

        if ($.isEmptyObject(getData())) {
            var psCheckoutData = {
                'sameAsShippingFlag': window.checkoutConfig.sameAsShippingFlag,
                'isSubscribedFlag': null,
                'orderNote': null,
                'deliveryDateFormData': null
            };
            saveData(psCheckoutData);
        }

        return {
            /**
             * Get selected shipping address
             *
             * @returns {string}
             */
            getSelectedShippingAddress: function () {
                return checkoutData.getSelectedShippingAddress();
            },

            /**
             * Set selected shipping address
             *
             * @param {string} data
             */
            setSelectedShippingAddress: function (data) {
                checkoutData.setSelectedShippingAddress(data);
            },

            /**
             * Get shipping address form data
             *
             * @returns {Object}
             */
            getShippingAddressFromData: function () {
                return checkoutData.getShippingAddressFromData();
            },

            /**
             * Set shipping address form data
             *
             * @param {Object} data
             */
            setShippingAddressFromData: function (data) {
                checkoutData.setShippingAddressFromData(data);
            },

            /**
             * Get new customer shipping address
             *
             * @returns {Object}
             */
            getNewCustomerShippingAddress: function () {
                return checkoutData.getNewCustomerShippingAddress();
            },

            /**
             * Set new customer shipping address
             *
             * @param {Object} data
             */
            setNewCustomerShippingAddress: function (data) {
                checkoutData.setNewCustomerShippingAddress(data);
            },

            /**
             * Get selected billing address
             *
             * @returns {string}
             */
            getSelectedBillingAddress: function () {
                return checkoutData.getSelectedBillingAddress();
            },

            /**
             * Set selected billing address
             *
             * @param {string} data
             */
            setSelectedBillingAddress: function (data) {
                checkoutData.setSelectedBillingAddress(data);
            },

            /**
             * Get billing address form data
             *
             * @returns {Object}
             */
            getBillingAddressFromData: function () {
                return checkoutData.getBillingAddressFromData();
            },

            /**
             * Set billing address form data
             *
             * @param {Object} data
             */
            setBillingAddressFromData: function (data) {
                checkoutData.setBillingAddressFromData(data);
            },

            /**
             * Get new customer billing address
             *
             * @returns {Object}
             */
            getNewCustomerBillingAddress: function () {
                return checkoutData.getNewCustomerBillingAddress();
            },


            /**
             * Set new customer billing address
             *
             * @param {Object} data
             */
            setNewCustomerBillingAddress: function (data) {
                checkoutData.setNewCustomerBillingAddress(data);
            },

            /**
             * Get same as shipping flag
             *
             * @returns {boolean}
             */
            getSameAsShippingFlag: function () {
                return getData().sameAsShippingFlag;
            },

            /**
             * Set same as shipping flag
             *
             * @param {boolean} flag
             */
            setSameAsShippingFlag: function (flag) {
                var obj = getData();

                obj.sameAsShippingFlag = flag;
                saveData(obj);
            },

            /**
             * Get selected shipping rate
             *
             * @returns {string}
             */
            getSelectedShippingRate: function() {
                return checkoutData.getSelectedShippingRate();
            },

            /**
             * Set selected shipping rate
             *
             * @param {string} data
             */
            setSelectedShippingRate: function (data) {
                checkoutData.setSelectedShippingRate(data);
            },

            /**
             * Get selected payment method
             *
             * @returns {string}
             */
            getSelectedPaymentMethod: function() {
                return checkoutData.getSelectedPaymentMethod();
            },

            /**
             * Set selected payment method
             *
             * @param {string} data
             */
            setSelectedPaymentMethod: function (data) {
                checkoutData.setSelectedPaymentMethod(data);
            },

            /**
             * Get validated email value
             *
             * @returns {string}
             */
            getValidatedEmailValue: function () {
                return checkoutData.getValidatedEmailValue();
            },

            /**
             * Set validated email value
             *
             * @param {string} email
             */
            setValidatedEmailValue: function (email) {
                checkoutData.setValidatedEmailValue(email);
            },

            /**
             * Get input field email value
             *
             * @returns {string}
             */
            getInputFieldEmailValue: function () {
                return checkoutData.getInputFieldEmailValue();
            },

            /**
             * Set input field email value
             *
             * @param {string} email
             */
            setInputFieldEmailValue: function (email) {
                checkoutData.setInputFieldEmailValue(email);
            },

            /**
             * Get verified is subscribed for newsletter flag
             *
             * @returns {boolean}
             */
            getVerifiedIsSubscribedFlag: function () {
                return getData().isSubscribedFlag;
            },

            /**
             * Set verified is subscribed for newsletter flag
             *
             * @param {boolean} flag
             */
            setVerifiedIsSubscribedFlag: function (flag) {
                var obj = getData();

                obj.isSubscribedFlag = flag;
                saveData(obj);
            },

            /**
             * Get order note
             *
             * @returns {string}
             */
            getOrderNote: function () {
                return getData().orderNote;
            },

            /**
             * Set order note
             *
             * @param {string} orderNote
             */
            setOrderNote: function (orderNote) {
                var obj = getData();

                obj.orderNote = orderNote;
                saveData(obj);
            },

            /**
             * Get delivery date form data
             *
             * @returns {Object}
             */
            getDeliveryDateFormData: function () {
                return getData().deliveryDateFormData;
            },

            /**
             * Set delivery date form data
             *
             * @param {Object} formData
             */
            setDeliveryDateFormData: function (formData) {
                var obj = getData();

                obj.deliveryDateFormData = formData;
                saveData(obj);
            },

        }
    }
);
