const acym_helperSelectionPage = {
    initSelectionPage: function () {
        if (jQuery('.acym__selection_disabled').length === 0) {
            this.setSelectionElement(false, false, undefined, '#acym__selection__button-select');
            this.setSelectionButton();
        }
    },
    setSelectionElement: function (haveSettings = false, configuration = false, callback = undefined, scrollElementSelector = undefined) {
        const $allCards = jQuery('.acym__selection__card:not(.acym__selection__card__disabled)');
        $allCards.off('click').on('click', function () {
            if (jQuery(this).hasClass('acym__selection__card-selected')) return;
            $allCards.removeClass('acym__selection__card-selected');
            jQuery(this).addClass('acym__selection__card-selected');

            if (haveSettings) acym_helperSelectionPage.setDisplaySettings(this);
            if (undefined !== callback) callback();

            if (undefined !== scrollElementSelector) {
                document.querySelector(scrollElementSelector)
                        .scrollIntoView({
                            behavior: 'smooth',
                            block: 'end'
                        });
            }

            if (configuration) return;

            if ((jQuery(this).hasClass('acym__selection__select-card') && jQuery(this).find('.acym__selection__select-card__select').val() !== '') || !jQuery(
                this).hasClass('acym__selection__select-card')) {
                jQuery('#acym__selection__button-select').removeAttr('disabled');
            } else {
                jQuery('#acym__selection__button-select').attr('disabled', 'true');
            }
        });
    },
    setSelectionButton: function () {
        jQuery('#acym__selection__button-select').off('click').on('click', function () {
            const $cardSelected = jQuery('.acym__selection__card-selected');
            let link = $cardSelected.attr('acym-data-link');
            if (link.indexOf('{dataid}') !== -1) {
                const listId = $cardSelected.find('.acym__email__new__card__select').val();
                link = link.replace('{dataid}', listId);
            }
            window.location.href = link;
        });
    },
    setDisplaySettings: function (element) {
        let settings = document.getElementsByClassName('send_settings');
        let selectedSetting;
        for (let setting of settings) {
            if (setting.id === `${element.id}_settings`) selectedSetting = setting;
            setting.style.display = 'none';
        }

        if (undefined !== selectedSetting) selectedSetting.style.display = 'flex';
    }
};
