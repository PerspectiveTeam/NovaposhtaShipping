define(['jquery', 'uiRegistry'], function ($, registry) {
    'use strict';

    var selectedMethod = '';

    function getMethod() {
        var method = $('input[name="order[shipping_method]"]:checked').val();

        // Core removes the rate controls when an address changes.
        if (method) {
            selectedMethod = method;
        }
        return selectedMethod;
    }

    function selected(component) {
        var value = component && component.value();

        return value && value !== '0' ? value : '';
    }

    function isActive(type) {
        var method = getMethod();

        return $('[name="order[' + type + '_address][country_id]"]').val() === 'UA' &&
            (!method || method.indexOf('novaposhtashipping_') === 0);
    }

    function refresh() {
        ['Billing', 'Shipping'].forEach(function (suffix) {
            var type = suffix.toLowerCase(),
                area = registry.get('areaInputAutocomplete' + suffix),
                city = registry.get('cityInputAutocomplete' + suffix),
                locked = !isActive(type) ||
                    (type === 'shipping' && $('#order-shipping_same_as_billing').is(':checked'));

            ['area', 'city', 'street', 'warehouse'].forEach(function (field) {
                var component = registry.get(field + 'InputAutocomplete' + suffix),
                    billing = registry.get(field + 'InputAutocompleteBilling'),
                    control = $('[name="order[' + type + '_address][novaposhta_' + field + ']"]'),
                    parent = field === 'city' ? selected(area) : selected(city),
                    value,
                    label;

                if (!component) {
                    return;
                }
                if (billing && type === 'shipping' && $('#order-shipping_same_as_billing').is(':checked')) {
                    value = selected(billing);
                    label = billing.preselectedLabel();
                    component.preselectedValue(value);
                    component.preselectedLabel(label || '');
                    component.value(value);
                    if (control.length && control.val() !== value) {
                        control.empty().append(new Option(label || '', value, true, true)).trigger('change');
                    }
                }
                component.disabled(locked || ((field === 'street' || field === 'warehouse') && !parent));
                if (field === 'city') {
                    control.data('areaRef', parent);
                } else if (field !== 'area') {
                    control.data('cityRef', parent);
                }
                control.prop('disabled', component.disabled()).trigger('change.select2');
            });
        });
    }

    refresh.isActive = isActive;
    refresh.getMethod = getMethod;
    return refresh;
});
