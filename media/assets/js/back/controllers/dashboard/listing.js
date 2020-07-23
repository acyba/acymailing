jQuery(document).ready(function ($) {
    function Init() {
        setActionButtonCampaigns();
    }

    Init();

    function setActionButtonCampaigns() {
        $('.acym__dashboard__active-campaigns__one-campaign__action').off('click').on('click', function () {
            let ajaxUrl = ACYM_AJAX_URL + '&ctrl=campaigns&task=cancelDashboardAndGetCampaignsAjax&id=' + $(this).attr('id');

            $.post(ajaxUrl, function (response) {
                response == 'error' ? console.log(response) // TODO mettre une notif
                                    : $('.acym__dashboard__active-campaigns__listing').html(response); //TODO mettre une notif
                setActionButtonCampaigns();
            });
        });
    }
});
