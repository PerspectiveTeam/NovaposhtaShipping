define([
    'jquery',
    'ko',
    'Magento_Ui/js/form/element/select',
    'mage/url',
    'postbox',
    'Magento_Checkout/js/action/set-shipping-information',
    'Magento_Checkout/js/model/quote',
    'uiRegistry',
    'Magento_Customer/js/customer-data',
    'Perspective_NovaposhtaShipping/js/lib/select2/select2'
], function ($, ko, Select, url, postbox, setShippingInformationAction, quote, registry, customerData) {
    'use strict';

    var NP_METHODS = ['c2w', 'w2w', 'w2c', 'c2c'];

    return Select.extend({

        defaults: {
            template: 'Perspective_NovaposhtaShipping/checkout/shipping/area',
            areaRef: '',
            exports: {
                'regionDefaultVisible': 'checkout.steps.shipping-step.shippingAddress.shipping-address-fieldset.region_id:visible'
            }
        },

        initialize: function () {
            this._super();
            var self = this;
            self._suppressAreaSync = false;
            self._lookups = null;
            self._directoryData = customerData.get('directory-data');

            self._directoryData.subscribe(function () {
                self._lookups = null;
            });

            postbox.subscribe('selectedRegionIdPost', function (regionId) {
                if (!regionId || self._suppressAreaSync) return;
                var lookups = self._getLookups();
                var areaRef = lookups.regionIdToAreaRef[String(regionId)] || null;
                if (areaRef && areaRef !== self.areaRef()) {
                    self._applyAreaToSelect(areaRef);
                    postbox.publish('selectedAreaPost', areaRef);
                }
            });
            return this;
        },

        initObservable: function () {
            this._super();
            this.observe('areaRef');
            this.observe('regionDefaultVisible');
            this.isNpMethod = ko.pureComputed(function () {
                var method = quote.shippingMethod();
                var code = method ? method.method_code : null;
                var isNp = NP_METHODS.indexOf(code) !== -1;
                this.regionDefaultVisible(!isNp);
                this.visible(isNp);
                return code;
            }, this);
            return this;
        },

        select2: function (element) {
            var self = this;
            self._select2Element = element;
            var lang = $('html').attr('lang') === 'uk' ? 'uk' : 'ru';

            var preselect = [{id: 0, text: $.mage.__('Choose region')}];
            if (self.options() && self.options().length) {
                var opt = self.options()[0];
                preselect = [{id: opt.value, text: opt.label}];
                self.areaRef(opt.value);
                postbox.publish('selectedAreaPost', opt.value);
                postbox.publish('selectedAreaTextPost', opt.label);
                self._syncRegionFromArea(opt.value);
            }

            $(element).select2({
                placeholder: $.mage.__('Choose region'),
                dropdownAutoWidth: true,
                width: '100%',
                minimumInputLength: 2,
                language: lang,
                data: preselect,
                ajax: {
                    url: url.build('rest/V1/novaposhtashipping/area'),
                    type: "POST",
                    dataType: 'json',
                    contentType: "application/json",
                    delay: 500,
                    data: function (params) {
                        return JSON.stringify({term: params.term});
                    },
                    processResults: function (data) {
                        return {results: JSON.parse(data)};
                    }
                }
            }).on('select2:select', function (e) {
                var selectedRef = e.params.data.id;
                self.areaRef(selectedRef);
                postbox.publish('selectedAreaPost', selectedRef);
                postbox.publish('selectedAreaTextPost', e.params.data.text);
                setShippingInformationAction();
                self._syncRegionFromArea(selectedRef);
            });
        },

        _getLookups: function () {
            if (this._lookups) return this._lookups;

            var regionIdToAreaRef = {};
            var areaRefToRegion = {};
            var uaRegions = (this._directoryData() || {});
            uaRegions = uaRegions['UA'] && uaRegions['UA']['regions'] || {};

            for (var regionId in uaRegions) {
                if (!uaRegions.hasOwnProperty(regionId)) continue;
                var region = uaRegions[regionId];
                var areaRef = region['area_ref'] || '';
                if (!areaRef) continue;
                regionIdToAreaRef[String(regionId)] = areaRef;
                areaRefToRegion[areaRef] = {regionId: String(regionId), name: region['name'] || ''};
            }

            this._lookups = {regionIdToAreaRef: regionIdToAreaRef, areaRefToRegion: areaRefToRegion};
            return this._lookups;
        },

        _syncRegionFromArea: function (areaRef) {
            var region = this._getLookups().areaRefToRegion[areaRef];
            if (!region) return;
            var regionId = region.regionId;
            this._suppressAreaSync = true;
            postbox.publish('selectedAreaRegionIdPost', regionId);
            var regionComponent = registry.get(
                'checkout.steps.shipping-step.shippingAddress.shipping-address-fieldset.region_id'
            );
            if (regionComponent && String(regionComponent.value()) !== regionId) {
                regionComponent.value(regionId);
            }
            this._suppressAreaSync = false;
        },

        _applyAreaToSelect: function (areaRef) {
            this.areaRef(areaRef);

            var el = this._select2Element;
            if (!el) return;
            var $el = $(el);
            if (!$el.data('select2')) return;

            if (!$el.find('option[value="' + areaRef + '"]').length) {
                var name = (this._getLookups().areaRefToRegion[areaRef] || {}).name || areaRef;
                $el.append(new Option(name, areaRef, true, true));
            }
            $el.val(areaRef).trigger('change.select2');
        },

        getPreview: function () {
            return $('[name="' + this.inputName + '"] option:selected').text();
        }
    });
});
