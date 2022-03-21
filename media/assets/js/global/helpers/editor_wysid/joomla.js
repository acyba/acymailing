const acym_editorWysidJoomla = {
    addMediaJoomlaWYSID: function (ui, rows) {
        rows = rows === undefined ? false : rows;
        acym_helperEditorWysid.$focusElement = jQuery(ui);
        let $modalUi = jQuery('#acym__wysid__modal__joomla-image__ui__iframe');
        let joomla4 = jQuery('#acym__wysid__modal__joomla-image__ui__actions').length > 0;
        $modalUi.css({
            'height': joomla4 ? '90%' : '100%',
            'width': '100%'
        });

        $modalUi.contents().find('.chzn-container-single').attr('style', '').css('width', '150px');
        acym_editorWysidJoomla.setInsertMediaJoomlaWYSID($modalUi, rows);
        $modalUi.on('load', function () {
            acym_editorWysidJoomla.setInsertMediaJoomlaWYSID($modalUi, rows);
        });
        jQuery('#acym__wysid__modal__joomla-image').css('display', 'inherit');
    },
    cancelMediaSelection: function (rows) {
        jQuery('#acym__wysid__modal__joomla-image').hide();
        if (!rows) {
            if (acym_helperEditorWysid.$focusElement.length && acym_helperEditorWysid.$focusElement.html().indexOf('insert_photo') !== -1) {
                acym_helperEditorWysid.$focusElement.replaceWith('');
            }
            acym_helperEditorWysid.setColumnRefreshUiWYSID();
            acym_editorWysidVersioning.setUndoAndAutoSave();
            acym_helperEditorWysid.checkForEmptyTbodyWYSID();
        }
    },
    validateMediaSelection: function (isRowBackgroundImage, imagesUrls, altValue, valueTitle, valueCaption) {
        // No image selected and no url provided
        if (acym_helper.empty(imagesUrls)) {
            alert(ACYM_JS_TXT.ACYM_SELECT_A_PICTURE);
            return;
        }

        // If we're selecting a background image for an editor row
        if (isRowBackgroundImage) {
            let linkImage = imagesUrls.pop();

            // If the name isn't correct, ask for confirmation
            if (linkImage.indexOf('..') >= 0 && !confirm(ACYM_JS_TXT.ACYM_INSERT_IMG_BAD_NAME)) return;

            let padding = acym_helperEditorWysid.$focusElement.css('padding-top');
            if (!acym_helperEditorWysid.$focusElement.hasClass('acym__wysid__template__content')) {
                acym_helperEditorWysid.$focusElement.css('background-color', '').attr('bgcolor', '');
                acym_helperEditorWysid.$focusElement.attr(
                    'style',
                    'background-image: url(\''
                    + linkImage
                    + '\'); background-size: cover; background-position: center; background-repeat: no-repeat; padding: '
                    + padding
                    + ' 0'
                ).attr('width', acym_helperEditorWysid.$focusElement.width());
            } else {
                acym_helperEditorWysid.$focusElement.attr(
                    'style',
                    'background-image: url(\''
                    + linkImage
                    + '\'); background-size: cover; background-position: center; background-repeat: no-repeat; padding: '
                    + padding
                    + ' 0 40px 0'
                );
                if (acym_helperEditorWysid.$focusElement.css('background-image') !== 'none') {
                    jQuery('#acym__wysid__background-image__template-delete').css('display', 'flex');
                }
            }
            acym_editorWysidNotifications.addEditorNotification({
                'message': ACYM_JS_TXT.ACYM_BECARFUL_BACKGROUND_IMG,
                'level': 'warning'
            });
        } else {
            // We're selecting an image to insert in the email

            let content = '';
            let $link = acym_helperEditorWysid.$focusElement.find('.acym__wysid__link__image');
            for (let i in imagesUrls) {
                if (!imagesUrls.hasOwnProperty(i)) continue;
                let linkImage = imagesUrls[i];

                // If the name isn't correct, ask for confirmation
                if (linkImage.indexOf('..') >= 0 && !confirm(ACYM_JS_TXT.ACYM_INSERT_IMG_BAD_NAME)) return;

                let alt = '';
                let title = '';
                if (altValue !== undefined) alt = altValue;
                if (valueTitle !== undefined) title = valueTitle;
                let classImage = parseInt(i) === 0 ? 'acym__wysid__media__inserted--selected' : '';

                content += '<tr class="acym__wysid__column__element" style="position: relative; top: inherit; left: inherit; right: inherit; bottom: inherit; height: auto;">';
                content += '<td class="large-12 acym__wysid__column__element__td">';
                content += '<div class="acym__wysid__tinymce--image">';
                content += '<div style="text-align: center; " data-mce-style="text-align: center">';
                if ($link.length > 0 && parseInt(i) === 0) content += '<a href="' + $link.attr('href') + '" class="acym__wysid__link__image" target="_blank">';
                content += '<img class="acym__wysid__media__inserted '
                           + classImage
                           + '" style="max-width: 100%; height: auto; box-sizing: border-box; padding: 0 5px; display:block; margin-left: auto; margin-right: auto;" src="'
                           + linkImage
                           + '" alt="'
                           + acym_helper.escape(alt)
                           + '" title="'
                           + acym_helper.escape(title)
                           + '" hspace="0"/>';
                if (valueCaption !== undefined && valueCaption.length > 0) {
                    content += acym_editorWysidContextModal.getImageCaptionDiv(valueCaption);
                }
                if ($link.length > 0) content += '</a>';
                content += '</div>';
                content += '</div>';
                content += '</td>';
                content += '</tr>';
            }
            acym_helperEditorWysid.$focusElement.replaceWith(content);
        }

        // Close the image selection modal
        jQuery('#acym__wysid__modal__joomla-image').hide();
        acym_helperEditorWysid.setColumnRefreshUiWYSID();
        acym_editorWysidVersioning.setUndoAndAutoSave();
    },
    setInsertMediaJoomlaWYSID: function ($modalUi, rows) {
        // Joomla 4
        jQuery('#acym__wysid__modal__joomla-image__ui__actions__cancel').off('click').on('click', function () {
            acym_editorWysidJoomla.cancelMediaSelection(rows);
        });
        jQuery('#acym__wysid__modal__joomla-image__ui__actions__select').off('click').on('click', function () {
            // 1 - Get current folder
            let imageUrl;
            let folderPath = ACYM_ROOT_URI;
            $modalUi.contents().find('.media-breadcrumb-item a').each(function () {
                folderPath += jQuery(this).text().trim() + '/';
            });


            // 2 - Get selected image(s)
            let imagesUrls = [];

            // When selecting images from the grid view
            $modalUi.contents().find('.media-browser-grid .media-browser-item.selected .media-browser-image .media-browser-item-info').each(function () {
                imagesUrls.push(folderPath + jQuery(this).text().trim());
            });

            // When selecting images from the list view instead of grid view
            if (imagesUrls.length === 0) {
                $modalUi.contents().find('.media-browser .media-browser-item.selected').each(function () {
                    if (!acym_helper.empty(jQuery(this).find('.size').text().trim())) {
                        imagesUrls.push(folderPath + jQuery(this).find('.name').text().trim());
                    }
                });
            }

            let altValue = jQuery('#acym__wysid__context__image__alt').val();
            let valueTitle = jQuery('#acym__wysid__context__image__title').val();
            let valueCaption = jQuery('#acym__wysid__context__image__caption').val();
            acym_editorWysidJoomla.validateMediaSelection(rows, imagesUrls, altValue, valueTitle, valueCaption);
        });

        // Joomla 3
        $modalUi.contents().find('.button-cancel').attr('onclick', '').off('click').on('click', function () {
            acym_editorWysidJoomla.cancelMediaSelection(rows);
        });
        $modalUi.contents().find('.pull-right .btn-success, .pull-right .btn-primary').attr('onclick', '').off('click').on('click', function () {
            let urlImg = $modalUi.contents().find('#f_url').val();
            let altValue = $modalUi.contents().find('#f_alt').val();
            let valueTitle = $modalUi.contents().find('#f_title').val();
            let valueCaption = $modalUi.contents().find('#f_caption').val();
            let imagesUrls = [];
            if (!acym_helper.empty(urlImg)) {
                if (urlImg.match('^' + ACYM_JOOMLA_MEDIA_FOLDER) || urlImg.match('^' + ACYM_JOOMLA_MEDIA_FOLDER_IMAGES)) {
                    urlImg = ACYM_JOOMLA_MEDIA_IMAGE + urlImg;
                }
                imagesUrls.push(urlImg);
            }
            acym_editorWysidJoomla.validateMediaSelection(rows, imagesUrls, altValue, valueTitle, valueCaption);
        });
    }
};
