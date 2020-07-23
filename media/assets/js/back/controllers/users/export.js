jQuery(document).ready(function ($) {

    function Init() {
        setRadioButtonUsersToExport();
        setCheckAllNoneFieldsExport();
        setCheckAllNoneListsExport();
        setButtonExport();
    }

    Init();

    function setRadioButtonUsersToExport() {
        let $selectLists = $('#acym__users__export__select_lists');
        let $selectAll = $('#acym__users__export__select_all');
        let $usersToExportInputs = $('#acym__users__export__users-to-export input');

        $usersToExportInputs.on('change', function () {
            if ($(this).val() === 'list') {
                $selectLists.show();
                $selectAll.hide();
            } else {
                $selectLists.hide();
                $selectAll.show();
                $('.modal__pagination__listing__lists__list--checkbox').prop('checked', false);
            }
        });
    }

    function setCheckAllNoneFieldsExport() {
        let $exportFields = $('.acym__users__export__export_fields');

        $('#acym__users__export__check_all_field').off('click').on('click', function () {
            $exportFields.prop('checked', true);
        });

        $('#acym__users__export__check_default_field').off('click').on('click', function () {
            $exportFields.prop('checked', false);
            $('#checkbox_email').prop('checked', true);
            $('#checkbox_name').prop('checked', true);
        });
    }

    function setCheckAllNoneListsExport() {
        let $exportFields = $('.modal__pagination__listing__lists__list--checkbox');

        $('#acym__users__export__check_all_list').off('click').on('click', function () {
            $exportFields.prop('checked', true);
        });

        $('#acym__users__export__check_none_list').off('click').on('click', function () {
            $exportFields.prop('checked', false);
        });
    }

    function setButtonExport() {
        $('#acym__export__button').on('click', function () {
            setTimeout(function () {
                $('#formSubmit')[0].disabled = false;
            }, 5);
        });
    }
});
