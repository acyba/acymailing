jQuery(document).ready(function ($) {
    const ajaxUrl = ACYM_AJAX_URL + '&ctrl=dynamics&task=trigger';

    function Init() {
        acym_helperCampaigns.initCampaigns();
        acym_helperSelectionPage.initSelectionPage();
        setShowRecipients();
        setSelectPluginField();
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

    function setSelectPluginField() {
        $('#acym_plugin_field').on('change', function () {
            if ($(this).val() == '') {
                $('#acym_div_date_field').hide();
            } else {
                findFieldByCurrentPlugin($(this).val());
            }
        });
    }

    function findFieldByCurrentPlugin(plugin) {
        acym_helper.get(ajaxUrl, {
            plugin: plugin,
            trigger: 'getJsonBirthdayField'
        }).then(response => {
            let data = acym_helper.parseJson(response);
            $('#acym_birthday_field option').remove();
            if (data.fields.length !== 0) {
                $('#acym_birthday_field').append($('<option></option>').attr('value', '').text(ACYM_JS_TXT.ACYM_SELECT_FIELD));
                for (const [key, value] of Object.entries(data.fields)) {
                    $('#acym_birthday_field').append($('<option></option>').attr('value', key).text(value));
                }
            } else {
                $('#acym_birthday_field').append($('<option></option>').attr('value', '').text(ACYM_JS_TXT.ACYM_NO_FIELD_AVAILABLE));
            }

            $('#acym_div_date_field').show();
        });
    }

    Init();
});
