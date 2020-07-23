jQuery(document).ready(function ($) {

    function Init() {
        setToggleCampaignSummaryGenerated();
    }

    Init();

    function setToggleCampaignSummaryGenerated() {
        $('.acym__campaign__summary__generated__mail__toogle__preview').off('click').on('click', function () {
            let $preview = $('#acym__wysid__email__preview');
            if ($preview.hasClass('acym__wysid__email__preview__extend')) {
                $preview.removeClass('acym__wysid__email__preview__extend');
            } else {
                $preview.addClass('acym__wysid__email__preview__extend');
            }
        });
    }
});
