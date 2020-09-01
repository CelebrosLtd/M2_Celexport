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
