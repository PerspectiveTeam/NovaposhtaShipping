const {test} = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const vm = require('node:vm');

function observable(value) {
    const subscribers = [];
    function result(next) {
        if (!arguments.length) return value;
        if (next !== value) {value = next;subscribers.forEach(callback => callback(next));}
        return result;
    }
    result.subscribe = callback => subscribers.push(callback);
    return result;
}

function fixture(directory = {UA: {regions: {
    11: {area_ref: 'kyiv-area', name: 'Київська область'},
    12: {area_ref: 'lviv-area', name: 'Львівська область'},
}}}) {
    const subscribers = {};
    const events = [];
    const postbox = {
        subscribe(name, callback, context) {(subscribers[name] ||= []).push(callback.bind(context));},
        publish(name, value) {events.push([name, value]);(subscribers[name] || []).forEach(callback => callback(value));},
    };
    const elements = new Map();
    const $ = element => {
        if (!elements.has(element)) elements.set(element, {
            handlers: {}, options: {}, selected: '',
            attr: () => 'uk',
            select2(options) {this.options = options;return this;},
            on(name, callback) {this.handlers[name] = callback;return this;},
            data: () => true,
            find: () => ({length: 0, prop() {}, remove() {}}),
            append() {return this;},
            val(value) {if (arguments.length) {this.selected = value;return this;}return this.selected;},
            trigger() {return this;},
        });
        return elements.get(element);
    };
    $.mage = {__: value => value};
    let method = {carrier_code: 'novaposhtashipping', method_code: 'c2w'};
    const region = {value: observable('')};
    region.value.subscribe(value => postbox.publish('selectedRegionIdPost', value));
    const data = observable(directory);
    const dependencies = {
        jquery: $, ko: {pureComputed: (callback, context) => callback.bind(context)},
        'Magento_Ui/js/form/element/select': {extend: value => value},
        'mage/url': {build: value => value}, postbox,
        'Magento_Checkout/js/model/quote': {shippingMethod: () => method},
        uiRegistry: {get: () => region},
        'Magento_Customer/js/customer-data': {get: () => data},
    };
    function component(name) {
        let definition;
        vm.runInNewContext(fs.readFileSync(path.resolve(__dirname,
            '../../view/frontend/web/js/view/checkout/shipping/' + name + '.js'), 'utf8'), {
            define: (names, factory) => {definition = factory(...names.map(key => dependencies[key]));},
            Option: function (label, ref) {this.text = label;this.value = ref;},
        });
        const instance = Object.assign({}, definition.defaults, definition, {
            _super() {}, observe(property) {
                if (typeof this[property] !== 'function') this[property] = observable(this[property]);
                return this;
            },
            value: observable(''), disabled: observable(true), visible: observable(false),
            options: () => [], inputName: name, getPreview: () => 'Львів',
        });
        instance.initObservable();
        instance.initialize();
        instance.value.subscribe(() => instance.setDifferedFromDefault?.());
        const element = {};
        instance.select2(element);
        instance.control = $(element);
        return instance;
    }
    const area = component('area');
    const city = component('city');
    return {area, city, region, data, events, postbox, setMethod: next => {method = next;}};
}

test('city is enabled without a region and searches globally with area metadata', () => {
    const {city} = fixture();
    assert.equal(city.disabled(), false);
    city.areaRef('another-region');
    const query = JSON.parse(city.control.options.ajax.data({term: 'Львів'}));
    assert.equal(query.areaRef, '');
    assert.equal(query.includeArea, true);
});

test('choosing a city sets NP and Magento regions without resetting the chosen city', () => {
    const {city, area, region} = fixture();
    city.value('lviv-city');
    city.control.handlers['select2:select']({params: {data: {
        id: 'lviv-city', text: 'Львів', areaRef: 'lviv-area', areaLabel: 'Львівська',
    }}});
    assert.equal(city.value(), 'lviv-city');
    assert.equal(city.areaRef(), 'lviv-area');
    assert.equal(area.areaRef(), 'lviv-area');
    assert.equal(region.value(), '12');
});

