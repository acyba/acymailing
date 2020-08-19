const acym_editorWysidModal = {
    setModalWindowWYSID: function () {
        jQuery('.acym__wysid__modal--close').click(function () {
            jQuery('.acym__wysid__modal').hide();
            if (acym_helperEditorWysid.$focusElement.length && acym_helperEditorWysid.$focusElement.prop('tagName') != 'TR') {
                acym_helperEditorWysid.$focusElement.replaceWith('');
            }
            acym_helperEditorWysid.checkForEmptyTbodyWYSID();
        });

        if (CMS_ACYM == 'joomla') {
            jQuery('.acym__wysid__modal__joomla-image--close').click(function () {
                jQuery('#acym__wysid__modal__joomla-image').hide();
                if (acym_helperEditorWysid.$focusElement.length
                    && acym_helperEditorWysid.$focusElement.prop('tagName')
                    != 'TR'
                    && !acym_helperEditorWysid.$focusElement.hasClass('acym__wysid__template__content')) {
                    acym_helperEditorWysid.$focusElement.replaceWith('');
                }
                acym_helperEditorWysid.checkForEmptyTbodyWYSID();
            });
        }
    },
    setSelectOneTemplate: function () {
        jQuery('.acym__template__choose__ajax').off('DOMSubtreeModified').on('DOMSubtreeModified', function () {
            jQuery('.acym__template__choose__list .acym__templates__oneTpl').off('click').on('click', function (e) {
                e.preventDefault();
                let thisLink = jQuery(this).find('a').attr('href');
                let ajaxUrl = ACYM_AJAX_URL + '&page=acymailing_mails&ctrl=' + acym_helper.ctrlMails + '&task=getMailContent&from=' + jQuery(this).attr('id');

                jQuery.post(ajaxUrl, function (response) {
                    if (response == 'error') {
                        alert(ACYM_JS_TXT.ACYM_ERROR);
                        return false;
                    }

                    window.location.href = thisLink;
                    return false;
                });
            });
        });
    },
    setDynamicsModal: function () {
        let $iframe = jQuery('#acym__wysid__modal__dynamic-text__ui__iframe');
        $iframe.on('load', function () {
            jQuery(this).contents().find('#wpadminbar').remove();
            $iframe.contents().find('#insertButton').off('click').on('click', function () {
                let $toInsert = $iframe.contents().find('#dtextcode').val();
                if ($toInsert) {
                    if (acym_helperEditorWysid.insertDTextInSubject) {
                        let subject = jQuery('#acym_subject_field');
                        subject.val(subject.val() + $toInsert);
                    } else {
                        let mailId = jQuery('input[name="editor_autoSave"]').val();
                        let ajaxUrl = ACYM_AJAX_URL + '&page=acymailing_dynamics&ctrl=' + acym_helper.ctrlDynamics + '&task=replaceDummy';
                        jQuery.ajax({
                            url: ajaxUrl,
                            type: 'POST',
                            data: {
                                'mailId': mailId,
                                'code': $toInsert
                            }
                        }).then(function (response) {
                            if (response) {
                                response = acym_helper.parseJson(response);
                            } else {
                                response = {'content': $toInsert};
                            }
                            $toInsert = '<span class="acym_dynamic mceNonEditable" data-dynamic="'
                                        + $toInsert
                                        + '">'
                                        + response.content
                                        + '<i class="acym_remove_dynamic acymicon-close">&zwj;</i></span> ';

                            // Magic line, I don't know why but without it the previous dtext isn't replaced by the new one
                            window.getSelection().getRangeAt(0).extractContents();

                            tinymce.activeEditor.execCommand('mceInsertContent', false, $toInsert);
                            acym_helperEditorWysid.setColumnRefreshUiWYSID();
                            acym_editorWysidVersioning.setUndoAndAutoSave();
                        });
                    }
                    jQuery('#acym__wysid__modal__dynamic-text').hide();
                }
            });
        });
    },
};
