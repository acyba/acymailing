let acym_helperEditorWysid = {};
jQuery(document).ready(function ($) {
    acym_helperEditorWysid = {
        initEditor: function () {
            if (jQuery('#acym__wysid').length < 1) return false;
            acym_helperEditorWysid.mailsSettings = acym_helperEditorWysid.saveSettings === '' ? {} : JSON.parse(acym_helperEditorWysid.saveSettings);
            jQuery('#editor_thumbnail').val('');
            jQuery('#acym__wysid__edit').append('<style id="acym__wysid__custom__style">' + acym_helperEditorWysid.parseTextToCss(acym_helperEditorWysid.savedStylesheet) + '</style>');
            acym_editorWysidVersioning.checkForUnsavedVersion();
            if (jQuery('#acym__walkthrough__email').length > 0) {
                jQuery('#acym__wysid').parent().insertAfter('#acym__walkthrough__email');
                jQuery('#acym__wysid__modal__dynamic-text').insertAfter('#acym__walkthrough__email');
            }

            acym_editorWysidDragDrop.setFixJquerySortableWYSID();
            acym_editorWysidDragDrop.setNewColumnElementDraggableWYSID();
            acym_editorWysidDragDrop.setNewRowElementDraggableWYSID();
            acym_editorWysidFormAction.setEditButtonWYSID();
            acym_editorWysidFormAction.setCancelButtonWYSID();
            acym_editorWysidFormAction.setSaveButtonWYSID();
            acym_editorWysidFormAction.setSaveAsTmplButtonWYSID();
            acym_editorWysidModal.setModalWindowWYSID();
            acym_editorWysidToolbar.setRightToolbarWYSID();

            acym_editorWysidModal.setDynamicsModal();
            acym_editorWysidFontStyle.setApplyStylesheetSettings();
            acym_editorWysidFontStyle.setOpenStylesheet();
            acym_editorWysidFontStyle.setCancelStylesheet();
            acym_editorWysidVersioning.setVersionControlCtrlZ();
            acym_helperEditorWysid.setAlertTimeoutSession();
            acym_editorWysidFontStyle.setSocialIconImport();
            acym_editorWysidTest.toggleSendTest();
            acym_editorWysidTest.sendTestAjax();
            acym_helperEditorWysid.preventSubmitEditor();
            jQuery('[id^="mce_"]').removeAttr('id');
        },
        setColumnRefreshUiWYSID: function (reloadTinyMCE = true) {
            acym_editorWysidRowSelector.setRowSelector();
            acym_editorWysidImage.setImageWidthHeightOnInsert();
            acym_editorWysidDragDrop.setRowElementSortableWYSID();
            acym_editorWysidImage.setDoubleClickImage();
            acym_editorWysidDragDrop.setColumnElementDraggableWYSID();
            acym_editorWysidDragDrop.setColumnSortableWYSID();
            acym_editorWysidColorPicker.setGeneralColorPickerWYSID();
            acym_editorWysidContextModal.setButtonContextModalWYSID();
            acym_editorWysidContextModal.setSpaceContextModalWYSID();
            acym_editorWysidContextModal.setFollowContextModalWYSID();
            acym_editorWysidContextModal.setSeparatorContextModalWYSID();
            acym_editorWysidColorPicker.setSettingsColorPickerWYSID(acym_editorWysidFontStyle.getSettingsOfHtmlElementWYSID(jQuery('#acym__wysid__right__toolbar__settings__font--select').val(), 'color'));
            acym_editorWysidFontStyle.setSettingsWYSID();
            acym_editorWysidFontStyle.setSettingsControlsWYSID();
            acym_editorWysidFontStyle.setAllHtmlElementStyleWYSID();
            acym_helperEditorWysid.checkForEmptyTbodyWYSID();
            acym_helperEditorWysid.setSizeEditorWYSID();
            acym_helperEditorWysid.setSelectOneTemplate();
            acym_editorWysidDynammic.setDynamicsActions();
            acym_editorWysidDynammic.setTagPWordBreak();
            acym_editorWysidDynammic.setTagPreInserted();
            acym_helperEditorWysid.setIntroForDynamics();
            acym_helperEditorWysid.removeBlankCharacters();
            if (reloadTinyMCE) acym_editorWysidTinymce.addTinyMceWYSID();
            acym_editorWysidTinymce.checkForEmptyText();

            jQuery('.columns, .acym__wysid__column').css('height', 'auto');
            jQuery('.acym__wysid__column__element__button').css('overflow', 'unset');

            //setShareContextModalWYSID();
        },
        checkForEmptyTbodyWYSID: function () {
            let $wysidRows = jQuery('.acym__wysid__row');
            if ($wysidRows.length === 1) {
                let $columns = $wysidRows.find('.acym__wysid__column');

                if ($columns.length === 1 && !$columns.find('tbody').children().length && !jQuery('#acym__wysid__default').length) {
                    $columns.addClass('acym__wysid__column__first');
                    let dropMessage = '<div id="acym__wysid__default">';
                    dropMessage += '<div id="acym__wysid__default__start" class="cell grid-x acym_vcenter">';
                    dropMessage += '<h1 class="cell">' + ACYM_JS_TXT.ACYM_TEMPLATE_EMPTY + '</h1>';
                    dropMessage += '<p class="cell">' + ACYM_JS_TXT.ACYM_DRAG_BLOCK_AND_DROP_HERE + '</p>';
                    dropMessage += '</div>';
                    dropMessage += '<div id="acym__wysid__default__dragging" style="display: none" class="cell grid-x acym_vcenter">';
                    dropMessage += '<h1 class="cell text-center">' + ACYM_JS_TXT.ACYM_WELL_DONE_DROP_HERE + '</h1>';
                    dropMessage += '</div>';
                    dropMessage += '</div>';
                    $columns.before(dropMessage);
                    let $startDefault = jQuery('#acym__wysid__default__start');
                    $startDefault.closest('#acym__wysid__default').attr('height', 'auto').closest('.columns').height($startDefault.height()).find('table').height($startDefault.height()).find('tbody').height($startDefault.height());
                }
            }

            $wysidRows.each(function () {
                if (jQuery(this).children().length == 0) {
                    jQuery('.acym__wysid__row').append('<div id="acym__wysid__row__temp_div"></div>');
                    acym_editorWysidNewRow.addRow1WYSID(jQuery('#acym__wysid__row__temp_div'));
                    acym_editorWysidRowSelector.setRowSelector();
                    acym_helperEditorWysid.checkForEmptyTbodyWYSID();
                } else {
                    jQuery(this)
                        .css({
                            'min-height': '0',
                            'display': 'table-cell',
                        });
                }
            });
            jQuery('.acym__wysid__column tbody').each(function () {
                jQuery(this).children().length == 0 ? jQuery(this)
                    .css({
                        'min-height': '75px',
                        'display': 'block',
                    })
                    .closest('table')
                    .css('min-height', '75px')
                    .css('display', 'block') : jQuery(this)
                    .css({
                        'min-height': '0px',
                        'display': 'table-row-group',
                    })
                    .closest('table')
                    .css('min-height', '0')
                    .css('display', 'table');
            });
        },
        removeBlankCharacters: function () {
            let $linkImages = jQuery('.acym__wysid__link__image');
            jQuery.each($linkImages, function () {
                jQuery(this).html(jQuery(this).html().replace(/\uFEFF/g, ''));
            });
        },
        setIntroForDynamics: function () {
            if (!ACYM_IS_ADMIN) return;
            jQuery('.acym__wysid__tinymce--text').on('click', function () {
                setTimeout(function () {
                    acym_helperIntroJS.introContent = [
                        {
                            element: '.mce-widget .mce-i-codesample',
                            text: ACYM_JS_TXT.ACYM_INTRO_ADD_DTEXT,
                            position: 'right',
                        },
                    ];
                    acym_helperIntroJS.setIntrojs('mail_editor_dtext');
                }, 200);
            });
        },
        setSizeEditorWYSID: function () {
            jQuery(window).off('resize').on('resize', function () {
                acym_editorWysidTinymce.addTinyMceWYSID();
                acym_helperEditorWysid.setSizeEditorWYSID();
            });

            let adminHeight = 0;

            if (CMS_ACYM == 'wordpress') {
                adminHeight = jQuery('#wpadminbar').innerHeight();
            } else {
                adminHeight = jQuery('nav.navbar').innerHeight() + jQuery('#status').innerHeight();
            }

            let wrapperPaddingTop = jQuery(window).width() > 639 ? 56 : 0; //TODO replace with wrapper padding + topbar margin etc
            let toolbarTop = jQuery('#acym__wysid__top-toolbar').innerHeight();
            jQuery('#acym__wysid').css('min-height', jQuery(window).height() - (wrapperPaddingTop + adminHeight));
            jQuery('#acym__wysid__template').css({
                'max-height': jQuery(window).height() - (wrapperPaddingTop + adminHeight + toolbarTop + 64),
                'min-height': jQuery(window).height() - (wrapperPaddingTop + adminHeight + toolbarTop + 64),
            });
            jQuery('.acym__wysid__right-toolbar__content').css('max-height', jQuery(window).height() - (wrapperPaddingTop + adminHeight + 48));
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
        parseTextToCss: function (text) {
            let css = '';
            jQuery.parsecss(text, function (e) {
                jQuery('#acym__wysid__custom__style').remove();
                let stylesheetToApply = e;
                for (let key in stylesheetToApply) {
                    let allCss = stylesheetToApply[key];
                    css += '#acym__wysid__template .body ' + key + '{';
                    for (let keyCss in allCss) {
                        css += keyCss + ':' + allCss[keyCss] + ';';
                    }
                    css += '} ';
                }
            });
            return css;
        },
        preventSubmitEditor: function () {
            jQuery('#acym_wrapper').keydown(function (event) {
                if (event.keyCode == 13 && event.target.nodeName != 'TEXTAREA') {
                    event.preventDefault();
                    return false;
                }
            });
        },
        setAlertTimeoutSession: function () {
            let $timeoutSessionInSeconds = jQuery('#acym__wysid__session--lifetime').val();
            let $timeoutToDisplayAlertInMilliseconds = ($timeoutSessionInSeconds - 60) * 1000;

            setTimeout(function () {
                acym_editorWysidNotifications.addEditorNotification({
                    'message': ACYM_JS_TXT.ACYM_SESSION_IS_GOING_TO_END,
                    'level': 'warning',
                });
            }, $timeoutToDisplayAlertInMilliseconds);
        },
        _selectedRows: [],
        $focusElement: '',
        saveSettings: jQuery('.acym__wysid__hidden__save__settings').val(),
        mailsSettings: {},
        insertDTextInSubject: true,
        stylesheetTemp: '',
        savedStylesheet: jQuery('.acym__wysid__hidden__save__stylesheet').val(),
        clicking: false,
        $resizingElement: '',
        versionControl: 0,
        timeClickImage: 0,
        dynamicPreviewIdentifier: 0,
        typingTimer: '',
        typingTimerGiphy: '',
        offsetGiphy: 0,
        queryGiphy: 'hello',
        defaultMailsSettings: {
            '#acym__wysid__background-colorpicker': {'background-color': '#efefef'},
            'p': {
                'font-family': 'Helvetica',
                'font-size': '12px',
                'font-weight': 'normal',
                'font-style': 'normal',
                'color': '#000000',
            },
            'a': {
                'font-family': 'Helvetica',
                'font-size': '12px',
                'font-weight': 'normal',
                'font-style': 'normal',
                'color': '#0000F1',
            },
            'li': {
                'font-family': 'Helvetica',
                'font-size': '12px',
                'font-weight': 'normal',
                'font-style': 'normal',
                'color': '#000000',
            },
            'h1': {
                'font-family': 'Helvetica',
                'font-size': '34px',
                'font-weight': 'normal',
                'font-style': 'normal',
                'color': '#000000',
            },
            'h2': {
                'font-family': 'Helvetica',
                'font-size': '30px',
                'font-weight': 'normal',
                'font-style': 'normal',
                'color': '#000000',
            },
            'h3': {
                'font-family': 'Helvetica',
                'font-size': '28px',
                'font-weight': 'normal',
                'font-style': 'normal',
                'color': '#000000',
            },
            'h4': {
                'font-family': 'Helvetica',
                'font-size': '24px',
                'font-weight': 'normal',
                'font-style': 'normal',
                'color': '#000000',
            },
            'h5': {
                'font-family': 'Helvetica',
                'font-size': '20px',
                'font-weight': 'normal',
                'font-style': 'normal',
                'color': '#000000',
            },
            'h6': {
                'font-family': 'Helvetica',
                'font-size': '18px',
                'font-weight': 'normal',
                'font-style': 'normal',
                'color': '#000000',
            },
        },
        socialMedia: {
            'facebook': {
                'src': MEDIA_URL_ACYM + '/images/logo/facebook.png',
                'link': '#',
                'text': 'Like',
            },
            'twitter': {
                'src': MEDIA_URL_ACYM + '/images/logo/twitter.png',
                'link': '#',
                'text': 'Tweet',
            },
            'pinterest': {
                'src': MEDIA_URL_ACYM + '/images/logo/pinterest.png',
                'link': '#',
                'text': 'pin it',
            },
            'linkedin': {
                'src': MEDIA_URL_ACYM + '/images/logo/linkedin.png',
                'link': '#',
                'text': 'share',
            },
            'instagram': {'src': MEDIA_URL_ACYM + '/images/logo/instagram.png'},
            'vimeo': {'src': MEDIA_URL_ACYM + '/images/logo/vimeo.png'},
            'wordpress': {'src': MEDIA_URL_ACYM + '/images/logo/wordpress.png'},
            'youtube': {'src': MEDIA_URL_ACYM + '/images/logo/youtube.png'},
        },
    };
});
