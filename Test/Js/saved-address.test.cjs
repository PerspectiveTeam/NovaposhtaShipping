const {test} = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const vm = require('node:vm');

function observable(value) {
    const listeners = [];
    function result(next) {
        if (!arguments.length) return value;
        if (value !== next) {value = next;listeners.forEach(listener => listener(next));}
        return result;
    }
    result.subscribe = listener => {listeners.push(listener);return {dispose() {}};};
    return result;
}
function extend(...args) {
    if (args[0] === true) args.shift();
    return Object.assign(...args);
}
const data = {
    area_novaposhta_field: 'area-a', area_novaposhta_field_label: 'Київська',
    city_novaposhta_field: 'city-a', city: 'Київ',
    novaposhta_street: 'street-a', novaposhta_street_label: 'вул. Хрещатик',
    novaposhta_building: '10', novaposhta_flat: '1',
};
function address(id = 1, city = 'Київ') {
    return {customerAddressId: id, countryId: 'UA', city, street: ['Original saved street', '10', '1'],
        customAttributes: [], getType: () => 'customer-address', getKey: () => 'customer-address' + id};
}
function fixture(metadata = data, cachedDraft = null, inline = false) {
    const first = address();
    const quote = {shippingAddress: observable(first), shippingMethod: observable({carrier_code: 'novaposhtashipping', method_code: 'c2c'})};
    const popup = {isVisible: observable(false)};
    let cached = cachedDraft, onProvider;
    function control() {return {value: observable(''), areaRef: observable(''), cityRef: observable(''), cityValue: observable(''),
        setOptions(options) {this.options = options;}};}
    const controls = Array.from({length: 10}, control);
    const nativeStreet = [0, 1, 2].map(index => ({index, value: observable('')}));
    controls[0].value('UA');
    let form = {};
    const provider = {
        get(name) {return name === 'shippingAddress' ? form : form[name.split('.').slice(1).join('.')];},
        set(name, value) {
            if (name === 'shippingAddress') {form = value;controls[0].value(form.country_id);}
            else form[name.split('.').slice(1).join('.')] = value;
            onProvider?.();
        },
        on(name, listener) {onProvider = listener;},
    };
    const dependencies = {
        jquery: {extend},
        'mage/translate': value => value,
        ko: {observable, observableArray: observable, pureComputed: callback => {callback.subscribe = () => {};return callback;}},
        uiRegistry: {get(names, callback) {
            if (!Array.isArray(names)) return {elems: () => nativeStreet};
            callback(provider, ...controls);
        }, async: () => callback => callback({visible() {}})},
        'Magento_Checkout/js/model/quote': quote,
        'Magento_Checkout/js/checkout-data': {getShippingAddressFromData: () => cached, setShippingAddressFromData: value => {cached = value;},setNewCustomerShippingAddress() {}},
        'Magento_Checkout/js/model/address-converter': {quoteAddressToFormAddressData: a => ({
            country_id: a.countryId, city: a.city, company: null, street: {0: a.street[0], 1: a.street[1], 2: a.street[2]},
        })},
        'Magento_Checkout/js/model/shipping-address/form-popup-state': popup,
        'Magento_Checkout/js/model/shipping-rates-validator': {},
        'Magento_Checkout/js/model/shipping-service': {isLoading: observable(false)},
    };
    let state;
    vm.runInNewContext(fs.readFileSync(path.resolve(__dirname, '../../view/frontend/web/js/model/checkout-address-state.js'), 'utf8'), {
        clearTimeout() {},
        define: (names, factory) => {state = factory(...names.map(name => dependencies[name]));},
        window: {checkoutConfig: {novaposhtaCustomerAddresses: {1: metadata, 2: {
            area_novaposhta_field: 'area-b', city_novaposhta_field: 'city-b', city: 'Львів',
        }}}},
    });
    state.init({isFormInline: inline});
    return {state, quote, popup, first, provider, controls, nativeStreet, get cached() {return cached;}};
}

test('a saved address with complete street data hides the additional editor and hydrates NP fields', () => {
    const {state, provider, controls, first} = fixture();
    assert.equal(state.complete(), true);
    assert.equal(state.showAdditional(), false);
    assert.equal(provider.get('shippingAddress.city_novaposhta_field'), 'city-a');
    assert.equal(controls[6].cityRef(), 'city-a');
    assert.deepEqual(first.street, ['Original saved street', '10', '1']);
    assert.equal(provider.get('shippingAddress.company'), '');
});

