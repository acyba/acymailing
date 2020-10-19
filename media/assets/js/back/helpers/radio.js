const acym_helperRadio = {
    setRadioIconsGlobal: function () {
        jQuery('i.acym_radio_unchecked').on('click', function () {
            let $radio = jQuery('[for="' + jQuery(this).attr('data-radio') + '"]');
            $radio.click();
        });

        jQuery('.acym_radio_group > input[type="radio"]').off('change').on('change', function () {
            let radioName = jQuery(this).attr('name');
            let checked = jQuery('input[name="' + radioName + '"]:checked').val();

            jQuery('input[name="' + radioName + '"]').each(function () {
                let radioId = jQuery(this).attr('id');
                if (jQuery(this).val() === checked) {
                    jQuery('i[data-radio="' + radioId + '"].acym_radio_unchecked').hide();
                    jQuery('i[data-radio="' + radioId + '"].acym_radio_checked').show();
                } else {
                    jQuery('i[data-radio="' + radioId + '"].acym_radio_unchecked').show();
                    jQuery('i[data-radio="' + radioId + '"].acym_radio_checked').hide();
                }
            });
        }).change();
    }
};
