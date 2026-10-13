const {test} = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const vm = require('node:vm');
const directory = path.resolve(__dirname, '../../view/frontend/web/js/mixin');

function load(name, dependencies) {
    let result;
    vm.runInNewContext(fs.readFileSync(path.join(directory, name), 'utf8'), {
        define: (names, factory) => {result = factory(...names.map(n => dependencies[n]));},
        window: {},
    });
    return result;
}

const wrapper = {wrap: (original, around) => (...args) => around(original, ...args)};

test('standard carrier forwards the action without reading or modifying addresses', () => {
    const quote = {
        shippingMethod: () => ({carrier_code: 'flatrate', method_code: 'c2w'}),
        shippingAddress: () => {throw new Error('Standard address must remain untouched');},
        billingAddress: () => {throw new Error('Billing address must remain untouched');},
    };
    const mixin = load('set-shipping-information-mixin.js', {
        'mage/utils/wrapper': wrapper,
        'Magento_Checkout/js/model/quote': quote,
        uiRegistry: {get: () => undefined},
        postbox: {subscribe() {}},
    });
    const response = {};
    assert.equal(mixin(() => response)(), response);
});

test('Nova Poshta augments shipping attributes and preserves a separate billing address', () => {
    const callbacks = {};
    const shipping = {extension_attributes: {other_module: 'preserved'}};
    const billing = {street: ['Billing street'], extension_attributes: {other_module: 'billing'}};
    const quote = {
        shippingMethod: () => ({carrier_code: 'novaposhtashipping', method_code: 'c2w'}),
        shippingAddress: () => shipping,
        billingAddress: () => {throw new Error('Separate billing address must remain untouched');},
    };
    const mixin = load('set-shipping-information-mixin.js', {
        'mage/utils/wrapper': wrapper,
        'Magento_Checkout/js/model/quote': quote,
        uiRegistry: {get: () => undefined},
        postbox: {subscribe: (name, callback) => {callbacks[name] = callback;}},
    });
    callbacks.selectedCityPost('selected-city');
    let calls = 0;
    mixin(() => {calls++;})();
    assert.equal(calls, 1);
    assert.equal(shipping.extension_attributes.perspective_novaposhta_shipping_city, 'selected-city');
    assert.equal(shipping.extension_attributes.other_module, 'preserved');
    assert.deepEqual(billing.street, ['Billing street']);
});

test('admin binds existing and subsequently inserted NP scopes without overriding core methods', () => {
    const calls = [];
    const bound = new Set();
    const existing = {};
    let observer;
    function AdminOrder() {}
    const coreResponse = () => {};
    const coreCustomer = () => {};
    const coreSameAsBilling = () => {};
    AdminOrder.prototype.loadAreaResponseHandler = coreResponse;
    AdminOrder.prototype.setCustomerAfter = coreCustomer;
    AdminOrder.prototype.setShippingAsBilling = coreSameAsBilling;
    const $ = selector => {
        if (typeof selector === 'function') {selector();return;}
        return {
            on() {},
            find: () => ({val: () => 'UA', toggle() {}, closest: () => ({toggle() {}})}),
            applyBindings() {calls.push(selector);bound.add(selector);},
        };
    };
    $.async = (options, callback) => {
        assert.equal(options.selector.includes('#order-data .field-novaposhtashipping_area'), true);
        observer = callback;
        callback(existing);
    };
    vm.runInNewContext(fs.readFileSync(path.resolve(directory,
        '../../../../adminhtml/web/js/order/create/scripts.js'), 'utf8'), {
        define: (names, factory) => factory($, {contextFor: element => bound.has(element)},
            Object.assign(() => {}, {getMethod: () => 'flatrate_flatrate'})),
        AdminOrder, document: {},
    });
    assert.equal(AdminOrder.prototype.loadAreaResponseHandler, coreResponse);
    assert.equal(AdminOrder.prototype.setCustomerAfter, coreCustomer);
    assert.equal(AdminOrder.prototype.setShippingAsBilling, coreSameAsBilling);
    assert.deepEqual(calls, [existing]);
    observer(existing);
    assert.equal(calls.length, 1);
    const inserted = {};
    observer(inserted);
    assert.deepEqual(calls, [existing, inserted]);
});

