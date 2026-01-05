jQuery(function ($) {

    function Init() {
        refreshActionsProcess();
        rebuildActions();
    }

    Init();

    function rebuildActions() {
        let $actionElement = $('#actions');
        if (!$actionElement.length) return;

        let actions = acym_helper.parseJson($actionElement.val());
        let and = 0;

        // Foreach actions
        $.each(actions, function (numAND, oneAction) {
            // Create a new block if needed
            if (and > 0) $('.acym__automation__add-action').trigger('click');

            $.each(oneAction, function (actionName, actionOptions) {
                // Select the action type in the correct dropdown
                let $actionSelect = $('.acym__automation__actions__select').last();
                $actionSelect.val(actionName);
                $actionSelect.trigger('change');

                let keys = Object.keys(actionOptions);
                $.each(keys, function (key) {
                    let optionName = keys[key];
                    let optionValue = actionOptions[keys[key]];

                    // Set the option values
                    let $optionField = $('[name^="acym_action[actions][' + and + '][' + actionName + '][' + optionName + ']"]');

                    acym_helperFilter.setFieldValue($optionField, optionValue);

                    if ('acy_add_queue' === actionName && 'mail_id' === optionName && '' !== optionValue) {
                        $optionField.next()
                                    .html(actionOptions.mail_name
                                          + '<i class="cursor-pointer acymicon-close acym__color__red acym__automation__action__reset__mail margin-left-1"></i>');
                        $optionField.prev().html(ACYM_JS_TXT.ACYM_EDIT_MAIL);
                    }

                    $optionField.trigger('change');
                });
            });
            and++;
        });
        refreshActionsProcess();
    }

    function refreshActionsProcess() {
        if ($('#adminAutomation').val() == 1) {
            $(document).find('[data-open*="acy_add_queuetime"]').attr('disabled', 'disabled');
        }
        acym_helperDatePicker.setDatePickerGlobal();
        acym_helperDatePicker.setRSDateChoice();
        acym_helperSelect2.setAjaxSelect2();
        setSelectAction();
        setButtonAddAction();
        setDeleteAction();
        acym_helper.setSubmitButtonGlobal();
        setSelectMailAction();
        acym_helperModal.setResetMail();
        acym_helperModal.setTemplateModal(true);
    }

    function setSelectAction() {
        const $options = $('#acym__automation__actions__json');
        if (!$options.length) {
            return;
        }

        const actions = acym_helper.parseJson($options.val());

        $('.acym__automation__actions__select').off('change.loadoptions').on('change.loadoptions', function () {
            $(this).parent().parent().find('.acym__automation__inserted__action').remove();
            const html = actions[$(this).val()].option.replace(
                /__and__/g,
                $(this).closest('.acym__automation__actions__one__action').attr('data-action-number')
            );
            $(this)
                .parent()
                .after('<div class="grid-x acym__automation__inserted__action margin-top-1 margin-left-2 margin-right-2 grid-margin-x cell acym_vcenter">' + html + '</div>');
            if ($('#adminAutomation').val() == 1) {
                $(this).parent().next().find('[data-open*="acy_add_queuetime"]').attr('disabled', 'disabled');
            }
            acym_helperSelect2.setSelect2();
            acym_helperDatePicker.setDatePickerGlobal();
            acym_helperTooltip.setTooltip();
            $(document).foundation();
            $('.reveal-overlay').appendTo('#acym_form');
            refreshActionsProcess();

            const $fieldsDropdown = $(this).closest('.acym__automation__actions__one__action').find('.acym__automation__actions__fields__dropdown');
            const $operatorDropdown = $(this).closest('.acym__automation__actions__one__action').find('.acym__automation__actions__operator__dropdown');

            $operatorDropdown.on('change', function () {
                $fieldsDropdown.trigger('change');
            });

            $fieldsDropdown.on('change', function () {
                const $parent = $(this).closest('.acym__automation__inserted__action');
                const $select = $parent.find('[data-action-field="' + $(this).val() + '"]');
                const $selects = $parent.find('.acym__automation__actions__fields__select');
                const $defaultInput = $parent.find('.acym__automation__action__regular-field');

                if ($select.length > 0 && $operatorDropdown.val() === '=') {
                    $defaultInput.attr('name', $defaultInput.attr('name').replace('acym_action', '')).hide();
                    $selects.each(function (index) {
                        $(this).attr('name', $(this).attr('name').replace('acym_action', ''));
                        $(this).closest('.acym__automation__one-field').hide();
                    });
                    if ($select.attr('name').indexOf('acym_action') === -1) {
                        $select.attr('name', 'acym_action' + $select.attr('name'));
                    }
                    $select.closest('.acym__automation__one-field').show();
                } else {
                    if ($defaultInput.attr('name').indexOf('acym_action') === -1) {
                        $defaultInput.attr('name', 'acym_action' + $defaultInput.attr('name'));
                    }
                    if ($selects.length > 0) {
                        $selects.each(function () {
                            $(this).attr('name', $(this).attr('name').replace('acym_action', ''));
                            $(this).closest('.acym__automation__one-field').hide();
                        });
                    }
                    $defaultInput.show();
                }
            });
            $(this).closest('.acym__automation__actions__one__action').find('.acym__automation__actions__fields__dropdown').trigger('change');
        });
    }

    function setSelectMailAction() {
        $('.acym__automation__actions__mails__select').on('change', function () {
            $(this)
                .closest('.acym__automation__inserted__action')
                .find('[data-task="createMail"]')
                .html('0' === $(this).val() ? ACYM_JS_TXT.ACYM_CREATE_MAIL : ACYM_JS_TXT.ACYM_EDIT_MAIL);
        });
    }

    function setButtonAddAction() {
        $('.acym__automation__add-action').off('click').on('click', function () {
            let $input = $('#acym__automation__action__number__action');
            $input.val(parseInt($input.val()) + 1);
            $(this).before($('#acym__automation__example').html());
            let $newElement = $(this).prev();
            $newElement.closest('.acym__automation__actions__one__action').attr('data-action-number', $input.val());
            $newElement.find('select')
                       .select2({
                           theme: 'foundation',
                           width: '100%'
                       });
            refreshActionsProcess();
        });
    }

    function setDeleteAction() {
        $('.acym__automation__delete__one__action').off('click').on('click', function () {
            $(this).closest('.acym__automation__actions__one__action').remove();
        });
    }
});
