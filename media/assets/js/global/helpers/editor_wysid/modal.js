const acym_editorWysidModal = {
    setModalWindowWYSID: function () {
        jQuery('.acym__wysid__modal--close').click(function () {
            jQuery('.acym__wysid__modal').hide();
            if (acym_helperEditorWysid.$focusElement.length && acym_helperEditorWysid.$focusElement.prop('tagName') != 'TR') {
                acym_helperEditorWysid.$focusElement.replaceWith('');
            }
            acym_helperEditorWysid.checkForEmptyTbodyWYSID();
        });

        if (ACYM_CMS == 'joomla') {
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
        let lastKnownRangeInEditor = null;

        jQuery('#insertButton').off('click').on('click', function () {
            let $toInsert = jQuery('#dtextcode').val();
            if (!$toInsert) return;

            let mailId = jQuery('input[name="editor_autoSave"]').val();
            let ajaxUrl = ACYM_AJAX_URL + '&page=acymailing_dynamics&ctrl=' + acym_helper.ctrlDynamics + '&task=replaceDummy';

            acym_helper.get(ajaxUrl, {
                'mailId': mailId,
                'code': $toInsert
            }).then(response => {
                if (!response) {
                    response = {'data': {'content': $toInsert}};
                }

                let toInsert = '<span id="acymRangeId" class="acym_dynamic mceNonEditable" data-dynamic="' + $toInsert + '">';
                toInsert += response.data.content;
                toInsert += '<em class="acym_remove_dynamic acymicon-close">&zwj;</em></span> &zwj;';

                if (!acym_helper.empty(lastKnownRangeInEditor) && !acym_editorWysidModal.isSelectionInEditor()) {
                    let selection = window.getSelection();
                    selection.removeAllRanges();
                    selection.addRange(lastKnownRangeInEditor);
                }
                tinyMCE.activeEditor.selection.setContent(toInsert);

                acym_helperEditorWysid.setColumnRefreshUiWYSID(false);
                acym_editorWysidVersioning.setUndoAndAutoSave();

                let dtext = document.getElementById('acymRangeId');
                jQuery(dtext).attr('contenteditable', 'false');
                dtext.removeAttribute('id');
            });
        });

        jQuery(document).on('selectionchange', function () {
            if (acym_editorWysidModal.isSelectionInEditor()) {
                lastKnownRangeInEditor = window.getSelection().getRangeAt(0);
            }
        });
    },
    isSelectionInEditor: function () {
        let $selectedNode = jQuery(document.getSelection().anchorNode);

        return $selectedNode.hasClass('acym__wysid__tinymce--text') || $selectedNode.closest('.acym__wysid__tinymce--text').length > 0;
    }
};
