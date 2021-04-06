const acym_helperPagination = {
    setPaginationGlobal: function () {
        jQuery('.acym__pagination__page').off('click').on('click', function () {
            jQuery('#acym_pagination').val(jQuery(this).attr('page'));
            jQuery('#acym_form').submit();
        });

        jQuery('.acym__lists__filter__tags').off('change').on('change', function () {
            jQuery('#select2-lists_tag-container').html(jQuery(this).find('option:selected').text());
            jQuery('#acym_pagination').val(1);
        });

        jQuery('.acym__templates__filter__tags').off('change').on('change', function () {
            jQuery('#select2-mails_tag-container').html(jQuery(this).find('option:selected').text());
            jQuery('#acym_pagination').val(1);
        });

        jQuery('.acym__choose_template__filter__tags').on('change', function () {
            jQuery('#acym_pagination').val(1);
            jQuery('#acym_form').submit();
        });

        jQuery('.acym__campaigns__filter__tags').off('change').on('change', function () {
            jQuery('#select2-campaigns_tag-container').html(jQuery(this).find('option:selected').text());
            jQuery('#acym_pagination').val(1);
        });

        jQuery('.acym__type__choosen').off('click').on('click', function () {
            jQuery('[id^="acym__type-template"]').val(jQuery(this).attr('data-type'));
            jQuery('.acym_ordering_option').removeClass('is-active');
            jQuery(this).addClass('is-active');
            acym_helperPagination.initPagination();
        });

        jQuery('.acym__filter__status').off('click').on('click', function () {
            jQuery('#acym_filter_status').val(jQuery(this).attr('acym-data-status'));
            acym_helperPagination.initPagination();
        });

        if (ACYM_IS_ADMIN) {
            jQuery('.acym__select__pagination__dropdown')
                .select2({
                    theme: 'foundation',
                    width: '60px'
                });
        }

        jQuery('#acym_pagination_element_per_page').on('change', () => {
            jQuery('#formSubmit').click();
        });
    },
    initPagination: function () {
        jQuery('#acym_pagination').val(1);
        jQuery('#acym_form').submit();
    }
};
