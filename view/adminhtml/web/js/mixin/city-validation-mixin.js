define(['jquery'], function ($) {
    'use strict';

    return function () {
        $.validator.addMethod(
            'validate-novaposhta-city',
            function (value, element) {
                if (value === $.mage.__('Choose the city...')) {
                    return false;
                }
                if (value.length === 0) {
                    return false
                }
                return true;
            },
            $.mage.__('Please select the city from lists.')
        );
    }
});
