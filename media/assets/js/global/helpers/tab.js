const acym_helperTab = {
    initTab: function () {
        acym_helperTab.setTab();
        acym_helperTab.reloadRadioButtons();
    },
    setTab: function () {
        jQuery('.acym_tab').off('click').on('click', function (e) {
            if (jQuery(this).attr('data-empty') === 'true') {
                return false;
            }

            let tabsIdentifier = jQuery(this).closest('.tabs').attr('id');
            localStorage.setItem('acy' + tabsIdentifier, jQuery(this).attr('data-tab-identifier'));

            let clickedIdentifier = jQuery(this).attr('data-tab-identifier');
            jQuery('.acym__tabs__inbar__element').each(function () {
                let identifier = jQuery(this).attr('acym-data-identifier');
                if (identifier.length > 0) {
                    if (identifier === clickedIdentifier) {
                        jQuery(this).show();
                    } else {
                        jQuery(this).hide();
                    }
                }
            });
        });

        let identifier = jQuery('.tabs').attr('id');
        let selectedTab = localStorage.getItem('acy' + identifier);

        let $lastSelected = jQuery('#' + identifier).find('a[data-tab-identifier="' + selectedTab + '"]');
        if ($lastSelected.length && !$lastSelected.closest('.tabs-title').hasClass('tabs-title-empty')) {
            $lastSelected.trigger('click');
        } else {
            let $tabs = jQuery('#' + identifier + ' .acym_tab');
            $tabs.each(function () {
                if (!jQuery(this).closest('.tabs-title').hasClass('tabs-title-empty')) {
                    jQuery(this).trigger('click');
                    return false;
                }
            });
        }
    },
    reloadRadioButtons: function () {
        jQuery('i.acym_radio_unchecked').on('click', function () {
            let $radio = jQuery('#' + jQuery(this).attr('data-radio'));
            $radio.trigger('click');
        });

        jQuery('input[type="radio"]').on('change', function () {
            let $checked = jQuery('input[name="' + jQuery(this).attr('name') + '"]:checked').val();
            jQuery('input[name="' + jQuery(this).attr('name') + '"]').each(function () {
                if (jQuery(this).val() === $checked) {
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
