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
            let settings = jQuery('input[name="multilingual[' + acym_editorWysidMultilingual.selectedLanguage + '][settings]"]').val();
            let stylesheet = jQuery('input[name="multilingual[' + acym_editorWysidMultilingual.selectedLanguage + '][stylesheet]"]').val();

            if (acym_editorWysidMultilingual.selectedLanguage !== 'main' && acym_helper.empty(subject) && acym_helper.empty(preview) && acym_helper.empty(
                content)) {
                jQuery(this).closest('.acym__content').find('> div').addClass('is-hidden');
                jQuery('#acym__wysid__edit__languages, #acym__wysid__edit__multilingual__creation').removeClass('is-hidden');

                acym_editorWysidMultilingual.switchLanguage('', '', '', '');
            } else {
                acym_editorWysidMultilingual.switchLanguage(subject, preview, content, autosave, settings, stylesheet);
                acym_editorWysidMultilingual.showEdition();
            }
        });

        jQuery('#acym__wysid__edit__multilingual__creation__default').off('click').on('click', function () {
            acym_editorWysidMultilingual.switchLanguage(
                jQuery('input[name="multilingual[main][subject]"]').val(),
                jQuery('input[name="multilingual[main][preview]"]').val(),
                jQuery('input[name="multilingual[main][content]"]').val(),
                '',
                jQuery('input[name="multilingual[main][settings]"]').val(),
                jQuery('input[name="multilingual[main][stylesheet]"]').val()
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
                jQuery('input[name="multilingual[' + acym_editorWysidMultilingual.currentLanguage + '][settings]"]').val('');
                jQuery('input[name="multilingual[' + acym_editorWysidMultilingual.currentLanguage + '][stylesheet]"]').val('');
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
        if (acym_helper.empty(currentSubject)) return true;

        jQuery('img[acym-data-lang="' + this.currentLanguage + '"]')
            .closest('.acym__wysid__edit__languages__selection')
            .addClass('acym__wysid__edit__languages__selection-done');

        // Save the value of the 3 fields
        jQuery('input[name="multilingual[' + this.currentLanguage + '][subject]"]').val(currentSubject);
        jQuery('input[name="multilingual[' + this.currentLanguage + '][preview]"]').val(jQuery('input[name="mail[preheader]"]').val());
        jQuery('input[name="multilingual[' + this.currentLanguage + '][content]"]').val(jQuery('#editor_content').val());
        jQuery('input[name="multilingual[' + this.currentLanguage + '][autosave]"]').val(jQuery('#editor_autoSave').val());
        jQuery('input[name="multilingual[' + this.currentLanguage + '][settings]"]').val(jQuery('#editor_settings').val());
        jQuery('input[name="multilingual[' + this.currentLanguage + '][stylesheet]"]').val(jQuery('#editor_stylesheet').val());

        if (saveStep) {
            jQuery('img[acym-data-lang="main"]').click();
        }

        return true;
    },
    switchLanguage: function (newSubject, newPreview, newContent, newAutosave, settings = '', stylesheet = '') {
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

        // Set the new current language
        this.currentLanguage = this.selectedLanguage;
    },
    showEdition: function () {
        jQuery('#acym__wysid__edit .acym__content > div').removeClass('is-hidden');
        jQuery('#acym__wysid__edit__multilingual__creation').addClass('is-hidden');

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
            let $loader = jQuery('.acym__template__choose__modal__loader');
            $loader.css('display', 'flex');
            let data = {
                ctrl: ACYM_IS_ADMIN ? 'mails' : 'frontmails',
                task: 'getMailByIdAjax',
                id: this.getAttribute('id')
            };

            acym_helper.get(ACYM_AJAX_URL, data).then(res => {
                if (res.error) {
                    acym_helperNotification.addNotification(res.message, 'error', true);
                    return;
                }
                acym_editorWysidMultilingual.switchLanguage(res.data.subject, res.data.preheader, res.data.body, '', res.data.settings, res.data.stylesheet);
                acym_editorWysidMultilingual.showEdition();

                jQuery(this).closest('#acym__template__choose__modal').find('.close-button').click();
                $loader.css('display', 'none');
            });
        });
    }
};
