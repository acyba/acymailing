jQuery(document).ready(function ($) {

    function Init() {
        setIntroMail();
        setAddstyleSheetHtml();
        acym_helperDynamic.setModalDynamics();
        acym_helperEditorHtml.initEditorHtml();
        acym_helperEditorWysid.initEditor();
    }

    Init();

    function setAddstyleSheetHtml() {
        if ($('#acym__wysid__edit__button').length < 1) {
            $('#acym__mail__edit__html__stylesheet__container').show();
        }
    }

    function setIntroMail() {
        $('#acym__wysid__edit__button').on('click', function () {
            acym_helperIntroJS.introContent = [
                {
                    element: 'table.body',
                    text: ACYM_JS_TXT.ACYM_INTRO_TEMPLATE
                },
                {
                    element: '.acym__wysid__right__toolbar__blocks',
                    text: ACYM_JS_TXT.ACYM_INTRO_DRAG_BLOCKS
                },
                {
                    element: '.acym__wysid__right__toolbar__contents',
                    text: ACYM_JS_TXT.ACYM_INTRO_DRAG_CONTENT
                },
                {
                    element: '#acym__wysid__right__toolbar__settings__tab',
                    text: ACYM_JS_TXT.ACYM_INTRO_SETTINGS
                }
            ];
            acym_helperIntroJS.setIntrojs('mail_editor_content');
        });

        $('#acym__wysid__right__toolbar__settings__tab').on('click', function () {
            setTimeout(function () {
                acym_helperIntroJS.introContent = [
                    {
                        element: '.acym__wysid__right__toolbar__design',
                        text: ACYM_JS_TXT.ACYM_INTRO_CUSTOMIZE_FONT
                    },
                    {
                        element: '#acym__wysid__right__toolbar__settings__stylesheet__open',
                        text: ACYM_JS_TXT.ACYM_INTRO_IMPORT_CSS
                    }
                ];
                acym_helperIntroJS.setIntrojs('mail_editor_settings');
            }, 500);
        });
    }
});
