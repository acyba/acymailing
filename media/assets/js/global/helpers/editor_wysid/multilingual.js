const acym_editorWysidMultilingual = {
    currentLanguage: 'main',
    selectedLanguage: '',
    forceReload: false,
    setLanguageSelection: function () {
        jQuery('.acym__wysid__edit__languages__selection img, .acym__wysid__edit__languages__selection__check').off('click').on('click', function () {
            acym_editorWysidMultilingual.selectedLanguage = jQuery(this).parent().find('img').attr('acym-data-lang');
            if (acym_editorWysidMultilingual.selectedLanguage === 'main' && acym_editorWysidMultilingual.currentLanguage === 'main') return false;

            let currentSubject = jQuery('input[name="mail[subject]"]').val();
            let currentpreview = jQuery('input[name="mail[preheader]"]').val();

            let currentContent = jQuery('#editor_content').val();
            if (jQuery('#acym__wysid__edit__multilingual__creation').hasClass('is-hidden') && acym_helper.empty(currentSubject) && (!acym_helper.empty(
                currentpreview) || !acym_helper.empty(currentContent))) {
                if (acym_editorWysidMultilingual.selectedLanguage !== acym_editorWysidMultilingual.currentLanguage) {
                    acym_helper.alert(ACYM_JS_TXT.ACYM_ENTER_SUBJECT);
                }
                return false;
            }

            if (acym_editorWysidMultilingual.selectedLanguage === acym_editorWysidMultilingual.currentLanguage && !acym_editorWysidMultilingual.forceReload) {
                return false;
            }
            acym_editorWysidMultilingual.forceReload = false;

            if (acym_editorWysidMultilingual.selectedLanguage === 'main') {
                jQuery('.acym__wysid__edit__preview__reset').addClass('is-hidden');
            } else {
                jQuery('.acym__wysid__edit__preview__reset').removeClass('is-hidden');
            }

            // Select the flag
            jQuery('.acym__wysid__edit__languages-selected').removeClass('acym__wysid__edit__languages-selected');
            jQuery('[acym-data-lang="' + acym_editorWysidMultilingual.selectedLanguage + '"]')
                .closest('.acym__wysid__edit__languages__selection')
                .addClass('acym__wysid__edit__languages-selected');

            let subject = jQuery('input[name="multilingual[' + acym_editorWysidMultilingual.selectedLanguage + '][subject]"]').val();
            let preview = jQuery('input[name="multilingual[' + acym_editorWysidMultilingual.selectedLanguage + '][preview]"]').val();
            let content = jQuery('input[name="multilingual[' + acym_editorWysidMultilingual.selectedLanguage + '][content]"]').val();
            let autosave = jQuery('input[name="multilingual[' + acym_editorWysidMultilingual.selectedLanguage + '][autosave]"]').val();

            if (acym_editorWysidMultilingual.selectedLanguage !== 'main' && acym_helper.empty(subject) && acym_helper.empty(preview) && acym_helper.empty(
                content)) {
                jQuery(this).closest('.acym__content').find('> div').addClass('is-hidden');
                jQuery('#acym__wysid__edit__languages, #acym__wysid__edit__multilingual__creation').removeClass('is-hidden');

                acym_editorWysidMultilingual.switchLanguage('', '', '', '');
            } else {
                acym_editorWysidMultilingual.switchLanguage(subject, preview, content, autosave);
                acym_editorWysidMultilingual.showEdition();
            }
        });

        jQuery('#acym__wysid__edit__multilingual__creation__default').off('click').on('click', function () {
            acym_editorWysidMultilingual.switchLanguage(
                jQuery('input[name="multilingual[main][subject]"]').val(),
                jQuery('input[name="multilingual[main][preview]"]').val(),
                jQuery('input[name="multilingual[main][content]"]').val(),
                ''
            );

            acym_editorWysidMultilingual.showEdition();
        });

        jQuery('#acym__wysid__edit__multilingual__creation__scratch').off('click').on('click', function () {
            acym_editorWysidMultilingual.switchLanguage('', '', jQuery('#default_template').val(), '');
            acym_editorWysidMultilingual.showEdition();
        });

        jQuery('#acym__wysid__edit__preview__reset__content').off('click').on('click', function () {
            if (acym_helper.confirm(ACYM_JS_TXT.ACYM_RESET_TRANSLATION)) {
                jQuery('input[name="multilingual[' + acym_editorWysidMultilingual.currentLanguage + '][subject]"]').val('');
                jQuery('input[name="multilingual[' + acym_editorWysidMultilingual.currentLanguage + '][preview]"]').val('');
                jQuery('input[name="multilingual[' + acym_editorWysidMultilingual.currentLanguage + '][content]"]').val('');
                jQuery('input[name="mail[subject]"]').val('');
                jQuery('input[name="mail[preheader]"]').val('');
                jQuery('#editor_content').val('');
                acym_editorWysidMultilingual.forceReload = true;
                let $currentFlag = jQuery('img[acym-data-lang="' + acym_editorWysidMultilingual.currentLanguage + '"]');
                $currentFlag.click()
                            .closest('.acym__wysid__edit__languages__selection')
                            .removeClass('acym__wysid__edit__languages__selection-done');
            }
        });
    },
    storeCurrentValues: function (saveStep = false) {
        // Make sure we're in a multilingual context
        let currentSubject = jQuery('input[name="mail[subject]"]').val();
        if (currentSubject.length === 0) return true;

        jQuery('img[acym-data-lang="' + this.currentLanguage + '"]')
            .closest('.acym__wysid__edit__languages__selection')
            .addClass('acym__wysid__edit__languages__selection-done');

        // Save the value of the 3 fields
        jQuery('input[name="multilingual[' + this.currentLanguage + '][subject]"]').val(currentSubject);
        jQuery('input[name="multilingual[' + this.currentLanguage + '][preview]"]').val(jQuery('input[name="mail[preheader]"]').val());
        jQuery('input[name="multilingual[' + this.currentLanguage + '][content]"]').val(jQuery('#editor_content').val());
        jQuery('input[name="multilingual[' + this.currentLanguage + '][autosave]"]').val(jQuery('#editor_autoSave').val());

        if (saveStep) {
            jQuery('img[acym-data-lang="main"]').click();
        }

        return true;
    },
    switchLanguage: function (newSubject, newPreview, newContent, newAutosave) {
        // Save the value of the 3 fields
        this.storeCurrentValues();

        // Set the empty value for the 3 fields
        jQuery('input[name="mail[subject]"]').val(newSubject);
        jQuery('input[name="mail[preheader]"]').val(newPreview);
        jQuery('#editor_content').val(newContent);
        jQuery('#editor_autoSave').val(newAutosave);
        acym_editorWysidVersioning.checkForUnsavedVersion();

        // Set the new current language
        this.currentLanguage = this.selectedLanguage;
    },
    showEdition: function () {
        jQuery('#acym__wysid__edit .acym__content > div').removeClass('is-hidden');
        jQuery('#acym__wysid__edit__multilingual__creation').addClass('is-hidden');

        // Reload the content preview
        acym_helperPreview.loadIframe('acym__wysid__preview__iframe__acym__wysid__email__preview', false);
    }
};
