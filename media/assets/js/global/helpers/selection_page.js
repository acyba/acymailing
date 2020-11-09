const acym_helperSelectionPage = {
    initSelectionPage: function () {
        if (jQuery('.acym__selection_disabled').length === 0) {
            this.setSelectionElement();
            this.setSelectionSelectCard();
            this.setSelectionButton();
            if (ACYM_IS_ADMIN) this.setSelectSelect2Theme();
        }
    },
    setSelectionElement: function () {
        const $allCards = jQuery('.acym__selection__card:not(.acym__selection__card__disabled)');
        $allCards.off('click').on('click', function () {
            if (jQuery(this).hasClass('acym__selection__card-selected')) return;
            $allCards.removeClass('acym__selection__card-selected');
            jQuery(this).addClass('acym__selection__card-selected');

            if ((jQuery(this).hasClass('acym__selection__select-card') && jQuery(this).find('.acym__selection__select-card__select').val() !== '') || !jQuery(
                this).hasClass('acym__selection__select-card')) {
                jQuery('#acym__selection__button-select').removeAttr('disabled');
            } else {
                jQuery('#acym__selection__button-select').attr('disabled', 'true');
            }
        });
    },
    setSelectionSelectCard: function () {
        jQuery('.acym__selection__select-card__select').on('change', function () {
            if (jQuery(this).closest('.acym__selection__card').hasClass('acym__selection__card-selected')) {
                if (jQuery(this).val() !== '') {
                    jQuery('#acym__selection__button-select').removeAttr('disabled');
                } else {
                    jQuery('#acym__selection__button-select').attr('disabled', 'true');
                }
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
    setSelectSelect2Theme: function () {
        jQuery('.acym__selection__select-card__select').select2({
            theme: 'sortBy',
            minimumResultsForSearch: Infinity
        });
    }
};
