define([
    'jquery',
    'ko',
    'Magento_Ui/js/form/element/select',
    'mage/url',
    'postbox',
    'mage/translate',
    'uiRegistry',
    'Perspective_NovaposhtaShipping/js/lib/select2/select2'
], function ($, ko, Select, url, postbox, translate, registry) {
    'use strict';

    return Select.extend({

        defaults: {
            template: 'Perspective_NovaposhtaShipping/order/create/area',
            backendRestURL: '',
            inputCustomName: '',
            preselectedLabel: '',
            preselectedValue: '',
            regionIdToAreaRef: {},
            addressType: 'shipping',
            isChanging: false
        },

        initialize: function (config) {
            this._super();
            this.backendRestURL(config.areaBackendUrl);
            this.inputCustomName(config.inputName);
            if (config.regionIdToAreaRef) {
                this.regionIdToAreaRef = config.regionIdToAreaRef;
            }
            if (config.addressType) {
                this.addressType = config.addressType;
            }

            var self = this;
            var sameAsBilling = $('#order-shipping_same_as_billing').is(':checked');

            if (this.addressType === 'shipping' && sameAsBilling) {
                this.disabled(true);
            }

            // Sync NP area when standard Magento region_id select changes
            $(document).on(
                'change',
                'select[name="order[' + self.addressType + '_address][region_id]"]',
                function () {
                    var regionId = $(this).val();
                    if (!regionId) return;
                    var areaRef = self._getAreaRefByRegionId(regionId);
                    if (areaRef) {
                        self._applyAreaRef(areaRef);
                    }
                }
            );

            // Sync shipping region options when billing country changes with same_as_billing on
            if (self.addressType === 'shipping') {
                $(document).on(
                    'change',
                    'select[name="order[billing_address][country_id]"]',
                    function () {
                        if (!$('#order-shipping_same_as_billing').is(':checked')) return;
                        var shippingCountry = document.querySelector(
                            'select[name="order[shipping_address][country_id]"]'
                        );
                        if (!shippingCountry) return;
                        shippingCountry.value = this.value;
                        shippingCountry.dispatchEvent(new Event('change', {bubbles: true}));
                        var shippingRegion = document.querySelector(
                            'select[name="order[shipping_address][region_id]"]'
                        );
                        if (shippingRegion) shippingRegion.disabled = true;
                    }
                );
            }

            return this;
        },

        initObservable: function () {
            this._super();
            this.observe('backendRestURL');
            this.observe('inputCustomName');
            this.observe('preselectedLabel');
            this.observe('preselectedValue');
            return this;
        },

        select2: function (element) {
            var self = this;

            if (this.inputCustomName()) {
                // Reassign name to prevent it from being lost after DOM updates
                this.inputName = element.name = this.inputCustomName();
            }

            var lang = $('html').attr('lang') === 'uk' ? 'uk' : 'ru';
            var preselectObject = {};

            if (this.preselectedLabel() && this.preselectedValue()) {
                preselectObject = {id: this.preselectedValue(), text: $.mage.__(this.preselectedLabel())};
            } else {
                preselectObject = {id: 0, text: $.mage.__('Choose region')};
            }

            $(element).select2({
                placeholder: $.mage.__(''),
                dropdownAutoWidth: true,
                width: '100%',
                minimumInputLength: 2,
                language: lang,
                data: [preselectObject],
                ajax: {
                    url: this.backendRestURL(),
                    type: "post",
                    dataType: 'json',
                    contentType: "application/json",
                    delay: 1000,
                    beforeSend: function (xhr, ajax) {
                        // Empty to remove magento's default handler
                    },
                    data: function (params) {
                        return JSON.stringify({
                            term: params.term,
                            form_key: window.FORM_KEY
                        });
                    },
                    processResults: function (data) {
                        return {
                            results: JSON.parse(data)
                        };
                    }
                }
            }).on('select2:select', function (e) {
                var value = e.params.data.id;
                var sameAsBilling = $('#order-shipping_same_as_billing').is(':checked');
                self._propagateToRegionSelect(value, sameAsBilling);
                if (sameAsBilling) {
                    self._syncShippingAreaFromBilling();
                }
                postbox.publish('selectedAreaPost', {areaRef: value, addressType: self.addressType});
            });
        },

        _applyAreaRef: function (areaRef) {
            this.value(areaRef);
        },

        _getRegionIdByAreaRef: function (areaRef) {
            var map = this.regionIdToAreaRef || {};
            for (var regionId in map) {
                if (map[regionId] === areaRef) return regionId;
            }
            return null;
        },

        _getAreaRefByRegionId: function (regionId) {
            var map = this.regionIdToAreaRef || {};
            return map[String(regionId)] || '';
        },

        getPreview: function () {
            return $('[id="' + this.uid + '"] option:selected').text();
        },

        _propagateToRegionSelect: function (areaRef, sameAsBilling) {
            var regionId = this._getRegionIdByAreaRef(areaRef);
            if (!regionId) {
                return;
            }

            var regionSelect = $('select[name="order[' + this.addressType + '_address][region_id]"]');
            regionSelect.val(regionId).trigger('change');

            if (this.addressType === 'billing' && sameAsBilling) {
                var shippingSelect = $('select[name="order[shipping_address][region_id]"]');
                if (shippingSelect.length) {
                    shippingSelect.val(regionId).trigger('change');
                }
            }

        },

        _syncShippingAreaFromBilling: function () {
            var billingSelect = $('[name="order[billing_address][novaposhta_area]"]');
            var shippingSelect = $('[name="order[shipping_address][novaposhta_area]"]');
            var selectedData = billingSelect.select2('data');
            if (selectedData && selectedData.length) {
                var option = new Option(selectedData[0].text, selectedData[0].id, true, true);
                shippingSelect.append(option).trigger('change');
            }
        },

        exportValue: function (value, control) {
            control.val(value);
        }
    });
});
