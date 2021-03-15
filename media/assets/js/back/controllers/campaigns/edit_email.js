jQuery(document).ready(function ($) {

    function Init() {
        acym_helperEditorHtml.initEditorHtml();
        acym_helperEditorWysid.initEditor();
        acym_helperCampaigns.setAutoOpenEditor();
        acym_helperModal.setResetMail();
        acym_helperModal.setTemplateModal();
    }

    Init();
});
