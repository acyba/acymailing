jQuery(document).ready(function ($) {
    const ajaxCalls = {};
    $.reloadCounters = function (element) {
        let or = $(element).closest('.acym__automation__group__filter').attr('data-filter-number');
        let $or = $(element).closest('.acym__automation__group__filter');
        let and = $(element).closest('.acym__automation__inserted__filter').attr('data-and');
        let ajaxUrl = ACYM_AJAX_URL + '&page=acymailing_automation&ctrl=automation&task=countresults&or=' + or + '&and=' + and;

        if (undefined !== ajaxCalls[and]) ajaxCalls[and].abort();

        $('#results_' + and).css('maxHeight', '18px').html('<i class="acymicon-circle-o-notch acymicon-spin"></i>');

        ajaxCalls[and] = $.get(ajaxUrl,
            $(element).closest('#acym_form').serialize() + '&page=acymailing_automation&ctrl=automation&task=countresults&or=' + or + '&and=' + and
        )
                          .done(function (result) {
                              $('#results_' + and).css('maxHeight', '').html(result);
                          })
                          .fail(function () {
                              $('#results_' + and).css('maxHeight', '').html(ACYM_JS_TXT.ACYM_ERROR);
                          });

        reloadGlobalCounter($or);
    };

    function Init() {
        reloadGlobalCounter($('.acym__automation__select__classic__filter').closest('.acym__automation__group__filter'));
        refreshFilterProcess();
        rebuildFilters();
    }

    Init();

    function reloadGlobalCounter(groupFilter) {
        let or = groupFilter.attr('data-filter-number');
        let $counterInput = groupFilter.find('.acym__automation__or__total__result');

        let ajaxUrlTotal = ACYM_AJAX_URL + '&page=acymailing_automation&ctrl=automation&task=countResultsOrTotal&or=' + or;

        $counterInput.html('<i class="acymicon-circle-o-notch acymicon-spin"></i>');

        $.get(ajaxUrlTotal, groupFilter.closest('#acym_form').serialize() + '&page=acymailing_automation&ctrl=automation&task=countResultsOrTotal&or=' + or)
         .done(function (result) {
             $counterInput.html(result);
         })
         .fail(function () {
             $counterInput.html(ACYM_JS_TXT.ACYM_ERROR);
         });
    }

    function refreshFilterProcess() {
        setSelectFilters('classic');
        setSelectFilters('user');
        setAddFilter();
        setChooseFilter();
        setAddFilterOr();
        setDeleteFilter();
        acym_helperDatePicker.setDatePickerGlobal();
        acym_helperDatePicker.setRSDateChoice();
        acym_helperSelect2.setAjaxSelect2();
        $.setAutomationReload();
    }

    function setSelectFilters(type) {
        let $options = $('#acym__automation__filter__' + type + '__options');
        if (!$options.length) return;

        let filters = JSON.parse($options.val());


        $('.acym__automation__select__' + type + '__filter').off('change').on('change', function () {
            let $inputAnd = $('#acym__automation__filters__count__and');
            $inputAnd.val(parseInt($inputAnd.val()) + 1);
            $(this).parent().parent().find('.acym__automation__inserted__filter').remove();
            let html = filters[$(this).val()].replace(/__numor__/g, $(this).closest('.acym__automation__group__filter').attr('data-filter-number'));
            html = html.replace(/__numand__/g, $inputAnd.val());
            $(this)
                .parent()
                .after('<div data-and="'
                       + $inputAnd.val()
                       + '" class="cell grid-x grid-margin-x grid-margin-y acym__automation__inserted__filter margin-top-1 margin-left-2"><span class="countresults margin-bottom-1" id="results_'
                       + $inputAnd.val()
                       + '"></span>'
                       + html
                       + '</div>');
            acym_helperSelect2.setSelect2();
            acym_helperDatePicker.setDatePickerGlobal();

            $('.switch-label').off('click').on('click', function () {
                let input = $('input[data-switch="' + $(this).attr('for') + '"]');
                input.attr('value', 1 - input.attr('value'));
            });

            let $operatorDropdown = $(this).closest('.acym__automation__one__filter').find('.acym__automation__filters__operator__dropdown');
            let $fieldsDropdown = $(this).closest('.acym__automation__one__filter').find('.acym__automation__filters__fields__dropdown');

            $operatorDropdown.on('change', function () {
                $fieldsDropdown.trigger('change');
            });

            $fieldsDropdown.on('change', function () {
                let $parent = $(this).closest('.acym__automation__inserted__filter');
                let $select = $parent.find('[data-filter-field="' + $(this).val() + '"]');
                let $selects = $parent.find('.acym__automation__filters__fields__select');
                let $defaultInput = $parent.find('.acym__automation__filter__regular-field');
                if ($select.length > 0 && ($operatorDropdown.val() === '=' || $operatorDropdown.val() === '!=')) {
                    $defaultInput.attr('name', $defaultInput.attr('name').replace('acym_action', '')).hide();
                    $selects.each(function (index) {
                        $(this).attr('name', $(this).attr('name').replace('acym_action', ''));
                        $(this).closest('.acym__automation__one-field').hide();
                    });
                    if ($select.attr('name').indexOf('acym_action') === -1) $select.attr('name', 'acym_action' + $select.attr('name'));
                    $select.closest('.acym__automation__one-field').show();
                } else {
                    if ($defaultInput.attr('name').indexOf('acym_action') === -1) $defaultInput.attr('name', 'acym_action' + $defaultInput.attr('name'));
                    if ($selects.length > 0) {
                        $selects.each(function () {
                            $(this).attr('name', $(this).attr('name').replace('acym_action', ''));
                            $(this).closest('.acym__automation__one-field').hide();
                        });
                    }
                    $defaultInput.show();
                }
            }).trigger('change');

            if ('classic' === type) {
                $(this)
                    .closest('.acym__automation__one__filter.acym__automation__one__filter__classic')
                    .find('.acym__automation__inserted__filter input, .acym__automation__inserted__filter select')
                    .on('change', function () {
                        $.reloadCounters(this);
                    });

                if ($(this).val() == 0) {
                    reloadGlobalCounter($(this).closest('.acym__automation__group__filter'));
                } else {
                    $.reloadCounters($(this)
                        .closest('.acym__automation__one__filter.acym__automation__one__filter__classic')
                        .find('.acym__automation__inserted__filter input, .acym__automation__inserted__filter select'));
                }
            }

            $(document).foundation();
            $('.reveal-overlay').appendTo('#acym_form');
            refreshFilterProcess();
        });
    }

    function setAddFilter() {
        $('.acym__automation__add-filter').off('click').on('click', function () {
            let nbANDs = $(this).closest('.acym__automation__group__filter').find('.acym__automation__one__filter').length;
            if (nbANDs === 0) {
                let $clone = $('#acym__automation__and__example').clone().removeAttr('id');
                $clone.find('.acym__automation__and').remove();
                $(this).parent().before($clone.show());
            } else {
                $(this).parent().before($('#acym__automation__and__example').clone().removeAttr('id').show());
            }
            let $newElement = $(this).parent().prev();
            $newElement.addClass('acym__automation__one__filter__' + $(this).attr('data-filter-type'));
            $newElement.find('.acym__automation__and__example__' + $(this).attr('data-filter-type') + '__select')
                       .show()
                       .find('select')
                       .addClass('acym__select')
                       .select2({
                           theme: 'foundation',
                           width: '100%'
                       });
            refreshFilterProcess();
        });
    }

    function setChooseFilter() {
        $('.acym__automation__choose__filter').off('click').on('click', function () {
            $('#acym__automation__type-filter__input').val($(this).attr('data-filter'));
            $('.selected-filter').removeClass('selected-filter');
            $(this).addClass('selected-filter');
            let $filtersContainers = $('.acym__automation__filter__container');
            $filtersContainers.hide();
            $filtersContainers.find('[name^="acym_action"]').each(function (index) {
                $(this).attr('name', $(this).attr('name').replace('acym_action', ''));
            });
            let $selectedContainer = $('#acym__automation__filters__type__' + $(this).attr('data-filter'));
            $selectedContainer.show();
            $selectedContainer.find('[name^="[filters]"]').each(function (index) {
                $(this).attr('name', 'acym_action' + $(this).attr('name'));
            });
        });
    }

    function setAddFilterOr() {
        $('.acym__automation__filters__or').off('click').on('click', function () {
            let $inputOr = $('#acym__automation__filters__count__or');
            $inputOr.val(parseInt($inputOr.val()) + 1);
            $(this).before($('#acym__automation__or__example').html());
            let $newElement = $(this).prev();
            $newElement.attr('data-filter-number', $inputOr.val());
            refreshFilterProcess();
            $newElement.find('button[data-filter-type]').attr('data-filter-type', $(this).attr('data-filter-type')).click();
            if ('classic' === $(this).attr('data-filter-type')) reloadGlobalCounter($newElement);
        });
    }

    //TODO: Replace parent() by closest()
    function setDeleteFilter() {
        $('.acym__automation__delete__one__filter').off('click').on('click', function () {
            let $groupFilter = $(this).closest('.acym__automation__group__filter');
            $(this).parent().parent().remove();
            reloadGlobalCounter($groupFilter);
        });
        $('.acym__automation__delete__group__filter').off('click').on('click', function () {
            $(this).parent().parent().prev().remove();
            $(this).parent().parent().remove();
        });
    }

    function rebuildFilters() {
        let $filterElement = $('#filters');
        if (!$filterElement.length) return;

        let filters = JSON.parse($filterElement.val());
        let type = filters['type_filter'];

        let or = 0;

        // Foreach OR block
        $.each(filters, function (numOR, oneORBlock) {
            let and = 0;
            if (numOR === 'type_filter') return true;

            // Create a new OR block if needed
            if (or !== 0) $('.acym__automation__filters__or[data-filter-type="' + type + '"]').click();

            // Foreach filters in this OR block
            $.each(oneORBlock, function (numAND, oneFilter) {
                // Create a new AND block if needed
                if (and !== 0) {
                    $('.acym__automation__group__filter[data-filter-number="' + or + '"]')
                        .find('.acym__automation__add-filter[data-filter-type="' + type + '"]')
                        .click();
                }

                $.each(oneFilter, function (filterName, filterOptions) {
                    // Select the filter type in the correct dropdown
                    let $filterSelect = $('.acym__automation__group__filter[data-filter-number="' + or + '"]')
                        .find('.acym__automation__select__' + type + '__filter')
                        .last();
                    $filterSelect.val(filterName);
                    $filterSelect.trigger('change');

                    let keys = Object.keys(filterOptions);
                    $.each(keys, function (key) {
                        let optionName = keys[key];
                        let optionValue = filterOptions[keys[key]];

                        // Set the option values
                        let $optionField = $('[name="acym_action[filters]['
                                             + or
                                             + ']['
                                             + $('#acym__automation__filters__count__and').val()
                                             + ']['
                                             + filterName
                                             + ']['
                                             + optionName
                                             + ']"]');

                        $.setFieldValue($optionField, optionValue);

                        $optionField.trigger('change');
                    });
                });
                and++;
            });
            or++;
        });
        refreshFilterProcess();
    }
});
