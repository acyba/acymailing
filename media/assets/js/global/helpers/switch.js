const acym_helperSwitch = {
    setSwitchFieldsGlobal: function () {
        document.addEventListener('change', acym_helperSwitch.syncHiddenInput, true);
    },
    syncHiddenInput: function (event) {
        if (!event.target.classList || !event.target.classList.contains('switch-input')) {
            return;
        }

        jQuery('input[data-switch="' + event.target.id + '"]').attr('value', event.target.checked ? 1 : 0).trigger('change');
    },
    setButtonSwitch: function () {
        let $switchButtons = jQuery('button[acym-button-switch-type]');
        $switchButtons.off('click').on('click', function (event) {
            event.preventDefault();
            let $clickedType = jQuery(this);
            $clickedType.parent().find('> .button:not("button-secondary")').addClass('button-secondary');
            $clickedType.removeClass('button-secondary');

            jQuery('a[data-tab-identifier="' + $clickedType.attr('acym-button-switch-type').toLowerCase() + '"]').trigger('click');
        });

        $switchButtons.first().trigger('click');
    }
};
