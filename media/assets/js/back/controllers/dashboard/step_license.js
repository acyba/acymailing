jQuery(function ($) {
    function Init() {
        attachLicence();
        activateCron();
    }

    Init();

    function attachLicence() {
        $('#acym__walk_through_license__button__license').on('click', function () {
            const $spinner = $('#acym__walkthrough__license__spinner__attach');
            $spinner.removeClass('is-hidden');

            const data = {
                ctrl: 'dashboard',
                task: 'ajaxAttachLicense',
                licenseKey: $('#acym__walkthrough__license__key').val()
            };

            acym_helper.post(ACYM_AJAX_URL, data).then(response => {
                const labelLicenseStatus = document.getElementById('acym__walk_through_license__licenseStatus');

                if (!response.error) {
                    $('#acym__walkthrough__license__button__cron').removeAttr('disabled');
                    $('.acym__tooltip_button__cron').css('display', 'none');
                    labelLicenseStatus.innerHTML = ACYM_JS_TXT.ACYM_LICENSE_ACTIVATED;
                    labelLicenseStatus.classList.add('acym__color__green');
                    labelLicenseStatus.classList.remove('acym__color__red');
                } else {
                    labelLicenseStatus.innerHTML = response.message;
                    labelLicenseStatus.classList.remove('acym__color__green');
                    labelLicenseStatus.classList.add('acym__color__red');
                }
                $spinner.addClass('is-hidden');
            });
        });
    }

    function activateCron() {
        $('#acym__walkthrough__license__button__cron').on('click', function () {
            if ($(this).attr('disabled') === 'disabled') {
                return;
            }

            const $spinner = $('#acym__walkthrough__license__spinner__cron');
            $spinner.removeClass('is-hidden');

            const data = {
                ctrl: 'dashboard',
                task: 'ajaxActivateCron'
            };

            acym_helper.post(ACYM_AJAX_URL, data).then(response => {
                const cronStatus = document.getElementById('acym__walk_through_license__cron_label');

                if (!response.error) {
                    cronStatus.innerHTML = ACYM_JS_TXT.ACYM_ACTIVATED;
                    cronStatus.classList.add('acym__color__green');
                    cronStatus.classList.remove('acym__color__red');
                } else {
                    cronStatus.innerHTML = response.message;
                    cronStatus.classList.remove('acym__color__green');
                    cronStatus.classList.add('acym__color__red');
                }
                $spinner.addClass('is-hidden');
            });
        });
    }
});
