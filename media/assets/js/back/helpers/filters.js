const acym_helperFilter = {
    setFieldValue: function ($field, value) {
        if ($field.hasClass('acym_select2_ajax')) {
            let ctrl = $field.attr('data-ctrl');
            if (!ctrl) ctrl = 'dynamics';
            let task = $field.attr('data-task');
            if (!task) task = 'trigger';

            let url = ACYM_AJAX_URL + '&ctrl=' + ctrl + '&task=' + task + '&id=' + encodeURIComponent(value);

            let dataParams = $field.attr('data-params');
            let decodedParams = acym_helper.parseJson(dataParams);
            url += '&' + jQuery.param(decodedParams);

            jQuery.get(url, function (response) {
                response = acym_helper.parseJson(response);
                if (Array.isArray(response)) {
                    response.map((option, index) => {
                        let newOption = new Option(option.text, option.value, false, true);
                        $field.append(newOption).trigger('change');
                    });
                } else {
                    let newOption = new Option(response.text, response.value, true, true);
                    $field.append(newOption).trigger('change');
                }
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
                jQuery('input[data-open="' + $field.attr('data-rs') + '"]').val(value);
            } else {
                jQuery('input[data-open="' + $field.attr('data-rs') + '"]').val(moment.unix(value).format('DD MMM YYYY HH:mm'));
            }
        }
    },
    setAutomationReload: function () {
        jQuery('[acym-automation-reload]').each(function () {
            jQuery(this).on('change', function () {
                let selectReload = jQuery(this);
                setTimeout(function () {
                    let params = selectReload.attr('acym-automation-reload');
                    let decodedParams = JSON.parse(params);

                    if (!acym_helper.empty(decodedParams['plugin'])) {
                        decodedParams = [decodedParams];
                    }

                    decodedParams.forEach(function (oneDecodedParams) {
                        let postParams = {
                            'ctrl': 'dynamics',
                            'task': 'trigger',
                            'plugin': oneDecodedParams['plugin'],
                            'trigger': oneDecodedParams['trigger']
                        };

                        if (oneDecodedParams['name']) {
                            postParams['name'] = oneDecodedParams['name'];
                            postParams['value'] = jQuery('[name="' + oneDecodedParams['name'] + '"]').val();
                        }

                        if (oneDecodedParams['params']) {
                            for (let [identifier, name] of Object.entries(oneDecodedParams['params'])) {
                                postParams[identifier] = name;
                            }
                        }

                        if (oneDecodedParams['paramFields']) {
                            for (let [identifier, name] of Object.entries(oneDecodedParams['paramFields'])) {
                                postParams[identifier] = jQuery('[name="' + name + '"]').val();
                            }
                        }

                        jQuery.ajax({
                            type: 'POST',
                            url: ACYM_AJAX_URL,
                            data: postParams,
                            success: function (result) {
                                let $container = jQuery(oneDecodedParams['change']);
                                $container.html(result);

                                [
                                    'segments',
                                    'automation'
                                ].forEach(function (selector) {
                                    let containerClasses = '.acym__' + selector + '__one__filter.acym__' + selector + '__one__filter__classic';
                                    let inputClasses = '.acym__' + selector + '__inserted__filter input';
                                    inputClasses += ', .acym__' + selector + '__inserted__filter textarea';
                                    inputClasses += ', .acym__' + selector + '__inserted__filter select';

                                    $container
                                        .closest(containerClasses)
                                        .find(inputClasses)
                                        .on('change acym-reloaded', function () {
                                            if (selector === 'segments') {
                                                acym_helperSegment.reloadCounters($container);
                                            } else {
                                                jQuery.reloadCounters($container);
                                            }
                                        }).trigger('acym-reloaded');
                                });

                                acym_helperSelect2.setSelect2();
                                acym_helperSelect2.setAjaxSelect2();
                                acym_helperTooltip.setTooltip();
                            }
                        });
                    });
                }, 100);
            });
        });
    }
};
