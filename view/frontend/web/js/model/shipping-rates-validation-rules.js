
define(
    [],
    function () {
        "use strict";
        return {
            getRules: function () {
                return {
                    'postcode': {
                        'required': false
                    },
                    'city': {
                        'required': true
                    },
                    'region': {
                        'required': false
                    },
                    'region_id': {
                        'required': false
                    },
                    'country_id': {
                        'required': false
                    },
                };
            }
        };
    }
);
