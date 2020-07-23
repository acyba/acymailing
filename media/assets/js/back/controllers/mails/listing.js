jQuery(document).ready(function ($) {

    function Init() {
        setCreateButton();
        setExportTemplate();
        setDuplicateTemplate();
    }

    Init();

    function setCreateButton() {
        $('.acym__create__template').off('click').on('click', function () {
            $('#acym_create_template_type_editor').val($(this).attr('data-editor'));
            $('input[name="task"]').val($(this).attr('data-task'));
            $('#formSubmit').click();
        });
    }

    function setExportTemplate() {
        $('.acym__listing__block__export').off('click').on('click', function () {
            let formAction = $('#acym_form').attr('action');
            $('#acym_form').attr('action', formAction.replace('admin.php?', 'admin-ajax.php?action=acymailing_router&'));
            $('input[name="templateId"]').val($(this).attr('data-template'));
            $('input[name="task"]').val($(this).attr('data-task'));
            $('#formSubmit').click();
            $('#acym_form').attr('action', formAction);
            $('input[name="task"]').val('');
            $('#formSubmit').removeAttr('disabled');
        });
    }

    function setDuplicateTemplate() {
        $('.acym__listing__block__duplicate').on('click', function () {
            $('input[name="task"]').val($(this).attr('data-task'));
            $('input[name="templateId"]').val($(this).attr('data-template'));
            $('#formSubmit').click();
        });
    }
});
