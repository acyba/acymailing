const acym_helperRadio = {
    setRadioIconsGlobal: function () {
        jQuery('i.acym_radio_unchecked').on('click', function () {
            let $radio = jQuery('[for="' + jQuery(this).attr('data-radio') + '"]');
            $radio.click();
        });

        jQuery('.acym_radio_group > input[type="radio"]').off('change').on('change', function () {
            let radioName = jQuery(this).attr('name');
            let checked = jQuery('input[name="' + radioName + '"]:checked').val();

            // We do it separated in two loops because we need to hide the related elements when unselected before showing the selected elements with acym-data-related
            // Some elements may be related to several options like in the config => queue process options
            let $radioOptions = jQuery('input[name="' + radioName + '"]');
            $radioOptions.each(function () {
                let radioId = jQuery(this).attr('id');
                let relatedElementsClass = jQuery(this).attr('acym-data-related');

                if (jQuery(this).val() !== checked) {
                    jQuery('i[data-radio="' + radioId + '"].acym_radio_unchecked').show();
                    jQuery('i[data-radio="' + radioId + '"].acym_radio_checked').hide();

                    if (!acym_helper.empty(relatedElementsClass)) {
                        jQuery('.' + relatedElementsClass).hide();
                    }
                }
            });

            $radioOptions.each(function () {
                let radioId = jQuery(this).attr('id');
                let relatedElementsClass = jQuery(this).attr('acym-data-related');

                if (jQuery(this).val() === checked) {
                    jQuery('i[data-radio="' + radioId + '"].acym_radio_unchecked').hide();
                    jQuery('i[data-radio="' + radioId + '"].acym_radio_checked').show();

                    if (!acym_helper.empty(relatedElementsClass)) {
                        jQuery('.' + relatedElementsClass).show();
                    }
                }
            });
        }).change();
    }
};
