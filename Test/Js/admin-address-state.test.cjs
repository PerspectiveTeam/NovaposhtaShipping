const {test} = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const vm = require('node:vm');

function fixture({sameAsBilling = false, country = 'UA', area = '', city = '', method = ''} = {}) {
    const components = {};
    const controls = {};
    for (const suffix of ['Billing', 'Shipping']) {
        for (const field of ['area', 'city', 'street', 'warehouse']) {
            let disabled;
            let value = field === 'area' ? area : field === 'city' ? city : '';
            let label = '';
            components[field + 'InputAutocomplete' + suffix] = {
                value(next) {if (arguments.length) value = next;return value;},
                preselectedValue() {},
                preselectedLabel(next) {if (arguments.length) label = next;return label;},
                disabled(value) {
                    if (arguments.length) disabled = value;
                    return disabled;
                },
            };
        }
    }
    const $ = selector => {
        if (selector.includes('[shipping_method]')) return {val: () => method};
        if (selector === '#order-shipping_same_as_billing') return {is: () => sameAsBilling};
        if (selector.includes('[country_id]')) return {val: () => country};
        return controls[selector] ||= {
            data(key, value) {this[key] = value;return this;},
            prop(key, value) {this[key] = value;return this;},
            trigger() {return this;},
        };
    };
    let refresh;
    vm.runInNewContext(fs.readFileSync(path.resolve(__dirname,
        '../../view/adminhtml/web/js/order/create/address-state.js'), 'utf8'), {
        define: (names, factory) => {refresh = factory($, {get: name => components[name]});},
    });
    refresh();
    return {components, controls, refresh,
        setSameAsBilling(value) {sameAsBilling = value;refresh();},
        setMethod(value) {method = value;refresh();},
    };
}

test('blank Ukrainian address enables region and city independently; street and warehouse wait for city', () => {
    const {components} = fixture();
    for (const suffix of ['Billing', 'Shipping']) {
        assert.equal(components['areaInputAutocomplete' + suffix].disabled(), false);
        assert.equal(components['cityInputAutocomplete' + suffix].disabled(), false);
        for (const field of ['street', 'warehouse']) {
            assert.equal(components[field + 'InputAutocomplete' + suffix].disabled(), true);
        }
    }
});

test('selected region enables city, selected city enables street and warehouse', () => {
    const {components} = fixture({area: 'area-ref'});
    assert.equal(components.cityInputAutocompleteBilling.disabled(), false);
    assert.equal(components.streetInputAutocompleteBilling.disabled(), true);
    const selected = fixture({area: 'area-ref', city: 'city-ref'});
    assert.equal(selected.components.streetInputAutocompleteBilling.disabled(), false);
    assert.equal(selected.components.warehouseInputAutocompleteBilling.disabled(), false);
    assert.equal(selected.controls['[name="order[billing_address][novaposhta_city]"]'].areaRef, 'area-ref');
    assert.equal(selected.controls['[name="order[billing_address][novaposhta_warehouse]"]'].cityRef, 'city-ref');
});

test('same-as-billing locks only shipping controls and leaves billing autocomplete usable', () => {
    const {components} = fixture({sameAsBilling: true, area: 'area-ref', city: 'city-ref'});
    for (const field of ['area', 'city', 'street', 'warehouse']) {
        assert.equal(components[field + 'InputAutocompleteBilling'].disabled(), false);
        assert.equal(components[field + 'InputAutocompleteShipping'].disabled(), true);
    }
});

test('non-Ukrainian addresses do not enable Nova Poshta controls', () => {
    const {components} = fixture({country: 'US', area: 'area-ref', city: 'city-ref'});
    assert.equal(Object.values(components).every(component => component.disabled()), true);
});

test('replaced address elements recover parent refs from surviving UI component values', () => {
    const {controls, refresh} = fixture({area: 'area-ref', city: 'city-ref'});
    const control = controls['[name="order[shipping_address][novaposhta_street]"]'];
    delete control.cityRef;
    control.disabled = true;
    refresh();
    assert.equal(control.cityRef, 'city-ref');
    assert.equal(control.disabled, false);
});


