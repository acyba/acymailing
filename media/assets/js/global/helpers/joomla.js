const acym_helperJoomlaGlobal = {
    openMediaManager: function (callbackSuccess, callbackCancel = null, onlyImages = true) {
        // Iframe with joomla media manager
        const $modalUi = jQuery('#acym__upload__modal__joomla-image__ui__iframe');

        // Change the iframe src if we want only the image or not
        let srcToUse = '';
        if (onlyImages) {
            srcToUse = $modalUi.attr('data-acym-src-image');
        } else {
            srcToUse = $modalUi.attr('data-acym-src');
        }
        if (srcToUse !== $modalUi.attr('src')) {
            $modalUi.attr('src', srcToUse);
        }

        const isJoomla4 = $modalUi.attr('data-acym-is-j4') === '1';
        $modalUi.css({
            'height': isJoomla4 ? '90%' : '100%',
            'width': '100%'
        });

        if (!isJoomla4) {
            jQuery('#acym__upload__modal__joomla-image__ui__actions').css('margin-top', '-50px');
        }

        if (isJoomla4 || !isJoomla4 && !onlyImages) {
            jQuery('#acym__upload__modal__joomla-image__ui__actions').show();
        }

        $modalUi.contents().find('.chzn-container-single').attr('style', '').css('width', '150px');
        acym_helperJoomlaGlobal.openMediaManagerEvents($modalUi, callbackSuccess, callbackCancel);
        $modalUi.on('load', function () {
            acym_helperJoomlaGlobal.openMediaManagerEvents($modalUi, callbackSuccess, callbackCancel);
        });
        jQuery('#acym__upload__modal__joomla-image').css('display', 'inherit');
    },
    openMediaManagerEvents: function ($modalUi, callbackSuccess, callbackCancel) {
        // Joomla 4
        jQuery('#acym__upload__modal__joomla-image__ui__actions__cancel').off('click').on('click', function () {
            jQuery('#acym__upload__modal__joomla-image').hide();
            jQuery('#acym__upload__modal__joomla-image__ui__actions').hide();
            if (callbackCancel !== null) {
                callbackCancel();
            }
        });
        jQuery('#acym__upload__modal__joomla-image__ui__actions__select').off('click').on('click', function () {
            const isJoomla4 = $modalUi.attr('data-acym-is-j4') === '1';
            // 1 - Get current folder
            let folderPath = ACYM_ROOT_URI;
            if (isJoomla4) {
                $modalUi.contents().find('.media-breadcrumb-item a').each(function () {
                    folderPath += jQuery(this).text().trim() + '/';
                });
            } else {
                const text = $modalUi.contents().find('#folderframe').contents().find('.breadcrumbs p').text().trim();
                folderPath += `${text}/`;
            }


            // 2 - Get selected image(s)
            const imagesUrls = [];

            // When selecting images from the grid view
            if (isJoomla4) {
                $modalUi.contents().find('.media-browser-grid .media-browser-item.selected .media-browser-item-info').each(function () {
                    imagesUrls.push(folderPath + jQuery(this).text().trim());
                });
            } else {
                $modalUi.contents().find('#folderframe').contents().find('[name="rm[]"]').each(function () {
                    if (!jQuery(this)[0].checked) {
                        return;
                    }

                    let fileName = jQuery(this).closest('.imgOutline').find('.imgPreview a').attr('title');

                    if (!fileName) {
                        fileName = jQuery(this).closest('.imgOutline').find('.small').attr('title');
                    }

                    imagesUrls.push(folderPath + fileName);
                });
            }

            // When selecting images from the list view instead of grid view
            if (imagesUrls.length === 0) {
                $modalUi.contents().find('.media-browser .media-browser-item.selected').each(function () {
                    if (!acym_helper.empty(jQuery(this).find('.size').text().trim())) {
                        imagesUrls.push(folderPath + jQuery(this).find('.name').text().trim());
                    }
                });
            }

            const mediaObject = {
                url: imagesUrls.pop(),
                alt: jQuery('#acym__upload__context__image__alt').val(),
                title: jQuery('#acym__upload_context__image__title').val(),
                caption: jQuery('#acym__upload__context__image__caption').val()
            };

            callbackSuccess(mediaObject);
            // Close the image selection modal
            jQuery('#acym__upload__modal__joomla-image').hide();
            jQuery('#acym__upload__modal__joomla-image__ui__actions').hide();
        });

        // Joomla 3
        $modalUi.contents().find('.button-cancel').attr('onclick', '').off('click').on('click', function () {
            jQuery('#acym__upload__modal__joomla-image').hide();
            jQuery('#acym__upload__modal__joomla-image__ui__actions').hide();
            if (callbackCancel !== null) {
                callbackCancel();
            }
        });
        $modalUi.contents().find('.pull-right .btn-success, .pull-right .btn-primary').attr('onclick', '').off('click').on('click', function () {
            let url = $modalUi.contents().find('#f_url').val();
            if (!acym_helper.empty(url)) {
                if (url.match('^' + ACYM_JOOMLA_MEDIA_FOLDER) || url.match('^' + ACYM_JOOMLA_MEDIA_FOLDER_IMAGES)) {
                    url = ACYM_JOOMLA_MEDIA_IMAGE + url;
                }
            }
            const mediaObject = {
                url,
                alt: $modalUi.contents().find('#f_alt').val(),
                title: $modalUi.contents().find('#f_title').val(),
                caption: $modalUi.contents().find('#f_caption').val()
            };
            callbackSuccess(mediaObject);
            // Close the image selection modal
            jQuery('#acym__upload__modal__joomla-image').hide();
            jQuery('#acym__upload__modal__joomla-image__ui__actions').hide();
        });
    }
};