test('a saved street address requires the new address flow when warehouse delivery is chosen', () => {
    const {state, quote, controls} = fixture();
    quote.shippingMethod({carrier_code: 'novaposhtashipping', method_code: 'c2w'});
    assert.equal(state.complete(), false);
    assert.equal(state.showAdditional(), false);
    assert.equal(state.needsNewAddress(), true);
    assert.match(state.newAddressMessage(), /warehouse/);
    assert.equal(controls[5].cityValue(), 'city-a');
});

test('a saved warehouse address requires a street address through the popup for address delivery', () => {
    const {state, popup} = fixture({area_novaposhta_field: 'area-a', city_novaposhta_field: 'city-a',
        novaposhta_warehouse: 'warehouse-a'});
    assert.equal(state.needsNewAddress(), true);
    assert.equal(state.showAdditional(), false);
    assert.match(state.newAddressMessage(), /street and house number/);
    popup.isVisible(true);
    assert.equal(state.needsNewAddress(), false);
});

test('guest inline checkout retains its destination fields without a new address prompt', () => {
    const {state} = fixture({}, null, true);
    assert.equal(state.showAdditional(), true);
    assert.equal(state.needsNewAddress(), false);
});

test('old saved-address overrides cannot silently change the selected destination', () => {
    const {state, quote, provider} = fixture(data, {novaposhta_address_overrides: {
        'customer-address1': {novaposhta_warehouse: 'old-override', city: 'Different city'},
    }});
    quote.shippingMethod({carrier_code: 'novaposhtashipping', method_code: 'c2w'});
    assert.equal(state.needsNewAddress(), true);
    assert.equal(provider.get('shippingAddress.novaposhta_warehouse'), '');
    assert.equal(provider.get('shippingAddress.city'), 'Київ');
});

test('switching saved addresses clears fields that belonged to the previous address', () => {
    const {state, quote, provider} = fixture({...data, novaposhta_warehouse: 'warehouse-a'});
    quote.shippingAddress(address(2, 'Львів'));
    assert.equal(state.selectedData().city_novaposhta_field, 'city-b');
    assert.equal(provider.get('shippingAddress.novaposhta_warehouse'), '');
    assert.equal(provider.get('shippingAddress.novaposhta_street'), '');
    assert.equal(state.complete(), false);
});

test('popup edits do not update the selected address and Cancel hydrates its original NP data', () => {
    const {state, popup, provider, first} = fixture();
    popup.isVisible(true);
    provider.set('shippingAddress.city_novaposhta_field', 'different-city');
    assert.equal(state.showAdditional(), false);
    assert.equal(first.city, 'Київ');
    popup.isVisible(false);
    assert.equal(provider.get('shippingAddress.city_novaposhta_field'), 'city-a');
    assert.equal(state.complete(), true);
});

test('a new popup address carries NP metadata while retaining other custom attributes', () => {
    const {state, provider} = fixture();
    provider.set('shippingAddress.custom_attributes', {another_module: 'retained'});
    state.attach();
    const attributes = provider.get('shippingAddress.custom_attributes');
    assert.equal(attributes.another_module, 'retained');
    assert.equal(JSON.parse(attributes.novaposhta_shipping_data).novaposhta_street, 'street-a');
});

test('saving a warehouse popup replaces native street lines and drops stale street metadata', () => {
    const {state, nativeStreet, quote, provider} = fixture({...data,
        novaposhta_warehouse: 'warehouse-a', novaposhta_warehouse_label: 'Warehouse destination'});
    quote.shippingMethod({carrier_code: 'novaposhtashipping', method_code: 'c2w'});
    nativeStreet[0].value('Old street');
    nativeStreet[1].value(undefined);
    nativeStreet[2].value(null);
    state.attach();
    assert.equal(nativeStreet[0].value(), 'Warehouse destination');
    assert.equal(nativeStreet[1].value(), '');
    assert.equal(nativeStreet[2].value(), '');
    const metadata = JSON.parse(provider.get('shippingAddress.custom_attributes').novaposhta_shipping_data);
    assert.equal(metadata.novaposhta_street, '');
    assert.equal(metadata.novaposhta_building, '');
    assert.equal(metadata.novaposhta_flat, '');
    assert.equal(metadata.novaposhta_is_private_house, false);
});

test('saving a street popup drops a previous warehouse and builds the new native address', () => {
    const {state, provider, nativeStreet} = fixture({...data, novaposhta_warehouse: 'old-warehouse',
        novaposhta_warehouse_label: 'Old warehouse'});
    state.attach();
    const metadata = JSON.parse(provider.get('shippingAddress.custom_attributes').novaposhta_shipping_data);
    assert.equal(metadata.novaposhta_warehouse, '');
    assert.equal(metadata.novaposhta_warehouse_label, '');
    assert.deepEqual(nativeStreet.map(field => field.value()), ['вул. Хрещатик', '10', '1']);
});

