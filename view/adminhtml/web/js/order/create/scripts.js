/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

define([
    'jquery',
    'ko',
    'Perspective_NovaposhtaShipping/js/order/create/address-state',
    'Magento_Ui/js/lib/knockout/bootstrap',
    'Magento_Ui/js/lib/view/utils/async'
], function ($, ko, refreshAddressState) {
    'use strict';

    var fields = '.field-novaposhtashipping_area, .field-novaposhtashipping_city, ' +
        '.field-novaposhtashipping_street, .field-novaposhtashipping_warehouse';

    function refreshNovaposhtaFields() {
        var method = refreshAddressState.getMethod(),
            isNovaposhta = method.indexOf('novaposhtashipping_') === 0,
            isWarehouse = /_(c2w|w2w)$/.test(method);

        ['billing', 'shipping'].forEach(function (type) {
            var container = $('#order-' + type + '_address'),
                visible = container.find('[name="order[' + type + '_address][country_id]"]').val() === 'UA' &&
                    (!method || isNovaposhta);

            container.find('.field-novaposhtashipping_area, .field-novaposhtashipping_city').toggle(visible);
            container.find('.field-novaposhtashipping_warehouse').toggle(visible && (!method || isWarehouse));
            container.find('.field-novaposhtashipping_street').toggle(visible && (!method || !isWarehouse));
            container.find('[name="order[' + type + '_address][city]"]').closest('.admin__field').toggle(!visible);
        });
        refreshAddressState();
    }

    var scopes = fields.split(', ').map(function (field) {
        return '#order-data ' + field + ' [data-bind^="scope:"]';
    }).join(', ');

    $.async({selector: scopes}, function (element) {
        if (!ko.contextFor(element)) {
            $(element).applyBindings();
        }
        refreshNovaposhtaFields();
    });
    $(document).on('change', 'input[name="order[shipping_method]"]', refreshNovaposhtaFields);
    $(document).on('change', '[name="order[billing_address][country_id]"], ' +
        '[name="order[shipping_address][country_id]"], #order-shipping_same_as_billing', refreshNovaposhtaFields);
    $(refreshNovaposhtaFields);
});
