define([
    'jquery', 'ko', 'uiRegistry', 'Magento_Checkout/js/model/quote',
    'Magento_Checkout/js/model/address-converter',
    'Magento_Checkout/js/model/shipping-address/form-popup-state',
    'Magento_Checkout/js/model/shipping-rates-validator',
    'Magento_Checkout/js/model/shipping-service', 'mage/translate'
], function ($, ko, registry, quote, addressConverter, popup, ratesValidator, shippingService, $t) {
    'use strict';

    var fieldset = 'checkout.steps.shipping-step.shippingAddress.shipping-address-fieldset.',
        additional = 'checkout.steps.shipping-step.shippingAddress.shippingAdditional.',
        attribute = 'novaposhta_shipping_data',
        defaults = {
            area_novaposhta_field: '', area_novaposhta_field_label: '', city_novaposhta_field: '',
            novaposhta_warehouse: '', novaposhta_warehouse_label: '',
            novaposhta_street: '', novaposhta_street_label: '',
            novaposhta_building: '', novaposhta_flat: '', novaposhta_is_private_house: false
        },
        fields = ['country_id', 'region_id', 'city', 'area_novaposhta_field', 'city_novaposhta_field'],
        children = [
            'perspective_novaposhtashipping_warehouse', 'perspective_novaposhtashipping_warehouse_house_street',
            'perspective_novaposhtashipping_warehouse_house_build_num', 'perspective_novaposhtashipping_house_door_num',
            'perspective_novaposhtashipping_private_house'
        ],
        state = {
            restoring: false, inline: true, ready: ko.observable(false),
            formCountry: ko.observable(''), selectedData: ko.observable({}),
            popupVisible: popup.isVisible
        },
        provider, controls;

    state.isNp = ko.pureComputed(function () {
        var method = quote.shippingMethod(), address = quote.shippingAddress(),
            country = popup.isVisible() || state.inline ? state.formCountry() : address && address.countryId;
        return method && method.carrier_code === 'novaposhtashipping' && country === 'UA';
    });
    state.methodCode = ko.pureComputed(function () {
        return state.isNp() ? quote.shippingMethod().method_code : false;
    });
    state.complete = ko.pureComputed(function () {
        var data = state.selectedData(), method = state.methodCode();
        function valid(value) { return !!value && value !== '0' && value !== 'none'; }
        if (!valid(data.area_novaposhta_field) || !valid(data.city_novaposhta_field)) return false;
        if (method === 'c2w' || method === 'w2w') return valid(data.novaposhta_warehouse);
        return (method === 'c2c' || method === 'w2c') && valid(data.novaposhta_street) &&
            !!data.novaposhta_building && (!!data.novaposhta_flat || !!data.novaposhta_is_private_house);
    });
    state.showAdditional = ko.pureComputed(function () {
        return state.ready() && state.isNp() && !popup.isVisible() && state.inline;
    });
    state.needsNewAddress = ko.pureComputed(function () {
        return state.ready() && !state.inline && state.isNp() && !popup.isVisible() && !state.complete();
    });
    state.newAddressMessage = ko.pureComputed(function () {
        var method = state.methodCode();
        return method === 'c2w' || method === 'w2w'
            ? $t('Select an address with a Nova Poshta warehouse, or use "+ New Address" to create a new delivery address.')
            : $t('Select an address with a Nova Poshta street and house number, or use "+ New Address" to create a new delivery address.');
    });
    state.showDestination = ko.pureComputed(function () {
        return !state.inline && state.isNp() && !popup.isVisible() && state.complete();
    });
    state.destinationLabel = ko.pureComputed(function () {
        var data = state.selectedData(), method = state.methodCode();
        if (method === 'c2w' || method === 'w2w') return data.novaposhta_warehouse_label || '';
        return [data.novaposhta_street_label, data.novaposhta_building,
            data.novaposhta_is_private_house ? '' : data.novaposhta_flat].filter(Boolean).join(', ');
    });

    state.read = function (address) {
        var attrs = address && address.customAttributes || [], data;
        Object.keys(attrs).some(function (key) {
            var value = attrs[key];
            if (key !== attribute && (!value || value.attribute_code !== attribute)) return false;
            try { data = JSON.parse(typeof value === 'object' ? value.value : value); } catch (e) { data = null; }
            return true;
        });
        return data && typeof data === 'object' && !Array.isArray(data) ? data :
            (window.checkoutConfig.novaposhtaCustomerAddresses || {})[address && address.customerAddressId] || {};
    };

    state.fromForm = function () {
        var form = provider.get('shippingAddress'), data = {};
        Object.keys(defaults).forEach(function (key) { data[key] = form[key] === undefined ? defaults[key] : form[key]; });
        data.city = form.city || '';
        ['country_id', 'region_id', 'region_code', 'region'].forEach(function (key) { data[key] = form[key] || ''; });
        return data;
    };
    state.quoteAddress = function () {
        return addressConverter.formAddressDataToQuoteAddress(provider.get('shippingAddress'));
    };

    function stopFormRates(loading) {
        // Hidden form changes must not replace a saved address with a new quote address.
        clearTimeout(ratesValidator.validateAddressTimeout);
        clearTimeout(ratesValidator.validateZipCodeTimeout);
        ratesValidator.validateAddressTimeout = 0;
        ratesValidator.validateZipCodeTimeout = 0;
        shippingService.isLoading(loading);
    }
    state.attach = function () {
        var data = state.fromForm(), method = state.methodCode(), lines;
        if (method === 'c2w' || method === 'w2w') {
            data.novaposhta_street = '';
            data.novaposhta_street_label = '';
            data.novaposhta_building = '';
            data.novaposhta_flat = '';
            data.novaposhta_is_private_house = false;
            lines = [data.novaposhta_warehouse_label];
        } else {
            data.novaposhta_warehouse = '';
            data.novaposhta_warehouse_label = '';
            lines = [data.novaposhta_street_label, data.novaposhta_building,
                data.novaposhta_is_private_house ? '' : data.novaposhta_flat];
        }
        registry.get(fieldset + 'street').elems().forEach(function (field) {
            field.value(lines[field.index] || '');
        });
        var attributes = $.extend({}, provider.get('shippingAddress.custom_attributes'));
        attributes[attribute] = JSON.stringify(data);
        provider.set('shippingAddress.custom_attributes', attributes);
    };

    function hydrate(address) {
        if (!provider || state.inline || popup.isVisible() || !address) return;
        var loading = shippingService.isLoading(),
            data = state.read(address),
            form = $.extend(true, {}, addressConverter.quoteAddressToFormAddressData(address), defaults, data),
            selections = [
                [controls.area, data.area_novaposhta_field, data.area_novaposhta_field_label],
                [controls.city, data.city_novaposhta_field, data.city],
                [controls.warehouse, data.novaposhta_warehouse, data.novaposhta_warehouse_label],
                [controls.street, data.novaposhta_street, data.novaposhta_street_label]
            ];
        form.city = address.city || '';
        Object.keys(form).forEach(function (name) {
            if (form[name] === null || form[name] === undefined) form[name] = '';
        });
        form.custom_attributes = form.custom_attributes || {};
        form.street = form.street || {};
        registry.get(fieldset + 'street').elems().forEach(function (field) {
            form.street[field.index] = form.street[field.index] || '';
        });
        Object.keys(form.street).forEach(function (key) { form.street[key] = form.street[key] || ''; });
        state.restoring = true;
        [controls.city, controls.warehouse, controls.street, controls.building, controls.flat].forEach(function (control) {
            control._initializing = true;
        });
        selections.forEach(function (selection) {
            selection[0].setOptions(selection[1] ? [{value: selection[1], label: selection[2] || ''}] : []);
        });
        controls.area.areaRef(data.area_novaposhta_field || '');
        controls.city.areaRef(data.area_novaposhta_field || '');
        provider.set('shippingAddress', form);
        controls.street.cityRef(data.city_novaposhta_field || '');
        controls.warehouse.cityValue(data.city_novaposhta_field || '');
        [controls.city, controls.warehouse, controls.street, controls.building, controls.flat].forEach(function (control) {
            control._initializing = false;
        });
        state.selectedData(data);
        stopFormRates(loading);
        state.restoring = false;
    }

    state.init = function (shipping) {
        state.inline = shipping.isFormInline;
        registry.get(['checkoutProvider'].concat(fields.map(function (name) { return fieldset + name; }),
            children.map(function (name) { return additional + name; })), function (source, country, region, cityNative,
                area, city, warehouse, street, building, flat, privateHouse) {
            provider = source;
            controls = {area: area, city: city, warehouse: warehouse, street: street, building: building, flat: flat};
            state.formCountry(country.value());
            country.value.subscribe(state.formCountry);
            state.ready(true);
            quote.shippingAddress.subscribe(hydrate);
            popup.isVisible.subscribe(function (visible) {
                if (!visible) hydrate(quote.shippingAddress());
            });
            hydrate(quote.shippingAddress());
        });
    };
    return state;
});
