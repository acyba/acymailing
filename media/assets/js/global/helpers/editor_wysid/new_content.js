const acym_editorWysidNewContent = {
    addTitleWYSID: function () {
        let content = '<tr class="acym__wysid__column__element" style="position: relative; top: inherit; left: inherit; right: inherit; bottom: inherit; height: auto;">';
        content += '<td class="large-12 acym__wysid__column__element__td">';

        content += '<div class="acym__wysid__tinymce--text">';
        content += '<h1 class="acym__wysid__tinymce--title--placeholder">&zwj;</h1>';
        content += '</div>';

        content += '</td>';
        content += '</tr>';

        return content;
    },
    addTextWYSID: function () {
        let content = '<tr class="acym__wysid__column__element" style="position: relative; top: inherit; left: inherit; right: inherit; bottom: inherit; height: auto;">';
        content += '<td class="large-12 acym__wysid__column__element__td">';

        content += '<div class="acym__wysid__tinymce--text">';
        content += '<p class="acym__wysid__tinymce--text--placeholder">&zwj;</p>';
        content += '</div>';

        content += '</td>';
        content += '</tr>';

        return content;
    },
    addButtonWYSID: function () {
        let content = '<tr class="acym__wysid__column__element" style="position: relative; top: inherit; left: inherit; right: inherit; bottom: inherit; height: auto;">';
        content += '<td class="large-12 acym__wysid__column__element__td">';

        content += '<div style="text-align: center;box-sizing: inherit;">';
        content += '<a class="acym__wysid__column__element__button acym__wysid__content-no-settings-style" style="background-color: #222222; color: white; padding: 25px 30px; max-width: 100%; overflow: unset; border: 1px solid white; text-overflow: ellipsis; text-align: center; text-decoration: none; word-break: break-word;display: inline-block; box-shadow: none; font-family: Arial; font-size: 14px; cursor: pointer; line-height: 1; border-radius: 0" href="#" target="_blank">'
                   + ACYM_JS_TXT.ACYM_BUTTON
                   + '</a>';
        content += '</div>';

        content += '</td>';
        content += '</tr>';

        return content;
    },
    addSpaceWYSID: function () {
        let content = '<tr class="acym__wysid__column__element" style="position: relative; top: inherit; left: inherit; right: inherit; bottom: inherit; height: auto;">';
        content += '<td class="large-12 acym__wysid__column__element__td" style="height: 50px">';

        content += '<span class="acy-editor__space" style="display:block; padding: 0;margin: 0; height: 100%"></span>';

        content += '</td>';
        content += '</tr>';

        return content;
    },
    addMediaWysid: function (ui) {
        let content = '<tr class="acym__wysid__column__element" style="position: relative; top: inherit; left: inherit; right: inherit; bottom: inherit; height: auto;">';
        content += '<td class="large-12 acym__wysid__column__element__td">';
        content += '<div class="acym__wysid__tinymce--image acym__wysid__media__inserted--focus">';
        content += '<div style="text-align: center" data-mce-style="text-align: center">';
        content += '<img class="acym__wysid__media__inserted" src="'
                   + ACYM_MEDIA_URL
                   + 'images/default_image.png" title="image" hspace="0" alt="" style="max-width: 100%; height: auto;  box-sizing: border-box; padding: 0 5px;display:inline-block; margin-left: auto; margin-right: auto;"/>';
        content += '</div>';
        content += '</div>';
        content += '</td>';
        content += '</tr>';
        jQuery(ui).replaceWith(content);

        jQuery('.acym__wysid__media__inserted--focus img').off('load').on('load', function () {
            let $editor = jQuery(this).closest('.acym__wysid__media__inserted--focus');
            $editor.removeClass('acym__wysid__media__inserted--focus');
            acym_helperEditorWysid.setColumnRefreshUiWYSID();
            acym_editorWysidTinymce.addTinyMceWYSID();
            // This timeout is here to make sure tinyMCE is initialised on images
            setTimeout(() => {
                $editor.find('img').trigger('click');
                document.querySelector('.acym__wysid__media__inserted--selected').click();
                acym_editorWysidImage.setImageWidthHeightOnInsert();
                acym_editorWysidTinymce.addTinyMceWYSID();
            }, 500);
        });
    },
    addVideoWYSID: function (ui) {
        acym_helperEditorWysid.$focusElement = jQuery(ui);
        acym_editorWysidNewContent.setModalVideoWYSID();
        jQuery('#acym__wysid__modal').css('display', 'inherit');
    },
    addGiphyWYSID: function (ui) {
        acym_helperEditorWysid.$focusElement = jQuery(ui);
        acym_editorWysidNewContent.setModalGiphyWYSID();
        jQuery('#acym__wysid__modal').css('display', 'inherit');
    },
    addFollowWYSID: function () {
        let content = '<tr class="acym__wysid__column__element" style="position: relative; top: inherit; left: inherit; right: inherit; bottom: inherit; height: auto;">';
        content += '<td class="large-12 acym__wysid__column__element__td">';
        content += '<div style="text-align: center;">';
        content += '<p class="acym__wysid__column__element__follow" style="text-align: center; cursor: pointer; padding: 0;margin: 0;">';

        content += '<a class="acym__wysid__column__element__follow__facebook" href="">';
        content += '<img hspace="0" style="display: inline-block; max-width: 100%; height: auto;  box-sizing: border-box; width: 40px; padding: 3px;" src="'
                   + acym_helperEditorWysid.socialMedia.facebook.src
                   + '" width="40" alt="facebook">';
        content += '</a>';

        content += '<a class="acym__wysid__column__element__follow__twitter" href="">';
        content += '<img hspace="0" style="display: inline-block; max-width: 100%; height: auto;  box-sizing: border-box; width: 40px; padding: 3px;" src="'
                   + acym_helperEditorWysid.socialMedia.twitter.src
                   + '"  width="40" alt="twitter">';
        content += '</a>';

        content += '</p>';
        content += '</div>';
        content += '</td>';
        content += '</tr>';

        return content;
    },
    addSeparatorWysid: function () {
        let content = '<tr class="acym__wysid__column__element acym__wysid__column__element__separator cursor-pointer" style="position: relative; top: inherit; left: inherit; right: inherit; bottom: inherit; height: auto;">';
        content += '<td class="large-12 acym__wysid__column__element__td">';

        content += '<hr style="color: black; size: 3px; border-bottom: 3px solid black; width: 100%; border-top: none; border-left: none; border-right: none;" class="acym__wysid__row__separator">';

        content += '</td>';
        content += '</tr>';

        return content;
    },
    addCustomZoneWYSID: function () {
        acym_editorWysidNewContent.setModalCustomZoneWYSID();
        let modal = jQuery('#acym__wysid__modal');
        modal.addClass('acym__wysid__modal__tiny');
        modal.css('display', 'inherit');
    },
    setModalGiphyWYSID: function () {
        let content = '<div class="grid-container"><div class="cell grid-x align-center grid-padding-x">';
        content += '<img hspace="0" class="cell" id="acym__wysid__modal__giphy--image" src="' + ACYM_MEDIA_URL + 'images/giphy.png" alt="">';
        content += '<div class="cell grid-x grid-margin-x"><input class="cell auto" type="text" id="acym__wysid__modal__giphy--search" placeholder="'
                   + ACYM_JS_TXT.ACYM_SEARCH_FOR_GIFS
                   + '">';
        content += '<button type="button" class="cell shrink button button-secondary" id="acym__wysid__modal__giphy--search--button">'
                   + ACYM_JS_TXT.ACYM_SEARCH_GIFS
                   + '</button></div>';
        content += '<p class="cell text-center" id="acym__wysid__modal__giphy--low-res-message">' + ACYM_JS_TXT.ACYM_GIPHY_LOW_RES_TEXT + '</p>';
        content += '</div></div>';
        jQuery('#acym__wysid__modal__ui__fields').html(content);

        content = '<div class="grid-container acym__wysid__modal__giphy__results__container">';
        content += '<h3 class="cell text-center acym__title__primary__color" id="acym__wysid__modal__giphy--error_message" style="display: none"></h3>';
        content += '<div class="cell grid-x grid-padding-x grid-margin-x grid-margin-y margin-top-1" id="acym__wysid__modal__giphy--results"></div></div>';
        jQuery('#acym__wysid__modal__ui__display').html(content);

        content = '<div class="grid-container"><div class="cell grid-x align-right grid-padding-x">';
        content += '<button class="button" type="button" id="acym__wysid__modal__giphy--insert" disabled="disabled">' + ACYM_JS_TXT.ACYM_INSERT + '</button>';
        content += '</div></div>';
        jQuery('#acym__wysid__modal__ui__search').html(content);

        acym_editorWysidGiphy.makeNewResearch('');
        acym_editorWysidGiphy.insertGif();
    },
    setModalVideoWYSID: function () {
        let content = '<div class="grid-container">';
        content += '<div class="grid-x grid-padding-x grid-padding-y">';

        content += '<div class="auto cell"></div>';

        content += '<div class="small-3 medium-3 cell" style="display: inline-flex">';
        content += '<img alt="" style="display: block; margin: auto; max-height: 50px" src="' + ACYM_MEDIA_URL + 'images/vimeo.png">';
        content += '</div>';

        content += '<div class="small-3 medium-3 cell" style="display: inline-flex">';
        content += '<img alt="" style="display: block; margin: auto; max-height: 50px" src="' + ACYM_MEDIA_URL + 'images/youtube.png">';
        content += '</div>';

        content += '<div class="small-4 medium-3 cell" style="display: inline-flex; padding: 15px 0 0 30px;">';
        content += '<img alt="" style="display: block; margin: auto; max-height: 50px" src="' + ACYM_MEDIA_URL + 'images/dailymotion.png">';
        content += '</div>';

        content += '<div class="auto cell"></div>';
        content += '<div class="small-8 medium-10 cell">';
        content += '<input id="acym__wysid__modal__video__search" type="text" placeholder="Url">';
        content += '</div>';

        content += '<div class="small-4 medium-2 cell">';
        content += '<button type="button" id="acym__wysid__modal__video__load" class="button primary expanded ">' + ACYM_JS_TXT.ACYM_LOAD + '</button>';
        content += '</div>';

        content += '</div>';
        content += '</div>';
        jQuery('#acym__wysid__modal__ui__fields').html(content);


        content = '<div class="grid-container">';

        content += '<div class="grid-x grid-padding-x">';
        content += '<div id="acym__wysid__modal__video__result" class="medium-12 cell"></div>';
        content += '</div>';

        content += '</div>';
        jQuery('#acym__wysid__modal__ui__display').html(content);


        content = '<div class="grid-container">';
        content += '<div class="grid-x grid-padding-x">';

        content += '<div class="small-8 medium-10 cell"></div>';

        content += '<div class="small-4 medium-2 cell">';
        content += '<button type="button" id="acym__wysid__modal__video__insert" class="button primary expanded disabled">'
                   + ACYM_JS_TXT.ACYM_INSERT
                   + '</button>';
        content += '</div>';

        content += '</div>';
        content += '</div>';
        jQuery('#acym__wysid__modal__ui__search').html(content);

        let $loadBtn = jQuery('#acym__wysid__modal__video__load');
        let $insertBtn = jQuery('#acym__wysid__modal__video__insert');
        let $searchInput = jQuery('#acym__wysid__modal__video__search');
        let $result = jQuery('#acym__wysid__modal__video__result');

        $loadBtn.off('click').on('click', function () {
            let url = $searchInput.val();

            $insertBtn.off('click').on('click', function () {
                let insertedVideo = '<tr class="acym__wysid__column__element">'
                                  + '<td class="large-12 acym__wysid__column__element__td">'
                                  + '<div class="acym__wysid__tinymce--image">'
                                  + '<div style="text-align: center" data-mce-style="text-align: center">'
                                  + $result.html()
                                  + '</div>'
                                  + '</div>'
                                  + '</td>'
                                  + '</tr>';
                acym_helperEditorWysid.$focusElement.replaceWith(insertedVideo);
                jQuery('#acym__wysid__modal').css('display', 'none');
                acym_helperEditorWysid.setColumnRefreshUiWYSID();
                acym_editorWysidRowSelector.setZoneAndBlockOverlays();
                acym_editorWysidTinymce.addTinyMceWYSID();
            });

            let youtubeId = url.match(/(?:https?:\/{2})?(?:w{3}\.)?youtu(?:be)?\.(?:com|be)(?:\/watch\?v=|\/)([^\s&]+)/);
            let dailymotionId = url.match(/^(?:(?:http|https):\/\/)?(?:www.)?(dailymotion\.com|dai\.ly)\/((video\/([^_]+))|(hub\/([^_]+)|([^\/_]+)))$/);
            let vimeoId = url.match(/^.*(vimeo\.com\/)((channels\/[A-z]+\/)|(groups\/[A-z]+\/videos\/))?([0-9]+)/);

            if (youtubeId != null) {
                $result.html('<a href="https://www.youtube.com/watch?v='
                             + youtubeId[1]
                             + '" target="_blank" class="acym__wysid__link__image"><img alt="" src="https://img.youtube.com/vi/'
                             + youtubeId[1]
                             + '/0.jpg" style="max-width: 100%; height: auto; box-sizing: border-box; padding: 0 5px; display: block;margin-left: auto; margin-right: auto; float: none;"/></a>');
                $insertBtn.removeClass('disabled');
            } else if (dailymotionId != null) {
                if (dailymotionId[4] !== undefined) {
                    $result.html('<a href="https://www.dailymotion.com/video/'
                                 + dailymotionId[4]
                                 + '" target="_blank" class="acym__wysid__link__image"><img alt="" src="https://www.dailymotion.com/thumbnail/video/'
                                 + dailymotionId[4]
                                 + '" style="max-width: 100%; height: auto; box-sizing: border-box; padding: 0 5px; display: block; margin-left: auto; margin-right: auto; float: none;"/></a>');
                } else {
                    $result.html('<a href="https://www.dailymotion.com/video/'
                                 + dailymotionId[2]
                                 + '" target="_blank" class="acym__wysid__link__image"><img alt="" src="https://www.dailymotion.com/thumbnail/video/'
                                 + dailymotionId[2]
                                 + '" style="max-width: 100%; height: auto; box-sizing: border-box; padding: 0 5px; display: block; margin-left: auto; margin-right: auto; float: none;"/></a>');
                }
                $insertBtn.removeClass('disabled');
            } else if (vimeoId != null) {
                let script = document.createElement('script');
                script.id = 'vimeothumb';
                script.type = 'text/javascript';
                script.src = 'https://vimeo.com/api/v2/video/' + vimeoId[5] + '.json?callback=showVimeoThumbnail';
                jQuery('#acym__wysid__modal__video__search').before(script);
                jQuery('#vimeothumb').remove();
            } else {
                $result.html('<div class="acym__wysid__error_msg" style="text-align: center; margin-top: 100px;">' + ACYM_JS_TXT.ACYM_NON_VALID_URL + '</div>');
                $insertBtn.addClass('disabled').off('click');
            }
            $result.off('click').on('click', function (e) {
                e.preventDefault();
            });
        });

        $searchInput.off('keyup').on('keyup', function (e) {
            if (e.key === 'Enter') e.preventDefault();
            if (e.key === 'Enter' || e.key === ',' || e.key === ';') {
                $loadBtn.trigger('click');
            }
        });
    },
    setModalCustomZoneWYSID: function () {
        let content = '<div class="grid-x text-center margin-y">';
        content += '<h5 class="cell">' + ACYM_JS_TXT.ACYM_NEW_CUSTOM_ZONE + '</h5>';
        content += '<div class="cell">' + ACYM_JS_TXT.ACYM_ZONE_SAVE_TEXT + '</div>';
        content += '<div class="cell"><input id="custom_zone_name" type="text" placeholder="' + ACYM_JS_TXT.ACYM_ZONE_NAME + '" value="" /></div>';
        content += '<div class="cell">';
        content += '<button class="button" type="button" id="custom_zone_cancel">' + ACYM_JS_TXT.ACYM_CANCEL + '</button>';
        content += '<button class="button margin-left-1" id="custom_zone_save" type="button" disabled="disabled">' + ACYM_JS_TXT.ACYM_SAVE;
        content += '<i id="custom_zone_save_spinner" class="acymicon-circle-o-notch acymicon-spin"></i>' + '</button>';
        content += '</div>';
        content += '</div>';

        jQuery('#acym__wysid__modal__ui__display').html(content);
        jQuery('#acym__wysid__modal__ui__fields').html('');
        jQuery('#acym__wysid__modal__ui__search').html('');

        jQuery('#custom_zone_name').on('keyup', function () {
            let $saveButton = jQuery('#custom_zone_save');
            if (jQuery(this).val().length === 0) {
                $saveButton.attr('disabled', 'true');
            } else {
                $saveButton.removeAttr('disabled');
            }
        });

        jQuery('#custom_zone_cancel').on('click', function () {
            let modal = jQuery('.acym__wysid__modal');
            modal.hide();
            modal.removeClass('acym__wysid__modal__tiny');
        });

        jQuery('#custom_zone_save').on('click', function () {
            let spinner = jQuery('#custom_zone_save_spinner');
            spinner.css('display', 'inline-block');

            const data = {
                ctrl: 'zones',
                task: 'save',
                name: jQuery('#custom_zone_name').val(),
                content: acym_helperEditorWysid.$focusElement.prop('outerHTML')
            };

            acym_helper.post(ACYM_AJAX_URL, data).then(response => {
                if (response.error) {
                    acym_editorWysidNotifications.addEditorNotification({
                        'message': '<div class="cell auto acym__autosave__notification">' + response.message + '</div>',
                        'level': 'error'
                    }, 3000, true);
                } else {
                    let newZone = '<div class="grid-x cell acym__wysid__zone__element--new ui-draggable ui-draggable-handle" data-acym-zone-id="'
                                  + response.data.id
                                  + '">';
                    newZone += '<i class="acymicon-delete"></i>';
                    newZone += '<i class="cell acymicon-dashboard"></i>';
                    newZone += '<div class="cell">' + data.name + '</div>';
                    newZone += '</div>';

                    jQuery('#custom_zones_none_message').hide();
                    jQuery('.acym__wysid__right__toolbar__saved_zones').append(newZone);
                    acym_editorWysidDragDrop.setNewZoneDraggable();
                }

                spinner.css('display', 'none');
                jQuery('#custom_zone_cancel').trigger('click');
            });
        });
    }
};

function showVimeoThumbnail(data) {
    let thumbnail = 'https://i.vimeocdn.com/filter/overlay?src=' + encodeURIComponent(data[0].thumbnail_large);
    thumbnail += '&src=' + encodeURIComponent('https://f.vimeocdn.com/p/images/crawler_play.png');

    let styling = 'max-width: 100%; height: auto; box-sizing: border-box; padding: 0 5px; display: block; margin-left: auto; margin-right: auto; float: none;';

    let thumbWithLink = '<a href="https://vimeo.com/' + data[0].id + '" target="_blank" class="acym__wysid__link__image">';
    thumbWithLink += '<img alt="" src="' + thumbnail + '" style="' + styling + '"/>';
    thumbWithLink += '</a>';

    jQuery('#acym__wysid__modal__video__result').html(thumbWithLink);
    jQuery('#acym__wysid__modal__video__insert').removeClass('disabled');
}