test('region mixin leaves billing and other region components untouched', () => {
    const mixin = load('region-select-mixin.js', {
        postbox: {subscribe() {throw new Error('Billing must not subscribe to Nova Poshta events');}},
        uiRegistry: {get() {throw new Error('Billing must not inspect shipping components');}},
    });
    const methods = mixin({extend: definition => definition});
    const billing = {
        name: 'checkout.steps.billing-step.payment.payments-list.checkmo-form.form-fields.region_id',
        _super() {},
        value: {subscribe() {throw new Error('Billing must not publish shipping region changes');}},
    };
    assert.equal(methods.initialize.call(billing), billing);
    assert.equal(methods.initObservable.call(billing), billing);
});


test('restored NP city and area are read from current fields even without selection events', () => {
    const shipping = {city: '', extension_attributes: {custom: true}};
    const mixin = load('set-shipping-information-mixin.js', {
        'mage/utils/wrapper': wrapper,
        'Magento_Checkout/js/model/quote': {
            shippingMethod: () => ({carrier_code: 'novaposhtashipping', method_code: 'c2c'}),
            shippingAddress: () => shipping,
            billingAddress: () => {throw new Error('Separate billing must remain untouched');},
        },
        uiRegistry: {get: name => name.endsWith('city_novaposhta_field') ? {
            hasValue: () => true, value: () => 'restored-city', getPreview: () => 'Київ',
        } : {areaRef: () => 'restored-area'}},
        postbox: {subscribe() {}},
    });
    mixin(() => {})();
    assert.equal(shipping.city, 'Київ');
    assert.equal(shipping.extension_attributes.perspective_novaposhta_shipping_city, 'restored-city');
    assert.equal(shipping.extension_attributes.perspective_novaposhta_shipping_area, 'restored-area');
    assert.equal(shipping.extension_attributes.custom, true);
});

for (const method of ['c2w', 'c2c']) {
    test('restored ' + method + ' delivery sends current destination fields without selection events', () => {
        const shipping = {extension_attributes: {custom: 'retained'}};
        const controls = {
            area_novaposhta_field: {areaRef: () => 'area-ref'},
            city_novaposhta_field: {hasValue: () => true, value: () => 'city-ref', getPreview: () => 'Київ'},
            perspective_novaposhtashipping_warehouse: {value: () => 'warehouse-ref'},
            perspective_novaposhtashipping_warehouse_house_street: {value: () => 'street-ref'},
            perspective_novaposhtashipping_warehouse_house_build_num: {value: () => '10'},
            perspective_novaposhtashipping_house_door_num: {value: () => '1'},
            perspective_novaposhtashipping_private_house: {isPrivateHouse: () => true},
        };
        const mixin = load('set-shipping-information-mixin.js', {
            'mage/utils/wrapper': wrapper,
            'Magento_Checkout/js/model/quote': {
                shippingMethod: () => ({carrier_code: 'novaposhtashipping', method_code: method}),
                shippingAddress: () => shipping,
                billingAddress: () => {throw new Error('Billing address must remain untouched');},
            },
            uiRegistry: {get: name => controls[name.split('.').at(-1)]},
            postbox: {subscribe() {}},
        });
        mixin(() => {})();
        const attrs = shipping.extension_attributes;
        assert.equal(attrs.perspective_novaposhta_shipping_city, 'city-ref');
        assert.equal(attrs.perspective_novaposhta_shipping_warehouse, method === 'c2w' ? 'warehouse-ref' : '');
        assert.equal(attrs.perspective_novaposhta_shipping_street, method === 'c2c' ? 'street-ref' : '');
        assert.equal(attrs.perspective_novaposhta_shipping_building, method === 'c2c' ? '10' : '');
        assert.equal(attrs.perspective_novaposhta_shipping_flat, method === 'c2c' ? '1' : '');
        assert.equal(attrs.perspective_novaposhta_shipping_is_private_house, method === 'c2c');
        assert.equal(attrs.custom, 'retained');
    });
}
