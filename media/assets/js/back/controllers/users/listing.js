jQuery(function($) {

    function Init() {
        setButtonAddSubscriptionUser();
        setCheckAll();
        setShowSubscription();
        setListingListFilter();
    }

    Init();

    function setButtonAddSubscriptionUser() {
        let spanNumberList = $('#acym__users__listing__number_to_add_to_list');
        let buttonAddToList = $('#acym__users__listing__button--add-to-list');
        if (spanNumberList.html() == '0') {
            buttonAddToList.attr('disabled', 'disabled');
        }
        spanNumberList.off('DOMSubtreeModified').on('DOMSubtreeModified', function () {
            if (spanNumberList.html() == '0') {
                buttonAddToList.attr('disabled', 'disabled');
            } else {
                buttonAddToList.removeAttr('disabled');
            }
        });
    }

    /**
     * Check / uncheck all checkboxes on a listing with the first checkbox
     */
    function setCheckAll() {
        $('#checkbox_all').off('change').on('change', function () {
            let listing_checkboxes = $('[name="elements_checked[]"]');
            if ($(this).is(':checked')) {
                listing_checkboxes.prop('checked', true);
            } else {
                listing_checkboxes.prop('checked', false);
            }
            listing_checkboxes.trigger('change');
        });
    }

    function setShowSubscription() {
        $('.acym__user__show-subscription').off('click').on('click', function () {
            let $buttonShowSubscription = $(this);
            let $subscriptions = $buttonShowSubscription.closest('.acym__users__subscription');
            let $buttonText = $subscriptions.find('.acym__user__show-subscription-bt');
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

    function setListingListFilter() {
        let $listListsSelect = jQuery('#users_list');
        let $listStatusesSelect = jQuery('#list_status');

        $listListsSelect.find('option:selected').val() !== '0' ? $listStatusesSelect.parent().show() : $listStatusesSelect.parent().hide();

        $listListsSelect.on('change', function () {
            jQuery('#select2-users_list-container').html(jQuery(this).find('option:selected').text());
            jQuery(this).val() !== '0' ? $listStatusesSelect.parent().show() : $listStatusesSelect.parent().hide();
        });

        jQuery('#list_status').off('change').on('change', function () {
            jQuery('#select2-list_status-container').html(jQuery(this).find('option:selected').text());
        });
    }
});
