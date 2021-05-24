jQuery(document).ready(function ($) {

    function Init() {
        acym_helperCampaigns.initCampaigns();
        acym_helperSelectionPage.initSelectionPage();
        setShowRecipients();
    }

    function setShowRecipients() {
        $('.acym__campaign__show-subscription').off('click').on('click', function () {
            let $buttonShowSubscription = $(this);
            let $subscriptions = $buttonShowSubscription.closest('.acym__campaign__recipients');
            let $buttonText = $subscriptions.find('.acym__campaign__show-subscription-bt');
            $subscriptions = $subscriptions.find('.acym_subscription_more');
            if ($buttonShowSubscription.attr('data-iscollapsed') == 0) {
                $buttonShowSubscription.attr('data-iscollapsed', '1').hide();
                $buttonText.text('<');
                $subscriptions.fadeIn('slow').css('display', 'inline-block');
                $buttonShowSubscription.fadeIn('slow');
            } else {
                $buttonShowSubscription.attr('data-iscollapsed', '0').hide();
                $subscriptions.fadeOut('slow', function () {
                    $buttonText.text('+' + $buttonShowSubscription.attr('acym-data-value'));
                    $buttonShowSubscription.fadeIn('fast');
                });
            }
        });
    }

    Init();
});
