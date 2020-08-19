const acym_editorWysidVersioning = {
    keepOldVersion: function (autoSave) {
        let autoSaveWithTmplDiv = '<div id="acym__wysid__template" class="cell">' + autoSave + '</div>';
        jQuery('#editor_autoSave').val('');
        jQuery('#acym__wysid__top-toolbar__keep').off('click').on('click', function () {
            jQuery('.acym__wysid__hidden__save__content').val(autoSaveWithTmplDiv);
            jQuery('#acym__wysid #acym__wysid__template').replaceWith(autoSaveWithTmplDiv);
            acym_helperEditorWysid.setColumnRefreshUiWYSID();
            jQuery('#acym__wysid__top-toolbar__notification__close').click();
        });
    },
    setUndoAndAutoSave: function (initEdit = false) {
        // If the user edits the email while the auto-saved message is still there, remove it
        if (!initEdit && jQuery('.acym__autosave__notification').length) acym_editorWysidNotifications.hideNotification();

        let $templateVersion = jQuery('[id^="template_version_"]');

        if ($templateVersion.length >= 10) $templateVersion[0].remove();
        let currentVersion = acym_helperEditorWysid.versionControl + 1;
        while (jQuery('#template_version_' + currentVersion).length > 0) {
            jQuery('#template_version_' + currentVersion).remove();
            currentVersion++;
        }
        acym_helperEditorWysid.versionControl++;
        jQuery('[name^="mce_"]').remove();
        jQuery('.acym__wysid__column--drag-start').removeClass('acym__wysid__column--drag-start');
        jQuery('.acym__editor__area').append('<input type="hidden" value="" id="template_version_' + acym_helperEditorWysid.versionControl + '">');

        let $template = jQuery('#acym__wysid__template');
        let template = $template.html();
        if (jQuery('#template_version_' + (acym_helperEditorWysid.versionControl - 1)).val() === template) return false;
        jQuery('#template_version_' + acym_helperEditorWysid.versionControl).val(template);

        // Auto save
        let $campaignID = jQuery('#acym__campaign__recipients__form__campaign');
        if (0 === $campaignID.length || $campaignID.length > 0 && '0' !== $campaignID.val()) {
            let $contentSave = $template.clone();
            $contentSave.find('.acym__wysid__row__selector, .acym__wysid__element__toolbox').remove();
            $contentSave.find('.acym__wysid__tinymce--text--placeholder--empty').removeClass('acym__wysid__tinymce--text--placeholder--empty');
            $contentSave = $contentSave.wrap('<div id="acym__wysid__template-save" class="cell">').html();
            let mailid = jQuery('#editor_mailid').val();
            if (!acym_helper.empty(mailid) && mailid !== '-1' && false === initEdit) {
                let ajaxUrl = ACYM_AJAX_URL + '&ctrl=' + acym_helper.ctrlMails + '&task=autoSave';
                ajaxUrl += '&language=' + acym_editorWysidMultilingual.currentLanguage;

                jQuery.ajax({
                    type: 'POST',
                    url: ajaxUrl,
                    data: {
                        autoSave: $contentSave,
                        mailId: mailid
                    },
                    success: function (res) {

                    },
                    error: function () {
                        console.log(ACYM_JS_TXT.ACYM_ERROR_SAVING);
                    }
                });
            }
        }
    },
    checkForUnsavedVersion: function () {
        let autoSave = jQuery('#editor_autoSave').val();
        if (undefined != autoSave && '' != autoSave) {
            acym_editorWysidNotifications.addEditorNotification({
                'message': '<div class="cell auto acym__autosave__notification">' + ACYM_JS_TXT.ACYM_AUTOSAVE_USE + '</div>',
                'level': 'info'
            }, false, false, true);
            acym_editorWysidVersioning.keepOldVersion(autoSave);
        }
    },
    setVersionControlCtrlZ: function () {
        jQuery(document).keydown(function (e) {
            if (jQuery('#acym__wysid__editor__source').height() > 0 || (undefined !== tinyMCE.focusedEditor && null !== tinyMCE.focusedEditor) || jQuery(
                '#acym__wysid__context__button').is(':visible') || jQuery('#acym__wysid__context__follow').is(':visible') || jQuery(
                '#acym__wysid__context__separator').is(':visible') || jQuery('#acym__wysid__context__share').is(':visible')) {
                return;
            }
            if (e.which !== 90 || (!e.ctrlKey && !e.metaKey) || acym_helperEditorWysid.versionControl <= 0) return;

            if (!e.shiftKey) {
                acym_editorWysidVersioning.makeVersionControlChangement(true);
            } else if (e.shiftKey) {
                acym_editorWysidVersioning.makeVersionControlChangement(false);
            }
        });

        jQuery('#acym__wysid__top-toolbar__undo').off('click').on('click', function () {
            acym_editorWysidVersioning.makeVersionControlChangement(true);
        });
        jQuery('#acym__wysid__top-toolbar__redo').off('click').on('click', function () {
            acym_editorWysidVersioning.makeVersionControlChangement(false);
        });
    },
    makeVersionControlChangement: function (undo) {
        let $templateVersionPlus = jQuery('#template_version_' + (acym_helperEditorWysid.versionControl + 1));
        let $templateVersionMinus = jQuery('#template_version_' + (acym_helperEditorWysid.versionControl - 1));
        if (undo) {
            if ($templateVersionMinus.length > 0 && $templateVersionMinus.val().length === 0) return;
            acym_helperEditorWysid.versionControl--;
            jQuery('#acym__wysid__template').html(jQuery('#template_version_' + acym_helperEditorWysid.versionControl).val());
            if (acym_helperEditorWysid.versionControl === 0) acym_editorWysidVersioning.setUndoAndAutoSave();
        } else {
            if ($templateVersionPlus.length > 0 && $templateVersionPlus.val().length === 0) return;
            acym_helperEditorWysid.versionControl++;

            jQuery('#acym__wysid__template').html(jQuery('#template_version_' + acym_helperEditorWysid.versionControl).val());
        }
        acym_helperEditorWysid.setColumnRefreshUiWYSID();
    }
};
