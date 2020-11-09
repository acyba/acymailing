jQuery(document).ready(function ($) {
    function followupEmail() {
        setDuplicateMail();
        setDeleteMail();
    }

    function setDuplicateMail() {
        $('.acym__followup__email__listing__action-duplicate').off('click').on('click', function () {
            $('[name="task"]').val('followupDuplicateMail');
            $('[name="action_mail_id"]').val($(this).attr('acym-data-id'));
            $('#acym_form').submit();
        });
    }

    function setDeleteMail() {
        $('.acym__followup__email__listing__action-delete').off('click').on('click', function () {
            if (acym_helper.confirm(ACYM_JS_TXT.ACYM_ARE_YOU_SURE)) {
                $('[name="task"]').val('followupDeleteMail');
                $('[name="action_mail_id"]').val($(this).attr('acym-data-id'));
                $('#acym_form').submit();
            }
        });
    }

    followupEmail();
});
