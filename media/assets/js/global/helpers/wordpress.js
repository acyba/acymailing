const acym_heleperWordPressGlobal = {
    openMediaManager: function (callbackSuccess, callbackCancel = null) {
        const file_frame = wp.media.frames.file_frame = wp.media({
            title: ACYM_JS_TXT.ACYM_SELECT_IMAGE_TO_UPLOAD,
            button: {text: ACYM_JS_TXT.ACYM_USE_THIS_IMAGE},
            multiple: false
        });

        file_frame.on('select', function () {
            const attachment = file_frame.state().get('selection').first().toJSON();

            const mediaObject = {
                url: attachment.url,
                alt: attachment.alt,
                title: attachment.title,
                caption: attachment.caption
            };

            callbackSuccess(mediaObject);
        });

        file_frame.on('escape', function () {
            if (callbackCancel !== null) {
                callbackCancel();
            }
        });

        file_frame.open();
    }
};
