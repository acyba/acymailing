jQuery(function($) {

    function Init() {
        setAddstyleSheetHtml();
        setUploadImageButton();
        setResetThumbnailButton();
        acym_helperEditorHtml.initEditorHtml();
        acym_helperEditorWysid.initEditor();
        acym_helperCampaigns.initAttachmentCampaigns();
    }

    Init();

    function setAddstyleSheetHtml() {
        if ($('#acym__wysid__edit__button').length < 1) {
            $('#acym__mail__edit__html__stylesheet__container').show();
        }
    }

    function setUploadImageButton() {
        const $button = $('#acym__mail__edit__thumbnail--input');
        const $input = $('#acym__mail__edit__thumbnail');
        const $fileDisplay = $('#acym__mail__edit__thumbnail--file');
        const $deleteButton = $('#acym__mail__edit__thumbnail--delete');
        const $savedDiv = $('#acym__mail__edit__thumbnail--saved');

        $button.off('click').on('click', function () {
            $input.click();
        });

        $input.on('change', function () {
            if (!this.files || !this.files[0]) {
                $fileDisplay.html('');
                $deleteButton.hide();
                $fileDisplay.hide();
                return;
            }
            if ($savedDiv.length) {
                $savedDiv.hide();
            }
            $fileDisplay.html(this.files[0].name);
            $deleteButton.show();
            $fileDisplay.css('display', 'flex');
        });

        $deleteButton.off('click').on('click', function () {
            $input[0].value = null;
            $input.change();
            if ($savedDiv.length) {
                $savedDiv.show();
            }
        });
    }

    function setResetThumbnailButton() {
        const $button = $('#acym__mail__edit__thumbnail--delete-saved');
        const $fileName = $('#acym__mail__edit__thumbnail--file-saved');
        const $input = $('[name="custom_thumbnail_reset"]');

        $button.off('click').on('click', function () {
            $input.val(1);
            $button.remove();
            $fileName.remove();
        });
    }
});
