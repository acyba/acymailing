const acym_helperEditorWysid = {
    initEditor: function () {
        // This function is called only once, when the page is loaded
        let $editor = jQuery('#acym__wysid');
        if ($editor.length < 1) return false;

        acym_helperEditorWysid.mailsSettings = acym_helper.parseJson(acym_helperEditorWysid.saveSettings);
        jQuery('#editor_thumbnail').val('');
        jQuery('#acym__wysid__edit')
            .append('<style id="acym__wysid__custom__style">' + acym_helperEditorWysid.parseTextToCss(acym_helperEditorWysid.savedStylesheet) + '</style>');

        if (jQuery('#acym__walkthrough__email').length > 0) {
            $editor.parent().insertAfter('#acym__walkthrough__email');
        }

        acym_editorWysidVersioning.checkForUnsavedVersion();
        acym_editorWysidVersioning.setVersionControlCtrlZ();

        acym_editorWysidImage.setChangeBuiltWithImage();

        acym_editorWysidDragDrop.setFixJquerySortableWYSID();
        acym_editorWysidDragDrop.setNewZoneDraggable();
        acym_editorWysidDragDrop.setNewBlockDraggable();
        acym_editorWysidDragDrop.setZonesSortable();
        acym_editorWysidDragDrop.setBlocksDraggable();

        acym_editorWysidFormAction.setOpenEditorButton();
        acym_editorWysidFormAction.setCancelButtonWYSID();
        acym_editorWysidFormAction.setSaveButtonWYSID();
        acym_editorWysidFormAction.setSaveAsTmplButtonWYSID();

        acym_editorWysidModal.setModalWindowWYSID();
        acym_editorWysidModal.setDTextInsertion();

        acym_editorWysidToolbar.setRightToolbarWYSID();

        acym_editorWysidFontStyle.setOpenStylesheet();
        acym_editorWysidFontStyle.setCancelStylesheet();
        acym_editorWysidFontStyle.setApplyStylesheetSettings();
        acym_editorWysidFontStyle.setSocialIconImport();

        acym_helperEditorWysid.setAlertTimeoutSession();
        acym_helperEditorWysid.preventSubmitEditor();
        acym_helperEditorWysid.setSelectOneTemplate();
        acym_helperEditorWysid.setSizeEditorWYSID();

        acym_editorWysidTest.toggleSendTest();
        acym_editorWysidTest.sendTestAjax();

        jQuery('[id^="mce_"]').removeAttr('id');

        acym_editorWysidMultilingual.setLanguageSelection();
        acym_editorWysidDynamic.setDTexts();
    },
    setColumnRefreshUiWYSID: function (autoSave = true, initEdit = false) {
        jQuery('.ui-helper-hidden-accessible').remove();
        acym_editorWysidDragDrop.setBlocksSortable();
        acym_helperEditorWysid.resizeBlockContainers();
        acym_editorWysidDynamic.setTagPWordBreak();
        acym_editorWysidDynamic.setTagPreInserted();
        acym_helperEditorWysid.removeBlankCharacters();
        acym_editorWysidTinymce.checkForEmptyText();

        // This was breaking the zone resizing, I kept it because I don't know why Rémi added it the 28/01/20
        // It was apparently related to the button block's padding bottom
        //jQuery('.columns, .acym__wysid__column').css('height', 'auto');
        jQuery('.acym__wysid__column__element__button').css('overflow', 'unset');

        if (autoSave) {
            acym_editorWysidVersioning.setUndoAndAutoSave(initEdit);
        }
    },
    resizeBlockContainers: function () {
        jQuery('.acym__wysid__column tbody').each(function () {
            if (jQuery(this).children().length === 0) {
                jQuery(this)
                    .css({
                        'min-height': '75px',
                        'display': 'block'
                    })
                    .closest('table')
                    .css('min-height', '75px')
                    .css('display', 'block');
            } else {
                jQuery(this)
                    .css({
                        'min-height': '0px',
                        'display': 'table-row-group'
                    })
                    .closest('table')
                    .css('min-height', '0')
                    .css('display', 'table');
            }
        });
    },
    addDefaultZone: function () {
        let $wysidRows = jQuery('.acym__wysid__row');

        // For each zone in the editor, if it is empty we add an empty container where we can drop blocks
        let defaultRowAdded = false;
        $wysidRows.each(function () {
            if (jQuery(this).children().length === 0) {
                jQuery('.acym__wysid__row').append('<div id="acym__wysid__row__temp_div"></div>');
                acym_editorWysidNewRow.addRow1WYSID(jQuery('#acym__wysid__row__temp_div'));
                defaultRowAdded = true;
            } else if (jQuery(this).children().length === 1 && jQuery(this).children().first().attr('id') === 'acym__powered_by_acymailing') {
                jQuery('.acym__wysid__row').prepend('<div id="acym__wysid__row__temp_div"></div>');
                acym_editorWysidNewRow.addRow1WYSID(jQuery('#acym__wysid__row__temp_div'));
                defaultRowAdded = true;
            } else {
                jQuery(this)
                    .css({
                        'min-height': '0',
                        'display': 'table-cell'
                    });
            }
        });
        if (defaultRowAdded) {
            acym_helperEditorWysid.addDefaultBlock();
            acym_helperEditorWysid.setColumnRefreshUiWYSID();
        }
    },
    addDefaultBlock: function () {
        let $wysidRows = jQuery('.acym__wysid__row');

        // Should always be 1, this is the td containing the zones
        if ($wysidRows.length !== 1) return;

        let $columns = $wysidRows.find('.acym__wysid__column');

        // If the container contains only one empty block, replace it with the default "drop here" text
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
            $startDefault.closest('#acym__wysid__default')
                         .attr('height', 'auto')
                         .closest('.columns')
                         .height($startDefault.height())
                         .find('table')
                         .height($startDefault.height())
                         .find('tbody')
                         .height($startDefault.height());

            acym_helperEditorWysid.resizeBlockContainers();
        }
    },
    removeBlankCharacters: function () {
        let $linkImages = jQuery('.acym__wysid__link__image');
        jQuery.each($linkImages, function () {
            jQuery(this).html(jQuery(this).html().replace(/\uFEFF/g, ''));
        });
    },
    setSizeEditorWYSID: function () {
        jQuery(window).off('resize').on('resize', function () {
            // Make sure we call these functions at the end of the resize
            clearTimeout(window.acymResize);
            window.acymResize = setTimeout(function () {
                acym_editorWysidTinymce.addTinyMceWYSID();
                acym_helperEditorWysid.resizeEditorBasedOnPage();
            }, 200);
        });
    },
    resizeEditorBasedOnPage: function () {
        let adminHeight;

        if (ACYM_CMS === 'wordpress') {
            adminHeight = jQuery('#wpadminbar').innerHeight();
        } else {
            if (ACYM_J40) {
                adminHeight = 0;
            } else {
                adminHeight = jQuery('nav.navbar').innerHeight() + jQuery('#status').innerHeight();
            }
        }

        let wrapperPaddingTop = jQuery(window).width() > 639 ? 56 : 0; //TODO replace with wrapper padding + topbar margin etc
        let toolbarTop = jQuery('#acym__wysid__top-toolbar').innerHeight();
        jQuery('#acym__wysid').css('min-height', jQuery(window).height() - (wrapperPaddingTop + adminHeight));

        let emailContainerHeight = jQuery(window).height() - (wrapperPaddingTop + adminHeight + toolbarTop + 64);
        jQuery('#acym__wysid__template').css({
            'max-height': emailContainerHeight,
            'min-height': emailContainerHeight
        });
        jQuery('.acym__wysid__right-toolbar__content').css('max-height', jQuery(window).height() - (wrapperPaddingTop + adminHeight + 48));
    },
    setSelectOneTemplate: function () {
        jQuery('.acym__template__choose__ajax').off('DOMSubtreeModified').on('DOMSubtreeModified', function () {
            jQuery('.acym__template__choose__list .acym__templates__oneTpl').off('click').on('click', function (e) {
                e.preventDefault();
                let thisLink = jQuery(this).find('a').attr('href');
                let ajaxUrl = ACYM_AJAX_URL + '&page=acymailing_mails&ctrl=' + acym_helper.ctrlMails + '&task=getMailContent&from=' + jQuery(this)
                    .attr('id');

                jQuery.post(ajaxUrl, function (response) {
                    if (response === 'error') {
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
        const imports = [...text.matchAll(/@import[^;]*;/gis)];
        for (let imp in imports) {
            css += imports[imp];
            text = text.replace(imports[imp], '');
        }
        jQuery.parsecss(text, function (e) {
            jQuery('#acym__wysid__custom__style').remove();
            css += acym_helperEditorWysid.parsecssP(e);
            const medias = [...text.matchAll(/@media[^{(]*(\([^{]*\))[^{]*{([^{}]*({[^}]*}[^{}]*)*[^{}]*)}/gis)];
            for (let media in medias) {
                css += '@media' + medias[media][1] + '{';
                jQuery.parsecss(medias[media][2], function (ecss) {
                    css += acym_helperEditorWysid.parsecssP(ecss);
                });
                css += '}';
            }
        });
        return css;
    },
    parsecssP: function (e) {
        let css = '';
        let stylesheetToApply = e;
        for (let key in stylesheetToApply) {
            let allCss = stylesheetToApply[key];
            let prependClass = key.indexOf('#acym__wysid__template') !== -1 ? '' : '#acym__wysid__template .body ';
            css += prependClass + key + '{';
            for (let keyCss in allCss) {
                css += keyCss + ':' + allCss[keyCss] + ';';
            }
            css += '} ';
        }
        return css;
    },
    preventSubmitEditor: function () {
        jQuery('#acym_wrapper').on('keydown', function (event) {
            if (event.key === 'Enter' && event.target.nodeName !== 'TEXTAREA') {
                event.preventDefault();
                return false;
            }
        });
    },
    setAlertTimeoutSession: function () {
        let timeoutSessionInSeconds = jQuery('#acym__wysid__session--lifetime').val();
        let timeoutToDisplayAlertInMilliseconds = (timeoutSessionInSeconds - 60) * 1000;

        setTimeout(function () {
            acym_editorWysidNotifications.addEditorNotification({
                'message': ACYM_JS_TXT.ACYM_SESSION_IS_GOING_TO_END,
                'level': 'warning'
            });
        }, timeoutToDisplayAlertInMilliseconds);
    },
    _selectedRows: [],
    $focusElement: '',
    saveSettings: jQuery('.acym__wysid__hidden__save__settings').val(),
    mailsSettings: {},
    stylesheetTemp: '',
    savedStylesheet: jQuery('.acym__wysid__hidden__save__stylesheet').val(),
    savedColors: jQuery('.acym__wysid__hidden__save__colors').val(),
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
            'color': '#000000'
        },
        'a': {
            'font-family': 'Helvetica',
            'font-size': '12px',
            'font-weight': 'normal',
            'font-style': 'normal',
            'color': '#0000F1'
        },
        'span.acym_link': {
            'font-family': 'Helvetica',
            'font-size': '12px',
            'font-weight': 'normal',
            'font-style': 'normal',
            'color': '#0000F1'
        },
        'li': {
            'font-family': 'Helvetica',
            'font-size': '12px',
            'font-weight': 'normal',
            'font-style': 'normal',
            'color': '#000000'
        },
        'h1': {
            'font-family': 'Helvetica',
            'font-size': '34px',
            'font-weight': 'normal',
            'font-style': 'normal',
            'color': '#000000'
        },
        'h2': {
            'font-family': 'Helvetica',
            'font-size': '30px',
            'font-weight': 'normal',
            'font-style': 'normal',
            'color': '#000000'
        },
        'h3': {
            'font-family': 'Helvetica',
            'font-size': '28px',
            'font-weight': 'normal',
            'font-style': 'normal',
            'color': '#000000'
        },
        'h4': {
            'font-family': 'Helvetica',
            'font-size': '24px',
            'font-weight': 'normal',
            'font-style': 'normal',
            'color': '#000000'
        },
        'h5': {
            'font-family': 'Helvetica',
            'font-size': '20px',
            'font-weight': 'normal',
            'font-style': 'normal',
            'color': '#000000'
        },
        'h6': {
            'font-family': 'Helvetica',
            'font-size': '18px',
            'font-weight': 'normal',
            'font-style': 'normal',
            'color': '#000000'
        },
        'default': {
            'font-family': 'Helvetica'
        }
    },
    socialMedia: {
        'facebook': {
            'src': ACYM_MEDIA_URL + '/images/logo/facebook.png',
            'link': '#',
            'text': 'Like'
        },
        'twitter': {
            'src': ACYM_MEDIA_URL + '/images/logo/twitter.png',
            'link': '#',
            'text': 'Tweet'
        },
        'pinterest': {
            'src': ACYM_MEDIA_URL + '/images/logo/pinterest.png',
            'link': '#',
            'text': 'pin it'
        },
        'linkedin': {
            'src': ACYM_MEDIA_URL + '/images/logo/linkedin.png',
            'link': '#',
            'text': 'share'
        },
        'instagram': {'src': ACYM_MEDIA_URL + '/images/logo/instagram.png'},
        'vimeo': {'src': ACYM_MEDIA_URL + '/images/logo/vimeo.png'},
        'wordpress': {'src': ACYM_MEDIA_URL + '/images/logo/wordpress.png'},
        'youtube': {'src': ACYM_MEDIA_URL + '/images/logo/youtube.png'}
    }
};
