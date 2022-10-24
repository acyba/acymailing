jQuery(function($) {
    function acym_front() {
        if (ACYM_CONTROLLER !== 'archive') {
            jQuery(document).foundation();
        }
        acym_helper.setSubmitButtonGlobal();
        acym_helper.setButtonRadio();
        acym_helperDatePicker.setDatePickerGlobal();
        acym_helperToggle.initJsToggle();
        acym_helperListing.initJsListing();
        acym_helperSearch.setClearSearch();
        acym_helperPagination.setPaginationGlobal();
        acym_helperModal.initOverlay();
        acym_helperSwitch.setSwitchFieldsGlobal();
        acym_helperSwitch.setButtonSwitch();
        acym_helperRadio.setRadioIconsGlobal();
        acym_helperTooltip.setTooltip();
        acym_helperTab.setTab();
        acym_helperPreview.setPreviewIframe();
        acym_helperErrorMessage.initErrorMessage();
    }

    acym_front();
});
