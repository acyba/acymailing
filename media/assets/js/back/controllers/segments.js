jQuery(document).ready(function ($) {
    $.setAutomationReload = function () {
        $('[acym-automation-reload]').each(function () {
            $(this).on('change', function () {
                let params = $(this).attr('acym-automation-reload');
                let decodedParams = JSON.parse(params);

                let postParams = {
                    'ctrl': 'dynamics',
                    'task': 'trigger',
                    'plugin': decodedParams['plugin'],
                    'trigger': decodedParams['trigger']
                };

                if (decodedParams['name']) {
                    postParams['name'] = decodedParams['name'];
                    postParams['value'] = $('[name="' + decodedParams['name'] + '"]').val();
                }

                if (decodedParams['params']) {
                    for (let [identifier, name] of Object.entries(decodedParams['params'])) {
                        postParams[identifier] = name;
                    }
                }

                if (decodedParams['paramFields']) {
                    for (let [identifier, name] of Object.entries(decodedParams['paramFields'])) {
                        postParams[identifier] = $('[name="' + name + '"]').val();
                    }
                }

                $.ajax({
                    type: 'POST',
                    url: ACYM_AJAX_URL,
                    data: postParams,
                    success: function (result) {
                        let $container = $(decodedParams['change']);
                        $container.html(result);

                        acym_helperSelect2.setSelect2();
                        acym_helperSelect2.setAjaxSelect2();
                        acym_helperTooltip.setTooltip();
                        $container
                            .closest('.acym__automation__one__filter.acym__automation__one__filter__classic')
                            .find('.acym__automation__inserted__filter input, .acym__automation__inserted__filter select')
                            .on('change', function () {
                                $.reloadCounters($container);
                            });
                    }
                });
            });
        });
    };
});
