/**
 * Celebros (C) 2022. All Rights Reserved.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish correct extension functionality.
 * If you wish to customize it, please contact Celebros.
 */

define(['jquery'], function($) {
    'use strict';

    return function() {
        $.validator.addMethod(
            'validate-options', function (value, element) {
                if (element.up().up().up().select('option[value='+value+'][selected]').length > 1) {
                    return false;
                }

                return true;
        }, $.mage.__('Duplication of options.'));
    }
});
