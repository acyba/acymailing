const acym_editorWysidFontStyle = {
    setAllHtmlElementStyleWYSID: function () {
        acym_editorWysidFontStyle.setAllSettingsOfHtmlElementWYSID('p');
        acym_editorWysidFontStyle.setAllSettingsOfHtmlElementWYSID('a');
        acym_editorWysidFontStyle.setAllSettingsOfHtmlElementWYSID('li');
        acym_editorWysidFontStyle.setAllSettingsOfHtmlElementWYSID('h1');
        acym_editorWysidFontStyle.setAllSettingsOfHtmlElementWYSID('h2');
        acym_editorWysidFontStyle.setAllSettingsOfHtmlElementWYSID('h3');
        acym_editorWysidFontStyle.setAllSettingsOfHtmlElementWYSID('h4');
        acym_editorWysidFontStyle.setAllSettingsOfHtmlElementWYSID('h5');
        acym_editorWysidFontStyle.setAllSettingsOfHtmlElementWYSID('h6');
        jQuery('.acym__wysid__template__content')
            .css('background-color', acym_editorWysidFontStyle.getSettingsOfHtmlElementWYSID('#acym__wysid__background-colorpicker', 'background-color'));
    },
    setAllSettingsOfHtmlElementWYSID: function (element) {
        jQuery.each(acym_helperEditorWysid.defaultMailsSettings[element], function (property) {
            jQuery('.acym__wysid__column__element ' + element + ':not(.acym__wysid__content-no-settings-style)')
                .css(property, acym_editorWysidFontStyle.getSettingsOfHtmlElementWYSID(element, property));
        });
    },
    getSettingsOfHtmlElementWYSID: function (element, property) {
        return acym_helperEditorWysid.mailsSettings[element]
               !== undefined
               && acym_helperEditorWysid.mailsSettings[element][property]
               && acym_helperEditorWysid.mailsSettings[element][property]
               !== undefined ? acym_helperEditorWysid.mailsSettings[element][property] : acym_helperEditorWysid.defaultMailsSettings[element][property];
    },
    setSettingsElementStyle: function (element, property, value) {
        acym_helperEditorWysid.mailsSettings[element] === undefined ? acym_helperEditorWysid.mailsSettings[element] = {} : true;

        acym_helperEditorWysid.mailsSettings[element][property] = value;
        acym_editorWysidFontStyle.setAllHtmlElementStyleWYSID();
    },
    setSettingsWYSID: function () {
        let $settingsBold = jQuery('#acym__wysid__right__toolbar__settings__bold');
        let $settingsItalic = jQuery('#acym__wysid__right__toolbar__settings__italic');
        let $currentFont = jQuery('#acym__wysid__right__toolbar__settings__font--select').val();
        let $contentTemplate = jQuery('#acym__wysid .acym__wysid__template__content');
        let $deleteBackgroundImage = jQuery('#acym__wysid__background-image__template-delete');
        jQuery('#acym__wysid__right__toolbar__settings__font-family')
            .val(acym_editorWysidFontStyle.getSettingsOfHtmlElementWYSID($currentFont, 'font-family'))
            .trigger('change');
        jQuery('#acym__wysid__right__toolbar__settings__font-size')
            .val(acym_editorWysidFontStyle.getSettingsOfHtmlElementWYSID($currentFont, 'font-size'))
            .trigger('change');

        $settingsBold.val(acym_editorWysidFontStyle.getSettingsOfHtmlElementWYSID($currentFont, 'font-weight') == 'bold' ? $settingsBold.addClass(
            'acym__wysid__right__toolbar__settings__bold--selected') : $settingsBold.removeClass('acym__wysid__right__toolbar__settings__bold--selected'));

        $settingsItalic.val(acym_editorWysidFontStyle.getSettingsOfHtmlElementWYSID($currentFont, 'font-style') == 'italic'
                            ? $settingsItalic.addClass('acym__wysid__right__toolbar__settings__italic--selected')
                            : $settingsItalic.removeClass('acym__wysid__right__toolbar__settings__italic--selected'));

        acym_editorWysidColorPicker.setSettingsColorPickerWYSID(acym_editorWysidFontStyle.getSettingsOfHtmlElementWYSID($currentFont, 'color'));
        acym_editorWysidFontStyle.setAllSettingsOfHtmlElementWYSID($currentFont);

        jQuery('#acym__wysid__background-image__template').off('click').on('click', function () {
            acym_editorWysidNewContent.addMediaWysid($contentTemplate, true);
        });

        if ($contentTemplate.css('background-image') !== 'none') $deleteBackgroundImage.css('display', 'flex');

        $deleteBackgroundImage.off('click').on('click', function () {
            $contentTemplate.css('background-image', 'none');
            jQuery(this).hide();
        });

        let $inputPaddingTop = jQuery('#acym__wysid__padding__top__content');

        $inputPaddingTop.val($contentTemplate.css('padding-top').replace(/[^-\d\.]/g, ''));
        $inputPaddingTop.off('change').on('change', function () {
            $contentTemplate.css('padding-top', jQuery(this).val() + 'px');
        });

        let $inputPaddingBottom = jQuery('#acym__wysid__padding__bottom__content');

        $inputPaddingBottom.val($contentTemplate.css('padding-bottom').replace(/[^-\d\.]/g, ''));
        $inputPaddingBottom.off('change').on('change', function () {
            $contentTemplate.css('padding-bottom', jQuery(this).val() + 'px');
        });


    },
    setSettingsControlsWYSID: function () {
        let $settingsBold = jQuery('#acym__wysid__right__toolbar__settings__bold');
        let $settingsItalic = jQuery('#acym__wysid__right__toolbar__settings__italic');
        let $toolbarFont = jQuery('#acym__wysid__right__toolbar__settings__font--select');

        $toolbarFont.on('change', function () {
            acym_editorWysidFontStyle.setSettingsWYSID();
        });

        jQuery('#acym__wysid__right__toolbar__settings__font-family').on('change', function () {
            acym_editorWysidFontStyle.setSettingsElementStyle(
                jQuery('#acym__wysid__right__toolbar__settings__font--select').val(),
                'font-family',
                jQuery(this).val()
            );
        });

        jQuery('#acym__wysid__right__toolbar__settings__font-size').on('change', function () {
            acym_editorWysidFontStyle.setSettingsElementStyle(
                jQuery('#acym__wysid__right__toolbar__settings__font--select').val(),
                'font-size',
                jQuery(this).val()
            );
        });

        $settingsBold.off('click').on('click', function () {
            $settingsBold.hasClass('acym__wysid__right__toolbar__settings__bold--selected') ? $settingsBold.removeClass(
                'acym__wysid__right__toolbar__settings__bold--selected') && acym_editorWysidFontStyle.setSettingsElementStyle(
                $toolbarFont.val(),
                'font-weight',
                'normal'
            ) : $settingsBold.addClass('acym__wysid__right__toolbar__settings__bold--selected') && acym_editorWysidFontStyle.setSettingsElementStyle(
                $toolbarFont.val(),
                'font-weight',
                'bold'
            );
        });

        $settingsItalic.off('click').on('click', function () {
            $settingsItalic.hasClass('acym__wysid__right__toolbar__settings__italic--selected') ? $settingsItalic.removeClass(
                'acym__wysid__right__toolbar__settings__italic--selected') && acym_editorWysidFontStyle.setSettingsElementStyle(
                $toolbarFont.val(),
                'font-style',
                'normal'
            ) : $settingsItalic.addClass('acym__wysid__right__toolbar__settings__italic--selected') && acym_editorWysidFontStyle.setSettingsElementStyle(
                $toolbarFont.val(),
                'font-style',
                'italic'
            );
        });
    },
    setApplyStylesheetSettings: function () {
        jQuery('#acym__wysid__right__toolbar__settings__stylesheet__apply').off('click').on('click', function () {
            let css = jQuery('#acym__wysid__right__toolbar__settings__stylesheet__textarea').val();
            jQuery('.acym__wysid__hidden__save__stylesheet').val(css);
            jQuery('#acym__wysid__edit').append('<style id="acym__wysid__custom__style">' + acym_helperEditorWysid.parseTextToCss(css) + '</style>');
            jQuery('#acym__wysid__right__toolbar__settings__stylesheet__modal').foundation('close');
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
            jQuery('#acym__wysid__right__toolbar__settings__stylesheet__modal').foundation('close');
        });
    },
    setSocialIconImport: function () {
        let allSocialIcons = JSON.parse(jQuery('#acym__mail__edit__editor__social__icons').val());
        if (undefined === allSocialIcons) return;

        jQuery.each(Object.keys(allSocialIcons), function (index, value) {
            acym_helperEditorWysid.socialMedia[value].src = allSocialIcons[value];
        });

        jQuery('.acym__wysid__social__icons__import__text').off('click').on('click', function () {
            let $this = jQuery(this);
            let $inputFile = $this.closest('.acym__wysid__right__toolbar__design__social__icons__one').find('input');
            $inputFile.click();
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
                    if (res.type !== undefined) {
                        if (res.type === 'success') {
                            let img = jQuery('img').filter('[src^="' + res.url + '"]');
                            let finalUrl = res.url + '.' + res.extension;

                            jQuery.each(img, function () {
                                let d = new Date();
                                jQuery(this).removeAttr('src').attr('src', finalUrl + '?d=' + d.getTime());
                            });
                            acym_helperEditorWysid.socialMedia[whichIcon].src = finalUrl;
                        }

                        $input.val('').trigger('change');
                        acym_editorWysidNotifications.addEditorNotification({
                            'message': res.message,
                            'level': res.type
                        });
                    }
                }
            });
        });
    }
};
