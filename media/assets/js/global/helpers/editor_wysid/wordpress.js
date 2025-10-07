const acym_editorWysidWordpress = {
    addMediaWPWYSID: function (ui, rows) {
        const htmlUi = jQuery(ui).html();
        rows = rows === undefined ? false : rows;

        acym_heleperWordPressGlobal.openMediaManager(function (attachment) {
            let insertImg = true;
            if (attachment.url.indexOf('..') >= 0) {
                insertImg = confirm(ACYM_JS_TXT.ACYM_INSERT_IMG_BAD_NAME);
            }
            if (insertImg) {
                if (rows) {
                    const padding = jQuery(ui).css('padding-top');
                    if (!jQuery(ui).hasClass('acym__wysid__template__content')) {
                        jQuery(ui).css('background-color', '').attr('bgcolor', '');
                        jQuery(ui)
                            .attr(
                                'style',
                                'background-image: url(\''
                                + attachment.url
                                + '\'); background-size: cover; background-position: center; background-repeat: no-repeat; padding: '
                                + padding
                                + ' 0'
                            )
                            .attr('width', jQuery(ui).width());
                    } else {
                        jQuery(ui)
                            .attr(
                                'style',
                                'background-image: url(\''
                                + attachment.url
                                + '\'); background-size: cover; background-position: center top; background-repeat: no-repeat; padding: '
                                + padding
                                + ' 0 40px 0'
                            );
                        if (jQuery(ui).css('background-image') !== 'none') jQuery('#acym__wysid__background-image__template-delete').css('display', 'flex');
                    }
                    acym_editorWysidNotifications.addEditorNotification({
                        'message': ACYM_JS_TXT.ACYM_BECARFUL_BACKGROUND_IMG,
                        'level': 'warning'
                    });
                } else {
                    const $link = jQuery(ui).find('.acym__wysid__link__image');
                    let content = '<tr class="acym__wysid__column__element" style="position: relative; top: inherit; left: inherit; right: inherit; bottom: inherit; height: auto;">';
                    content += '<td class="large-12 acym__wysid__column__element__td">';
                    content += '<div class="acym__wysid__tinymce--image">';
                    content += '<div style="text-align: center" data-mce-style="text-align: center">';
                    if ($link.length > 0) {
                        content += '<a href="' + $link.attr('href') + '" class="acym__wysid__link__image" target="_blank">';
                    }
                    content += '<img hspace="0" class="acym__wysid__media__inserted acym__wysid__media__inserted--focus acym__wysid__media__inserted--selected" src="'
                               + attachment.url
                               + '" title="'
                               + acym_helper.escape(attachment.title)
                               + '" alt="'
                               + acym_helper.escape(attachment.alt)
                               + '" style="max-width: 100%; height: auto;  box-sizing: border-box; padding: 0 5px;display:inline-block; margin-left: auto; margin-right: auto; vertical-align: middle;"/>';
                    if (attachment.caption !== undefined && attachment.caption.length > 0) {
                        content += acym_editorWysidContextModal.getImageCaptionDiv(attachment.caption);
                    }
                    if ($link.length > 0) {
                        content += '</a>';
                    }
                    content += '</div>';
                    content += '</div>';
                    content += '</td>';
                    content += '</tr>';
                    jQuery(ui).replaceWith(content);

                    jQuery('.acym__wysid__media__inserted--focus').on('load', function () {
                        jQuery(this).removeClass('acym__wysid__media__inserted--focus');
                        acym_helperEditorWysid.setColumnRefreshUiWYSID();
                        acym_editorWysidImage.setImageWidthHeightOnInsert();
                        acym_editorWysidTinymce.addTinyMceWYSID();
                    });

                    jQuery('#acym__upload__context__image__alt').val(attachment.alt);
                    jQuery('#acym__upload_context__image__title').val(attachment.title);
                    jQuery('#acym__upload__context__image__caption').val(attachment.caption);
                }
            } else {
                if (!rows) {
                    jQuery(ui).replaceWith('');
                    acym_helperEditorWysid.setColumnRefreshUiWYSID();
                    acym_editorWysidImage.setImageWidthHeightOnInsert();
                    acym_editorWysidTinymce.addTinyMceWYSID();
                }
            }
        }, function () {
            if (!rows) {
                if (htmlUi.indexOf('insert_photo') !== -1) {
                    jQuery(ui).replaceWith('');
                }
                acym_helperEditorWysid.setColumnRefreshUiWYSID();
                acym_editorWysidImage.setImageWidthHeightOnInsert();
                acym_editorWysidTinymce.addTinyMceWYSID();
            }
        });
    }
};
