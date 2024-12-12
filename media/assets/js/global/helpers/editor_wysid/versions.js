const acym_editorWysidVersions = {
    currentVersion: 'main',
    selectedVersion: '',
    forceReload: false,
    setVersionSelection: function () {
        jQuery('.acym__wysid__edit__versions__selection__element, .acym__wysid__edit__versions__selection__check').off('click').on('click', function () {
            acym_editorWysidVersions.selectedVersion = jQuery(this).parent().find('[acym-data-version]').attr('acym-data-version');
            if (acym_editorWysidVersions.selectedVersion === 'main' && acym_editorWysidVersions.currentVersion === 'main') return false;

            const currentSubject = jQuery('input[name="mail[subject]"]').val();
            const currentpreview = jQuery('input[name="mail[preheader]"]').val();

            const currentContent = jQuery('#editor_content').val();
            if (jQuery('#acym__wysid__edit__versions__creation').hasClass('is-hidden') && acym_helper.empty(currentSubject) && (!acym_helper.empty(
                currentpreview) || !acym_helper.empty(currentContent))) {
                if (acym_editorWysidVersions.selectedVersion !== acym_editorWysidVersions.currentVersion) {
                    acym_helper.alert(ACYM_JS_TXT.ACYM_ENTER_SUBJECT);
                }
                return false;
            }

            if (acym_editorWysidVersions.selectedVersion === acym_editorWysidVersions.currentVersion && !acym_editorWysidVersions.forceReload) {
                return false;
            }
            acym_editorWysidVersions.forceReload = false;

            if (acym_editorWysidVersions.selectedVersion === 'main') {
                jQuery('.acym__wysid__edit__preview__reset').addClass('is-hidden');
            } else {
                jQuery('.acym__wysid__edit__preview__reset').removeClass('is-hidden');
            }

            // Select the flag
            jQuery('.acym__wysid__edit__versions-selected').removeClass('acym__wysid__edit__versions-selected');
            jQuery('[acym-data-version="' + acym_editorWysidVersions.selectedVersion + '"]')
                .closest('.acym__wysid__edit__versions__selection')
                .addClass('acym__wysid__edit__versions-selected');

            const subject = jQuery('input[name="versions[' + acym_editorWysidVersions.selectedVersion + '][subject]"]').val();
            const preview = jQuery('input[name="versions[' + acym_editorWysidVersions.selectedVersion + '][preview]"]').val();
            const content = jQuery('input[name="versions[' + acym_editorWysidVersions.selectedVersion + '][content]"]').val();
            const autosave = jQuery('input[name="versions[' + acym_editorWysidVersions.selectedVersion + '][autosave]"]').val();
            const settings = jQuery('input[name="versions[' + acym_editorWysidVersions.selectedVersion + '][settings]"]').val();
            const stylesheet = jQuery('input[name="versions[' + acym_editorWysidVersions.selectedVersion + '][stylesheet]"]').val();

            if (acym_editorWysidVersions.selectedVersion !== 'main' && acym_helper.empty(subject) && acym_helper.empty(preview) && acym_helper.empty(content)) {
                jQuery(this).closest('.acym__content').find('> div').addClass('is-hidden');
                jQuery('#acym__wysid__edit__versions, #acym__wysid__edit__versions__creation').removeClass('is-hidden');

                acym_editorWysidVersions.switchVersion('', '', '', '');
            } else {
                acym_editorWysidVersions.switchVersion(subject, preview, content, autosave, settings, stylesheet);
                acym_editorWysidVersions.showEdition();
            }
        });

        jQuery('#acym__wysid__edit__versions__creation__default').off('click').on('click', function () {
            acym_editorWysidVersions.switchVersion(
                jQuery('input[name="versions[main][subject]"]').val(),
                jQuery('input[name="versions[main][preview]"]').val(),
                jQuery('input[name="versions[main][content]"]').val(),
                '',
                jQuery('input[name="versions[main][settings]"]').val(),
                jQuery('input[name="versions[main][stylesheet]"]').val()
            );

            acym_editorWysidVersions.showEdition();
        });

        jQuery('#acym__wysid__edit__versions__creation__scratch').off('click').on('click', function () {
            acym_editorWysidVersions.switchVersion('', '', jQuery('#default_template').val(), '');
            acym_editorWysidVersions.showEdition();
        });

        jQuery('#acym__wysid__edit__preview__reset__content').off('click').on('click', function () {
            if (acym_helper.confirm(ACYM_JS_TXT.ACYM_RESET_TRANSLATION)) {
                jQuery('input[name="versions[' + acym_editorWysidVersions.currentVersion + '][subject]"]').val('');
                jQuery('input[name="versions[' + acym_editorWysidVersions.currentVersion + '][preview]"]').val('');
                jQuery('input[name="versions[' + acym_editorWysidVersions.currentVersion + '][content]"]').val('');
                jQuery('input[name="versions[' + acym_editorWysidVersions.currentVersion + '][settings]"]').val('');
                jQuery('input[name="versions[' + acym_editorWysidVersions.currentVersion + '][stylesheet]"]').val('');
                jQuery('input[name="mail[subject]"]').val('');
                jQuery('input[name="mail[preheader]"]').val('');
                jQuery('#editor_content').val('');
                acym_editorWysidVersions.forceReload = true;
                const $currentFlag = jQuery('img[acym-data-version="' + acym_editorWysidVersions.currentVersion + '"]');
                $currentFlag.trigger('click')
                            .closest('.acym__wysid__edit__versions__selection')
                            .removeClass('acym__wysid__edit__versions__selection-done');
            }
        });
    },
    storeCurrentValues: function (saveStep = false) {
        // Make sure we're in a multilingual context
        const currentSubject = jQuery('input[name="mail[subject]"]').val();

        if (!acym_helperModal.isMultilingualEdition) {
            return true;
        }

        if (jQuery('#acym__wysid__edit__versions__creation').hasClass('is-hidden') && acym_helper.empty(currentSubject)) {
            return true;
        }

        jQuery('img[acym-data-version="' + this.currentVersion + '"]')
            .closest('.acym__wysid__edit__versions__selection')
            .addClass('acym__wysid__edit__versions__selection-done');

        // Save the value of the 3 fields
        jQuery('input[name="versions[' + this.currentVersion + '][subject]"]').val(currentSubject);
        jQuery('input[name="versions[' + this.currentVersion + '][preview]"]').val(jQuery('input[name="mail[preheader]"]').val());
        jQuery('input[name="versions[' + this.currentVersion + '][content]"]').val(jQuery('#editor_content').val());
        jQuery('input[name="versions[' + this.currentVersion + '][autosave]"]').val(jQuery('#editor_autoSave').val());
        jQuery('input[name="versions[' + this.currentVersion + '][settings]"]').val(jQuery('#editor_settings').val());
        jQuery('input[name="versions[' + this.currentVersion + '][stylesheet]"]').val(jQuery('#editor_stylesheet').val());

        if (saveStep) {
            jQuery('img[acym-data-version="main"]').trigger('click');
        }

        return true;
    },
    switchVersion: function (newSubject, newPreview, newContent, newAutosave, settings = '', stylesheet = '') {
        // Save the value of the 3 fields
        this.storeCurrentValues();

        // Set the empty value for the 3 fields
        jQuery('input[name="mail[subject]"]').val(newSubject);
        jQuery('input[name="mail[preheader]"]').val(newPreview);
        jQuery('#editor_content').val(newContent);
        jQuery('#editor_autoSave').val(newAutosave);
        if (settings !== '') jQuery('#editor_settings').val(settings);
        if (settings !== '') jQuery('#editor_stylesheet').val(stylesheet);
        acym_editorWysidVersioning.checkForUnsavedVersion();

        // Set the new current version
        this.currentVersion = this.selectedVersion;
    },
    showEdition: function () {
        jQuery('#acym__wysid__edit .acym__content > div').removeClass('is-hidden');
        jQuery('#acym__wysid__edit__versions__creation').addClass('is-hidden');

        // Reload the content preview
        acym_helperPreview.loadIframe('acym__wysid__preview__iframe__acym__wysid__email__preview', false);
    },
    setClickStartFromTemplate: function () {
        if (!acym_helperModal.isMultilingualEdition) return;
        jQuery('.acym__templates__oneTpl a').off('click').on('click', function (event) {
            event.preventDefault();
        });
        jQuery('.acym__templates__oneTpl').off('click').on('click', function (event) {
            event.preventDefault();
            const $loader = jQuery('.acym__template__choose__modal__loader');
            $loader.css('display', 'flex');
            const data = {
                ctrl: ACYM_IS_ADMIN ? 'mails' : 'frontmails',
                task: 'getMailByIdAjax',
                id: this.getAttribute('id')
            };

            acym_helper.get(ACYM_AJAX_URL, data).then(res => {
                if (res.error) {
                    acym_helperNotification.addNotification(res.message, 'error', true);
                    return;
                }
                acym_editorWysidVersions.switchVersion(res.data.subject, res.data.preheader, res.data.body, '', res.data.settings, res.data.stylesheet);
                acym_editorWysidVersions.showEdition();

                jQuery(this).closest('#acym__template__choose__modal').find('.close-button').trigger('click');
                $loader.css('display', 'none');
            });
        });
    }
};
