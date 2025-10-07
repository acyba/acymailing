const acym_editorWysidJoomla = {
    addMediaJoomlaWYSID: function (ui, rows) {
        rows = rows === undefined ? false : rows;
        acym_helperEditorWysid.$focusElement = jQuery(ui);
        acym_helperJoomlaGlobal.openMediaManager(function (mediaObject) {
            acym_editorWysidJoomla.validateMediaSelection(rows, [mediaObject.url], mediaObject.alt, mediaObject.title, mediaObject.caption);
            acym_helperEditorWysid.setColumnRefreshUiWYSID();
        }, function () {
            acym_editorWysidJoomla.cancelMediaSelection(rows);
        });
    },
    cancelMediaSelection: function (rows) {
        if (!rows) {
            if (acym_helperEditorWysid.$focusElement.length && acym_helperEditorWysid.$focusElement.html().indexOf('insert_photo') !== -1) {
                acym_helperEditorWysid.$focusElement.replaceWith('');
            }
            acym_helperEditorWysid.setColumnRefreshUiWYSID();
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
            const $link = acym_helperEditorWysid.$focusElement.find('.acym__wysid__link__image');
            for (let i in imagesUrls) {
                if (!imagesUrls.hasOwnProperty(i)) continue;
                const linkImage = imagesUrls[i];

                // If the name isn't correct, ask for confirmation
                if (linkImage.indexOf('..') >= 0 && !confirm(ACYM_JS_TXT.ACYM_INSERT_IMG_BAD_NAME)) return;

                let alt = '';
                let title = '';
                let caption = '';
                if (altValue !== undefined) alt = altValue;
                if (valueTitle !== undefined) title = valueTitle;
                if (valueCaption !== undefined) caption = valueCaption;
                let classImage = parseInt(i) === 0 ? 'acym__wysid__media__inserted--selected' : '';

                content += '<tr class="acym__wysid__column__element" style="position: relative; top: inherit; left: inherit; right: inherit; bottom: inherit; height: auto;">';
                content += '<td class="large-12 acym__wysid__column__element__td">';
                content += '<div class="acym__wysid__tinymce--image">';
                content += '<div style="text-align: center; " data-mce-style="text-align: center">';
                if ($link.length > 0 && parseInt(i) === 0) content += '<a href="' + $link.attr('href') + '" class="acym__wysid__link__image" target="_blank">';
                content += '<img class="acym__wysid__media__inserted '
                           + classImage
                           + '" style="max-width: 100%; height: auto; box-sizing: border-box; padding: 0 5px; display:block; margin-left: auto; margin-right: auto; vertical-align: middle;" src="'
                           + linkImage
                           + '" alt="'
                           + acym_helper.escape(alt)
                           + '" title="'
                           + acym_helper.escape(title)
                           + '" hspace="0"/>';
                if (caption.length > 0) {
                    content += acym_editorWysidContextModal.getImageCaptionDiv(caption);
                }
                if ($link.length > 0) content += '</a>';
                content += '</div>';
                content += '</div>';
                content += '</td>';
                content += '</tr>';

                jQuery('#acym__upload__context__image__alt').val(alt);
                jQuery('#acym__upload_context__image__title').val(title);
                jQuery('#acym__upload__context__image__caption').val(caption);
            }
            acym_helperEditorWysid.$focusElement.replaceWith(content);

            const imgSelected = jQuery('.acym__wysid__media__inserted--selected');
            jQuery('#acym__wysid__context__image__width').val(imgSelected.width());
            jQuery('#acym__wysid__context__image__height').val(imgSelected.height());

            acym_editorWysidImage.setImageWidthHeightOnInsert();
            acym_editorWysidTinymce.addTinyMceWYSID();
        }
    }
};
