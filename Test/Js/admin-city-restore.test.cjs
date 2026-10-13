const {test} = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const vm = require('node:vm');

function fixture() {
    let component, request, success;
    const events = [];
    const element = {isConnected: true};
    const select = {
        areaRef: '',
        data() {return this.areaRef;},
        append() {return this;},
        trigger(event) {events.push(event);return this;},
    };
    const $ = value => {
        if (value === element) return select;
        if (value === '#order-shipping_same_as_billing') return {is: () => false};
        return {val: () => 'Київ'};
    };
    $.ajax = options => {
        request = options;
        return {done(callback) {success = callback;}};
    };
    vm.runInNewContext(fs.readFileSync(path.resolve(__dirname,
        '../../view/adminhtml/web/js/order/create/city.js'), 'utf8'), {
        define: (names, factory) => {
            component = factory(...names.map(name => {
                if (name === 'jquery') return $;
                if (name === 'Magento_Ui/js/form/element/select') return {extend: value => value};
                if (name.endsWith('/address-state')) return {isActive: () => true};
                return {};
            }));
        },
        Option: function () {},
        window: {FORM_KEY: 'local-test-form-key'},
    });
    component._restoreCityFromAddress.call({
        addressType: 'billing', hasValue: () => false, backendRestURL: () => '/city',
    }, element);
    return {
        request, select, events,
        respond: () => success(JSON.stringify([{id: 'city-ref', text: 'Київ', areaRef: 'kyiv-area'}])),
    };
}

test('city hydration uses an intact JSON body and accepts its region becoming ready during the request', () => {
    const state = fixture();
    assert.equal(typeof state.request.beforeSend, 'function');
    assert.equal(JSON.parse(state.request.data).includeArea, true);
    state.select.areaRef = 'kyiv-area';
    state.respond();
    assert.equal(state.events[1].type, 'select2:select');
    assert.equal(state.events[1].params.restore, true);
    assert.equal(state.events[1].params.data.id, 'city-ref');
});

test('city hydration ignores a stale response after the merchant chooses another region', () => {
    const state = fixture();
    state.select.areaRef = 'lviv-area';
    state.respond();
    assert.equal(state.events.length, 0);
});
