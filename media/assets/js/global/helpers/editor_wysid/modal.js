const acym_editorWysidModal = {
    setModalWindowWYSID: function () {
        // Modal used when saving custom zones or inserting GIFs / videos for example
        jQuery('.acym__wysid__modal--close').on('click', function () {
            let modal = jQuery('.acym__wysid__modal');
            modal.hide();
            modal.removeClass('acym__wysid__modal__tiny');

            // Don't remove the previous element if it's a TABLE => it's a zone we were saving but we closed the modal to abort
            // Don't remove the previous element if it's a TR => it's a GIF we edited then closed the modal without replacing by a new GIF
            if (acym_helperEditorWysid.$focusElement.length
                && acym_helperEditorWysid.$focusElement.prop('tagName')
                !== 'TABLE'
                && acym_helperEditorWysid.$focusElement.prop('tagName')
                !== 'TR') {
                acym_helperEditorWysid.$focusElement.replaceWith('');
            }
            acym_editorWysidRowSelector.setZoneAndBlockOverlays();
            acym_helperEditorWysid.addDefaultBlock();
        });

        if (ACYM_CMS !== 'joomla') return;

        jQuery('.acym__wysid__modal__joomla-image--close').on('click', function () {
            jQuery('#acym__wysid__modal__joomla-image').hide();
            if (acym_helperEditorWysid.$focusElement.length
                && acym_helperEditorWysid.$focusElement.prop('tagName')
                !== 'TR'
                && !acym_helperEditorWysid.$focusElement.hasClass('acym__wysid__template__content')) {
                acym_helperEditorWysid.$focusElement.replaceWith('');
            }
        });
    },
    setDTextInsertion: function () {
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

                acym_helperEditorWysid.setColumnRefreshUiWYSID();
                acym_editorWysidDynamic.setDTextActions();

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
