const acym_helperThumbnail = {
    setAjaxSaveThumbnail: function () {
        let $editorThumbnail = jQuery('#editor_thumbnail');
        let $savedThumbnail = jQuery('[name="thumbnail"]');
        let ajaxUrl = ACYM_AJAX_URL + '&ctrl=' + acym_helper.ctrlMails + '&task=setNewThumbnail';

        if ($editorThumbnail.val().indexOf('.png') !== -1) return true;

        if ($editorThumbnail.val() === '' && $savedThumbnail.val() !== '') {
            $editorThumbnail.val($savedThumbnail.val());
            return true;
        }
        if ($savedThumbnail.val() === '' && $editorThumbnail.val() === '') {
            return true;
        } else {
            let generatedThumbnail = $savedThumbnail.val();
            $savedThumbnail.val('');
            return jQuery.ajax({
                type: 'POST',
                url: ajaxUrl,
                data: {
                    content: $editorThumbnail.val(),
                    thumbnail: generatedThumbnail
                },
                timeout: 5000,
                success: function (res) {
                    $editorThumbnail.val(res);
                },
                error: function (XMLHttpRequest, textStatus, errorThrown) {
                    if (textStatus === 'timeout') errorThrown = ACYM_JS_TXT.ACYM_REQUEST_FAILED_TIMEOUT;
                    acym_helperNotification.addNotification(acym_helper.sprintf(ACYM_JS_TXT.ACYM_COULD_NOT_SAVE_THUMBNAIL_ERROR_X, errorThrown), 'error');
                    $editorThumbnail.val('');
                }
            });
        }
    }
};
