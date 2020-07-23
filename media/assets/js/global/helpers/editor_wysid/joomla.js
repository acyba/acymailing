const acym_editorWysidJoomla = {
    addMediaJoomlaWYSID: function (ui, rows) {
        rows = rows === undefined ? false : rows;
        acym_helperEditorWysid.$focusElement = jQuery(ui);
        let $modalUi = jQuery('#acym__wysid__modal__joomla-image__ui__iframe');
        $modalUi.css('height', '100%').css('width', '100%');
        $modalUi.contents().find('.chzn-container-single').attr('style', '').css('width', '150px');
        acym_editorWysidJoomla.setInsertMediaJoomlaWYSID($modalUi, rows);
        $modalUi.on('load', function () {
            acym_editorWysidJoomla.setInsertMediaJoomlaWYSID($modalUi, rows);
        });
        jQuery('#acym__wysid__modal__joomla-image').css('display', 'inherit');
    },
    setInsertMediaJoomlaWYSID: function ($modalUi, rows) {
        $modalUi.contents().find('.button-cancel').attr('onclick', '').off('click').on('click', function () {
            jQuery('#acym__wysid__modal__joomla-image').hide();
            if (!rows) {
                if (acym_helperEditorWysid.$focusElement.length && acym_helperEditorWysid.$focusElement.html().indexOf('insert_photo') !== -1) acym_helperEditorWysid.$focusElement.replaceWith('');
                acym_helperEditorWysid.setColumnRefreshUiWYSID();
                acym_editorWysidVersioning.setUndoAndAutoSave();
                acym_helperEditorWysid.checkForEmptyTbodyWYSID();
            }
        });
        $modalUi.contents().find('.pull-right .btn-success, .pull-right .btn-primary').attr('onclick', '').off('click').on('click', function () {
            let inputUrlImg = $modalUi.contents().find('#f_url').val();
            let $inputAlt = $modalUi.contents().find('#f_alt');
            let $inputTitle = $modalUi.contents().find('#f_title');
            let $linkImage = $modalUi.contents().find('#imageframe').contents().find('.img-preview');
            let imageSelected = false;
            let insertImg = true;
            $linkImage.each(function () {
                if (jQuery(this).hasClass('selected')) {
                    if (jQuery(this).find('img').attr('src').indexOf('..') >= 0) {
                        insertImg = confirm(ACYM_JS_TXT.ACYM_INSERT_IMG_BAD_NAME);
                    }
                    if (insertImg) {
                        if (rows) {
                            let padding = acym_helperEditorWysid.$focusElement.css('padding-top');
                            if (!acym_helperEditorWysid.$focusElement.hasClass('acym__wysid__template__content')) {
                                acym_helperEditorWysid.$focusElement.css('background-color', '').attr('bgcolor', '');
                                acym_helperEditorWysid.$focusElement.attr('style', 'background-image: url(\'' + jQuery(this).find('img').attr('src') + '\'); background-size: cover; background-position: center; background-repeat: no-repeat; padding: ' + padding + ' 0').attr('width', acym_helperEditorWysid.$focusElement.width());
                            } else {
                                acym_helperEditorWysid.$focusElement.attr('style', 'background-image: url(\'' + jQuery(this).find('img').attr('src') + '\'); background-size: cover; background-position: center; background-repeat: no-repeat; padding: ' + padding + ' 0 40px 0');
                                if (acym_helperEditorWysid.$focusElement.css('background-image') !== 'none') jQuery('#acym__wysid__background-image__template-delete').css('display', 'flex');
                            }
                            acym_editorWysidNotifications.addEditorNotification({
                                'message': ACYM_JS_TXT.ACYM_BECARFUL_BACKGROUND_IMG,
                                'level': 'warning',
                            });
                        } else {
                            let alt = '';
                            let title = '';
                            if ($inputAlt.val() !== undefined) alt = $inputAlt.val();
                            if ($inputTitle.val() !== undefined) title = $inputTitle.val();
                            let content = '<tr class="acym__wysid__column__element" style="position: relative; top: inherit; left: inherit; right: inherit; bottom: inherit; height: auto;">';
                            content += '<td class="large-12 acym__wysid__column__element__td">';
                            content += '<div class="acym__wysid__tinymce--image">';
                            content += '<img class="acym__wysid__media__inserted" style="max-width: 100%; height: auto;  box-sizing: border-box; padding: 0 5px;display:block; margin-left: auto; margin-right: auto;" src="' + jQuery(this).find('img').attr('src') + '" alt="' + acym_helper.escape(alt) + '" title="' + acym_helper.escape(title) + '"/>';
                            content += '</div>';
                            content += '</td>';
                            content += '</tr>';
                            acym_helperEditorWysid.$focusElement.replaceWith(content);
                        }
                        jQuery('#acym__wysid__modal__joomla-image').css('display', 'none');
                        acym_helperEditorWysid.setColumnRefreshUiWYSID();
                        acym_editorWysidVersioning.setUndoAndAutoSave();
                        imageSelected = true;
                    }
                }
            });
            if (!imageSelected && inputUrlImg !== '') {
                inputUrlImg = inputUrlImg.match('^images/') ? ACYM_JOOMLA_MEDIA_IMAGE + inputUrlImg : inputUrlImg;
                let content = '<tr class="acym__wysid__column__element" style="position: relative; top: inherit; left: inherit; right: inherit; bottom: inherit; height: auto;">';
                content += '<td class="large-12 acym__wysid__column__element__td">';
                content += '<div class="acym__wysid__tinymce--image">';
                content += '<img class="acym__wysid__media__inserted" style="max-width: 100%; height: auto;  box-sizing: border-box; padding: 0 5px;display:block; margin-left: auto; margin-right: auto;" src="' + inputUrlImg + '" alt="' + jQuery(this).attr('title') + '"/>';
                content += '</div>';
                content += '</td>';
                content += '</tr>';
                acym_helperEditorWysid.$focusElement.replaceWith(content);
                jQuery('#acym__wysid__modal__joomla-image').css('display', 'none');
                acym_helperEditorWysid.setColumnRefreshUiWYSID();
                acym_editorWysidVersioning.setUndoAndAutoSave();
                imageSelected = true;
                return;
            }
            imageSelected == false && insertImg == true ? alert('Please select a picture') : true;
            imageSelected = false;

        });
    },
};
