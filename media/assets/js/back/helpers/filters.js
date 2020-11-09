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
                    let newOption = new Option(response.text, response.value, false, false);
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
    }
};
