/**
 * Mixin for the standard region_id select component.
 * Bidirectional sync with the NP area field via postbox.
 * Also resets NP area field when country changes.
 */
define([
    'postbox',
    'uiRegistry'
], function (postbox, registry) {
    'use strict';

    return function (Component) {
        return Component.extend({

            initialize: function () {
                this._super();
                var self = this;
                self._suppressRegionSync = false;

                postbox.subscribe('selectedAreaRegionIdPost', function (regionId) {
                    if (!regionId) return;
                    var strId = String(regionId);
                    if (String(self.value()) !== strId) {
                        self._suppressRegionSync = true;
                        self.value(strId);
                        self._suppressRegionSync = false;
                    }
                });

                var countryComponent = registry.get(
                    'checkout.steps.shipping-step.shippingAddress.shipping-address-fieldset.country_id'
                );

                if (countryComponent && this.name.indexOf('shippingAddress.shipping-address-fieldset') !== -1) {
                    self._previousCountryId = countryComponent.value();
                    countryComponent.value.subscribe(function (newCountryId) {
                        if (self._previousCountryId && self._previousCountryId !== newCountryId) {
                            self._resetNpAreaField();
                        }
                        self._previousCountryId = newCountryId;
                    });
                }

                return this;
            },

            initObservable: function () {
                this._super();
                var self = this;
                this.value.subscribe(function (newVal) {
                    if (!newVal || self._suppressRegionSync) return;
                    var areaComponent = registry.get(
                        'checkout.steps.shipping-step.shippingAddress.shipping-address-fieldset.area_novaposhta_field'
                    );
                    if (!areaComponent) return;
                    postbox.publish('selectedRegionIdPost', newVal);
                });
                return this;
            },

            /**
             * Resets the NP area field and triggers area reset chain (city resets via postbox).
             */
            _resetNpAreaField: function () {
                var areaComponent = registry.get(
                    'checkout.steps.shipping-step.shippingAddress.shipping-address-fieldset.area_novaposhta_field'
                );
                if (!areaComponent) return;

                areaComponent.areaRef('');
                areaComponent.value('');

                var el = areaComponent._select2Element;
                if (el) {
                    var $ = require('jquery');
                    var $el = $(el);
                    if ($el.data('select2')) {
                        $el.find('option[value!=""]').remove();
                        $el.val(null).trigger('change.select2');
                    }
                }

                postbox.publish('selectedAreaPost', '');
            }
        });
    };
});
