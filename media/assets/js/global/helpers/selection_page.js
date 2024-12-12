const acym_helperSelectionPage = {
    initSelectionPage: function () {
        if (jQuery('.acym__selection_disabled').length === 0) {
            this.setSelectionElement(false, false, undefined, '#acym__selection__button-select');
            this.setSelectionButton();
            this.setCampaignSelectionButton();
            this.handlePromotionPopup();
            this.setCampaignTabs();
        }
    },
    setSelectionElement: function (haveSettings = false, configuration = false, callback = undefined, scrollElementSelector = undefined, options = {}) {
        const locatorPrefix = options.prefix !== undefined ? options.prefix : '';
        const $allCards = jQuery(`${locatorPrefix} .acym__selection__card:not(.acym__selection__card__disabled), ${locatorPrefix} .acym__campaign__selection__card:not(.acym__selection__card__disabled)`);

        $allCards.off('click').on('click', function () {
            if (jQuery(this).hasClass('acym__selection__card-selected')) return;
            $allCards.removeClass('acym__selection__card-selected');
            jQuery(this).addClass('acym__selection__card-selected');

            if (haveSettings) acym_helperSelectionPage.setDisplaySettings(this);
            if (undefined !== callback) callback(this);
            if (!jQuery(this).hasClass('acym__selection__card__promotion') && jQuery(this).hasClass('acym__campaign__selection__card') && jQuery(this)
                .hasClass('acym__selection__scroll') && undefined !== scrollElementSelector) {
                document.querySelector(scrollElementSelector)
                        .scrollIntoView({
                            behavior: 'smooth',
                            block: 'center'
                        });
            }

            if (configuration) return;

            if (jQuery(this).hasClass('acym__selection__card__promotion')) {
                acym_helperSelectionPage.updatePromotionPopupText(this);
                jQuery('#acym__selection__button-select').attr('disabled', 'true');
                jQuery('.acym__promotion__popup__container').css('display', 'flex');
            } else if (!jQuery(this).hasClass('acym__selection__select-card') || jQuery(this).find('.acym__selection__select-card__select').val() !== '') {
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
    },
    setCampaignSelectionButton: function () {
        jQuery('.acym__campaign__selection__button-select').off('click').on('click', function () {
            if (jQuery(this).hasClass('acym__promotion__disabled__button')) {
                jQuery('.acym__promotion__popup__container').css('display', 'flex');
            } else {
                let link = jQuery(this).attr('acym-data-link');
                if (link && link.indexOf('{dataid}') !== -1) {
                    const listId = jQuery(this).closest('.acym__campaign__selection__card').find('.acym__email__new__card__select').val();
                    link = link.replace('{dataid}', listId);
                }
                if (link) window.location.href = link;
            }
        });
    },
    handlePromotionPopup: function () {
        const $popupContainer = jQuery('.acym__promotion__popup__container');
        const $popupContent = jQuery('.acym__promotion__popup__content');
        let type = '';
        const $cardType = jQuery('.acym__selection__card') ? '.acym__selection__card' : '.acym__campaign__selection__card';
        if ($cardType === '.acym__selection__card') {
            type = 'acym__selection';
        } else {
            type = 'acym__campaign__selection';
        }
        jQuery('.acym__promotion__popup__back').off('click').on('click', function () {
            $popupContainer.css('display', 'none');
            jQuery(`.${type}__card-selected`).removeClass(`${type}__card-selected`);
            jQuery(`#${type}__button-select`).attr('disabled', 'true');
        });

        $popupContainer.off('click').on('click', function (e) {
            if (!$popupContent.is(e.target) && $popupContent.has(e.target).length === 0) {
                $popupContainer.css('display', 'none');
                jQuery(`.${type}__card-selected`).removeClass(`${type}__card-selected`);
                jQuery(`#${type}__button-select`).attr('disabled', 'true');
            }
        });
    },
    setCampaignTabs: function () {
        jQuery('.step').off('click').on('click', function () {
            jQuery('.step').removeClass('current_step');
            jQuery(this).addClass('current_step');

            const tabId = jQuery(this).attr('data-tab');

            jQuery('#acym__campaign__selection__newsletters, #acym__campaign__selection__onetime').hide();
            jQuery(`#${tabId}`).show();
        });
    },
    updatePromotionPopupText: function (card) {
        const $card = jQuery(card);
        if (!$card.hasClass('scheduled')) {
            jQuery('#promotionPopup .acym__enterprise__popup').show();
            jQuery('#promotionPopup .acym__essential__popup').hide();
        } else if ($card.hasClass('scheduled')) {
            console.log('Essential');
            jQuery('#promotionPopup .acym__enterprise__popup').hide();
            jQuery('#promotionPopup .acym__essential__popup').show();
        }
    }
};
