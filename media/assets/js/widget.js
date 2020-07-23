const acym_widget = {
    init: function () {
        this.initSelect();
        this.initWordpressWidget();
    },
    initSelect: function () {
        jQuery('.acym_simple_select2').not('#widget-list .acym_simple_select2').not('.select2-hidden-accessible').select2({width: '100%'});

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
                        trigger: 'getPosts',
                    };
                },
                processResults: function (data) {
                    let options = [];
                    if (data) {
                        jQuery.each(data, function (index, text) {
                            options.push({
                                id: text[0],
                                text: text[1],
                            });
                        });
                    }
                    return {
                        results: options,
                    };
                },
                cache: true,
            },
            minimumInputLength: 3,
            width: '100%',
            allowClear: true,
            placeholder: '- - -',
        });
    },
    initWordpressWidget: function () {
        jQuery(document).on('widget-added', () => {
            this.initSelect();
        });

        jQuery(document).on('widget-updated', () => {
            this.initSelect();
        });
    },
};

acym_widget.init();
