const acym_editorWysidTest = {
    toggleSendTest: function () {
        let $sendTestContainer = jQuery('#acym__wysid__send__test');
        let $rightToolbar = jQuery('#acym__wysid__right-toolbar');
        jQuery('#acym__wysid__test__button').off('click').on('click', function () {
            acym_editorWysidTest.toggleSendTestAndRightToolbar($sendTestContainer, $rightToolbar);
            jQuery(window).on('mousedown', function (event) {
                if (jQuery(event.target).closest('#acym__wysid__send__test').length > 0) return true;
                acym_editorWysidTest.toggleSendTestAndRightToolbar($sendTestContainer, $rightToolbar);
            });
        });

        jQuery('.acym__wysid__send__test-close').off('click').on('click', function () {
            acym_editorWysidTest.toggleSendTestAndRightToolbar($sendTestContainer, $rightToolbar);
        });
    },
    toggleSendTestAndRightToolbar: function ($sendTestContainer, $rightToolbar) {
        jQuery(window).off('mousedown');
        if ($sendTestContainer.hasClass('acym__wysid__show')) {
            $sendTestContainer.removeClass('acym__wysid__show');
            $rightToolbar.removeClass('acym__wysid__hidden');
        } else {
            $sendTestContainer.addClass('acym__wysid__show');
            $rightToolbar.addClass('acym__wysid__hidden');
        }
    },
    sendTestAjax: function () {
        jQuery('#acym__wysid__send__test__button').off('click').on('click', function () {
            jQuery(this).attr('disabled', 'true');
            jQuery('.acym__wysid__send__test__icon').hide();
            jQuery('.acym__wysid__send__test__icon__loader').show();
            if (jQuery('[name="ctrl"]').val().indexOf('campaigns') !== -1) {
                acym_editorWysidFormAction.saveTemplate(true, false);
                return true;
            }
            acym_editorWysidFormAction.setThumbnailPreSave()
                                      .then(function (dataUrl) {
                                          // Copy img content in hidden input
                                          jQuery('#editor_thumbnail').attr('value', dataUrl);
                                          acym_editorWysidFormAction.saveTemplate(true, false);
                                      })
                                      .catch(function (err) {
                                          acym_editorWysidFormAction.saveTemplate(true, false);
                                          console.error('Error generating template thumbnail: ' + err);
                                      });
            return true;
        });
    },
    sendTest: function (id) {
        let url = ACYM_AJAX_URL;
        url += '&page=acymailing_mails';
        url += '&ctrl=' + acym_helper.ctrlMails;
        url += '&task=sendTest';
        url += '&id=' + id;
        url += '&controller=' + jQuery('[name="ctrl"]').val();
        url += '&test_note=' + encodeURIComponent(jQuery('#acym__wysid__send__test__note').val());
        if (ACYM_IS_ADMIN) {
            url += '&test_emails=' + encodeURIComponent(jQuery('.acym__multiselect__email').val().join(','));
        } else {
            url += '&test_emails=' + encodeURIComponent(jQuery('input[name="emails_test"]').val());
        }
        url += '&lang_version=' + acym_editorWysidMultilingual.currentLanguage;

        jQuery.post(url, function (res) {
            res = acym_helper.parseJson(res);
            res.message = res.message.replace(/<.*?>/gm, ' ');
            let error = res.level === 'error';
            acym_editorWysidNotifications.addEditorNotification(res, error ? false : 3000, !error);
            jQuery('.acym__wysid__send__test__icon').show();
            jQuery('.acym__wysid__send__test__icon__loader').hide();
            jQuery('#acym__wysid__send__test__button').removeAttr('disabled');
            if (res.level === 'info') jQuery('.acym__wysid__send__test-close').click();
            acym_helperEditorWysid.setColumnRefreshUiWYSID();
        });
    }
};
