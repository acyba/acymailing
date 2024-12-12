const acym_editorWysidFontStyle = {
    allHtmlElementTypes: [
        'p',
        'a',
        'span.acym_link',
        'li',
        'h1',
        'h2',
        'h3',
        'h4',
        'h5',
        'h6'
    ],
    currentlySelectedType: 'p',
    applyCssOnAllElementTypesBasedOnSettings: function () {
        this.allHtmlElementTypes.map(oneHtmlElementType => {
            acym_editorWysidFontStyle.applyCssOnElementsBasedOnSettings(oneHtmlElementType);
        });
        acym_editorWysidFontStyle.applyCssOnePropertyOnElementsBasedOnSettings('#acym__wysid__background-colorpicker', 'background-color');
    },
    applyCssOnElementsBasedOnSettings: function (oneHtmlElementType) {
        jQuery.each(acym_helperEditorWysid.defaultMailsSettings[oneHtmlElementType], function (property) {
            acym_editorWysidFontStyle.applyCssOnePropertyOnElementsBasedOnSettings(oneHtmlElementType, property);
        });
    },
    applyCssOnePropertyOnElementsBasedOnSettings: function (oneHtmlElementType, property) {
        let selector = '';
        if (oneHtmlElementType === '#acym__wysid__background-colorpicker') {
            selector = '.acym__wysid__template__content';
        } else {
            selector = '.acym__wysid__column__element ' + oneHtmlElementType + ':not(.acym__wysid__content-no-settings-style)';
        }

        jQuery(selector).css(property, acym_editorWysidFontStyle.getPropertyOfOneType(oneHtmlElementType, property));
    },
    getPropertyOfOneType: function (oneHtmlElementType, property) {
        return acym_helperEditorWysid.mailsSettings[oneHtmlElementType]
               !== undefined
               && acym_helperEditorWysid.mailsSettings[oneHtmlElementType][property]
               && acym_helperEditorWysid.mailsSettings[oneHtmlElementType][property]
               !== undefined
               ? acym_helperEditorWysid.mailsSettings[oneHtmlElementType][property]
               : acym_helperEditorWysid.defaultMailsSettings[oneHtmlElementType][property];
    },
    saveAndApplyPropertyOnOneType: function (oneHtmlElementType, property, value, override = true) {
        if (acym_helperEditorWysid.mailsSettings[oneHtmlElementType] === undefined) {
            acym_helperEditorWysid.mailsSettings[oneHtmlElementType] = {};
        }

        if (!override
            && !acym_helper.empty(acym_helperEditorWysid.mailsSettings[oneHtmlElementType].overridden)
            && !acym_helper.empty(acym_helperEditorWysid.mailsSettings[oneHtmlElementType].overridden[property])) {
            return;
        }

        acym_helperEditorWysid.mailsSettings[oneHtmlElementType][property] = value;
        if (override) {
            if (acym_helper.empty(acym_helperEditorWysid.mailsSettings[oneHtmlElementType].overridden)) {
                acym_helperEditorWysid.mailsSettings[oneHtmlElementType].overridden = {};
            }
            acym_helperEditorWysid.mailsSettings[oneHtmlElementType].overridden[property] = true;
        }

        acym_editorWysidFontStyle.applyCssOnePropertyOnElementsBasedOnSettings(oneHtmlElementType, property);
    },
    setDesignOptionValuesForSelectedType: function () {
        jQuery('#acym__wysid__right__toolbar__settings__font-family')
            .val(acym_editorWysidFontStyle.getPropertyOfOneType(acym_editorWysidFontStyle.currentlySelectedType, 'font-family'))
            .trigger('change');

        jQuery('#acym__wysid__right__toolbar__settings__font-size')
            .val(acym_editorWysidFontStyle.getPropertyOfOneType(acym_editorWysidFontStyle.currentlySelectedType, 'font-size'))
            .trigger('change');

        jQuery('#acym__wysid__right__toolbar__settings__line-height')
            .val(acym_editorWysidFontStyle.getPropertyOfOneType(acym_editorWysidFontStyle.currentlySelectedType, 'line-height'))
            .trigger('change');

        let $settingsBold = jQuery('#acym__wysid__right__toolbar__settings__bold');
        if (acym_editorWysidFontStyle.getPropertyOfOneType(acym_editorWysidFontStyle.currentlySelectedType, 'font-weight') === 'bold') {
            $settingsBold.addClass('acym__wysid__right__toolbar__settings__bold--selected');
        } else {
            $settingsBold.removeClass('acym__wysid__right__toolbar__settings__bold--selected');
        }

        let $settingsItalic = jQuery('#acym__wysid__right__toolbar__settings__italic');
        if (acym_editorWysidFontStyle.getPropertyOfOneType(acym_editorWysidFontStyle.currentlySelectedType, 'font-style') === 'italic') {
            $settingsItalic.addClass('acym__wysid__right__toolbar__settings__italic--selected');
        } else {
            $settingsItalic.removeClass('acym__wysid__right__toolbar__settings__italic--selected');
        }

        acym_editorWysidColorPicker.setSettingsColorPickerWYSID();
    },
    setSettingsModificationHandling: function () {
        let selectedHtmlElementType = acym_editorWysidFontStyle.currentlySelectedType;
        let $emailContentContainer = jQuery('#acym__wysid .acym__wysid__template__content');
        let $deleteBackgroundImage = jQuery('#acym__wysid__background-image__template-delete');
        let $inputPaddingTop = jQuery('#acym__wysid__padding__top__content');
        let $inputPaddingBottom = jQuery('#acym__wysid__padding__bottom__content');

        // Init template design options
        jQuery('#acym__wysid__background-image__template').off('click').on('click', function () {
            acym_editorWysidImage.addMediaWPWYSID($emailContentContainer, true);
        });

        if ($emailContentContainer.css('background-image') !== 'none') {
            $deleteBackgroundImage.css('display', 'flex');
        }

        $deleteBackgroundImage.off('click').on('click', function () {
            $emailContentContainer.css('background-image', 'none');
            jQuery(this).hide();
        });

        $inputPaddingTop.val($emailContentContainer.css('padding-top').replace(/[^-\d\.]/g, ''));
        $inputPaddingTop.off('change').on('change', function () {
            $emailContentContainer.css('padding-top', jQuery(this).val() + 'px');
        });

        $inputPaddingBottom.val($emailContentContainer.css('padding-bottom').replace(/[^-\d\.]/g, ''));
        $inputPaddingBottom.off('change').on('change', function () {
            $emailContentContainer.css('padding-bottom', jQuery(this).val() + 'px');
        });

        acym_editorWysidFontStyle.initDefaultFont();

        // Init design options
        acym_editorWysidFontStyle.setDesignModificationHandling();
        acym_editorWysidFontStyle.setDesignOptionValuesForSelectedType();
        acym_editorWysidFontStyle.applyCssOnElementsBasedOnSettings(selectedHtmlElementType);
    },
    setDesignModificationHandling: function () {
        let $settingsBold = jQuery('#acym__wysid__right__toolbar__settings__bold');
        let $settingsItalic = jQuery('#acym__wysid__right__toolbar__settings__italic');

        jQuery('#acym__wysid__right__toolbar__settings__font--select').on('change', function () {
            acym_editorWysidFontStyle.currentlySelectedType = jQuery(this).val();
            acym_editorWysidFontStyle.setDesignOptionValuesForSelectedType();
        });

        jQuery('#acym__wysid__right__toolbar__settings__font-family').off('select2:select').on('change select2:select', function (event) {
            acym_editorWysidFontStyle.saveAndApplyPropertyOnOneType(acym_editorWysidFontStyle.currentlySelectedType,
                'font-family',
                jQuery(this).val(),
                event.type !== 'change'
            );
        });

        jQuery('#acym__wysid__right__toolbar__settings__font-size').on('change', function () {
            acym_editorWysidFontStyle.saveAndApplyPropertyOnOneType(acym_editorWysidFontStyle.currentlySelectedType, 'font-size', jQuery(this).val());
        });

        jQuery('#acym__wysid__right__toolbar__settings__line-height').on('change', function () {
            acym_editorWysidFontStyle.saveAndApplyPropertyOnOneType(acym_editorWysidFontStyle.currentlySelectedType, 'line-height', jQuery(this).val());
        });

        $settingsBold.off('click').off('click').on('click', function () {
            if ($settingsBold.hasClass('acym__wysid__right__toolbar__settings__bold--selected')) {
                $settingsBold.removeClass('acym__wysid__right__toolbar__settings__bold--selected');
                acym_editorWysidFontStyle.saveAndApplyPropertyOnOneType(acym_editorWysidFontStyle.currentlySelectedType, 'font-weight', 'normal');
            } else {
                $settingsBold.addClass('acym__wysid__right__toolbar__settings__bold--selected');
                acym_editorWysidFontStyle.saveAndApplyPropertyOnOneType(acym_editorWysidFontStyle.currentlySelectedType, 'font-weight', 'bold');
            }
        });

        $settingsItalic.off('click').off('click').on('click', function () {
            if ($settingsItalic.hasClass('acym__wysid__right__toolbar__settings__italic--selected')) {
                $settingsItalic.removeClass('acym__wysid__right__toolbar__settings__italic--selected');
                acym_editorWysidFontStyle.saveAndApplyPropertyOnOneType(acym_editorWysidFontStyle.currentlySelectedType, 'font-style', 'normal');
            } else {
                $settingsItalic.addClass('acym__wysid__right__toolbar__settings__italic--selected');
                acym_editorWysidFontStyle.saveAndApplyPropertyOnOneType(acym_editorWysidFontStyle.currentlySelectedType, 'font-style', 'italic');
            }
        });
    },
    setOpenStylesheet: function () {
        jQuery('#acym__wysid__right__toolbar__settings__stylesheet__open').on('click', function () {
            acym_helperEditorWysid.stylesheetTemp = jQuery('#acym__wysid__right__toolbar__settings__stylesheet__textarea').val();
        });
    },
    setCancelStylesheet: function () {
        jQuery('#acym__wysid__right__toolbar__settings__stylesheet__cancel').on('click', function () {
            jQuery('#acym__wysid__right__toolbar__settings__stylesheet__textarea').val(acym_helperEditorWysid.stylesheetTemp);
            jQuery('#acym__wysid__right__toolbar__settings__stylesheet__modal .close-button').trigger('click');
        });
    },
    setApplyStylesheetSettings: function () {
        jQuery('#acym__wysid__right__toolbar__settings__stylesheet__apply').off('click').on('click', function () {
            let css = jQuery('#acym__wysid__right__toolbar__settings__stylesheet__textarea').val();
            jQuery('.acym__wysid__hidden__save__stylesheet').val(css);
            jQuery('#acym__wysid__edit').append('<style id="acym__wysid__custom__style">' + acym_helperEditorWysid.parseTextToCss(css) + '</style>');
            jQuery('#acym__wysid__right__toolbar__settings__stylesheet__modal .close-button').trigger('click');
        });
    },
    setSocialIconImport: function () {
        let allSocialIcons = acym_helper.parseJson(jQuery('#acym__mail__edit__editor__social__icons').val());
        if (undefined === allSocialIcons) return;

        jQuery.each(Object.keys(allSocialIcons), function (index, value) {
            if (!acym_helperEditorWysid.socialMedia[value]) {
                return;
            }
            acym_helperEditorWysid.socialMedia[value].src = allSocialIcons[value];
        });

        jQuery('.acym__wysid__social__icons__import__text').off('click').on('click', function () {
            let $this = jQuery(this);
            let $inputFile = $this.closest('.acym__wysid__right__toolbar__design__social__icons__one').find('input');
            $inputFile.trigger('click');
            $inputFile.off('change').on('change', function () {
                let filename = jQuery(this).val().split('\\').pop();
                $this.closest('.acym__wysid__right__toolbar__design__social__icons__one').find('.acym__wysid__social__icons__import__delete').remove();
                if (undefined === filename || '' === filename) {
                    $this.html(ACYM_JS_TXT.ACYM_SELECT_NEW_ICON);
                    $this.closest('.acym__wysid__right__toolbar__design__social__icons__one').find('button').attr('disabled', 'disabled');
                } else {
                    $this.html(filename).after('<i class="acymicon-close cell shrink acym__wysid__social__icons__import__delete cursor-pointer"></i>');
                    $this.closest('.acym__wysid__right__toolbar__design__social__icons__one').find('button').removeAttr('disabled');
                }
                jQuery('.acym__wysid__social__icons__import__delete').off('click').on('click', function () {
                    jQuery(this).closest('.acym__wysid__right__toolbar__design__social__icons__one').find('button').attr('disabled', 'disabled');
                    jQuery(this).closest('.acym__wysid__right__toolbar__design__social__icons__one').find('input').val('').trigger('change');
                    jQuery(this).remove();
                });
            });
        });

        jQuery('.acym__wysid__social__icons__import').off('click').on('click', function () {
            jQuery(this)
                .closest('div')
                .find('.acym__wysid__social__icons__import__delete')
                .html('')
                .attr('class', 'acymicon-circle-o-notch acymicon-spin acym__wysid__social__icons__import__delete')
                .css('color', '#303e46');
            let $input = jQuery(this).closest('.acym__wysid__right__toolbar__design__social__icons__one').find('input');
            let file_data = $input.prop('files')[0];
            let form_data = new FormData();
            form_data.append('file', file_data);
            let whichIcon = $input.attr('name').replace('icon_', '');
            let ajaxUrl = ACYM_AJAX_URL + '&ctrl=' + acym_helper.ctrlMails + '&task=setNewIconShare&social=' + whichIcon;
            jQuery.ajax({
                url: ajaxUrl,
                dataType: 'text',  // what to expect back from the PHP script, if anything
                cache: false,
                contentType: false,
                processData: false,
                data: form_data,
                type: 'post',
                success: function (res) {
                    res = acym_helper.parseJson(res);
                    if (!res.error) {
                        let img = jQuery('img').filter('[src^="' + res.data.url + '"]');
                        let finalUrl = res.data.url + '.' + res.data.extension;

                        jQuery.each(img, function () {
                            let d = new Date();
                            jQuery(this).removeAttr('src').attr('src', finalUrl + '?d=' + d.getTime());
                        });
                        acym_helperEditorWysid.socialMedia[whichIcon].src = finalUrl;
                    }

                    $input.val('').trigger('change');
                    acym_editorWysidNotifications.addEditorNotification({
                        'message': res.message,
                        'level': res.error ? 'error' : 'success'
                    });
                }
            });
        });
    },
    initDefaultFont: function () {
        let savedFont = acym_helperEditorWysid.defaultMailsSettings.default['font-family'];
        if (!acym_helper.empty(acym_helperEditorWysid.mailsSettings.default)
            && !acym_helper.empty(acym_helperEditorWysid.mailsSettings.default['font-family'])) {
            savedFont = acym_helperEditorWysid.mailsSettings.default['font-family'];
        }

        const $defaultFontSelect = jQuery('[name="default_font"]');

        $defaultFontSelect.on('change', function (event) {
            let font = jQuery(this).val();

            if (acym_helper.empty(acym_helperEditorWysid.mailsSettings.default)) acym_helperEditorWysid.mailsSettings.default = {};
            acym_helperEditorWysid.mailsSettings.default['font-family'] = font;

            acym_editorWysidFontStyle.allHtmlElementTypes.map(oneHtmlElementType => {
                acym_editorWysidFontStyle.saveAndApplyPropertyOnOneType(oneHtmlElementType, 'font-family', font, false);
            });
        });

        $defaultFontSelect.val(savedFont).trigger('change');
    }
};
