const {test} = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const vm = require('node:vm');

function fixture({country = 'UA', quoteCountry = 'US', loading = false, selected = 'novaposhtashipping_c2w', inline = true, virtual = false, active = true} = {}) {
    const saved = [];
    const selectedAddresses = [];
    const selectedMethods = [];
    let onAddress;
    let onLoading;
    let disposed = 0;
    let countryValue = quoteCountry;
    const provider = {
        address: {country_id: country, region_id: '1095', city: '', street: ['']},
        get: () => provider.address,
        on(name, callback) {assert.equal(name, 'shippingAddress');onAddress = callback;},
    };
    const isLoading = () => loading;
    isLoading.subscribe = callback => {onLoading = callback;return {dispose() {disposed++;onLoading = undefined;}};};
    const dependencies = {
        'Magento_Checkout/js/model/quote': {
            isVirtual: () => virtual,
            shippingAddress: () => ({countryId: countryValue}),
            shippingMethod: value => selectedMethods.push(value),
        },
        uiRegistry: {get(names, callback) {callback(provider);}},
        'Magento_Checkout/js/checkout-data': {
            setShippingAddressFromData: address => saved.push(structuredClone(address)),
            getSelectedShippingRate: () => selected,
        },
        'Magento_Checkout/js/model/address-converter': {formAddressDataToQuoteAddress: data => ({countryId: data.country_id})},
        'Magento_Checkout/js/action/select-shipping-address': address => {selectedAddresses.push(address);countryValue = address.countryId;},
        'Magento_Checkout/js/model/shipping-service': {isLoading},
    };
    let mixin;
    vm.runInNewContext(fs.readFileSync(path.resolve(__dirname, '../../view/frontend/web/js/mixin/shipping-mixin.js'), 'utf8'), {
        define: (names, factory) => {mixin = factory(...names.map(name => dependencies[name]));},
        window: {checkoutConfig: {activeCarriers: active ? ['novaposhtashipping'] : []}},
    });
    const methods = mixin({extend: value => value});
    const instance = {_super() {}, isFormInline: inline};
    assert.equal(methods.initialize.call(instance), instance);
    return {saved, selectedAddresses, selectedMethods, get disposed() {return disposed;},
        finishRates() {loading = false;onLoading?.(false);},
        edit(values) {Object.assign(provider.address, values);onAddress?.(provider.address);},
    };
}

test('initial Ukrainian form reconciles the quote country and allows the saved NP method to resolve', () => {
    const state = fixture();
    assert.equal(state.selectedAddresses.length, 1);
    assert.equal(state.selectedAddresses[0].countryId, 'UA');
    assert.deepEqual(state.selectedMethods, [null]);
    assert.equal(state.saved[0].country_id, 'UA');
});

test('initial rate request finishes before country reconciliation, preventing its stale result from replacing NP rates', () => {
    const state = fixture({loading: true});
    assert.equal(state.selectedAddresses.length, 0);
    state.finishRates();
    assert.equal(state.selectedAddresses.length, 1);
    assert.equal(state.disposed, 1);
    state.finishRates();
    assert.equal(state.selectedAddresses.length, 1);
});

test('a Ukrainian draft survives without a street and records region and city changes', () => {
    const state = fixture({quoteCountry: 'UA'});
    state.edit({city: 'Київ', city_novaposhta_field: 'city-ref', area_novaposhta_field: 'area-ref'});
    const last = state.saved.at(-1);
    assert.equal(last.street[0], '');
    assert.equal(last.city, 'Київ');
    assert.equal(last.city_novaposhta_field, 'city-ref');
    assert.equal(last.area_novaposhta_field, 'area-ref');
    assert.equal(state.selectedAddresses.length, 0);
});

test('switching away from Ukraine updates the draft instead of restoring Ukraine on refresh', () => {
    const state = fixture({quoteCountry: 'UA'});
    state.edit({country_id: 'US', region_id: '12', city: 'San Francisco'});
    state.edit({region_id: '13'});
    assert.equal(state.saved.at(-1).country_id, 'US');
    assert.equal(state.saved.at(-1).region_id, '13');
});

test('country reconciliation preserves an explicitly selected standard shipping method', () => {
    const state = fixture({selected: 'flatrate_flatrate'});
    assert.equal(state.selectedAddresses.length, 1);
    assert.equal(state.selectedMethods.length, 0);
});

for (const [name, options] of [
    ['a non-Ukrainian form', {country: 'US'}],
    ['a selected customer address', {inline: false}],
    ['a virtual quote', {virtual: true}],
    ['a disabled Nova Poshta carrier', {active: false}],
]) {
    test(name + ' keeps the standard address initialization unchanged', () => {
        const state = fixture(options);
        assert.equal(state.saved.length, 0);
        assert.equal(state.selectedAddresses.length, 0);
        assert.equal(state.selectedMethods.length, 0);
    });
}
