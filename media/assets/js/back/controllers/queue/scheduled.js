jQuery(function($) {
    function Init() {
        setMoreListsQueue();
        setToggleDeleteCampaign();
    }

    Init();

    function setMoreListsQueue() {
        $('.acymicon-plus-circle').off('click').on('click', function () {
            $('i[data-toggle^="campaign' + $(this).attr('data-campaign') + '_list"]').removeClass('acyhidden');
            $(this).addClass('acyhidden');
        });
    }

    function setToggleDeleteCampaign() {
        $('.acym__queue__cancel__button').off('click').on('click', function () {
            if (acym_helper.confirm(ACYM_JS_TXT.ACYM_CONFIRMATION_CANCEL_CAMPAIGN_QUEUE)) {
                $('[name="acym__queue__cancel__mail_id"]').val($(this).attr('mailid'));
                $('[name="task"]').val('cancelScheduledSending');

                $('#formSubmit').trigger('click');
            }
        });
    }
});
