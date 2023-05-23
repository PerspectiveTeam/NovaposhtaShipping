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
    return Component.extend(
        {
            availableCity: ko.observableArray([]),
            selectedItemShipping: ko.observable(),
            urlForSendRequestToNP: ko.observable(),
            contactPersonSearchUrl: ko.observable(),
            contactPersonAddressSearchUrl: ko.observable(),
            formKey: ko.observable(),
            quoteId: ko.observable(),
            initialize: function (config) {
                var self = this;
                self.urlForSendRequestToNP(config.npUrl);
                self.contactPersonSearchUrl(config.contactPersonSearchUrl);
                self.contactPersonAddressSearchUrl(config.contactPersonAddressSearchUrl);
                self.formKey(config.form_key);
                self.quoteId(config.quote_id);
                self.availableCity(config.cityColl);
                self.bind('click', /^np_c2c_city_checkbox$/i, self.onCheckboxCity);
                self.bind('click', /^np_c2c_street_checkbox$/i, self.onCheckboxStreet);
                self.bind('click', /^np_c2c_street_num_checkbox$/i, self.onCheckboxStreetNum);
                self.bind('click', /^np_c2c_flat_checkbox$/i, self.onCheckboxFlat);
                self.bind('click', /^np_c2c_choosen_city_address$/i, self.onCityChange);
                self.bind('click', /^np_c2c_choosen_contact_person_address$/i, self.onContactPersonChange);
                self.bind('click', /^create_ttn_c2c$/i, self.onCreateTTN);
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
                document.getElementById('np_c2c_city').toggleAttribute('disabled');
                console.log('city_checkbox');
            },
            onCityChange: function (a, b) {
                console.log('city changed');
                $('#loader').show();
                $.ajax({
                    type: "POST",
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

                            var contactPersonSelect = $('#np_c2c_choosen_contact_person_address');
                            contactPersonSelect.find('option').remove().end();
                            $.each(contactPersonArr, function (indx, val) {
                                contactPersonSelect.append($("<option />").val(val[1].ref).text(val[1].description));
                            });
                            console.log(a + " " + b + " " + c);
                            $('#np_c2c_choosen_contact_person_address').change();
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
                        citySender: $('#np_c2c_choosen_city_address').val(),
                        senderAddress: $('#np_c2c_choosen_contact_person_address').val(),
                    },
                    success: function (a, b, c) {
                        console.log('contact person good');
                        var contactPersonAddressArr = null;
                        try {
                            contactPersonAddressArr = Object.entries(JSON.parse(a));

                            var contactPersonAddressSelect = $('#np_c2c_choosen_contact_person_address_place');
                            contactPersonAddressSelect.find('option').remove().end();
                            $.each(contactPersonAddressArr, function (indx, val) {
                                contactPersonAddressSelect.append($("<option />").val(val[1].ref).text(val[1].description));
                            });
                            console.log(a + " " + b + " " + c);
                            $('#np_c2c_choosen_contact_person_address_place').change();
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
            onCheckboxStreet: function (a, b) {
                document.getElementById('np_c2c_street').toggleAttribute('disabled');
                console.log('street_checkbox');
            },
            onCheckboxStreetNum: function (a, b) {
                document.getElementById('np_c2c_street_num').toggleAttribute('disabled');
                console.log('street_num_checkbox');
            },
            onCheckboxFlat: function (a, b) {
                document.getElementById('np_c2c_flat').toggleAttribute('disabled');
                console.log('flat_checkbox');
            },

            onCreateTTN: function (a, b) {
                if ($) {
                    $(a).prop('disabled', true);
                    $('#loader').show();
                    $.ajax({
                        method: "POST",
                        url: this.urlForSendRequestToNP(),
                        data: {
                            form_key: this.formKey(),
                            quoteId: this.quoteId(),
                            cityHidden: $('[name="np_w2c_cityValue"]').val(),
                            street: $('#np_c2c_street').val(),
                            building: $('#np_c2c_street_num').val(),
                            flat: $('#np_c2c_flat').val(),
                            order_id: $('#order_id').val(),
                            citySender: $('#np_c2c_choosen_city_address').val(),
                            sender: $('#np_c2c_choosen_contact_person_address').val(),
                            senderAddress: $('#np_c2c_choosen_contact_person_address_place').val(),
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
                                    var errorDesc = $('#error-list-desc-c2c');
                                    errorDesc.html('');
                                    errorDesc.append(resultHtmlError);
                                    html = "";
                                    $.each(responseFromBackend.errorCodes, function (idx, val) {
                                        html += "<li>" + val + "</li>";
                                    });
                                    resultHtmlError = "<ul>" + html + "</ul>";
                                    var errorCodes = $('#error-list-codes-c2c');
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
                            $(a).prop('disabled', false);
                            console.log(a + " " + b + " " + c);
                        }
                    });
                }
            }
        });
});
