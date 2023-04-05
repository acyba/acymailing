const acym_helperSegment = {
    filterRebuilt: true,
    blocksRebuilt: true,
    ajaxCalls: {},
    globalAjaxCall: '',
    reloadCounters: function (element) {
        if (!acym_helperSegment.filterRebuilt) return;

        let and = jQuery(element).closest('.acym__segments__inserted__filter').attr('data-and');
        let or = jQuery(element).closest('[data-filter-number]').attr('data-filter-number');
        let ajaxUrl = ACYM_AJAX_URL + '&page=acymailing_segments&ctrl=segments&task=countResults&and=' + and + '&or=' + or;

        if (undefined !== this.ajaxCalls[and] && typeof this.ajaxCalls[and].abort === 'function') {
            this.ajaxCalls[and].abort();
        }

        jQuery('#results_' + and)
            .find('.acym__segments__edit__filter-results')
            .html('<i class="acymicon-circle-o-notch acymicon-spin"></i>');

        let ajaxData = jQuery(element).closest('#acym_form').serialize() + '&page=acymailing_segments&ctrl=segments&task=countResults&and=' + and + '&or=' + or;
        this.ajaxCalls[and] = jQuery.post(ajaxUrl, ajaxData)
                                    .done(function (response) {
                                        response = acym_helper.parseJson(response);
                                        jQuery('#results_' + and)
                                            .find('.acym__segments__edit__filter-results')
                                            .html(response.error ? ACYM_JS_TXT.ACYM_ERROR : response.message);
                                    })
                                    .fail(function () {
                                        jQuery('#results_' + and).find('.acym__segments__edit__filter-results').html(ACYM_JS_TXT.ACYM_ERROR);
                                    });

        this.reloadGlobalCounter();
    },
    reloadGlobalCounter: function () {
        if (!acym_helperSegment.blocksRebuilt) return;

        let $counterInput = '';
        if (jQuery('#acym__campaigns__segment').length > 0) {
            $counterInput = jQuery('#acym__campaigns__segment__edit-user-count');
        } else {
            let $noUserMessage = jQuery('.acym__segments__edit__filters__no-users');
            $counterInput = jQuery('.acym__segments__edit__filters__total');

            if (jQuery('[name^=acym_action]').length === 0) {
                $noUserMessage.show();
                $counterInput.hide();
                return;
            }

            $noUserMessage.hide();
            $counterInput.show();
        }

        if (this.globalAjaxCall !== '' && typeof this.globalAjaxCall.abort === 'function') {
            this.globalAjaxCall.abort();
            this.globalAjaxCall = '';
        }

        const exclude = jQuery('input[value="exclude"]:checked').length ? 1 : 0;
        let groupFilter = jQuery('.acym__segments__group__filter');
        let ajaxUrlTotal = ACYM_AJAX_URL + '&page=acymailing_segments&ctrl=segments&task=countResultsTotal&exclude=' + exclude;
        let ajaxData = groupFilter.closest('#acym_form').serialize() + '&page=acymailing_segments&ctrl=segments&task=countResultsTotal&exclude=' + exclude;
        $counterInput.html('<i class="acymicon-circle-o-notch acymicon-spin"></i>');

        this.globalAjaxCall = jQuery.post(ajaxUrlTotal, ajaxData)
                                    .done(function (response) {
                                        response = acym_helper.parseJson(response);
                                        $counterInput.html(response.error ? ACYM_JS_TXT.ACYM_ERROR : response.message);
                                    })
                                    .fail(function () {
                                        $counterInput.html(ACYM_JS_TXT.ACYM_ERROR);
                                    });
    },
    refreshFilterProcess: function () {
        this.setSelectFilters('classic');
        this.setAddFilter();
        this.setDeleteFilter();
        acym_helperDatePicker.setDatePickerGlobal();
        acym_helperDatePicker.setRSDateChoice();
        acym_helperSelect2.setAjaxSelect2();
        this.setAddFilterOr();
    },
    setSelectFilters: function (type) {
        let inCampaignStep = jQuery('#acym__campaigns__segment').length > 0;
        let $options = jQuery('#acym__segments__edit__info__options');
        if (!$options.length) return;

        let filters = acym_helper.parseJson($options.val());

        jQuery('.acym__segments__select__' + type + '__filter').off('change').on('change', function () {
            if (inCampaignStep) {
                jQuery('[name="segment_selected"]').val('');
            }
            let $inputAnd = jQuery('#acym__segments__filters__count__and');
            let deleteFilter = '';
            if (parseInt($inputAnd.val()) !== 0) {
                deleteFilter = `<span class="cell acym_vcenter acym__segments__delete__one__filter"><i class="acymicon-delete"></i>${ACYM_JS_TXT.ACYM_DELETE_THIS_FILTER}</span>`;
            }

            $inputAnd.val(parseInt($inputAnd.val()) + 1);

            let seeUsersFilter = acym_helperSegment.getSeeUserModalButton(jQuery(this), $inputAnd.val());

            jQuery(this).parent().parent().find('.acym__segments__inserted__filter').remove();
            let html = filters[jQuery(this).val()].replace(/__numor__/g, jQuery(this).closest('.acym__segments__group__filter').attr('data-filter-number'));
            html = html.replace(/__numand__/g, $inputAnd.val());
            let classOptions = inCampaignStep ? 'medium-9' : 'medium-6';
            jQuery(this)
                .parent()
                .after('<div data-and="'
                       + $inputAnd.val()
                       + '" class="cell grid-x grid-margin-x grid-margin-y acym__segments__inserted__filter margin-top-1 margin-left-2 acym_vcenter"><div class="cell grid-x '
                       + classOptions
                       + ' grid-margin-x grid-margin-y">'
                       + html
                       + '</div>'
                       + '<span class="countresults margin-bottom-1 cell auto grid-x" id="results_'
                       + $inputAnd.val()
                       + '"><span class="acym__segments__edit__filter-results cell"></span>'
                       + deleteFilter
                       + seeUsersFilter
                       + '</span>'
                       + '</div>');
            acym_helperSelect2.setSelect2();
            acym_helperDatePicker.setDatePickerGlobal();
            acym_helperTooltip.setTooltip();
            acym_helperFilter.setAutomationReload();
            jQuery(document).foundation();
            jQuery(document).trigger('acym__modal__users__summary__ready');

            jQuery('.switch-label').off('click').on('click', function () {
                let input = jQuery('input[data-switch="' + jQuery(this).attr('for') + '"]');
                input.attr('value', 1 - input.attr('value'));
            });

            let $operatorDropdown = jQuery(this).closest('.acym__segments__one__filter').find('.acym__automation__filters__operator__dropdown');
            let $fieldsDropdown = jQuery(this).closest('.acym__segments__one__filter').find('.acym__automation__filters__fields__dropdown');

            $operatorDropdown.on('change', function () {
                $fieldsDropdown.trigger('change');
            });

            $fieldsDropdown.on('change', function () {
                let $parent = jQuery(this).closest('.acym__segments__inserted__filter');
                let $select = $parent.find('[data-filter-field="' + jQuery(this).val() + '"]');
                let $selects = $parent.find('.acym__automation__filters__fields__select');
                let $defaultInput = $parent.find('.acym__automation__filter__regular-field');
                if ($select.length > 0 && ($operatorDropdown.val() === '=' || $operatorDropdown.val() === '!=')) {
                    $defaultInput.attr('name', $defaultInput.attr('name').replace('acym_action', '')).hide();
                    $selects.each(function (index) {
                        jQuery(this).attr('name', jQuery(this).attr('name').replace('acym_action', ''));
                        jQuery(this).closest('.acym__automation__one-field').hide();
                    });
                    if ($select.attr('name').indexOf('acym_action') === -1) $select.attr('name', 'acym_action' + $select.attr('name'));
                    $select.closest('.acym__automation__one-field').show();
                } else {
                    if ($defaultInput.attr('name').indexOf('acym_action') === -1) $defaultInput.attr('name', 'acym_action' + $defaultInput.attr('name'));
                    if ($selects.length > 0) {
                        $selects.each(function () {
                            jQuery(this).attr('name', jQuery(this).attr('name').replace('acym_action', ''));
                            jQuery(this).closest('.acym__automation__one-field').hide();
                        });
                    }
                    $defaultInput.show();
                }
            }).trigger('change');

            jQuery(this)
                .closest('.acym__segments__one__filter.acym__segments__one__filter__classic')
                .find('.acym__segments__inserted__filter input, .acym__segments__inserted__filter select')
                .on('change', function () {
                    acym_helperSegment.reloadCounters(this);
                });

            if (jQuery(this).val() == 0) {
                acym_helperSegment.reloadGlobalCounter(jQuery(this).closest('.acym__segments__group__filter'));
            } else {
                acym_helperSegment.reloadCounters(jQuery(this)
                    .closest('.acym__segments__one__filter.acym__segments__one__filter__classic')
                    .find('.acym__segments__inserted__filter input, .acym__segments__inserted__filter select, .acym__segments__inserted__filter textarea'));
            }

            let $toggle = jQuery(this).closest('.acym__segments__one__filter').find('[data-acym-toggle-filter]');

            acym_helperFilter.setToggle($toggle, 'acym__segments__one__filter');

            jQuery(document).foundation();
            jQuery('.reveal-overlay').appendTo('#acym_form');
            acym_helperSegment.refreshFilterProcess();
        });
    },
    setAddFilter: function () {
        jQuery('.acym__segments__add-filter').off('click').on('click', function () {
            let nbANDs = jQuery(this).closest('.acym__segments__group__filter').find('.acym__segments__one__filter').length;
            if (nbANDs === 0) {
                let $clone = jQuery('#acym__segments__and__example').clone().removeAttr('id');
                $clone.find('.acym__automation__and').remove();
                jQuery(this).parent().before($clone.show());
            } else {
                jQuery(this).parent().before(jQuery('#acym__segments__and__example').clone().removeAttr('id').show());
            }
            let $newElement = jQuery(this).parent().prev();
            $newElement.addClass('acym__segments__one__filter__' + jQuery(this).attr('data-filter-type'));
            $newElement.find('.acym__segments__and__example__' + jQuery(this).attr('data-filter-type') + '__select')
                       .show()
                       .find('select')
                       .addClass('acym__select')
                       .select2({
                           theme: 'foundation',
                           width: '100%'
                       });
            acym_helperSegment.refreshFilterProcess();
        });
    },
    setDeleteFilter: function () {
        jQuery('.acym__segments__delete__one__filter').off('click').on('click', function () {
            jQuery(this).closest('.acym__segments__one__filter').remove();
            acym_helperSegment.reloadGlobalCounter();
        });
        jQuery('.acym__segments__delete__one__or').off('click').on('click', function () {
            jQuery(this).closest('.acym__segments__or__container').remove();
            acym_helperSegment.reloadGlobalCounter();
        });

    },
    rebuildFilters: function () {
        let $filterElement = jQuery('#acym__segments__filters');
        if ($filterElement.val() === '') return;

        let filters = acym_helper.parseJson($filterElement.val());
        let or = 0;
        // Foreach OR block
        let lastOrBlock = parseInt(Object.keys(filters).slice(-1)[0]);
        acym_helperSegment.blocksRebuilt = false;
        jQuery.each(filters, function (numOR, oneORBlock) {

            // Create a new OR block if needed
            if (or !== 0) jQuery('.acym__automation__filters__or').trigger('click');

            let and = 0;
            let lastFilterInOrBlock = Object.keys(oneORBlock).slice(-1)[0];
            jQuery.each(oneORBlock, function (numAND, oneFilter) {
                // Create a new AND block if needed
                if (and !== 0) {
                    jQuery('.acym__segments__group__filter[data-filter-number="' + or + '"]')
                        .find('.acym__segments__add-filter[data-filter-type]')
                        .trigger('click');
                }

                acym_helperSegment.filterRebuilt = false;
                jQuery.each(oneFilter, function (filterName, filterOptions) {
                    // Select the filter type in the correct dropdown
                    let $filterSelect = jQuery('.acym__segments__group__filter[data-filter-number="' + or + '"]')
                        .find('.acym__segments__select__classic__filter')
                        .last();
                    $filterSelect.val(filterName);
                    $filterSelect.trigger('change');

                    let keys = Object.keys(filterOptions);
                    jQuery.each(keys, function (key) {
                        const optionName = keys[key];
                        const optionValue = filterOptions[keys[key]];

                        // Set the option values
                        let $optionField = jQuery('[name="acym_action[filters]['
                                                  + or
                                                  + ']['
                                                  + jQuery('#acym__segments__filters__count__and').val()
                                                  + ']['
                                                  + filterName
                                                  + ']['
                                                  + optionName
                                                  + ']"]');
                        acym_helperFilter.setFieldValue($optionField, optionValue);

                        if (key === keys.length - 1) {
                            acym_helperSegment.filterRebuilt = true;
                            if (lastFilterInOrBlock === numAND && lastOrBlock === parseInt(numOR)) {
                                acym_helperSegment.blocksRebuilt = true;
                            }
                        }

                        $optionField.trigger('change');
                    });
                });
                and++;
            });
            or++;
        });
        this.refreshFilterProcess();
    },
    setAddFilterOr: function () {
        jQuery('.acym__automation__filters__or').off('click').on('click', function () {
            let $inputOr = jQuery('#acym__automation__filters__count__or');
            $inputOr.val(parseInt($inputOr.val()) + 1);
            jQuery(this).before(jQuery('#acym__automation__or__example').html());
            let $newElement = jQuery(this).prev();
            $newElement.find('.acym__segments__group__filter').attr('data-filter-number', $inputOr.val());
            acym_helperSegment.refreshFilterProcess();
            $newElement.find('button[data-filter-type]').trigger('click');
            if ('classic' === jQuery(this).attr('data-filter-type')) acym_helperSegment.reloadGlobalCounter($newElement);
        });
    },
    getSeeUserModalButton: function ($element, andValue) {
        const idModal = `acym__segments__see-users__${andValue}`;
        const $seeUserModalButton = jQuery('#acym__segment__see-users__example').clone();
        const $seeUserModal = jQuery('#acym__segments__see-users').closest('.reveal-overlay').clone();

        $seeUserModalButton.find('[data-open="acym__segments__see-users"]').attr('data-open', idModal);
        $seeUserModal.find('#acym__segments__see-users').attr('id', idModal);

        const dataSeeUserModal = {
            ctrl: 'segments',
            task: 'usersSummary',
            and: andValue,
            or: $element.closest('[data-filter-number]').attr('data-filter-number')
        };
        $seeUserModal.find('[acym-data-query]').attr('acym-data-query', JSON.stringify(dataSeeUserModal));

        const seeUserModalHtml = $seeUserModalButton.html() + $seeUserModal[0].outerHTML;

        return `<span class="cell shrink acym__segments__see-users">${seeUserModalHtml}</span>`;
    }
};
