jQuery(document).ready(function ($) {

    function Init() {
        acym_helperDynamic.setModalDynamics();
        acym_helperEditorHtml.initEditorHtml();
        acym_helperEditorWysid.initEditor();
        acym_helperCampaigns.setAutoOpenEditor();
    }

    Init();
});
