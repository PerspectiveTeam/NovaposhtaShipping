define([
    'jquery',
    'ko',
    'uiComponent',
    'Magento_Ui/js/form/element/abstract',
    'mage/url',
    'underscore',
    'mage/translate',
    'postbox',
    'uiRegistry',
    'jquery/ui',
    'mage/menu'
], function (
    $,
    ko,
    Component,
    Abstract,
    url,
    _,
    translate,
    postbox,
    uiRegistry
) {
    'use strict';

    ko.bindingHandlers.cityAutocompleteAddress = {

        init: function (element, valueAccessor, allBindings, viewModel, bindingContext) {
            uiRegistry.set('addressCityAutocompleteBindingApplied', true);
            var thisViewModel = viewModel;

            // valueAccessor = { selected: mySelectedOptionObservable, options: myArrayOfLabelValuePairs }
            var settings = valueAccessor();
            uiRegistry.set('addressCityAutocompleteBindingElement', element);
        }
    };
    return Component.extend(
        {
            availableCity: ko.observableArray([]),
            availableWarehouse: ko.observableArray([]),
            selectedItem: ko.observable(),
            cityCaption: ko.observable(),
            streetCaption: ko.observable(),
            streetNumCaption: ko.observable(),
            streetApartNumCaption: ko.observable(),
            selectedStreet: ko.observable().subscribeTo("selectedStreetPost"),
            selectedStreetNum: ko.observable().subscribeTo("selectedStreetNumPost"),
            selectedFlatNum: ko.observable().subscribeTo("selectedApartNumPost"),

            initialize: function (config) {
                this._super();
                var selfAddressComponent = this;

                this.getCities(selfAddressComponent, config);
                selfAddressComponent.streetCaption($.mage.__('Please wait until city data loads and choose city.'));
                selfAddressComponent.selectedStreet.subscribe(function (value) {
                    ko.postbox.publish("selectedStreetPost", value);
                    var test = value;
                    var CustomAttrIsArray = false;

                });
                selfAddressComponent.selectedStreetNum.subscribe(function (value) {
                    ko.postbox.publish("selectedStreetNumPost", value);
                    var test = value;
                    var CustomAttrIsArray = false;
                });
                selfAddressComponent.selectedFlatNum.subscribe(function (value) {
                    ko.postbox.publish("selectedApartNumPost", value);
                    var test = value;
                    var CustomAttrIsArray = false;
                });
                selfAddressComponent.selectedItem.subscribe(function (cityValue) {
                    ko.postbox.publish("selectedCityPost", cityValue);
                });
                try {
                    ko.applyBindings(this, document.getElementById("novaposhta-address-change-wrapper"));
                } catch (e) {
                    console.log(e);
                }
                return this;
            },
            updateElementValueWithLabel: function (event, ui) {
                // Stop the default behavior
                event.preventDefault();

                // Update the value of the html element with the label
                // of the activated option in the list (ui.item)
                if (ui) {
                    if (ui.item) {
                        if (ui.item.label) {
                            $(event.target).val(ui.item.label);
                        }
                    }
                }
                // Update our SelectedOption observable
                if (ui) {
                    if (ui.item) {
                        if (ui.item.value) {
                            this.selectedItem(ui.item.value);
                        }
                    }
                }
            },
            getCities: function (selfAddressComponent, config) {
                selfAddressComponent.cityCaption($.mage.__('Please wait while data loading...'));
                if (!(uiRegistry.get('perspective_novaposhta_city_load_lock_flag'))) {
                    uiRegistry.set('perspective_novaposhta_city_load_lock_flag', true);
                    $.ajax({
                        url: config.cityUrl,
                        data: {
                            form_key: window.FORM_KEY
                        },
                        type: "POST",
                        dataType: 'json',
                        showLoader: true,
                        error: function (data) {
                            alert($.mage.__("An error have been occurred while fetching cities list. Try to reload page or contact with us"));
                            console.log(data.responseText);
                            selfAddressComponent.cityCaption($.mage.__('Error while retrieving data. Reload the page or contact to developer'));
                        },
                        success: function (data) {
                            if (data !== undefined) {
                                selfAddressComponent.availableCity.removeAll();
                                var items = JSON.parse(data);
                                _.each(items.cityList, function (dropdownValue, key) {
                                    var value = dropdownValue.value;
                                    var label = dropdownValue.label;
                                    var option = {
                                        'value': value,
                                        'label': label
                                    };
                                    selfAddressComponent.availableCity.push(option);
                                });
                                selfAddressComponent.cityCaption($.mage.__('Choose the city...'));
                                selfAddressComponent.streetCaption($.mage.__('Firstly choose the city...'));
                                postbox.publish("perspective_novaposhta_city_array_event", selfAddressComponent.availableCity());
                                uiRegistry.set("perspective_novaposhta_city_array", selfAddressComponent.availableCity());
                                uiRegistry.set('perspective_novaposhta_city_load_lock_flag', false);
                                $(uiRegistry.get('addressCityAutocompleteBindingElement')).autocomplete({
                                    minChars: 0,
                                    source: selfAddressComponent.availableCity(),
                                    select: function (event, ui) {
                                        selfAddressComponent.updateElementValueWithLabel(event, ui);
                                    },
                                    change: function (event, ui) {
                                        selfAddressComponent.updateElementValueWithLabel(event, ui);
                                    }
                                });
                            } else {
                                selfAddressComponent.availableCity.removeAll();
                                selfAddressComponent.cityCaption($.mage.__('Error occur when cities collection have been fetched'));
                            }
                        }
                    });
                } else {
                    if (uiRegistry.get("perspective_novaposhta_city_array")) {
                        selfAddressComponent.availableCity(uiRegistry.get("perspective_novaposhta_city_array"));
                        $(uiRegistry.get('addressCityAutocompleteBindingElement')).autocomplete({
                            minChars: 0,
                            source: selfAddressComponent.availableCity(),
                            select: function (event, ui) {
                                selfAddressComponent.updateElementValueWithLabel(event, ui);
                            },
                            change: function (event, ui) {
                                selfAddressComponent.updateElementValueWithLabel(event, ui);
                            }
                        });
                        selfAddressComponent.cityCaption($.mage.__('Choose the city...'));
                        selfAddressComponent.streetCaption($.mage.__('Firstly choose the city...'));
                    } else {
                        postbox.subscribe("perspective_novaposhta_city_array_event", function (value) {
                            selfAddressComponent.availableCity(value);
                            $(uiRegistry.get('addressCityAutocompleteBindingElement')).autocomplete({
                                minChars: 0,
                                source: selfAddressComponent.availableCity(),
                                select: function (event, ui) {
                                    selfAddressComponent.updateElementValueWithLabel(event, ui);
                                },
                                change: function (event, ui) {
                                    selfAddressComponent.updateElementValueWithLabel(event, ui);
                                }
                            });
                            postbox.unsubscribeFrom('perspective_novaposhta_city_array_event');
                            selfAddressComponent.cityCaption($.mage.__('Choose the city...'));
                            selfAddressComponent.streetCaption($.mage.__('Firstly choose the city...'));
                        });
                    }
                }
            }
        },
    );
});