test('standard shipping never renders NP controls', () => {
    const {state, quote} = fixture();
    quote.shippingMethod({carrier_code: 'flatrate', method_code: 'c2c'});
    assert.equal(state.isNp(), false);
    assert.equal(state.showAdditional(), false);
});

test('incomplete or placeholder refs require creating an address in the popup', () => {
    const {state} = fixture({...data, city_novaposhta_field: '0'});
    assert.equal(state.complete(), false);
    assert.equal(state.showAdditional(), false);
    assert.equal(state.needsNewAddress(), true);
});

test('a private house is complete without an apartment number', () => {
    const {state} = fixture({...data, novaposhta_flat: '', novaposhta_is_private_house: true});
    assert.equal(state.complete(), true);
});

function payloadMixin(method, values) {
    let mixin;
    vm.runInNewContext(fs.readFileSync(path.resolve(__dirname, '../../view/frontend/web/js/mixin/shipping-payload-mixin.js'), 'utf8'), {
        define: (names, factory) => {mixin = factory({extend}, {wrap: (original, around) => payload => around(original, payload)},
            {shippingMethod: () => method}, {ready: () => true, fromForm: () => values, quoteAddress: () => ({})});},
    });
    return mixin(value => value);
}

test('NP payload uses the chosen destination without mutating the saved address or billing address', () => {
    const saved = {city: 'Київ', street: ['Original saved street'], extension_attributes: {other_module: true}};
    const billing = {street: ['Separate billing street']};
    const payload = {addressInformation: {shipping_address: saved, billing_address: billing}};
    const result = payloadMixin({carrier_code: 'novaposhtashipping', method_code: 'c2w'}, {
        city: 'Київ', novaposhta_warehouse_label: 'Відділення №1',
    })(payload);
    assert.equal(result.addressInformation.shipping_address.street[0], 'Відділення №1');
    assert.deepEqual(saved.street, ['Original saved street']);
    assert.equal(result.addressInformation.billing_address, billing);
    assert.equal(result.addressInformation.shipping_address.extension_attributes.other_module, true);
});

test('switching to a standard carrier removes only NP payload attributes and preserves its native destination', () => {
    const saved = {street: ['Original saved street'], extension_attributes: {
        perspective_novaposhta_shipping_city: 'city-a', another_module: 'retained',
    }};
    const result = payloadMixin({carrier_code: 'flatrate', method_code: 'flatrate'}, {})({addressInformation: {shipping_address: saved}});
    assert.deepEqual(Object.keys(result.addressInformation.shipping_address.extension_attributes), ['another_module']);
    assert.equal(result.addressInformation.shipping_address.street[0], 'Original saved street');
    assert.equal(saved.extension_attributes.perspective_novaposhta_shipping_city, 'city-a');
});

test('other modules retain their shippingAdditional elements outside the NP popup', () => {
    let definition;
    vm.runInNewContext(fs.readFileSync(path.resolve(__dirname, '../../view/frontend/web/js/view/checkout/shipping/additional.js'), 'utf8'), {
        define: (names, factory) => {definition = factory({extend: value => value}, {});},
    });
    const np = {name: 'shippingAdditional.perspective_novaposhtashipping_warehouse'};
    const other = {name: 'shippingAdditional.other_module_delivery_note'};
    const instance = {elems: () => [np, other]};
    assert.equal(definition.novaposhtaElements.call(instance)[0], np);
    assert.equal(definition.otherElements.call(instance)[0], other);
});

test('the new address action delegates to the Magento shipping popup', () => {
    let definition, opened = 0;
    vm.runInNewContext(fs.readFileSync(path.resolve(__dirname, '../../view/frontend/web/js/view/checkout/shipping/additional.js'), 'utf8'), {
        define: (names, factory) => {definition = factory({extend: value => value}, {}, {
            get(name) {
                assert.equal(name, 'checkout.steps.shipping-step.shippingAddress');
                return {showFormPopUp() {opened++;}};
            },
        });},
    });
    definition.openNewAddress();
    assert.equal(opened, 1);
});

