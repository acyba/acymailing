const acym_helperListing = {
    initJsListing: function () {
        acym_helperListing.setCheckAll();
        acym_helperListing.setOrdering();
        acym_helperListing.setSelectActions();
        acym_helperListing.setCheckboxesActionsListings();
        acym_helperToolbar.initToolbar();
    },
    setSortableListing: function () {
        const updateOrder = function () {
            let list = jQuery('.acym__sortable__listing .acym__listing__row');
            const noSortableElement = list.filter('.acym__no__sortable');
            noSortableElement.insertAfter(list.last());
            list = jQuery('.acym__sortable__listing .acym__listing__row');
            const ctrl = jQuery('.acym__sortable__listing').attr('data-sort-ctrl');
            const order = [];
            list.each(function (i) {
                order.push(jQuery(this).attr('data-id-element'));
            });

            acym_helper.post(ACYM_AJAX_URL, {
                ctrl: ctrl,
                task: 'ajaxSetOrdering',
                order: JSON.stringify(order)
            }).then(response => {
                if (response.error) {
                    console.log('Error can\'t order these elements');
                }
            });
        };

        jQuery('.acym__sortable__listing').sortable({
            items: '.acym__listing__row',
            handle: '.acym__sortable__listing__handle',
            animation: 150,
            stop: function (event, ui) {
                updateOrder();
            }
        });

        updateOrder();
    },
    setCheckAll: function () {
        jQuery('#checkbox_all').off('change').on('change', function () {
            let listing_checkboxes = jQuery('[name="elements_checked[]"]');
            if (jQuery(this).is(':checked')) {
                listing_checkboxes.prop('checked', true);
            } else {
                listing_checkboxes.prop('checked', false);
            }
            listing_checkboxes.trigger('change');
        });
    },
    setOrdering: function () {
        jQuery('#acym__listing__ordering').off('change').on('change', function () {
            let form = jQuery(this).closest('#acym_form');
            let ctrl = form.find('[name="ctrl"]').val();
            if (ctrl != 'campaigns') {
                form.find('[name="task"]').val('listing');
            }
            form.submit();
        });

        jQuery('.acym__listing__ordering__sort-order').off('click').on('click', function () {
            let inputSortOrder = jQuery('#acym__listing__ordering__sort-order--input');
            let form = jQuery(this).closest('#acym_form');

            if (inputSortOrder.val() === 'asc') {
                inputSortOrder.val('desc');
            } else {
                inputSortOrder.val('asc');
            }
            if (form.find('[name="task"]').val() === '') {
                form.find('[name="task"]').val('listing');
            }
            form.submit();
        });

        jQuery('.acym__select__sort').on('change', function () {
            jQuery(this).closest('#acym_form').submit();
        });
    },
    setSelectActions: function () {
        if (ACYM_IS_ADMIN) {
            jQuery('#listing_actions')
                .select2({
                    theme: 'foundation',
                    minimumResultsForSearch: Infinity
                });
        }

        function getURLPageParameter() {
            const urlParams = new URLSearchParams(window.location.search);
            const pageParam = urlParams.get('page');
            let page = '';
            if (pageParam) {
                page = pageParam.replace(/^acymailing_/, '');
            } else if (urlParams.get('ctrl')) {
                page = urlParams.get('ctrl');
            }

            return page ? page.replace('front', '') : '';
        }

        function getURLTaskParameter() {
            const urlParam = new URLSearchParams(window.location.search);
            const task = urlParam.get('task');
            return task ? task : '';
        }

        function triggerAction() {
            let action = this.value;
            let fastAction = false;
            let nbChecked = jQuery('[name="elements_checked[]"]:checked').length;
            if (!action || action === '0') {
                action = this.dataset.action;
                nbChecked = 1;
                fastAction = true;
            }

            const page = getURLPageParameter();
            const task = getURLTaskParameter();
            const task_content = [
                'welcome',
                'unsubscribe',
                'specificListing',
                'mailboxes',
                'followup'
            ];
            let customMessages;
            if (!task_content.includes(task)) {
                customMessages = {
                    forms: {
                        fastAction: ACYM_JS_TXT.ACYM_FORM,
                        regularAction: ACYM_JS_TXT.ACYM_FORMS
                    },
                    users: {
                        fastAction: ACYM_JS_TXT.ACYM_USER,
                        regularAction: ACYM_JS_TXT.ACYM_USERS
                    },
                    fields: {
                        fastAction: ACYM_JS_TXT.ACYM_FIELD,
                        regularAction: ACYM_JS_TXT.ACYM_FIELDS
                    },
                    lists: {
                        fastAction: ACYM_JS_TXT.ACYM_LIST,
                        regularAction: ACYM_JS_TXT.ACYM_LISTS
                    },
                    segments: {
                        fastAction: ACYM_JS_TXT.ACYM_SEGMENT,
                        regularAction: ACYM_JS_TXT.ACYM_SEGMENTS
                    },
                    campaigns: {
                        fastAction: ACYM_JS_TXT.ACYM_CAMPAIGN,
                        regularAction: ACYM_JS_TXT.ACYM_CAMPAIGNS
                    },
                    mails: {
                        fastAction: ACYM_JS_TXT.ACYM_TEMPLATE,
                        regularAction: ACYM_JS_TXT.ACYM_TEMPLATES
                    },
                    override: {
                        fastAction: ACYM_JS_TXT.ACYM_OVERRIDE,
                        regularAction: ACYM_JS_TXT.ACYM_OVERRIDES
                    },
                    automation: {
                        fastAction: ACYM_JS_TXT.ACYM_AUTOMATION,
                        regularAction: ACYM_JS_TXT.ACYM_AUTOMATIONS
                    },
                    bounces: {
                        fastAction: ACYM_JS_TXT.ACYM_BOUNCE,
                        regularAction: ACYM_JS_TXT.ACYM_BOUNCES
                    },
                    '': {
                        fastAction: ACYM_JS_TXT.ACYM_ENTITY,
                        regularAction: ACYM_JS_TXT.ACYM_ENTITY
                    }
                };

            } else if (task === 'followup') {
                customMessages = {
                    campaigns: {
                        fastAction: ACYM_JS_TXT.ACYM_FOLLOW_UP,
                        regularAction: ACYM_JS_TXT.ACYM_FOLLOW_UPS
                    }
                };
            } else {
                customMessages = {
                    bounces: {
                        fastAction: ACYM_JS_TXT.ACYM_MAILBOX_ACTION,
                        regularAction: ACYM_JS_TXT.ACYM_MAILBOX_ACTIONS
                    },
                    campaigns: {
                        fastAction: ACYM_JS_TXT.ACYM_EMAIL,
                        regularAction: ACYM_JS_TXT.ACYM_EMAILS
                    }
                };
            }

            const customMessage = fastAction || nbChecked === 1 ? customMessages[page].fastAction : customMessages[page].regularAction;
            const deleteMessageComplete = jQuery('#acym__listing__action__delete-message').val();
            const actionTexts = {
                'delete': (fastAction || nbChecked === 1)
                          ? `${ACYM_JS_TXT.ACYM_ARE_YOU_SURE_DELETE_ONE_X}`
                          : `${ACYM_JS_TXT.ACYM_ARE_YOU_SURE_DELETE_X} ${deleteMessageComplete}`,
                'setActive': (fastAction || nbChecked === 1) ? `${ACYM_JS_TXT.ACYM_ARE_YOU_SURE_ACTIVE_ONE_X}` : `${ACYM_JS_TXT.ACYM_ARE_YOU_SURE_ACTIVE_X}`,
                'setInactive': (fastAction || nbChecked === 1)
                               ? `${ACYM_JS_TXT.ACYM_ARE_YOU_SURE_INACTIVE_ONE_X}`
                               : `${ACYM_JS_TXT.ACYM_ARE_YOU_SURE_INACTIVE_X}`,
                'default': `${ACYM_JS_TXT.ACYM_ARE_YOU_SURE}`
            };

            if (action.includes('delete')) {
                action = 'delete';
            }
            const actionType = actionTexts[action] || actionTexts['default'];
            const confirmMessage = acym_helper.sprintf(actionType, nbChecked > 0 ? `${nbChecked} ${customMessage.toLowerCase()}` : customMessage.toLowerCase());

            if ('duplicate' === action || 'duplicateFollowup' === action || 'export' === action || acym_helper.confirm(confirmMessage)) {
                const form = jQuery(this).closest('#acym_form');
                const ctrl = jQuery(this).attr('data-ctrl');
                if (ctrl !== undefined) {
                    form.find('[name="return_listing"]').val(form.find('[name="ctrl"]').val());
                    form.find('[name="ctrl"]').val(ctrl);
                }
                if (!jQuery(this).val() || jQuery(this).val() === '0') action = this.dataset.action;

                form.find('[name="task"]').val(fastAction ? action : jQuery(this).val());
                if (fastAction) {
                    jQuery(':checkbox').prop('checked', false);
                    const checkboxId = '#checkbox_' + this.dataset.acyElementid;
                    jQuery(checkboxId).prop('checked', true);
                }
                form.submit();
            } else {
                jQuery(this).val('0');
            }
        }

        jQuery('#listing_actions').off('change').on('change', triggerAction);
        jQuery('.fastActions').on('click', triggerAction);
    },
    setCheckboxesActionsListings: function () {
        let listing_checkboxes = jQuery('[name="elements_checked[]"]');
        listing_checkboxes.off('change').on('change', function () {
            let nbChecked = jQuery('[name="elements_checked[]"]:checked').length;

            if (nbChecked === 1) {
                jQuery('.acym__campaign__duplicate').show();
            } else {
                jQuery('.acym__campaign__duplicate').hide();
            }

            // Shows the "Actions" dropdown when a box is checked
            if (nbChecked > 0) {
                jQuery('#listing_actions').removeAttr('disabled');
            } else {
                jQuery('#listing_actions').attr('disabled', 'true');
            }

            // Updates the "Check all" box
            if (jQuery('[name="elements_checked[]"]:not(:checked)').length > 0) {
                jQuery('#checkbox_all').prop('checked', false);
            } else {
                jQuery('#checkbox_all').prop('checked', true);
            }

            // On the users listing, handle the export button
            if (jQuery('#acym__users').length) {
                let $nbToExport = jQuery('#acym__users__listing__number_to_export');
                let $nbToAddToList = jQuery('#acym__users__listing__number_to_add_to_list');
                let $buttonAddToList = jQuery('#acym__users__listing__button--add-to-list');

                acym_helperListing.checkFiltersApplied();
                if (nbChecked === 0) {
                    $nbToExport.html('&nbsp;(' + $nbToExport.attr('data-default') + ')');
                    $buttonAddToList.addClass('disabled').attr('disabled', 'true');
                    $nbToAddToList.html(nbChecked);
                } else {
                    $nbToExport.html('&nbsp;(' + nbChecked + ')');
                    $buttonAddToList.removeClass('disabled').removeAttr('disabled');
                    $nbToAddToList.html(nbChecked);
                }
            }

            if (jQuery('#acym__lists').length) {
                let $nbToExport = jQuery('#acym__lists__listing__number_to_export');

                if (nbChecked === 0) {
                    $nbToExport.html($nbToExport.attr('data-default'));
                } else {
                    $nbToExport.html(nbChecked);
                }
            }
        });
        listing_checkboxes.trigger('change');

        if (jQuery('#acym__users').length) {
            jQuery('#users_list, [name="users_search"]').on('change', function () {
                acym_helperListing.checkFiltersApplied();
            });
            jQuery('.acym__status__select').on('click', function () {
                acym_helperListing.checkFiltersApplied();
            });
        }
    },
    checkFiltersApplied: function () {
        let nbChecked = jQuery('[name="elements_checked[]"]:checked').length;
        let $nbToExport = jQuery('#acym__users__listing__number_to_export');
        if (nbChecked === 0 && (jQuery('#users_list').val() > 0 || jQuery('[name="users_search"]').val().length > 0 || jQuery('.acym__status__select')
            .attr('acym-data-status').length > 0)) {
            $nbToExport.hide();
        } else {
            $nbToExport.show();
        }
    }
};
