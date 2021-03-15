const acym_helperSwitch = {
    setSwitchFieldsGlobal: function () {
        jQuery('.switch-label').on('click', function () {
            if (jQuery(this).hasClass('disabled')) {
                return;
            }

            let input = jQuery('input[data-switch="' + jQuery(this).attr('for') + '"]');
            input.attr('value', 1 - input.attr('value'));
        });
    },
    setButtonSwitch: function () {
        let $switchButtons = jQuery('button[acym-button-switch-type]');
        $switchButtons.off('click').on('click', function (event) {
            event.preventDefault();
            let $clickedType = jQuery(this);
            $clickedType.parent().find('> .button:not("button-secondary")').addClass('button-secondary');
            $clickedType.removeClass('button-secondary');

            jQuery('a[data-tab-identifier="' + $clickedType.attr('acym-button-switch-type').toLowerCase() + '"]').click();
        });

        $switchButtons.first().click();
    }
};
