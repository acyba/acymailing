const acym_widget = {
    init: function () {
        this.initSelect();
        this.initWordpressWidget();
    },
    initSelect: function () {
        jQuery('.acym_toggle_div_title').off('click').on('click', function () {
            let closing = jQuery(this).hasClass('acym_toggle_div_active');

            let container = jQuery(this).closest('.acym_toggle_zone');
            container.find('.acym_toggle_div_active + .acym_toggle_div').slideUp();
            container.find('.acym_toggle_div_active').removeClass('acym_toggle_div_active');

            if (closing) return;

            jQuery(this).addClass('acym_toggle_div_active');
            container.find('.acym_toggle_div_active + .acym_toggle_div').slideDown();
        });

        jQuery('.acym_simple_select2').not('#widget-list .acym_simple_select2').not('#available-widgets-list .acym_simple_select2').not('.select2-hidden-accessible').select2({width: '100%'});

        jQuery('.acym_post_select2').not('#widget-list .acym_post_select2').not('.select2-hidden-accessible').select2({
            ajax: {
                url: ajaxurl,
                dataType: 'json',
                delay: 250,
                data: function (params) {
                    return {
                        searchedterm: params.term,
                        action: 'acymailing_router',
                        page: 'acymailing_dynamics',
                        ctrl: 'dynamics',
                        task: 'trigger',
                        plugin: 'plgAcymPost',
                        trigger: 'getPosts'
                    };
                },
                processResults: function (data) {
                    let options = [];
                    if (data) {
                        jQuery.each(data, function (index, text) {
                            options.push({
                                id: text[0],
                                text: text[1]
                            });
                        });
                    }
                    return {
                        results: options
                    };
                },
                cache: true
            },
            minimumInputLength: 3,
            width: '100%',
            allowClear: true,
            placeholder: '- - -'
        });
    },
    initWordpressWidget: function () {
        jQuery(document).on('widget-added', () => {
            this.initSelect();
        });

        jQuery(document).on('widget-updated', () => {
            this.initSelect();
        });
    }
};

acym_widget.init();
