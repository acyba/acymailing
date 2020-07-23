jQuery(document).ready(function ($) {
    function Init() {
        setInputClickEdit();
        setCheckbox();
        setButtonEditDetails();
        setUpdateSummary();
    }

    Init();

    function setInputClickEdit() {
        $('.acym__bounce__select__subscribe').off('click').on('click', function (e) {
            e.preventDefault();
        });
    }

    function setCheckbox() {
        $('input[value="save_message"]').on('click', function (e) {
            if ($('input[value="delete_user"]').is(':checked')) {
                alert(ACYM_JS_TXT.ACYM_CANT_DELETE_AND_SAVE);
                e.preventDefault();
            }
        });
        $('input[value="delete_user"]').on('click', function (e) {
            if ($('input[value="save_message"]').is(':checked')) {
                alert(ACYM_JS_TXT.ACYM_CANT_DELETE_AND_SAVE);
                e.preventDefault();
            }
        });
    }

    function setButtonEditDetails() {
        $('#acym__bounces__display_details').off('click').on('click', function () {
            $('#acy_bounces_details').toggle();
        });
    }

    function setUpdateSummary() {
        // Global options
        let selector = '[name="bounce[regex]"], [name="bounce[executed_on][]"], [name="bounce[increment_stats]"]';
        selector += ', [name="bounce[execute_action_after]"], [name="bounce[action_user][]"], [name="bounce[subscribe_user_list]"]';
        selector += ', [name="bounce[action_message][]"], [name="bounce[action_message][forward_to]"]';
        $(selector).on('change', function () {
            $('#acym__bounces__summary__changes').css('display', 'block');
        });
    }
});
