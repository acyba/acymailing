jQuery(function($) {
    function Init() {
        setActionButtonCampaigns();
        acym_helperStats.setLineChartOpenTimeWeek();
    }

    Init();

    function setActionButtonCampaigns() {
        $('.acym__dashboard__active-campaigns__one-campaign__action').off('click').on('click', function () {
            const data = {
                ctrl: 'campaigns',
                task: 'cancelDashboardAndGetCampaignsAjax',
                id: $(this).attr('id')
            };

            acym_helper.post(ACYM_AJAX_URL, data).then(response => {
                if (response.error) {
                    window.scrollTo(0, 0);
                    acym_helperNotification.addNotification(response.message, 'error');
                } else {
                    $('.acym__dashboard__active-campaigns__listing').html(response.data.content);
                }

                setActionButtonCampaigns();
            });
        });
    }
});
