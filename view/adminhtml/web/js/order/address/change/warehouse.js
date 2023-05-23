define([
    'jquery',
    'ko',
    'uiComponent',
    'Magento_Ui/js/form/element/abstract',
    'mage/url',
    'underscore',
    'mage/translate',
    'uiRegistry',
    'postbox',
    'jquery/jquery.cookie'
], function ($, ko, Component, Abstract, url, _, translate, uiRegistry, postbox) {
    'use strict';

    ko.bindingHandlers.cityAutocompleteWarehouse = {

        init: function (element, valueAccessor, allBindings, viewModel, bindingContext) {
            uiRegistry.set('warehouseCityAutocompleteBindingApplied', true);
            var thisViewModel = viewModel;

            // valueAccessor = { selected: mySelectedOptionObservable, options: myArrayOfLabelValuePairs }
            var settings = valueAccessor();

            var selectedOption = settings.selected;
            // var options = settings.options;
            uiRegistry.set('warehouseCityAutocompleteBindingElement', element);
        }
    };
    return Component.extend(
        {
            availableCity: ko.observableArray([]),
            availableWarehouse: ko.observableArray([]),
            selectedItem: ko.observable(),
            cityCaption: ko.observable(),
            warehouseCaption: ko.observable(),
            selectedWarehouse: ko.observable(),
            initialize: function (config) {
                this._super();
                var selfWarehouseComponent = this;
                this.getCities(selfWarehouseComponent);
                selfWarehouseComponent.warehouseCaption($.mage.__('Please wait until city data loads and choose city.'));
                selfWarehouseComponent.selectedWarehouse.subscribe(function (value) {
                    var test = value;
                });
                selfWarehouseComponent.selectedItem.subscribe(function (cityValue) {
                    if (cityValue !== undefined) {
                        selfWarehouseComponent.getCityWarehouses(cityValue, selfWarehouseComponent);
                        selfWarehouseComponent.warehouseCaption($.mage.__('Please wait while data loading...'));
                    }
                });
                try {
                    ko.applyBindings(this, document.getElementById("warehouse-and-city-wrapper"));
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
            getCityWarehouses: function (cityValue, selfWarehouseComponent) {
                $.ajax({
                    url: selfWarehouseComponent.warehouseUrl,
                    data: {
                        cityId: cityValue,
                        form_key: window.FORM_KEY
                    },
                    showLoader: true,
                    type: "GET",
                    dataType: 'json',
                    error: function (data) {
                        alert($.mage.__("An error have been occurred while fetching warehouses list. Try to reload page or contact with us"));
                        console.log(data.responseText);
                        selfWarehouseComponent.cityCaption($.mage.__('Error while retrieving data. Reload the page or contact to developer'));
                    },
                    success: function (data) {
                        if (data !== undefined) {
                            selfWarehouseComponent.availableWarehouse.removeAll();
                            var items = JSON.parse(data);
                            _.each(items.warehouseList, function (dropdownValue, key) {
                                var value = dropdownValue.value;
                                var label = dropdownValue.label;
                                var option = {
                                    'value': value,
                                    'label': label
                                };
                                selfWarehouseComponent.availableWarehouse.push(option);
                            });
                        }
                        selfWarehouseComponent.warehouseCaption($.mage.__('Choose the warehouse...'));
                    }
                });
            },
            getCities: function (selfWarehouseComponent) {
                selfWarehouseComponent.cityCaption($.mage.__('Please wait while data loading...'));
                if (!(uiRegistry.get('perspective_novaposhta_city_load_lock_flag'))) {
                    uiRegistry.set('perspective_novaposhta_city_load_lock_flag', true);
                    $.ajax({
                        url: selfWarehouseComponent.cityUrl,
                        data: {
                            form_key: window.FORM_KEY
                        },
                        type: "POST",
                        dataType: 'json',
                        showLoader: true,
                        error: function (data) {
                            alert($.mage.__("An error have been occurred while fetching cities list. Try to reload page or contact with us"));
                            console.log(data.responseText);
                            selfWarehouseComponent.cityCaption($.mage.__('Error while retrieving data. Reload the page or contact to developer'));
                        },
                        success: function (data) {
                            if (data !== undefined) {
                                selfWarehouseComponent.availableCity.removeAll();
                                var items = JSON.parse(data);
                                _.each(items.cityList, function (dropdownValue, key) {
                                    var value = dropdownValue.value;
                                    var label = dropdownValue.label;
                                    var option = {
                                        'value': value,
                                        'label': label
                                    };
                                    selfWarehouseComponent.availableCity.push(option);
                                });
                                postbox.publish("perspective_novaposhta_city_array_event", selfWarehouseComponent.availableCity());
                                uiRegistry.set("perspective_novaposhta_city_array", selfWarehouseComponent.availableCity());
                                uiRegistry.set('perspective_novaposhta_city_load_lock_flag', false);
                                selfWarehouseComponent.cityCaption($.mage.__('Choose the city...'));
                                selfWarehouseComponent.warehouseCaption($.mage.__('Firstly choose the city...'));
                                $(uiRegistry.get('warehouseCityAutocompleteBindingElement')).autocomplete({
                                    minChars: 0,
                                    source: selfWarehouseComponent.availableCity(),
                                    select: function (event, ui) {
                                        selfWarehouseComponent.updateElementValueWithLabel(event, ui);
                                    },
                                    change: function (event, ui) {
                                        selfWarehouseComponent.updateElementValueWithLabel(event, ui);
                                    }
                                });
                            }
                        }
                    });
                } else {
                    if (uiRegistry.get("perspective_novaposhta_city_array")){
                        selfWarehouseComponent.availableCity(uiRegistry.get("perspective_novaposhta_city_array"));
                        $(uiRegistry.get('warehouseCityAutocompleteBindingElement')).autocomplete({
                            minChars: 0,
                            source: selfWarehouseComponent.availableCity(),
                            select: function (event, ui) {
                                selfWarehouseComponent.updateElementValueWithLabel(event, ui);
                            },
                            change: function (event, ui) {
                                selfWarehouseComponent.updateElementValueWithLabel(event, ui);
                            }
                        });
                        selfWarehouseComponent.cityCaption($.mage.__('Choose the city...'));
                        selfWarehouseComponent.streetCaption($.mage.__('Firstly choose the city...'));
                    }else {
                        postbox.subscribe("perspective_novaposhta_city_array_event", function (value) {
                            selfWarehouseComponent.availableCity(value);
                            $(uiRegistry.get('warehouseCityAutocompleteBindingElement')).autocomplete({
                                minChars: 0,
                                source: selfWarehouseComponent.availableCity(),
                                select: function (event, ui) {
                                    selfWarehouseComponent.updateElementValueWithLabel(event, ui);
                                },
                                change: function (event, ui) {
                                    selfWarehouseComponent.updateElementValueWithLabel(event, ui);
                                }
                            });
                            postbox.unsubscribeFrom('perspective_novaposhta_city_array_event');
                            uiRegistry.set('perspective_novaposhta_city_load_lock_flag', false);
                            selfWarehouseComponent.cityCaption($.mage.__('Choose the city...'));
                            selfWarehouseComponent.streetCaption($.mage.__('Firstly choose the city...'));
                        });
                    }
                }
            },

        },
    );
});
