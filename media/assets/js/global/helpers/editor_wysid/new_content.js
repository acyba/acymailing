const acym_editorWysidNewContent = {
    addTitleWYSID: function (ui) {
        let content = '<tr class="acym__wysid__column__element" style="position: relative; top: inherit; left: inherit; right: inherit; bottom: inherit; height: auto;">';
        content += '<td class="large-12 acym__wysid__column__element__td">';

        content += '<div class="acym__wysid__tinymce--text">';
        content += '<h1 class="acym__wysid__tinymce--title--placeholder">&zwj;</h1>';
        content += '</div>';

        content += '</td>';
        content += '</tr>';
        jQuery(ui).replaceWith(content);
        acym_helperEditorWysid.setColumnRefreshUiWYSID();
        acym_editorWysidVersioning.setUndoAndAutoSave();
    },
    addTextWYSID: function (ui) {
        let content = '<tr class="acym__wysid__column__element" style="position: relative; top: inherit; left: inherit; right: inherit; bottom: inherit; height: auto;">';
        content += '<td class="large-12 acym__wysid__column__element__td">';

        content += '<div class="acym__wysid__tinymce--text">';
        content += '<p class="acym__wysid__tinymce--text--placeholder">&zwj;</p>';
        content += '</div>';

        content += '</td>';
        content += '</tr>';
        jQuery(ui).replaceWith(content);
        acym_helperEditorWysid.setColumnRefreshUiWYSID();
        acym_editorWysidVersioning.setUndoAndAutoSave();
    },
    addMediaWysid: function (ui, rows) {
        if (ACYM_CMS === 'wordpress') {
            acym_editorWysidWordpress.addMediaWPWYSID(ui, rows);
        } else {
            acym_editorWysidJoomla.addMediaJoomlaWYSID(ui, rows);
        }
    },
    addButtonWYSID: function (ui) {
        let content = '<tr class="acym__wysid__column__element" style="position: relative; top: inherit; left: inherit; right: inherit; bottom: inherit; height: auto;">';
        content += '<td class="large-12 acym__wysid__column__element__td">';

        content += '<div style="text-align: center;box-sizing: inherit;">';
        content += '<a class="acym__wysid__column__element__button acym__wysid__content-no-settings-style" style="background-color: #222222; color: white; padding: 25px 30px; max-width: 100%; overflow: unset; border: 1px solid white; text-overflow: ellipsis; text-align: center; text-decoration: none; word-break: break-all;display: inline-block; box-shadow: none;font-family: Arial; font-size: 14px; cursor: pointer; line-height: 1; border-radius: 0" href="#">Button</a>';
        content += '</div>';

        content += '</td>';
        content += '</tr>';
        jQuery(ui).replaceWith(content);
        acym_helperEditorWysid.setColumnRefreshUiWYSID();
        acym_editorWysidVersioning.setUndoAndAutoSave();
    },
    addFollowWYSID: function (ui) {
        let content = '<tr class="acym__wysid__column__element" style="position: relative; top: inherit; left: inherit; right: inherit; bottom: inherit; height: auto;">';
        content += '<td class="large-12 acym__wysid__column__element__td">';
        content += '<div style="text-align: center;">';
        content += '<p class="acym__wysid__column__element__follow" style="text-align: center; cursor: pointer; padding: 0;margin: 0;">';

        content += '<a class="acym__wysid__column__element__follow__facebook" href="">';
        content += '<img style="display: inline-block; max-width: 100%; height: auto;  box-sizing: border-box; width: 40px; padding: 3px;" src="'
                   + acym_helperEditorWysid.socialMedia.facebook.src
                   + '" width="40" alt="facebook">';
        content += '</a>';

        content += '<a class="acym__wysid__column__element__follow__twitter" href="">';
        content += '<img style="display: inline-block; max-width: 100%; height: auto;  box-sizing: border-box; width: 40px; padding: 3px;" src="'
                   + acym_helperEditorWysid.socialMedia.twitter.src
                   + '"  width="40" alt="twitter">';
        content += '</a>';

        content += '</p>';
        content += '</div>';
        content += '</td>';
        content += '</tr>';
        jQuery(ui).replaceWith(content);
        acym_helperEditorWysid.setColumnRefreshUiWYSID();
        acym_editorWysidVersioning.setUndoAndAutoSave();
    },
    addSpaceWYSID: function (ui) {
        let content = '<tr class="acym__wysid__column__element" style="position: relative; top: inherit; left: inherit; right: inherit; bottom: inherit; height: auto;">';
        content += '<td class="large-12 acym__wysid__column__element__td">';

        content += '<span class="acy-editor__space" style="display:block; padding: 0;margin: 0; height: 50px"></span>';

        content += '</td>';
        content += '</tr>';
        jQuery(ui).replaceWith(content);
        acym_helperEditorWysid.setColumnRefreshUiWYSID();
        acym_editorWysidVersioning.setUndoAndAutoSave();
    },
    addVideoWYSID: function (ui) {
        acym_helperEditorWysid.$focusElement = jQuery(ui);
        acym_editorWysidNewContent.setModalVideoWYSID();
        jQuery('#acym__wysid__modal').css('display', 'inherit');
        acym_helperEditorWysid.setColumnRefreshUiWYSID();
    },
    addGiphyWYSID: function (ui) {
        acym_helperEditorWysid.$focusElement = jQuery(ui);
        acym_editorWysidNewContent.setModalGiphyWYSID();
        jQuery('#acym__wysid__modal').css('display', 'inherit');
        acym_helperEditorWysid.setColumnRefreshUiWYSID();
    },
    addSeparatorWysid: function (ui) {
        let content = '<tr class="acym__wysid__column__element acym__wysid__column__element__separator cursor-pointer" style="position: relative; top: inherit; left: inherit; right: inherit; bottom: inherit; height: auto;">';
        content += '<td class="large-12 acym__wysid__column__element__td">';

        content += '<hr style="border-bottom: 3px solid black; width: 100%; border-top: none; border-left: none; border-right: none;" class="acym__wysid__row__separator">';

        content += '</td>';
        content += '</tr>';
        jQuery(ui).replaceWith(content);
        acym_helperEditorWysid.setColumnRefreshUiWYSID();
        acym_editorWysidVersioning.setUndoAndAutoSave();
    },
    addShareWYSID: function (ui) {
        let content = '<tr class="acym__wysid__column__element" style="position: relative; top: inherit; left: inherit; right: inherit; bottom: inherit; height: auto;">';
        content += '<td class="large-12 acym__wysid__column__element__td">';

        content += '<div class="acym__wysid__column__element__share acym__wysid__column__element__share--add acy" style="text-align: center; margin-top: 10px; margin-bottom: 10px;">';

        content += '<a style="display: inline-block" class="acym__wysid__column__element__share__social acym__wysid__column__element__share__facebook" href="'
                   + acym_helperEditorWysid.socialMedia.facebook.link
                   + '">';
        content += '<img style="vertical-align: middle; width: 30px; display: inline; margin-right: 5px;" src="'
                   + acym_helperEditorWysid.socialMedia.facebook.src
                   + '" alt="'
                   + acym_helperEditorWysid.socialMedia.facebook.src
                   + '">';
        content += '<span style="color: #303e46; vertical-align: middle; margin-right: 10px; font-size: 15px">'
                   + acym_helperEditorWysid.socialMedia.facebook.text
                   + '</span>';
        content += '</a>';

        content += '<a style="display: inline-block" class="acym__wysid__column__element__share__social acym__wysid__column__element__share__twitter" href="'
                   + acym_helperEditorWysid.socialMedia.twitter.link
                   + '">';
        content += '<img style="vertical-align: middle; width: 30px; display: inline; margin-right: 5px;" src="'
                   + acym_helperEditorWysid.socialMedia.twitter.src
                   + '" alt="'
                   + acym_helperEditorWysid.socialMedia.twitter.src
                   + '">';
        content += '<span style="color: #303e46; vertical-align: middle; margin-right: 10px; font-size: 15px">'
                   + acym_helperEditorWysid.socialMedia.twitter.text
                   + '</span>';
        content += '</a>';

        content += '</div>';

        content += '</td>';
        content += '</tr>';
        jQuery(ui).replaceWith(content);
        acym_helperEditorWysid.setColumnRefreshUiWYSID();
        acym_editorWysidVersioning.setUndoAndAutoSave();
    },
    setModalGiphyWYSID: function () {
        let content = '<div class="grid-container"><div class="cell grid-x align-center grid-padding-x">';
        content += '<img class="cell" id="acym__wysid__modal__giphy--image" src="' + ACYM_MEDIA_URL + 'images/giphy.png" alt="">';
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
        content += '<button class="button" type="button" id="acym__wysid__modal__giphy--insert" disabled="disabled">'
                   + ACYM_JS_TXT.ACYM_INSERT_GIF
                   + '</button>';
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
        content += '<button type="button" id="acym__wysid__modal__video__load" class="button primary expanded " href="#">Load</button>';
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
        content += '<button type="button" id="acym__wysid__modal__video__insert" class="button primary expanded disabled" href="#">Insert</button>';
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

            $insertBtn.unbind('click').click(function () {
                acym_helperEditorWysid.$focusElement.replaceWith(
                    '<tr class="acym__wysid__column__element"><td class="large-12 acym__wysid__column__element__td"><div class="acym__wysid__tinymce--image"><p>'
                    + $result.html()
                    + '</p></div></td></tr>');
                acym_editorWysidTinymce.addTinyMceWYSID();
                jQuery('#acym__wysid__modal').css('display', 'none');
                acym_editorWysidVersioning.setUndoAndAutoSave();
                acym_helperEditorWysid.setColumnRefreshUiWYSID();
            });

            let youtubeId = url.match(/(?:https?:\/{2})?(?:w{3}\.)?youtu(?:be)?\.(?:com|be)(?:\/watch\?v=|\/)([^\s&]+)/);
            let dailymotionId = url.match(/^(?:(?:http|https):\/\/)?(?:www.)?(dailymotion\.com|dai\.ly)\/((video\/([^_]+))|(hub\/([^_]+)|([^\/_]+)))$/);
            let vimeoId = url.match(/^.*(vimeo\.com\/)((channels\/[A-z]+\/)|(groups\/[A-z]+\/videos\/))?([0-9]+)/);

            if (youtubeId != null) {
                $result.html('<a href="https://www.youtube.com/watch?v='
                             + youtubeId[1]
                             + '"><img alt="" src="https://img.youtube.com/vi/'
                             + youtubeId[1]
                             + '/0.jpg" style="max-width: 100%; height: auto; box-sizing: border-box; padding: 0 5px; display: block;margin-left: auto; margin-right: auto;"/></a>');
                $insertBtn.removeClass('disabled');
            } else if (dailymotionId != null) {
                if (dailymotionId !== null) {
                    if (dailymotionId[4] !== undefined) {
                        $result.html('<a href="https://www.dailymotion.com/video/'
                                     + dailymotionId[4]
                                     + '"><img alt="" src="https://www.dailymotion.com/thumbnail/video/'
                                     + dailymotionId[4]
                                     + '" style="max-width: 100%; height: auto; box-sizing: border-box; padding: 0 5px; display: block; margin-left: auto; margin-right: auto;"/></a>');
                    }
                    $result.html('<a href="https://www.dailymotion.com/video/'
                                 + dailymotionId[2]
                                 + '"><img alt="" src="https://www.dailymotion.com/thumbnail/video/'
                                 + dailymotionId[2]
                                 + '" style="max-width: 100%; height: auto; box-sizing: border-box; padding: 0 5px; display: block; margin-left: auto; margin-right: auto;"/></a>');
                }
                $insertBtn.removeClass('disabled');
            } else if (vimeoId != null) {
                jQuery.ajax({
                    url: 'https://www.vimeo.com/api/v2/video/' + vimeoId[5] + '.json',
                    dataType: 'jsonp',
                    success: function (data) {
                        $result.html('<a href="https://vimeo.com/'
                                     + vimeoId[5]
                                     + '"><img alt="" src="'
                                     + data[0].thumbnail_large
                                     + '" style="max-width: 100%; height: auto; box-sizing: border-box; padding: 0 5px; display: block; margin-left: auto; margin-right: auto;"/></a>');
                        $insertBtn.removeClass('disabled');
                    }
                });
            } else {
                $result.html('<div class="acym__wysid__error_msg" style="text-align: center; margin-top: 100px;">' + ACYM_JS_TXT.ACYM_NON_VALID_URL + '</div>');
                $insertBtn.addClass('disabled').off('click');
            }
            $result.unbind('click').click(function (e) {
                e.preventDefault();
            });
        });

        $searchInput.keyup(function (e) {
            let code = e.which;
            if (code == 13) e.preventDefault();
            if (code == 13 || code == 188 || code == 186) {
                $loadBtn.click();
            }
        });
    }
};
