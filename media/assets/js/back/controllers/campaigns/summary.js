jQuery(function($) {
    function summary() {
        acym_helperCampaigns.setClickFlagsSummary();

        jQuery('[name="resend_target"]').on('change', function () {
            if (jQuery(this).val() === 'new') {
                jQuery('#resend_receivers_new').show();
                jQuery('#resend_receivers_all').hide();
            } else {
                jQuery('#resend_receivers_new').hide();
                jQuery('#resend_receivers_all').show();
            }
        });
    }

    summary();
});
