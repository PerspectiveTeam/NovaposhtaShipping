define([
    'jquery',
    'ko',
    'uiComponent',
    'uiRegistry'
], function (
    $,
    ko,
    Component,
    uiRegistry
) {
    'use strict';
    return Component.extend(
        {
            selectedItemShipping: ko.observable(),
            urlForSendRequestToNP: ko.observable(),
            formKey: ko.observable(),
            quoteId: ko.observable(),
            initialize: function (config) {
                var self = this;
                self.urlForSendRequestToNP(config.npUrl);
                self.formKey(config.form_key);
                self.quoteId(config.quote_id);
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
                            cityHidden: uiRegistry.get("cityInputAutocompleteShipping").value(),
                            warehouse: uiRegistry.get("warehouseInputAutocompleteShipping").value(),
                            order_id: $('#order_id').val(),
                            sender: $("input[name='novaposhtashipping_sender_hidden']").val(),
                            contactPerson: $("input[name='novaposhtashipping_contact_person_hidden']").val(),
                            contactPersonAddress: $("input[name='novaposhtashipping_contact_person_address_hidden']").val(),
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
