jQuery(document).ready(function ($) {

    function Init() {
        setAddstyleSheetHtml();
        acym_helperDynamic.setModalDynamics();
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
});
