const acym_helperRadio = {
    setRadioIconsGlobal: function () {
        jQuery('i.acym_radio_unchecked').on('click', function () {
            let $radio = jQuery('[for="' + jQuery(this).attr('data-radio') + '"]');
            $radio.click();
        });

        jQuery('input[type="radio"]').on('change', function () {
            let $checked = jQuery('input[name="' + jQuery(this).attr('name') + '"]:checked').val();
            jQuery('input[name="' + jQuery(this).attr('name') + '"]').each(function () {
                if (jQuery(this).val() == $checked) {
                    jQuery('i[data-radio="' + jQuery(this).attr('id') + '"].acym_radio_unchecked').hide();
                    jQuery('i[data-radio="' + jQuery(this).attr('id') + '"].acym_radio_checked').show();
                } else {
                    jQuery('i[data-radio="' + jQuery(this).attr('id') + '"].acym_radio_unchecked').show();
                    jQuery('i[data-radio="' + jQuery(this).attr('id') + '"].acym_radio_checked').hide();
                }
            });
        }).change();
    }
};
