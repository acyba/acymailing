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
    addUnsplashWYSID: function (ui) {
        acym_helperEditorWysid.$focusElement = jQuery(ui);
        acym_editorWysidNewContent.setModalUnsplashWYSID();
        jQuery('#acym__wysid__modal').css('display', 'inherit');
    },
    addFollowWYSID: function () {
        let content = '<tr class="acym__wysid__column__element" style="position: relative; top: inherit; left: inherit; right: inherit; bottom: inherit; height: auto;">';
        content += '<td class="large-12 acym__wysid__column__element__td">';
        content += '<div style="text-align: center;">';
        content += '<p class="acym__wysid__column__element__follow" style="text-align: center; cursor: pointer; padding: 0;margin: 0;">';

        content += '<a class="acym__wysid__column__element__follow__facebook" href="" target="_blank">';
        content += '<img hspace="0" style="display: inline-block; max-width: 100%; height: auto;  box-sizing: border-box; width: 40px; padding: 3px;" src="'
                   + acym_helperEditorWysid.socialMedia.facebook.src
                   + '" width="40" alt="facebook">';
        content += '</a>';

        content += '<a class="acym__wysid__column__element__follow__twitter" href="" target="_blank">';
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
        let content = '<div class="grid-container"><div class="cell grid-x align-center grid-padding-x margin-bottom-1">';
        content += '<img hspace="0" class="cell" id="acym__wysid__modal__giphy--image" src="' + ACYM_MEDIA_URL + 'images/giphy.png" alt="Giphy logo">';
        content += '<div class="cell grid-x grid-margin-x"><input class="cell auto" type="text" id="acym__wysid__modal__giphy--search" placeholder="'
                   + ACYM_JS_TXT.ACYM_SEARCH_FOR_GIFS
                   + '">';
        content += '<button type="button" class="cell shrink button button-secondary" id="acym__wysid__modal__giphy--search--button">'
                   + ACYM_JS_TXT.ACYM_SEARCH_GIFS
                   + '</button></div>';
        content += '</div></div>';
        jQuery('#acym__wysid__modal__ui__fields').html(content);

        content = '<div class="grid-container acym__wysid__modal__giphy__results__container">';
        content += '<h3 class="cell text-center acym__title__primary__color" id="acym__wysid__modal__giphy--error_message" style="display: none"></h3>';
        content += '<div class="cell grid-x grid-padding-x grid-margin-x margin-y" id="acym__wysid__modal__giphy--results"></div></div>';
        jQuery('#acym__wysid__modal__ui__display').html(content);

        content = '<div class="grid-container"><div class="cell grid-x align-right grid-padding-x">';
        content += '<button class="button" type="button" id="acym__wysid__modal__giphy--insert" disabled="disabled">' + ACYM_JS_TXT.ACYM_INSERT + '</button>';
        content += '</div></div>';
        jQuery('#acym__wysid__modal__ui__search').html(content);

        acym_editorWysidGiphy.makeNewResearch('');
        acym_editorWysidGiphy.insertGif();
    },
    setModalUnsplashWYSID: function () {
        let content = `<div class="grid-container">
            <div class="cell grid-x align-center grid-padding-x margin-bottom-1">
                <img class="cell" id="acym__wysid__modal__unsplash--image" src="${ACYM_MEDIA_URL}images/unsplash.svg" alt="Unsplash logo">
                <div class="cell grid-x grid-margin-x margin-y">
                    <input class="cell auto unsplash_fields" type="text" id="acym__wysid__modal__unsplash--search" placeholder="${ACYM_JS_TXT.ACYM_SEARCH_FOR_IMAGES}">
                    <div class="cell medium-3 large-2">
                        <select id="acym__wysid__modal__unsplash--size" class="unsplash_fields">
                            <option value="full">${ACYM_JS_TXT.ACYM_FULL_WIDTH}</option>
                            <option value="regular" selected="selected">${ACYM_JS_TXT.ACYM_MEDIUM}</option>
                            <option value="small">${ACYM_JS_TXT.ACYM_SMALL}</option>
                            <option value="thumb">${ACYM_JS_TXT.ACYM_THUMBNAIL}</option>
                        </select>
                    </div>
                    <div class="cell medium-3 large-2">
                        <select id="acym__wysid__modal__unsplash--orientation" class="unsplash_fields">
                            <option value="all" selected="selected">${ACYM_JS_TXT.ACYM_ORIENTATION}</option>
                            <option value="landscape">${ACYM_JS_TXT.ACYM_LANDSCAPE}</option>
                            <option value="portrait">${ACYM_JS_TXT.ACYM_PORTRAIT}</option>
                            <option value="squarish">${ACYM_JS_TXT.ACYM_SQUARISH}</option>
                        </select>
                    </div>
                    <button type="button" class="cell shrink button button-secondary unsplash_fields" id="acym__wysid__modal__unsplash--search--button">${ACYM_JS_TXT.ACYM_SEARCH_IMAGES}</button
                </div>
            </div>
        </div>`;
        jQuery('#acym__wysid__modal__ui__fields').html(content);

        if (ACYM_IS_ADMIN) {
            jQuery('#acym__wysid__modal__unsplash--size, #acym__wysid__modal__unsplash--orientation').select2({
                theme: 'foundation',
                width: '100%'
            });
        }

        content = `<div class="grid-container acym__wysid__modal__unsplash__results__container">
            <h3 class="cell text-center acym__title__primary__color" id="acym__wysid__modal__unsplash--error_message" style="display: none"></h3>
            <div class="cell grid-x grid-padding-x grid-margin-x margin-y" id="acym__wysid__modal__unsplash--results"></div>
        </div>`;
        jQuery('#acym__wysid__modal__ui__display').html(content);

        content = `<div class="grid-container">
            <div class="cell grid-x align-right grid-padding-x">
                <button class="button" type="button" id="acym__wysid__modal__unsplash--insert" disabled="disabled">${ACYM_JS_TXT.ACYM_INSERT}</button>
            </div>
        </div>`;
        jQuery('#acym__wysid__modal__ui__search').html(content);

        acym_editorWysidUnsplash.init();
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
            const url = $searchInput.val();

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

            $result.html(`<i class="acymicon-circle-o-notch acymicon-spin" id="acym__wysid__modal__video__spinner"></i>`);

            const ajaxUrl = ACYM_AJAX_URL + '&ctrl=' + acym_helper.ctrlMails + '&task=ajaxCheckVideoUrl';
            acym_helper.post(ajaxUrl, {
                'url': url
            }).then(res => {
                if (!res.data.new_image_name) {
                    $result.html(`<div class="acym__wysid__error_msg" style="text-align: center; margin-top: 100px;">${ACYM_JS_TXT.ACYM_NON_VALID_URL}</div>`);
                    $insertBtn.addClass('disabled').off('click');
                } else {
                    $result.html(`<a href="${url}" target="_blank" class="acym__wysid__link__image">
                                    <img alt="" src="${res.data.new_image_name}" style="max-width: 100%; height: auto; box-sizing: border-box; padding: 0 5px; display: block; margin-left: auto; margin-right: auto; float: none;"/>
                                </a>`);

                    $result.off('click').on('click', function (e) {
                        e.preventDefault();
                    });
                    $insertBtn.removeClass('disabled');
                }
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
        let content = `
            <div class="grid-x margin-y">
                <h5 class="cell text-center">${ACYM_JS_TXT.ACYM_NEW_CUSTOM_ZONE}</h5>
                <div class="cell text-center">${ACYM_JS_TXT.ACYM_ZONE_SAVE_TEXT}</div>
                <div class="cell grid-x margin-y">
                    <label for="custom_zone_name" class="cell small-4 text-left">
                        <span class="margin-left-2">${ACYM_JS_TXT.ACYM_ZONE_NAME}</span>
                        <span class="acym__color__red">*</span>
                    </label>
                    <div class="cell small-8">
                        <input id="custom_zone_name" type="text" value="" />
                    </div>
                    <label for="custom_zone_image" class="cell small-4 text-left">
                        <span class="margin-left-2">${ACYM_JS_TXT.ACYM_IMAGE}</span>
                    </label>
                    <div class="cell small-8">
                        <input id="custom_zone_image" type="file" value="" />
                    </div>
                </div>
                <div class="cell align-center grid-x">
                    <button class="button button-secondary" type="button" id="custom_zone_cancel">${ACYM_JS_TXT.ACYM_CANCEL}</button>
                    <button class="button margin-left-1" id="custom_zone_save" type="button" disabled="disabled">
                        ${ACYM_JS_TXT.ACYM_SAVE}
                        <i id="custom_zone_save_spinner" class="acymicon-circle-o-notch acymicon-spin"></i>
                    </button>
                </div>
            </div>`;

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
            const spinner = jQuery('#custom_zone_save_spinner');
            spinner.css('display', 'inline-block');

            const newZoneName = jQuery('#custom_zone_name').val();

            let zoneContent = acym_helperEditorWysid.$focusElement.prop('outerHTML');
            // Remove the zone overlay
            zoneContent = zoneContent.replace(/<div class="acym__wysid__row__selector"(.|\n)*?<tbody/, '<tbody');
            // Remove the blocks overlay
            zoneContent = zoneContent.replace(/<div class="acym__wysid__element__toolbox"(.|\n)*?<\/div>/g, '');

            const formData = new FormData();
            formData.append('ctrl', 'zones');
            formData.append('task', 'save');
            formData.append('name', newZoneName);
            formData.append('content', zoneContent);

            const selectedImages = jQuery('#custom_zone_image').prop('files');
            if (selectedImages && selectedImages[0]) {
                formData.append('image', selectedImages[0]);
            }

            jQuery.ajax({
                url: ACYM_AJAX_URL,
                dataType: 'text',
                cache: false,
                contentType: false,
                processData: false,
                data: formData,
                type: 'post',
                success: function (response) {
                    response = acym_helper.parseJson(response);

                    if (response.error) {
                        acym_editorWysidNotifications.addEditorNotification({
                            message: `<div class="cell auto acym__autosave__notification">${response.message}</div>`,
                            level: 'error'
                        }, 3000, true);
                    } else {
                        let newZone = `<div class="grid-x cell acym__wysid__zone__element--new ui-draggable ui-draggable-handle" data-acym-zone-id="${response.data.id}">`;
                        if (ACYM_IS_ADMIN) {
                            newZone += `<i class="acymicon-delete"></i>`;
                        }

                        if (response.data.image) {
                            newZone += `<img class="cell saved_zone_image" alt="Logo custom zone" src="${response.data.image}" />`;
                        } else {
                            newZone += '<i class="cell acymicon-dashboard"></i>';
                        }

                        newZone += `<div class="cell">${newZoneName}</div>
                                </div>`;

                        jQuery('#custom_zones_none_message').hide();
                        jQuery('.acym__wysid__right__toolbar__saved_zones').append(newZone);
                        acym_editorWysidDragDrop.setNewZoneDraggable();
                    }

                    spinner.css('display', 'none');
                    jQuery('#custom_zone_cancel').trigger('click');
                }
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