test('Next blocks an incompatible NP address before hidden-field validation and preserves standard checkout', () => {
    let mixin, carrier = 'novaposhtashipping', calls = 0, message;
    vm.runInNewContext(fs.readFileSync(path.resolve(__dirname, '../../view/frontend/web/js/mixin/shipping-mixin.js'), 'utf8'), {
        define: (names, factory) => {mixin = factory(...names.map(name => ({
            'Magento_Checkout/js/model/quote': {shippingMethod: () => ({carrier_code: carrier, method_code: 'c2c'})},
            'Perspective_NovaposhtaShipping/js/model/checkout-address-state': {
                needsNewAddress: () => true, newAddressMessage: () => 'Use + New Address to create an address',
            },
        })[name]));},
    });
    const methods = mixin({extend: value => value});
    const instance = {_super() {calls++;return true;}, errorValidationMessage(value) {message = value;},
        validateNpAreaField() {throw new Error('No validation of hidden fields');}};
    assert.equal(methods.validateShippingInformation.call(instance), false);
    assert.match(message, /New Address/);
    assert.equal(calls, 0);
    carrier = 'flatrate';
    assert.equal(methods.validateShippingInformation.call(instance), true);
    assert.equal(calls, 1);
});

for (const invalid of [false, true]) {
    test('confirming an NP popup clears the old message only when native validation succeeds: invalid=' + invalid, () => {
        let mixin, message = 'Use + New Address', attached = 0;
        vm.runInNewContext(fs.readFileSync(path.resolve(__dirname, '../../view/frontend/web/js/mixin/shipping-mixin.js'), 'utf8'), {
            define: (names, factory) => {mixin = factory(...names.map(name => ({
                'Magento_Checkout/js/model/quote': {shippingMethod: () => ({method_code: 'c2w'})},
                'Perspective_NovaposhtaShipping/js/model/checkout-address-state': {
                    isNp: () => true, attach() {attached++;},
                },
            })[name]));},
        });
        const methods = mixin({extend: value => value});
        const instance = {_super: () => 'native result', source: {get: () => invalid},
            validateNpAreaField: () => true, validateNpCityField: () => true, validateNpWarehouseField: () => true,
            errorValidationMessage(value) {message = value;}};
        assert.equal(methods.saveNewAddress.call(instance), 'native result');
        assert.equal(attached, 1);
        assert.equal(message, invalid ? 'Use + New Address' : false);
    });
}

test('warehouse searches use the current city and remain usable after restoring a selected option', () => {
    let definition, options, selected;
    const jq = {select2(value) {options = value;return this;},on(name, callback) {selected = callback;return this;},
        parent() {return this;},width: () => 500};
    const jquery = () => jq;jquery.mage = {__: value => value};
    vm.runInNewContext(fs.readFileSync(path.resolve(__dirname, '../../view/frontend/web/js/view/checkout/shipping/warehouse/warehouse.js'), 'utf8'), {
        define: (names, factory) => {definition = factory(...names.map(name => {
            if (name === 'jquery') return jquery;
            if (name === 'Magento_Ui/js/form/element/select') return {extend: value => value};
            if (name === 'mage/url') return {build: value => value};
        }));},
    });
    let city = 'city-a';
    const instance = {options: () => [{value: 'warehouse-a', label: 'Warehouse A'}], value: () => 'warehouse-a',
        cityValue: () => city, setOptions(value) {this.restored = value;}};
    definition.select2.call(instance, {});
    assert.equal(JSON.parse(options.ajax.data()).term, 'city-a');
    city = 'city-b';
    assert.equal(JSON.parse(options.ajax.data()).term, 'city-b');
    const results = options.ajax.processResults(JSON.stringify([{value:'one',label:'Відділення №1'},{value:'two',label:'Відділення №2'}]),{term:'№2'}).results;
    assert.equal(results.length, 1);
    assert.equal(results[0].id, 'two');
    selected({params:{data:{id:'two',text:'Відділення №2'}}});
    assert.equal(instance.restored[0].value, 'two');
});

test('a new popup address uses its metadata instead of stale overrides belonging to an older new address', () => {
    const {state, quote, provider} = fixture(data, {novaposhta_address_overrides: {
        'new-customer-address': {city_novaposhta_field: '0', novaposhta_warehouse: ''},
    }});
    quote.shippingMethod({carrier_code:'novaposhtashipping',method_code:'c2w'});
    const fresh = {...address(undefined, 'Львів'), getType: () => 'new-customer-address', getKey: () => 'new-customer-address',
        customAttributes: [{attribute_code:'novaposhta_shipping_data',value:JSON.stringify({
            area_novaposhta_field:'area-b',city_novaposhta_field:'city-b',city:'Львів',
            novaposhta_warehouse:'warehouse-b',novaposhta_warehouse_label:'Відділення №1',
        })}]};
    quote.shippingAddress(fresh);
    assert.equal(state.complete(), true);
    assert.equal(provider.get('shippingAddress.novaposhta_warehouse'), 'warehouse-b');
});
