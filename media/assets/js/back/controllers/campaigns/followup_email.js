jQuery(document).ready(function ($) {
    function followupEmail() {
        setDuplicateMail();
        setDeleteMail();
        setEmailAddQueue();
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

    function setEmailAddQueue() {
        $('.acym__followup__add_queue').off('click').on('click', function () {
            let data = {
                ctrl: 'followups',
                task: 'addQueueAjax',
                emailId: $(this).attr('data-acym-email-id')
            };

            acym_helper.get(ACYM_AJAX_URL, data).then(res => {
                if (res.error) {
                    acym_helperNotification.addNotification(res.message, 'error', true);
                    return;
                }

                acym_helperNotification.addNotification(res.message, 'success', true);
                $(this).closest('.acym__message').remove();
            });
        });
    }

    followupEmail();
});
