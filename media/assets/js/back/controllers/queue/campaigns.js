jQuery(document).ready(function ($) {

    function Init() {
        setMoreListsQueue();
        setToggleDeleteCampaign();
        setTogglePlayPauseCampaign();
        setButtonConfigureCron();
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
                $('[name="task"]').val('cancelSending');

                $('#formSubmit').click();
            }
        });
    }

    function setTogglePlayPauseCampaign() {
        $('.acym__queue__play_pause__button').off('click').on('click', function () {
            let active;

            if ($(this).hasClass('acymicon-pause-circle')) {
                active = 0;
            } else {
                active = 1;
            }

            $('[name="acym__queue__play_pause__campaign_id"]').val($(this).attr('campaignid'));
            $('[name="acym__queue__play_pause__active__new_value"]').val(active);
            $('[name="task"]').val('playPauseSending');

            $('#formSubmit').click();
        });
    }

    function setButtonConfigureCron() {
        $('#acym__queue__configure-cron').on('click', function () {
            localStorage.setItem('acyconfiguration', 'queue_process');
        });
    }
});