test('enabling same-as-billing replaces separate shipping refs with billing refs', () => {
    const state = fixture({area: 'billing-area', city: 'billing-city'});
    state.components.areaInputAutocompleteShipping.value('shipping-area');
    state.components.cityInputAutocompleteShipping.value('shipping-city');
    state.setSameAsBilling(true);
    assert.equal(state.components.areaInputAutocompleteShipping.value(), 'billing-area');
    assert.equal(state.components.cityInputAutocompleteShipping.value(), 'billing-city');
    state.setSameAsBilling(false);
    assert.equal(state.components.cityInputAutocompleteShipping.disabled(), false);
    assert.equal(state.controls['[name="order[shipping_address][novaposhta_city]"]'].areaRef, 'billing-area');
});


test('another carrier keeps NP controls inactive even for Ukrainian addresses', () => {
    const {components, refresh} = fixture({method: 'flatrate_flatrate', area: 'area-ref', city: 'city-ref'});
    assert.equal(refresh.isActive('billing'), false);
    assert.equal(Object.values(components).every(component => component.disabled()), true);
});

for (const [name, method] of [
    ['area', '_propagateToRegionSelect'], ['city', '_propagateToCityInput'],
    ['street', '_propagateToStreetInput'], ['warehouse', '_propagateToWarehouseInput'],
]) {
    test(name + ' does not read or modify native address controls for another carrier', () => {
        let component;
        const refresh = Object.assign(() => {}, {isActive: () => false});
        vm.runInNewContext(fs.readFileSync(path.resolve(__dirname,
            '../../view/adminhtml/web/js/order/create/' + name + '.js'), 'utf8'), {
            define: (names, factory) => {
                component = factory(...names.map(dependency => {
                    if (dependency === 'jquery') return () => {throw new Error('Native address accessed');};
                    if (dependency === 'Magento_Ui/js/form/element/select') return {extend: value => value};
                    if (dependency.endsWith('/address-state')) return refresh;
                    return {};
                }));
            },
        });
        component[method].call({addressType: 'billing'}, 'selected-value', true);
        component[method].call({addressType: 'shipping'}, 'selected-value', false);
    });
}


test('core rate reset retains the chosen carrier context until a different method is chosen', () => {
    const state = fixture({method: 'flatrate_flatrate'});
    state.setMethod('');
    assert.equal(state.refresh.isActive('billing'), false);
    state.setMethod('novaposhtashipping_c2w');
    assert.equal(state.refresh.isActive('billing'), true);
    state.setMethod('');
    assert.equal(state.refresh.getMethod(), 'novaposhtashipping_c2w');
    assert.equal(state.refresh.isActive('billing'), true);
});

for (const [name, method, field] of [
    ['city', '_propagateToCityInput', 'city'],
    ['street', '_propagateToStreetInput', 'street][0'],
    ['warehouse', '_propagateToWarehouseInput', 'street][0'],
]) {
    test(name + ' updates and notifies only the edited address when shipping differs from billing', () => {
        let component;
        const changes = [];
        const controls = {};
        const $ = selector => controls[selector] ||= {
            current: 'original',
            val(value) {if (arguments.length) {this.current = value;return this;}return this.current;},
            trigger() {changes.push(selector);return this;},
        };
        vm.runInNewContext(fs.readFileSync(path.resolve(__dirname,
            '../../view/adminhtml/web/js/order/create/' + name + '.js'), 'utf8'), {
            define: (names, factory) => {
                component = factory(...names.map(dependency => {
                    if (dependency === 'jquery') return $;
                    if (dependency === 'Magento_Ui/js/form/element/select') return {extend: value => value};
                    if (dependency.endsWith('/address-state')) return {isActive: () => true};
                    return {};
                }));
            },
        });
        for (const suffix of ['Shipping', 'Billing']) {
            const scope = {addressType: suffix.toLowerCase(), index: name + 'InputAutocomplete' + suffix,
                exportValue: component.exportValue, isOrphan: () => false};
            const target = '[name="order[' + suffix.toLowerCase() + '_address][' + field + ']"]';
            const before = changes.length;
            component[method].call(scope, 'selected-' + suffix, false);
            assert.equal(changes.length, before + 1);
            assert.equal(changes.at(-1), target);
            component[method].call(scope, 'selected-' + suffix, false);
            assert.equal(changes.length, before + 1);
        }
    });
}
