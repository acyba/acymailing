const acym_editorWysidFormAction = {
    saveAjaxMail: function (controller, sendTest, saveAsTmpl) {
        if (controller.indexOf('mails') !== -1 || saveAsTmpl) {
            return jQuery.when(acym_helperThumbnail.setAjaxSaveThumbnail()).done(function () {
                return acym_editorWysidFormAction._ajaxCall(controller, sendTest, saveAsTmpl);
            }).fail(function (err) {
                console.log(err);
                return acym_editorWysidFormAction._ajaxCall(controller, sendTest, saveAsTmpl);
            });
        } else {
            return acym_editorWysidFormAction._ajaxCall(controller, sendTest, saveAsTmpl);
        }
    },
    saveEmail: function (sendTest, saveAsTmpl) {
        let $warning = jQuery('#acym__wysid__warning__thumbnail');
        if (!$warning.is(':visible')) {
            let heightOverlay = window.innerHeight - jQuery('#acym__wysid__top-toolbar').offset().top - jQuery('#acym__wysid__wrap').height();
            $warning.css('bottom', '-' + heightOverlay + 'px').toggle();
        }
        let $emailContent = jQuery('#acym__wysid__template');
        $emailContent.css({
            'overflow': 'hidden',
            'overflow-y': 'auto'
        });

        jQuery('.acym__wysid__row__selector, .acym__wysid__element__toolbox').remove();
        jQuery('.acym__wysid__tinymce--text--placeholder--empty').removeClass('acym__wysid__tinymce--text--placeholder--empty');

        jQuery('#acym__wysid__template img').each(function () {
            let width = jQuery(this).width();
            let outerWidth = jQuery(this).outerWidth();
            jQuery(this)
                .attr('width', width)
                .attr('height', jQuery(this).height())
                .css('width', outerWidth);
        });

        jQuery('[id^="template_version_"]').remove();

        //We remove the comments
        jQuery('#acym__wysid__template *').contents().each(function () {
            if (this.nodeType === Node.COMMENT_NODE) {
                jQuery(this).remove();
            }
        });

        $emailContent.find('.acym__wysid__column__element__td').css('outline-width', '0px');
        $emailContent.find('[contenteditable]').attr('contenteditable', 'false');
        jQuery('#acym__wysid__template a.acym__wysid__column__element__button').each(function () {
            let buttonMicrosoft = acym_editorWysidOutlook.getOutlookButton(jQuery(this));
            jQuery(this).before('<!--[if mso]>' + buttonMicrosoft + '<![endif]--><!--[if !mso]>');
            jQuery(this).after('<![endif]-->');
        });

        jQuery('#acym__wysid__template .acym__wysid__row__element').each(function () {
            if (jQuery(this).css('background-image') !== '' && jQuery(this).css('background-image') !== 'none') {
                acym_editorWysidOutlook.setBackgroundOutlook(jQuery(this));
            }
        });

        $emailContent.find('.acym__wysid__tinymce--image br[data-mce-bogus]').remove();
        if (!sendTest && !saveAsTmpl) {
            jQuery('.mce-edit-focus').removeClass('mce-edit-focus');
            $emailContent.find('[name^="mce_"]').remove();
            $emailContent.find('#acym__wysid__default').remove();
        }

        if (saveAsTmpl) {
            jQuery('.acym__wysid__hidden__save__content__template').val('<div id="acym__wysid__template" class="cell">' + $emailContent.html() + '</div>');
            jQuery('.acym__wysid__hidden__save__settings__template').val(JSON.stringify(acym_helperEditorWysid.mailsSettings));
            jQuery('.acym__wysid__hidden__save__stylesheet__template').val(jQuery('#acym__wysid__right__toolbar__settings__stylesheet__textarea').val());
        } else {
            jQuery('.acym__wysid__hidden__save__content')
                .val('<div id="acym__wysid__template" class="cell">' + $emailContent.html() + '</div>')
                .trigger('change');
            jQuery('.acym__wysid__hidden__save__settings').val(JSON.stringify(acym_helperEditorWysid.mailsSettings));
            jQuery('.acym__wysid__hidden__save__stylesheet').val(jQuery('#acym__wysid__right__toolbar__settings__stylesheet__textarea').val());
        }

        return acym_editorWysidFormAction.saveAjaxMail(jQuery('[name="ctrl"]').val(), sendTest, saveAsTmpl);
    },
    _ajaxCall: function (controller, fromSendTest, saveAsTmpl) {
        // Handle when multilingual
        acym_editorWysidMultilingual.storeCurrentValues();

        let ajaxUrl = ACYM_AJAX_URL + '&ctrl=' + controller;
        if (saveAsTmpl) {
            jQuery('input[name="task"]').val('saveAsTmplAjax');
            ajaxUrl += '&saveAsTmpl=1';
        } else {
            jQuery('input[name="task"]').val('saveAjax');
        }

        return acym_helper.post(ajaxUrl, jQuery('#acym_form').serialize()).done(function (res) {
            if (res.error) {
                acym_helperNotification.addNotification(res.message, 'error');
            } else {
                if (!saveAsTmpl) {
                    jQuery('mail' === controller ? '[name="id"]' : '[name="id"], [name="mail[id]"]').val(res.data.result);
                    if (!fromSendTest) {
                        jQuery('#acym_header').css('display', '');
                        jQuery('.acym__content').css('display', '');
                        jQuery('#acym__wysid').css('display', 'none');
                        jQuery('#acym__wysid__edit').css('display', '');
                    }
                } else {
                    acym_editorWysidNotifications.addEditorNotification({
                        'message': ACYM_JS_TXT.ACYM_TEMPLATE_CREATED,
                        'level': 'success'
                    }, 3000, false);
                }
                if (fromSendTest) acym_editorWysidTest.sendTest(res.data.result);
                jQuery('#acym__wysid__warning__thumbnail').toggle();
            }
            jQuery('#acym__wysid__save__button').removeAttr('disabled');
            acym_helperEditorWysid.setColumnRefreshUiWYSID(false);
            acym_editorWysidTinymce.addTinyMceWYSID();
            acym_editorWysidRowSelector.setZoneAndBlockOverlays();
        }).fail(function (jqXHR, textStatus) {
            alert('Request failed: ' + textStatus);
        });
    },
    setSaveButtonWYSID: function () {
        jQuery('#acym__wysid__save__button').off('click').on('click', function () {
            // Directly save the email
            if (jQuery('[name="ctrl"]').val().indexOf('campaigns') !== -1 || jQuery('#acym__mail__type').val() === 'followup') {
                acym_editorWysidFormAction.saveEmail(false, false);
                return true;
            }

            // Generate a thumbnail then save the email
            acym_editorWysidFormAction.setSaveTmpl(false);
        });
    },
    setSaveAsTmplButtonWYSID: function () {
        jQuery('#acym__wysid__saveastmpl__button').off('click').on('click', function () {
            if (acym_helper.confirm(ACYM_JS_TXT.ACYM_SAVE_AS_TEMPLATE_CONFIRMATION)) {
                acym_editorWysidFormAction.setSaveTmpl(true);
            }
        });
    },
    setSaveTmpl: function (saveAsTmpl) {
        let $editorArea = jQuery('#acym__wysid__wrap');
        let heightOverlay = window.innerHeight - jQuery('#acym__wysid__top-toolbar').offset().top - $editorArea.height();
        jQuery('#acym__wysid__warning__thumbnail').css('bottom', '-' + heightOverlay + 'px').toggle();

        setTimeout(() => {
            acym_editorWysidFormAction.setThumbnailPreSave()
                                      .then(function (dataUrl) {
                                          // Copy img content in hidden input
                                          jQuery('#editor_thumbnail').attr('value', dataUrl);
                                          acym_editorWysidFormAction.saveEmail(false, saveAsTmpl);
                                      })
                                      .catch(function (err) {
                                          console.error('Error generating template thumbnail: ' + err);
                                          acym_editorWysidFormAction.saveEmail(false, saveAsTmpl);
                                      });
        }, 10);
    },
    setThumbnailPreSave: function () {
        jQuery('#acym__wysid__template').css({
            'overflow': 'unset',
            'overflow-y': 'unset'
        });

        let tmplheight = jQuery('.acym__wysid__template__content').height();
        let node = document.getElementById('acym__wysid__template');

        return html2canvas(node, {
            height: tmplheight,
            logging: false
        }).then(canvas => {
            return canvas.toDataURL('image/png');
        });
    },
    setOpenEditorButton: function () {
        jQuery('#acym__wysid__edit__button').off('click').on('click', function () {
            let $editorContent = jQuery('#acym__wysid__template');

            if (jQuery('#acym__wysid .acym__wysid__template__content').css('background-image') !== 'none') {
                jQuery('#acym__wysid__background-image__template-delete').hide();
            }
            jQuery('.acym__wysid__row__element__toolbox__colorpicker').spectrum('destroy');
            jQuery('.sp-container').remove();
            jQuery('#acym_header').css('display', 'none');
            jQuery('.acym__content').css('display', 'none');
            let acymWysidDivStyle = {
                'display': 'inherit'
            };
            if (ACYM_CMS === 'joomla') {
                acymWysidDivStyle.top = jQuery('.navbar-fixed-top').height() + 'px';
            }
            jQuery('#acym__wysid').css(acymWysidDivStyle);
            jQuery('#acym__wysid__edit').css('display', 'none');

            let savedContent = jQuery('.acym__wysid__hidden__save__content').val();
            if (!acym_helper.empty(savedContent)) {
                $editorContent.replaceWith(savedContent);
            }

            let savedSettings = jQuery('.acym__wysid__hidden__save__settings').val();
            acym_helperEditorWysid.saveSettings = acym_helper.empty(savedSettings) ? '' : savedSettings;
            acym_helperEditorWysid.mailsSettings = acym_helperEditorWysid.saveSettings === '' ? {} : acym_helper.parseJson(acym_helperEditorWysid.saveSettings);

            let savedStylesheet = jQuery('.acym__wysid__hidden__save__stylesheet').val();
            if (!acym_helper.empty(savedStylesheet)) {
                acym_helperEditorWysid.savedStylesheet = savedStylesheet;
                jQuery('#acym__wysid__right__toolbar__settings__stylesheet__textarea').val(acym_helperEditorWysid.savedStylesheet);
            }

            let $images = $editorContent.find('img');
            let numberImages = $images.length;

            // We apply the zone and block overlays after the images are loaded to make sure the height of these overlays is correct
            if (numberImages > 0) {
                let countLoadedImages = 0;
                $images.on('load', function () {
                    countLoadedImages++;
                    if (numberImages === countLoadedImages) {
                        acym_editorWysidRowSelector.setZoneAndBlockOverlays();
                    }
                });
            } else {
                acym_editorWysidRowSelector.setZoneAndBlockOverlays();
            }

            let $emailContent = $editorContent;
            $emailContent.find('[contenteditable]').attr('contenteditable', 'true');
            $emailContent.find('[id^="mce_"]').removeAttr('id');

            acym_helperEditorWysid.setColumnRefreshUiWYSID(true, true);

            let $elementsToReload = jQuery('tr[data-dynamic]');
            if ($elementsToReload.length > 0) {
                acym_editorWysidDynamic.insertDContent('', $elementsToReload);
            }

            acym_editorWysidFontStyle.setSettingsModificationHandling();
            acym_editorWysidImage.setImageWidthHeightOnInsert();
            acym_helperEditorWysid.resizeEditorBasedOnPage();
            acym_editorWysidColorPicker.setGeneralColorPickerWYSID();
            acym_editorWysidContextModal.setButtonOptions();
            acym_editorWysidContextModal.setSpaceOptions();
            acym_editorWysidContextModal.setFollowOptions();
            acym_editorWysidContextModal.setSeparatorOptions();
            acym_editorWysidContextModal.setBuiltWithOptions();
            acym_editorWysidFontStyle.applyCssOnAllElementTypesBasedOnSettings();
            acym_editorWysidDynamic.setDTextActions();
            acym_editorWysidDynamic.setDContentActions();
            acym_editorWysidTinymce.addTinyMceWYSID();
            acym_editorWysidRowSelector.setZoneAndBlockOverlays();
        });
    },
    setCancelButtonWYSID: function () {
        jQuery('#acym__wysid__cancel__button').off('click').on('click', function () {
            jQuery('#acym_header').css('display', '');
            jQuery('.acym__content').css('display', '');
            jQuery('#acym__wysid').css('display', 'none');
            jQuery('#acym__wysid__edit').css('display', '');
            jQuery('.acym__wysid__hidden__save__stylesheet').val(acym_helperEditorWysid.savedStylesheet);
        });
    }
};