test('choosing a city overrides a previously selected different region', () => {
    const {city, area, region} = fixture();
    region.value('11');
    city.value('lviv-city');
    city.control.handlers['select2:select']({params: {data: {
        id: 'lviv-city', text: 'Львів', areaRef: 'lviv-area', areaLabel: 'Львівська',
    }}});
    assert.equal(area.areaRef(), 'lviv-area');
    assert.equal(region.value(), '12');
    assert.equal(city.value(), 'lviv-city');
});

test('manual region change resets the previous city but keeps city search enabled', () => {
    const {city, region} = fixture();
    city.areaRef('lviv-area');city.value('lviv-city');
    region.value('11');
    assert.equal(city.value(), '');
    assert.equal(city.disabled(), false);
});

test('region mapping arriving after city selection still synchronizes Magento region', () => {
    const state = fixture({});
    state.city.value('lviv-city');
    state.city.control.handlers['select2:select']({params: {data: {
        id: 'lviv-city', text: 'Львів', areaRef: 'lviv-area', areaLabel: 'Львівська',
    }}});
    assert.equal(state.region.value(), '');
    state.data({UA: {regions: {12: {area_ref: 'lviv-area', name: 'Львівська область'}}}});
    assert.equal(state.region.value(), '12');
    assert.equal(state.city.value(), 'lviv-city');
});

test('standard carrier region changes do not change NP city or publish address reset events', () => {
    const state = fixture();
    state.city.areaRef('lviv-area');state.city.value('lviv-city');
    state.setMethod({carrier_code: 'flatrate', method_code: 'flatrate'});
    const before = state.events.length;
    state.region.value('11');
    assert.equal(state.city.value(), 'lviv-city');
    assert.equal(state.events.slice(before).some(([name]) => name === 'selectedCityPost'), false);
});

test('multiple Magento regions mapped to one NP area use the first match, as in Admin', () => {
    const state = fixture({UA: {regions: {
        11: {area_ref: 'kyiv-area', name: 'Київська область'},
        13: {area_ref: 'kyiv-area', name: 'Київ'},
    }}});
    state.city.value('kyiv-city');
    state.city.control.handlers['select2:select']({params: {data: {
        id: 'kyiv-city', text: 'Київ', areaRef: 'kyiv-area', areaLabel: 'Київська',
    }}});
    assert.equal(state.region.value(), '11');
});

test('country reset while another carrier is selected does not publish dependent address resets', () => {
    const state = fixture();
    state.city.areaRef('lviv-area');state.city.value('lviv-city');
    state.setMethod({carrier_code: 'flatrate', method_code: 'flatrate'});
    const before = state.events.length;
    const cityName = state.city.cityName();
    state.postbox.publish('selectedAreaPost', '');
    assert.equal(state.city.value(), '');
    assert.equal(state.city.cityName(), cityName);
    assert.equal(state.events.slice(before).some(([name]) => name === 'selectedCityPost'), false);
});

test('rendering street visibility repeatedly does not launch competing street preload requests', () => {
    let component;
    let requests = 0;
    const $ = {ajax() {requests++;}};
    vm.runInNewContext(fs.readFileSync(path.resolve(__dirname,
        '../../view/frontend/web/js/view/checkout/shipping/address/street.js'), 'utf8'), {
        define: (names, factory) => {
            component = factory(...names.map(name => {
                if (name === 'jquery') return $;
                if (name === 'Magento_Ui/js/form/element/select') return {extend: value => value};
                if (name === 'Magento_Checkout/js/model/quote') return {
                    shippingMethod: () => ({carrier_code: 'novaposhtashipping', method_code: 'c2c'}),
                };
                if (name === 'mage/translate') return value => value;
                if (name === 'mage/url') return {build: value => value};
                return {};
            }));
        },
    });
    component.cityRef = () => 'city-ref';
    for (let i = 0; i < 3; i++) assert.equal(component.selectedMethodCode(), 'c2c');
    assert.equal(requests, 0);
});
