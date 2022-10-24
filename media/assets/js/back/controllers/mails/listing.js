jQuery(function($) {

    function Init() {
        setCreateButton();
        setExportTemplate();
        setDuplicateTemplate();
        initButtonFile();
    }

    Init();

    function setCreateButton() {
        $('.acym__create__template').off('click').on('click', function () {
            $('#acym_create_template_type_editor').val($(this).attr('data-editor'));
            $('input[name="task"]').val($(this).attr('data-task'));
            $('#formSubmit').trigger('click');
        });
    }

    function setExportTemplate() {
        $('.acym__listing__block__export').off('click').on('click', function () {
            let formAction = $('#acym_form').attr('action');
            $('#acym_form').attr('action', formAction.replace('admin.php?', 'admin-ajax.php?action=acymailing_router&'));
            $('input[name="templateId"]').val($(this).attr('data-template'));
            $('input[name="task"]').val($(this).attr('data-task'));
            $('#formSubmit').trigger('click');
            $('#acym_form').attr('action', formAction);
            $('input[name="task"]').val('');
            $('#formSubmit').removeAttr('disabled');
        });
    }

    function setDuplicateTemplate() {
        $('.acym__listing__block__duplicate').on('click', function () {
            $('input[name="task"]').val($(this).attr('data-task'));
            $('input[name="templateId"]').val($(this).attr('data-template'));
            $('#formSubmit').trigger('click');
        });
    }

    function initButtonFile() {
        let $inputFile = $('[name="uploadedfile"]');
        let $fileName = $('#acym__template__import__filename');
        $('#acym__template__import__file').off('click').on('click', function () {
            $inputFile.trigger('click');
        });
        $inputFile.on('change', function () {
            let name = '';
            if (acym_helper.empty($(this).val())) {
                name = ACYM_JS_TXT.ACYM_NO_FILE_CHOSEN;
            } else {
                let path = $(this).val().split('\\');
                name = path[path.length - 1];
            }
            $fileName.html(name);
        });
    }
});
