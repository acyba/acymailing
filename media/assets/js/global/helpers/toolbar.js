const acym_helperToolbar = {

    initToolbar: function () {
        this.setMoreOptionsButtons();
        this.setStatusOnload();
    },
    setMoreOptionsButtons: function () {
        let $moreOptionsButton = jQuery('#acym__toolbar__button-more-filters');
        let $moreOptionsDiv = jQuery('.acym__toolbar__more-filters');
        let $applyButton = jQuery('#acym__toolbar__more-filters-apply');

        $moreOptionsButton.off('click').on('click', function () {
            $moreOptionsDiv.toggle();
            if ($moreOptionsDiv.is(':visible')) {
                $moreOptionsButton.addClass('toggled');
                $moreOptionsButton.html('<i class="acymicon-filter"></i>' + ACYM_JS_TXT.ACYM_HIDE_FILTERS);
            } else {
                $moreOptionsButton.removeClass('toggled');
                $moreOptionsButton.html('<i class="acymicon-filter"></i>' + ACYM_JS_TXT.ACYM_SHOW_FILTERS);
            }
        });

        $applyButton.off('click').on('click', function () {
            jQuery('#acym_form').submit();
        });
    },
    setStatusOnload: function () {
        let statuses = jQuery('#acym__toolbar__statuses-value').val();

        if (undefined === statuses || '' === statuses) return;

        statuses = acym_helper.parseJson(statuses);
        Object.keys(statuses).map(status => {
            let $input = jQuery('[name="' + status + '"]');
            if ($input.length === 0) return false;
            $input.val(statuses[status]);
        });

        jQuery('#acym__toolbar__button-more-filters').trigger('click');
    }
};
