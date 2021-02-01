const acym_helperListing = {
    initJsListing: function () {
        acym_helperListing.setCheckAll();
        acym_helperListing.setOrdering();
        acym_helperListing.setSelectActions();
        acym_helperListing.setCheckboxesActionsListings();
        acym_helperToolbar.initToolbar();
    },
    setSortableListing: function () {
        jQuery('.acym__sortable__listing').sortable({
            items: '.acym__listing__row',
            handle: '.acym__sortable__listing__handle',
            animation: 150,
            stop: function (event, ui) {
                let list = jQuery('.acym__sortable__listing .acym__listing__row');
                let ctrl = jQuery('.acym__sortable__listing').attr('data-sort-ctrl');
                let order = [];
                list.each(function (i) {
                    order.push(jQuery(this).attr('data-id-element'));
                });
                order = JSON.stringify(order);
                let ajaxUrl = ACYM_AJAX_URL + '&ctrl=' + ctrl + '&task=setOrdering&order=' + order;
                jQuery.post(ajaxUrl, function (response) {
                    if (response == 'error') {
                        console.log('Error can\'t order these elements');
                    }
                });
            }
        });
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

        jQuery('.acym__select__sort').on('change', function(){
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

        jQuery('#listing_actions').off('change').on('change', function () {
            let action = this.value;
            let confirmMessage = '';
            let deleteMessageComplete = jQuery('#acym__listing__action__delete-message').val();

            switch (action) {
                case 'delete':
                    confirmMessage = `${ACYM_JS_TXT.ACYM_ARE_YOU_SURE_DELETE} ${deleteMessageComplete}`;
                    break;
                case 'setActive':
                    confirmMessage = ACYM_JS_TXT.ACYM_ARE_YOU_SURE_ACTIVE;
                    break;
                case 'setInactive':
                    confirmMessage = ACYM_JS_TXT.ACYM_ARE_YOU_SURE_INACTIVE;
                    break;
                default:
                    confirmMessage = ACYM_JS_TXT.ACYM_ARE_YOU_SURE;
            }

            if ('duplicate' == action || acym_helper.confirm(confirmMessage)) {
                let form = jQuery(this).closest('#acym_form');
                let ctrl = jQuery(this).attr('data-ctrl');
                if (ctrl !== undefined) {
                    form.find('[name="return_listing"]').val(form.find('[name="ctrl"]').val());
                    form.find('[name="ctrl"]').val(ctrl);
                }
                form.find('[name="task"]').val(jQuery(this).val());
                form.submit();
            } else {
                jQuery(this).val('0');
            }
        });
    },
    setCheckboxesActionsListings: function () {
        let listing_checkboxes = jQuery('[name="elements_checked[]"]');
        listing_checkboxes.off('change').on('change', function () {
            let nbChecked = (jQuery('[name="elements_checked[]"]:checked').length);

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

            //Si on est sur le listing user, on fait le compte des utilisateurs cochés.
            if (jQuery('#acym__users').length) {
                let $nbToExport = jQuery('#acym__users__listing__number_to_export');
                let $nbToAddToList = jQuery('#acym__users__listing__number_to_add_to_list');
                let $buttonAddToList = jQuery('#acym__users__listing__button--add-to-list');

                if (nbChecked == 0) {
                    $nbToExport.html($nbToExport.attr('data-default'));
                    $buttonAddToList.addClass('disabled').attr('disabled', 'true');
                    $nbToAddToList.html(nbChecked);
                } else {
                    $nbToExport.html(nbChecked);
                    $buttonAddToList.removeClass('disabled').removeAttr('disabled');
                    $nbToAddToList.html(nbChecked);
                }
            }

            if (jQuery('#acym__lists').length) {
                let $nbToExport = jQuery('#acym__lists__listing__number_to_export');

                if (nbChecked == 0) {
                    $nbToExport.html($nbToExport.attr('data-default'));
                } else {
                    $nbToExport.html(nbChecked);
                }
            }
        });
        listing_checkboxes.trigger('change');
    }
};
