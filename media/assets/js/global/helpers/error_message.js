const acym_helperErrorMessage = {
    initErrorMessage: function () {
        let error = [];
        let isError = false;
        jQuery(document).on('invalid.zf.abide', function (ev, elt) {
            if (elt.is(':radio') && undefined === elt.attr('required')) {
                return;
            }

            jQuery('#formSubmit')[0].disabled = false;
            // Build error message
            let errorTxt = acym_helperErrorMessage.getErrorMessage(elt);

            // Create message display with timeout before removal
            elt.after('<div class="acym__input-error"><i class="acymicon-exclamation-circle"></i>' + errorTxt + '</div>');
            let $messages = jQuery('.acym__input-error');
            setTimeout(function () {
                $messages.remove();
            }, 5000);
            error = [];
        }).on('valid.zf.abide', function (e, ui) {
            if (ui.attr('type') === 'email' && ui.val() !== '') {
                error[0] = error[0] === undefined ? 0 : error[0];
                error[0] = (!acym_helper.emailValid(ui.val()) || ui.val() === '') ? error[0] + 1 : 0;
            }
        }).on('submit', function (e) {
            let formSubmit = jQuery('#formSubmit')[0];
            if (formSubmit === undefined) return true;
            error[3] = error[3] === undefined ? 0 : error[3];
            let $userExport = jQuery('#acym__users__export');
            let $listsSelected = $userExport.find('#acym__modal__lists-selected');
            let $usersToExport = jQuery('input[name="export_users-to-export"]:checked');
            if ($userExport.length > 0 && $usersToExport.val() === 'list' && $listsSelected.val() === '[]') {
                error[3]++;
                acym_helperNotification.addNotification(ACYM_JS_TXT.ACYM_EXPORT_SELECT_LIST, 'error');
            } else if ($userExport.length > 0 && $usersToExport.val() === 'list' && $listsSelected.val() !== '[]') {
                error[3] = 0;
            }

            if (error.length > 0) {
                jQuery.each(error, function (key, value) {
                    if (value > 0) {
                        isError = true;
                    }
                });
            }

            if (isError) {
                formSubmit.disabled = false;
                e.preventDefault();
                isError = false;
            } else {
                formSubmit.disabled = true;
                isError = false;
            }
        });
    },
    getErrorMessage: function (elt) {

        let typeElt = elt.attr('type');
        let patternElt = elt.attr('pattern');
        let requiredElt = elt.attr('required');
        let errorTxt = [];

        // Required
        if (requiredElt !== undefined) {
            errorTxt.push(ACYM_JS_TXT.requiredMsg + '.');
        }

        if (typeElt === 'email') {
            errorTxt.push(ACYM_JS_TXT.email);
        } else if (typeElt === 'text') {
            if (patternElt !== undefined && ACYM_JS_TXT[patternElt] !== undefined) {
                errorTxt.push(ACYM_JS_TXT[patternElt]);
            } else {
                errorTxt.push(ACYM_JS_TXT.defaultMsg);
            }
        }

        return errorTxt.join(' ');
    },
};
