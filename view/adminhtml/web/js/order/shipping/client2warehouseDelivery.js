define([
    'jquery',
    'ko',
    'uiComponent',
    'postbox',
    'Magento_Ui/js/form/element/abstract',
    'underscore',
    'mage/url',
    'mage/translate',
    'jquery/ui',
    'mage/menu',
], function (
    $,
    ko,
    Component,
    postbox,
    Abstract,
    _,
    url,
    translate,
    ui,
    menu
) {
    'use strict';
    return Component.extend({
        warehouseCaption: ko.observable(),
        selectedItemShipping: ko.observable(),
        urlForSendRequestToNP: ko.observable(),
        contactPersonSearchUrl: ko.observable(),
        contactPersonAddressSearchUrl: ko.observable(),
        quoteId: ko.observable(),
        warehouseUrl: ko.observable(),
        formKey: ko.observable(),
        selectedWarehouse: ko.observable(),
        initialize: function (config) {
            var self = this;
            self.urlForSendRequestToNP(config.npUrl);
            self.contactPersonSearchUrl(config.contactPersonSearchUrl);
            self.contactPersonAddressSearchUrl(config.contactPersonAddressSearchUrl);
            self.formKey(config.form_key);
            self.warehouseUrl(config.warehouseUrl);
            self.quoteId(config.quote_id);
            self.bind('click', /^np_w2c_city_checkbox$/i, self.onCheckboxCity);
            self.bind('click', /^np_w2c_warehouse_checkbox$/i, self.onCheckboxWarehouse);
            self.bind('click', /^np_w2c_choosen_city_address$/i, self.onCityChange);
            self.bind('click', /^np_w2c_choosen_contact_person_address$/i, self.onContactPersonChange);
            self.bind('click', /^create_ttn_w2c$/i, self.onCreateTTN);
            return this;
        },
        bind: function (eventType, elementIdRegExp, cb) {
            document.addEventListener(eventType, function (event) {
                var el = event.target, found;
                while (el && !(found = el.id.match(elementIdRegExp))) {
                    el = el.parentElement;
                }
                if (found) {
                    cb.call(this, el, event);
                }
            }.bind(this));
        },
        onCheckboxCity: function (a, b) {
            document.getElementsByName('np_w2c_cityValue')[0]?.toggleAttribute('disabled');
            console.log('city_checkbox');
        },
        onCheckboxWarehouse: function (a, b) {
            document.getElementsByName('np_w2c_warehouse')[0]?.toggleAttribute('disabled');
            console.log('warehouse_checkbox');
        },

        onCityChange: function (a, b) {
            console.log('city changed');
            $('#loader').show();
            $.ajax({
                method: "POST",
                url: this.contactPersonSearchUrl(),
                data: {
                    form_key: this.formKey(),
                    citySender: $(a).val(),
                },
                success: function (a, b, c) {
                    console.log('city good');
                    var contactPersonArr = null;
                    try {
                        if (a instanceof Object) {
                            contactPersonArr = Object.entries(a);
                        } else {
                            contactPersonArr = Object.entries(JSON.parse(a));
                        }

                        var contactPersonSelect = $('#np_w2c_choosen_contact_person_address');
                        contactPersonSelect.find('option').remove().end();
                        $.each(contactPersonArr, function (indx, val) {
                            contactPersonSelect.append($("<option />").val(val[1].ref).text(val[1].description));
                        });
                        console.log(a + " " + b + " " + c);
                        $('#np_w2c_choosen_contact_person_address').change();
                    } catch (e) {
                        console.log(e);
                    }
                    $('#loader').hide();
                },
                error: function (a, b, c) {
                    console.log('city error');
                    $('#loader').hide();
                    console.log(a + " " + b + " " + c);
                }
            });
        },
        onContactPersonChange: function (a, b) {
            console.log('contact person changed');
            $('#loader').show();
            $.ajax({
                method: "POST",
                url: this.contactPersonAddressSearchUrl(),
                data: {
                    form_key: this.formKey(),
                    citySender: $('#np_w2c_choosen_city_address').val(),
                    senderAddress: $('#np_w2c_choosen_contact_person_address').val(),
                },
                success: function (a, b, c) {
                    console.log('contact person good');
                    var contactPersonAddressArr = null;
                    try {
                        contactPersonAddressArr = Object.entries(JSON.parse(a));

                        var contactPersonAddressSelect = $('#np_w2c_choosen_contact_person_address_place');
                        contactPersonAddressSelect.find('option').remove().end();
                        $.each(contactPersonAddressArr, function (indx, val) {
                            contactPersonAddressSelect.append($("<option />").val(val[1].ref).text(val[1].description));
                        });
                        console.log(a + " " + b + " " + c);
                        $('#np_w2c_choosen_contact_person_address_place').change();
                    } catch (e) {
                        console.log(e);
                    }
                    $('#loader').hide();
                },
                error: function (a, b, c) {
                    console.log('contact person error');
                    $('#loader').hide();
                    console.log(a + " " + b + " " + c);
                }
            });
        },

        onCreateTTN: function (a, b) {
            if ($) {
                $('#loader').show();
                $.ajax({
                    method: "POST",
                    url: this.urlForSendRequestToNP(),
                    data: {
                        form_key: this.formKey(),
                        quoteId: this.quoteId(),
                        cityHidden: $('[name="np_w2c_cityValue"]').val(),
                        warehouse: $('[name="np_w2c_warehouse"]').val(),
                        order_id: $('#order_id').val(),
                        citySender: $('#np_w2c_choosen_city_address').val(),
                        sender: $('#np_w2c_choosen_contact_person_address').val(),
                        senderAddress: $('#np_w2c_choosen_contact_person_address_place').val(),
                    },
                    success: function (a, b, c) {
                        console.log('good');
                        var responseFromBackend = null;
                        try {
                            responseFromBackend = JSON.parse(a);

                            if (responseFromBackend.success) {
                                window.trackingControl.add();
                                var lastCount = $('#track_row_container tr').length - 1;
                                $('[name="tracking\\[' + lastCount + '\\]\\[carrier_code\\]"]').val('custom');
                                $('[name="tracking\\[' + lastCount + '\\]\\[title\\]"]').val(responseFromBackend.data[0].Ref);
                                $('[name="tracking\\[' + lastCount + '\\]\\[number\\]"]').val(responseFromBackend.data[0].IntDocNumber);
                            } else {
                                var html = "";
                                $.each(responseFromBackend.errors, function (idx, val) {
                                    html += "<li>" + val + "</li>";
                                });
                                var resultHtmlError = "<ul>" + html + "</ul>";
                                var errorDesc = $('#error-list-desc-w2c');
                                errorDesc.html('');
                                errorDesc.append(resultHtmlError);
                                html = "";
                                $.each(responseFromBackend.errorCodes, function (idx, val) {
                                    html += "<li>" + val + "</li>";
                                });
                                resultHtmlError = "<ul>" + html + "</ul>";
                                var errorCodes = $('#error-list-codes-w2c');
                                errorCodes.html('');
                                errorCodes.append(resultHtmlError);
                                console.log(a + " " + b + " " + c);
                            }
                        } catch (e) {
                            console.log(e);
                        }
                        $('#loader').hide();
                    },
                    error: function (a, b, c) {
                        console.log('error');
                        $('#loader').hide();
                        console.log(a + " " + b + " " + c);
                    }
                });

            }
        }
    });
});
