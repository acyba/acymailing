const acym_helperThumbnail = {
    setAjaxSaveThumbnail: function () {
        const $editorThumbnail = jQuery('#editor_thumbnail');
        const $savedThumbnail = jQuery('[name="thumbnail"]');

        if ($editorThumbnail.val().indexOf('.png') !== -1) {
            return true;
        }

        if ($editorThumbnail.val() === '') {
            if ($savedThumbnail.val() !== '') {
                $editorThumbnail.val($savedThumbnail.val());
            }

            return true;
        }

        const generatedThumbnail = $savedThumbnail.val();
        $savedThumbnail.val('');

        const data = {
            ctrl: acym_helper.ctrlMails,
            task: 'setNewThumbnail',
            content: $editorThumbnail.val(),
            thumbnail: generatedThumbnail
        };

        return acym_helper.post(ACYM_AJAX_URL, data).then(response => {
            if (response.error) {
                acym_helperNotification.addNotification(acym_helper.sprintf(ACYM_JS_TXT.ACYM_COULD_NOT_SAVE_THUMBNAIL_ERROR_X, response.message), 'error');
                $editorThumbnail.val('');
            } else {
                $editorThumbnail.val(response.data.fileName);
            }

            location.reload();
        });
    }
};
