jQuery(document).ready(function ($) {

    function Init() {
        setIntroJSCampaign();
        setSpamTest();
        sendTestAjax();
    }

    Init();

    function sendTestAjax() {
        $('#acym__campaign__send-test').off('click').on('click', function () {
            $('#acym__campaigns__send-test__spinner').show();
            let test = $(this);
            test.attr('disabled', 'true');
            let url = ACYM_AJAX_URL + '&page=acymailing_campaigns&ctrl=campaigns&task=test&id=' + $('input[name="id"]').val() + '&test_note=' + encodeURIComponent(jQuery('#acym__wysid__send__test__note').val()) + '&test_emails=' + encodeURIComponent($('.acym__multiselect__email').val().join(','));
            $.post(url, function (res) {
                test.removeAttr('disabled');
                $('#acym__campaigns__send-test__spinner').hide();
                res = JSON.parse(res);
                acym_helperNotification.addNotification(res.message, res.type);
            });
        });
    }

    function setIntroJSCampaign() {
        acym_helperIntroJS.introContent = [
            {
                element: '#spam_test_zone',
                text: ACYM_JS_TXT.ACYM_INTRO_SAFE_CHECK,
                position: 'right',
            },
        ];
        acym_helperIntroJS.setIntrojs('campaigns_tests');
    }

    function setSpamTest() {
        $('#launch_spamtest').off('click').on('click', function () {
            if ($(this).hasClass('acym_icon_disabled')) return false;

            $(this).addClass('acym_icon_disabled');

            $('.acym_icon_container').find('i:not(".acymicon-question-circle-o")').removeClass().html('').addClass('acymicon-circle-o-notch acymicon-spin');
            $('.acym_check_results').hide().html('');

            $('#acym_spam_test_details').addClass('is-hidden');
            $('#safe_check_results').removeClass('is-hidden');

            handleAjaxResult('Content', 'check_words', 'checkContent');
            handleAjaxResult('Links', 'check_links', 'checkLinks');
            if ($('#check_spam').find('i').hasClass('acymicon-question-circle-o')) {
                $('.acym__campaigns__test__pro').toggle();
            } else {
                handleAjaxResult('SPAM', 'check_spam', 'checkSPAM');
            }

        });

        $('#acym_spam_test_details button').off('click').on('click', function () {
            $('#check_spam').click();
        });
    }

    let _spamtestStep = 0;

    function handleAjaxResult(xhr, check, task) {
        let campaignID = $('input[name="id"]').val();
        let $check = $('#' + check).removeClass('acym_clickable').off('click');
        let $checkI = $check.find('.acym_icon_container i');

        $.ajax({
            url: ACYM_AJAX_URL + '&ctrl=campaigns&task=' + task + '&id=' + campaignID,
            success: function (data) {

                if (xhr === 'SPAM') {
                    let result = $.parseJSON(data);
                    if (result.type === 'success') {
                        $check.next('.acym_check_results').html(ACYM_JS_TXT.ACYM_TESTS_SPAM_SENT).show();

                        let nbTesterCalls = 0;
                        let testerCall = setInterval(function () {
                            nbTesterCalls++;
                            $.getJSON(result.message + '&format=json', function (data) {
                                if (data.status !== false) {
                                    clearInterval(testerCall);
                                    $check.next('.acym_check_results').hide();
                                    $('#acym_spam_test_details').removeClass('is-hidden');

                                    if (data.mark > -2) {
                                        $checkI.removeClass().addClass('acymicon-check-circle acym_icon_green');
                                    } else {
                                        $checkI.removeClass().addClass('acymicon-exclamation-circle acym_icon_red');
                                    }

                                    if (!$check.attr('data-open')) {
                                        $check.attr('data-open', $check.attr('data-iframe'));
                                    }
                                    $check.addClass('acym_clickable').attr('data-iframe', result.message + '&lang=' + result.lang);
                                    acym_helperModal.initModal();
                                } else if (nbTesterCalls > 5) {
                                    $check.next('.acym_check_results').html(data.title);
                                    $check.addClass('acym_clickable').on('click', function () {
                                        $(this).next('.acym_check_results').slideToggle();
                                    });
                                    $checkI.removeClass().addClass('acymicon-exclamation-circle acym_icon_red');
                                    clearInterval(testerCall);
                                }
                            });
                        }, 10000);
                    } else {
                        $check.next('.acym_check_results').html(result.message);
                        $check.addClass('acym_clickable').on('click', function () {
                            $(this).next('.acym_check_results').slideToggle();
                        });
                        $checkI.removeClass().addClass('acymicon-exclamation-circle acym_icon_red');
                    }
                } else {
                    if (data) {
                        $checkI.removeClass().addClass('acymicon-exclamation-circle acym_icon_red');
                        $check.addClass('acym_clickable').on('click', function () {
                            $(this).next('.acym_check_results').slideToggle();
                        });
                        $check.next('.acym_check_results').html(data);
                    } else {
                        $checkI.removeClass().addClass('acymicon-check-circle acym_icon_green');
                    }
                }

                _spamtestStep++;
                if (_spamtestStep === 3) {
                    _spamtestStep = 0;
                    $('#launch_spamtest').removeClass('acym_icon_disabled');
                }
            },
        });
    }
});
