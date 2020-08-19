const acym_helperSwitch = {
    setSwitchFieldsGlobal: function () {
        jQuery('.switch-label').on('click', function () {
            let input = jQuery('input[data-switch="' + jQuery(this).attr('for') + '"]');
            input.attr('value', 1 - input.attr('value'));
        });
    }
};
