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

    $.setFieldValue = function ($field, value) {
        if ($field.hasClass('acym_select2_ajax')) {
            let ctrl = $field.attr('data-ctrl');
            if (!ctrl) ctrl = 'dynamics';
            let task = $field.attr('data-task');
            if (!task) task = 'trigger';

            let url = ACYM_AJAX_URL + '&ctrl=' + ctrl + '&task=' + task + '&id=' + encodeURIComponent(value);

            let dataParams = $field.attr('data-params');
            let decodedParams = acym_helper.parseJson(dataParams);
            url += '&' + $.param(decodedParams);

            $.get(url, function (response) {
                response = acym_helper.parseJson(response);
                let newOption = new Option(response.value, value, false, false);
                $field.append(newOption).trigger('change');
            });

        } else if (!$field.is(':checkbox') && $field.attr('data-switch') === undefined) {
            $field.val(value);
        } else if ($field.is(':checkbox') && value == 1) {
            $field.prop('checked', true);
        } else if ($field.attr('data-switch') !== undefined && $field.val() != value) {
            $field.closest('.medium-3').find('.cell.switch-label').click();
        }

        if ($field.attr('data-rs') !== undefined && value !== '') {
            if (value.indexOf(']') !== -1) {
                $('input[data-open="' + $field.attr('data-rs') + '"]').val(value);
            } else {
                $('input[data-open="' + $field.attr('data-rs') + '"]').val(moment.unix(value).format('DD MMM YYYY HH:mm'));
            }
        }
    };
});
