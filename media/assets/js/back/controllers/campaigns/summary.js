jQuery(function ($) {
    function summary() {
        acym_helperCampaigns.setClickFlagsSummary();
        acym_helperCampaigns.setClickVersionSummary();

        $('[name="resend_target"]').on('change', function () {
            if ($(this).val() === 'new') {
                $('#resend_receivers_new').show();
                $('#resend_receivers_all').hide();
            } else {
                $('#resend_receivers_new').hide();
                $('#resend_receivers_all').show();
            }
        });
    }

    function refreshArchive() {
        $('#acym__campaigns__summary__refresh__archive').on('click', function () {
            const $resultContainer = $('#acym__campaigns__summary__refresh__archive__message');
            $resultContainer.html('<i class="acymicon-circle-o-notch acymicon-spin"></i>');

            acym_helper.post(ACYM_AJAX_URL, {
                ctrl: 'campaigns',
                task: 'updateArchive',
                campaignId: parseInt($('input[name="campaignId"]').val())
            }).then(response => {
                if (response.error) {
                    let errorMessage = '<i class="acymicon-remove acym__color__red"></i>';
                    if (response.message) {
                        errorMessage += '<div>' + response.message + '</div>';
                    }

                    $resultContainer.html(errorMessage);
                } else {
                    $resultContainer.html('<i class="acymicon-check-circle acym__color__green"></i>');
                }
            });
        });
    }

    summary();
    refreshArchive();
});
