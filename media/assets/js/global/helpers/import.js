const acym_helperImport = {
    initImport: function () {
        acym_helperImport.setVerificationGenericImport();
        acym_helperImport.setImportCMSLists();
        acym_helperImport.setImportFromFileEvent();
        acym_helperImport.setImportSubmit();
        acym_helperImport.setChangeCharset();
        acym_helperImport.setChangeTableName();
        acym_helperImport.setCreateListFromImportPage();
    },
    setImportCMSLists: function () {
        jQuery('.acym__users__import__button').off('click').on('click', function () {
            acym_helperImport.getSubmitButton().trigger('click');
        });

        jQuery('#acym__users__import__skip__button').off('click').on('click', function () {
            jQuery('[name="acym__entity_select__selected"]').attr('value', '');
            acym_helperImport.getSubmitButton().trigger('click');
        });
    },
    getSubmitButton: function () {
        let $submitButton;
        if (jQuery('#acym__users__import__from_database').is(':visible')) {
            $submitButton = jQuery('#submit_import_database');
        } else if (jQuery('#acym__users__import__cms_users').is(':visible')) {
            $submitButton = jQuery('#submit_import_cms');
        } else {
            $submitButton = jQuery('#submit_import_mailpoet');
        }

        return $submitButton;
    },
    setVerificationGenericImport: function () {
        let $submitButton = jQuery('#formSubmit');
        jQuery('.acym__users__import__generic__import__button').off('click').on('click', function () {
            if (acym_helperImport.verifyGenericImport()) {
                $submitButton.trigger('click');
            }
        });

        jQuery('#acym__users__generic__import__skip__button').off('click').on('click', function () {
            if (acym_helperImport.verifyGenericImport()) {
                jQuery('[name="acym__entity_select__selected"]').attr('value', '');
                $submitButton.trigger('click');
            }
        });
    },
    verifyGenericImport: function () {
        if (jQuery(this).attr('id') === 'no-list') {
            jQuery('#acym__modal__lists-selected').attr('value', '');
            jQuery('#acym__import__new-list').attr('value', '');
        }

        let subval = true;
        let errors = '';
        let string = '';
        let emailField = false;
        let columns = '';
        let selectedFields = [];
        let $checkboxIgnoreValue = jQuery('#acym__users__import__from_file__ignore__checkbox').is(':checked');

        let fieldNb = jQuery('.fieldAssignment').length;

        if (isNaN(fieldNb)) fieldNb = 1;

        for (let i = 0 ; i < fieldNb ; i++) {
            let $fieldAssignement = jQuery('#fieldAssignment' + i);
            string = $fieldAssignement.val();
            if (string == 0) {
                if ($checkboxIgnoreValue) {
                    $fieldAssignement.val(1);
                    string = 1;
                } else {
                    acym_helperImport.setColorUnassignedField($fieldAssignement);
                    subval = false;
                    errors += '\n' + acym_helper.sprintf(ACYM_JS_TXT.ACYM_ASSIGN_COLUMN_TO_FIELD, i + 1);
                }
            }

            if (string == 'email') {
                emailField = true;
            }

            if (string != 1 && selectedFields.indexOf(string) !== -1) {
                subval = false;
                errors += '\n' + acym_helper.sprintf(ACYM_JS_TXT.ACYM_DUPLICATE_X_FOR_X, string, i + 1);
            } else {
                selectedFields.push(string);
            }
            columns += ',' + string;
        }

        if (!emailField) {
            subval = false;
            errors += '\n' + ACYM_JS_TXT.ACYM_ASSIGN_EMAIL_COLUMN;
        }

        if (subval === false) {
            alert(ACYM_JS_TXT.ACYM_FILL_ALL_INFORMATION + '\n' + errors);
            return false;
        }

        if (columns.substr(0, 1) === ',') {
            columns = columns.substring(1);
        }

        jQuery('#import_columns').val(columns);

        return true;
    },
    setColorUnassignedField: function ($select) {
        if ($select.val() == 0) {
            $select.addClass('fieldAssignmentError');
        } else {
            $select.removeClass('fieldAssignmentError');
        }
    },
    setImportFromFileEvent: function () {
        let $inputFile = jQuery('#acym__users__import__from_file__import__input');
        $inputFile.val(null);
        $inputFile.off('change').on('change', function () {
            let $form = jQuery('#acym_form');

            if (this.files.length > 0 && this.files[0].name.substr(this.files[0].name.length - 3).toLowerCase() === 'csv') {
                jQuery('.acym__users__import__from_file__file-name').html(this.files[0].name);
                jQuery('.acym__users__import__from_file__file').show();
                jQuery('.acym__users__import__from_file__choose').hide();
                jQuery('.acym__users__import__from_file__button-valid').removeAttr('disabled');
                jQuery('.acym__users__import__from_file__file__close').off('click').on('click', function () {
                    jQuery('.acym__users__import__from_file__file-name').html('');
                    jQuery('.acym__users__import__from_file__button-valid').prop('disabled', true);
                    jQuery('.acym__users__import__from_file__file').hide();
                    jQuery('.acym__users__import__from_file__choose').show();
                    $inputFile.val(null);
                });
                jQuery('.acym__users__import__from_file__button-valid').off('click').on('click', function () {
                    $form.find('[name="import_from"]').val('file');
                    $form.submit();
                });
            } else {
                acym_helperNotification.addNotification(ACYM_JS_TXT.ACYM_COULD_NOT_UPLOAD_CSV_FILE, 'error');
            }
        });
    },
    setImportSubmit: function () {
        jQuery('.acym__import__submit').on('click', function () {
            let $from = jQuery(this).attr('data-from');
            let $form = jQuery('#acym_form');

            $form.find('[name="import_from"]').val($from);
            $form.find('#formSubmit').trigger('click');
        });
    },
    setChangeCharset: function () {
        jQuery('#acyencoding').on('change', function () {
            let URL = ACYM_AJAX_URL
                      + '&ctrl='
                      + acym_helper.ctrlUsers
                      + '&task=ajaxEncoding&encoding='
                      + jQuery(this).val()
                      + '&acym_import_filename='
                      + jQuery(this)
                          .attr('data-filename');
            let selectedDropdowns = '';
            let fieldNb = jQuery('.fieldAssignment').length;
            if (isNaN(fieldNb)) fieldNb = 1;

            for (let i = 0 ; i < fieldNb ; i++) {
                selectedDropdowns += '&fieldAssignment' + i + '=' + jQuery('#fieldAssignment' + i).val();
            }

            URL += selectedDropdowns;

            jQuery.post(URL, function (response) {
                jQuery('#acym__users__import__generic__matchdata').html(response);
                jQuery('.fieldAssignment').select2({theme: 'foundation'});
            });
        });
    },
    setChangeTableName: function () {
        let tableName = '';
        jQuery('#acym__users__import__from_database__field--tablename').off('change').on('change', function () {
            tableName = jQuery(this).val();
            jQuery('#select2-acym__users__import__from_database__field--tablename-container').html(tableName);
            let url = ACYM_AJAX_URL + '&ctrl=' + acym_helper.ctrlUsers + '&task=getColumnsFromTable&tablename=' + tableName;

            jQuery.post(url, function (response) {
                jQuery('.acym__users__import__from_database__fields').html(response);
            });
        });
    },
    setCreateListFromImportPage: function () {
        let $buttonImport = jQuery('#acym__users__import__create-list__button');
        let $buttonGenericImport = jQuery('#acym__users__generic__import__create-list__button');

        $buttonImport.off('click').on('click', function () {
            jQuery(this).html(ACYM_JS_TXT.ACYM_SAVE);
            acym_helperImport.setActionListenerButtonCreateList(false);
        });

        $buttonGenericImport.off('click').on('click', function () {
            jQuery(this).html(ACYM_JS_TXT.ACYM_SAVE);
            acym_helperImport.setActionListenerButtonCreateList(true);
        });
    },
    setActionListenerButtonCreateList: function (isGeneric) {
        let $fieldDiv = jQuery('#acym__users__import__create-list');
        let $field = jQuery('#acym__users__import__create-list__field');
        let $modalWindow = jQuery('#acym__user__import__add-subscription__modal');

        if ($fieldDiv.is(':visible')) {
            if ($field.val() === '') return;
            jQuery('#acym__users__import__create-list__loading-logo').show();
            let selectedListsIds = jQuery('[name="acym__entity_select__selected"]').val();
            let url = ACYM_AJAX_URL
                      + '&ctrl='
                      + acym_helper.ctrlLists
                      + '&task=ajaxCreateNewList&list_name='
                      + encodeURIComponent($field.val())
                      + '&generic='
                      + (isGeneric ? '1' : '0')
                      + '&selected='
                      + selectedListsIds;

            jQuery.post(url, function (response) {
                $modalWindow.html(response);
                $modalWindow.children().show();
                $modalWindow.children()
                            .append(
                                '<button class="close-button" data-close aria-label="Close reveal" type="button"><span aria-hidden="true">&times;</span></button>');
                $fieldDiv.hide();
                jQuery(window).trigger('refreshEntitySelect');
                jQuery('#acym__users__import__create-list__loading-logo').hide();
                acym_helperImport.setCreateListFromImportPage();
            });
        } else {
            $fieldDiv.show();
            $field.focus();
        }

        $field.keydown(function (event) {
            if (event.key === 'Enter') {
                event.preventDefault();
                return false;
            }
        });
    }
};
