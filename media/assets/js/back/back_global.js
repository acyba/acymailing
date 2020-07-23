jQuery(document).ready(function ($) {
    function BackGlobal() {
        $(document).foundation();

        acym_helper.setSubmitButtonGlobal();
        acym_helper.preventEnter();
        acym_helper.setMessageClose();
        acym_helper.setDeleteOptionsGlobal();
        acym_helperSelect2.initJsSelect2();
        acym_helperToggle.initJsToggle();
        acym_helperListing.initJsListing();
        acym_helperTab.setTab();
        acym_helperDatePicker.setDatePickerGlobal();
        acym_helperInput.setInputFile();
        acym_helperTooltip.setTooltip();
        acym_helperBack.setDoNotRemindMe();
        acym_helperNotification.setNotificationCenter();
        acym_helperPagination.setPaginationGlobal();
        acym_helperSwitch.setSwitchFieldsGlobal();
        acym_helperSearch.setClearSearch();
        acym_helperPreview.setPreviewIframe();
        acym_helperRadio.setRadioIconsGlobal();
        acym_helperDynamic.initPopup();
        acym_helperModal.initOverlay();
        acym_helperNotification.removeNotifications();
        acym_helperHeader.setVersionButton();
        acym_helperErrorMessage.initErrorMessage();
        acym_helperModal.initModal();
        acym_helperDebugger.initDebugger();
        if (CMS_ACYM === 'joomla') acym_helperJoomla.setJoomlaLeftMenu();

    }

    BackGlobal();
});
