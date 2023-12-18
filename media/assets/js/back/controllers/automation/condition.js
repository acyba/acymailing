jQuery(function ($) {

    function Init() {
        refreshConditionProcess();
        rebuildConditions();
    }

    Init();

    function refreshConditionProcess() {
        setSelectConditions('classic');
        setSelectConditions('user');
        setAddCondition();
        setChooseCondition();
        setAddConditionOr();
        setDeleteCondition();
        acym_helperDatePicker.setDatePickerGlobal();
        acym_helperDatePicker.setRSDateChoice();
        acym_helperSelect2.setAjaxSelect2();
        acym_helperFilter.setAutomationReload();
    }

    function rebuildConditions() {
        let $conditionElement = $('#conditions');
        if (!$conditionElement.length) return;

        let conditions = acym_helper.parseJson($conditionElement.val());
        let type = conditions['type_condition'];

        let or = 0;

        // Foreach OR block
        $.each(conditions, function (numOR, oneORBlock) {
            let and = 0;
            if (numOR === 'type_condition') return true;

            // Create a new OR block if needed
            if (or !== 0) $('.acym__automation__conditions__or[data-condition-type="' + type + '"]').trigger('click');
            // Foreach conditions in this OR block
            $.each(oneORBlock, function (numAND, oneCondition) {
                // Create a new AND block if needed
                if (and !== 0) {
                    $('.acym__automation__group__condition[data-condition-number="' + or + '"]')
                        .find('.acym__automation__add-condition[data-condition-type="' + type + '"]')
                        .trigger('click');
                }

                $.each(oneCondition, function (conditionName, conditionOptions) {
                    // Select the condition type in the correct dropdown
                    let $conditionSelect = $('.acym__automation__group__condition[data-condition-number="' + or + '"]')
                        .find('.acym__automation__select__' + type + '__condition')
                        .last();
                    $conditionSelect.val(conditionName);
                    $conditionSelect.trigger('change');

                    let keys = Object.keys(conditionOptions);
                    $.each(keys, function (key) {
                        let optionName = keys[key];
                        let optionValue = conditionOptions[keys[key]];

                        // Set the option values
                        let $optionField = $('[name^="acym_condition[conditions]['
                                             + or
                                             + ']['
                                             + $('#acym__automation__conditions__count__and').val()
                                             + ']['
                                             + conditionName
                                             + ']['
                                             + optionName
                                             + ']"]');

                        acym_helperFilter.setFieldValue($optionField, optionValue);

                        $optionField.trigger('change');
                    });
                });
                and++;
            });
            or++;
        });
        refreshConditionProcess();
    }

    function setSelectConditions(type) {
        let $options = $('#acym__automation__condition__' + type + '__options');
        if (!$options.length) return;

        let conditions = acym_helper.parseJson($options.val());

        $('.acym__automation__select__' + type + '__condition').off('change').on('change', function () {
            let $inputAnd = $('#acym__automation__conditions__count__and');
            $inputAnd.val(parseInt($inputAnd.val()) + 1);
            $(this).parent().parent().find('.acym__automation__inserted__condition').remove();
            let html = conditions[$(this).val()].replace(/__numor__/g, $(this).closest('.acym__automation__group__condition').attr('data-condition-number'));
            html = html.replace(/__numand__/g, $inputAnd.val());
            $(this)
                .parent()
                .after('<div data-and="'
                       + $inputAnd.val()
                       + '" class="cell grid-x grid-margin-x margin-y acym__automation__inserted__condition margin-top-1 margin-left-2">'
                       + html
                       + '</div>');
            acym_helperSelect2.setSelect2();
            acym_helperDatePicker.setDatePickerGlobal();
            acym_helperTooltip.setTooltip();

            $('.switch-label').off('click').on('click', function () {
                let input = $('input[data-switch="' + $(this).attr('for') + '"]');
                input.attr('value', 1 - input.attr('value'));
            });

            let $operatorDropdown = $(this).closest('.acym__automation__one__condition').find('.acym__automation__conditions__operator__dropdown');
            let $fieldsDropdown = $(this).closest('.acym__automation__one__condition').find('.acym__automation__conditions__fields__dropdown');

            $operatorDropdown.on('change', function () {
                $fieldsDropdown.trigger('change');
            });

            $fieldsDropdown.on('change', function () {
                let $parent = $(this).closest('.acym__automation__inserted__condition');
                let $select = $parent.find('[data-condition-field="' + $(this).val() + '"]');
                let $selects = $parent.find('.acym__automation__conditions__fields__select');
                let $defaultInput = $parent.find('.acym__automation__condition__regular-field');
                if ($select.length > 0 && ($operatorDropdown.val() === '=' || $operatorDropdown.val() === '!=')) {
                    $defaultInput.attr('name', $defaultInput.attr('name').replace('acym_condition', '')).hide();
                    $selects.each(function (index) {
                        $(this).attr('name', $(this).attr('name').replace('acym_condition', ''));
                        $(this).closest('.acym__automation__one-field').hide();
                    });
                    if ($select.attr('name').indexOf('acym_condition') === -1) $select.attr('name', 'acym_condition' + $select.attr('name'));
                    $select.closest('.acym__automation__one-field').show();
                } else {
                    if ($defaultInput.attr('name').indexOf('acym_condition') === -1) $defaultInput.attr('name', 'acym_condition' + $defaultInput.attr('name'));
                    if ($selects.length > 0) {
                        $selects.each(function () {
                            $(this).attr('name', $(this).attr('name').replace('acym_condition', ''));
                            $(this).closest('.acym__automation__one-field').hide();
                        });
                    }
                    $defaultInput.show();
                }
            }).trigger('change');

            $(document).foundation();
            $('.reveal-overlay').appendTo('#acym_form');
            refreshConditionProcess();
        });
    }

    function setAddCondition() {
        $('.acym__automation__add-condition').off('click').on('click', function () {
            let nbANDs = $(this).closest('.acym__automation__group__condition').find('.acym__automation__one__condition').length;
            if (nbANDs === 0) {
                let $clone = $('#acym__automation__and__example').clone().removeAttr('id');
                $clone.find('.acym__automation__and').remove();
                $(this).parent().before($clone.show());
            } else {
                $(this).parent().before($('#acym__automation__and__example').clone().removeAttr('id').show());
            }
            let $newElement = $(this).parent().prev();
            $newElement.addClass('acym__automation__one__condition__' + $(this).attr('data-condition-type'));
            $newElement.find('.acym__automation__and__example__' + $(this).attr('data-condition-type') + '__select')
                       .show()
                       .find('select')
                       .addClass('acym__select')
                       .select2({
                           theme: 'foundation',
                           width: '100%'
                       });
            refreshConditionProcess();
        });
    }

    function setAddConditionOr() {
        $('.acym__automation__conditions__or').off('click').on('click', function () {
            let $inputOr = $('#acym__automation__conditions__count__or');
            $inputOr.val(parseInt($inputOr.val()) + 1);
            $(this).before($('#acym__automation__or__example').html());
            let $newElement = $(this).prev();
            $newElement.attr('data-condition-number', $inputOr.val());
            refreshConditionProcess();
            $newElement.find('button[data-condition-type]').attr('data-condition-type', $(this).attr('data-condition-type')).trigger('click');
        });
    }

    function setDeleteCondition() {
        $('.acym__automation__delete__one__condition').off('click').on('click', function () {
            $(this).closest('.acym__automation__one__condition').remove();
        });
        $('.acym__automation__delete__group__condition').off('click').on('click', function () {
            let $orBlock = $(this).closest('.acym__automation__group__condition');
            $orBlock.prev().remove();
            $orBlock.remove();
        });
    }

    function setChooseCondition() {
        $('.acym__automation__choose__condition').off('click').on('click', function () {
            $('#acym__automation__type-condition__input').val($(this).attr('data-condition'));
            $('.selected-condition').removeClass('selected-condition');
            $(this).addClass('selected-condition');
            let $conditionsContainers = $('.acym__automation__condition__container');
            $conditionsContainers.hide();
            $conditionsContainers.find('[name^="type_condition"]').each(function (index) {
                $(this).attr('name', $(this).attr('name').replace('type_condition', ''));
            });
            let $selectedContainer = $('#acym__automation__conditions__type__' + $(this).attr('data-condition'));
            $selectedContainer.show();
            $selectedContainer.find('[name^="[conditions]"]').each(function (index) {
                $(this).attr('name', 'type_condition' + $(this).attr('name'));
            });
        });
    }

    $.cleanCondition = function () {
        const conditionTypes = {};
        $('.acym__automation__choose__condition').each((index, element) => {
            conditionTypes[element.getAttribute('data-condition')] = $(`#acym__automation__conditions__type__${element.getAttribute('data-condition')}`);
        });
        const conditionTypeNotSelected = $('.acym__automation__choose__condition:not(.selected-condition)').attr('data-condition');

        $(conditionTypes[conditionTypeNotSelected]).find('[name^="acym_condition"]').remove();

        return true;
    };
});
