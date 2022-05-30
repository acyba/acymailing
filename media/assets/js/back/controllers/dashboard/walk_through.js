jQuery(document).ready(function ($) {
    function Init() {
        attachLicence();
        activateCron();
        setSendingMethodSwitch();
        setReplyToInformation();
        setSubscribeUser();
        setWalkthroughList();
        setStepFailToggle();
        setChoiceWalkthroughResult();
        setPreventSubmit();
        acym_helperEditorWysid.initEditor();
        acym_helperMailer.setTestCredentialsSendingMethods();
        acym_helperMailer.setButtonCopyFromPlugin();
        acym_helperMailer.setSynchroExistingUsers();
        acym_helperSelectionPage.setSelectionElement(true, false, undefined, '#acym__selection__button-select');
    }

    function setSendingMethodSwitch() {
        let $method = $('input[name="config[mailer_method]"]');
        $method.on('change', function () {
            $('.send_settings').hide();
            let selected = $('input[name="config[mailer_method]"]:checked').val();
            const $settings = $(`#${selected}_settings`);
            if ($settings.length > 0) {
                $settings.show();
                jQuery('#acym__selection__button-select').removeAttr('disabled');
            }
        });

        $method.trigger('change');
    }

    Init();

    function setReplyToInformation() {
        $('#acym__walk-through-1__content__toggle-reply-to__checkbox').off('change').on('change', function () {
            $('.acym__walk-through-1__content__reply-to').toggle();
        });
    }

    function setSubscribeUser() {
        $('#acym__subscribe__news').on('click', function () {
            let emailUser = $('input[type="email"]').val();
            if (!acym_helper.emailValid(emailUser)) {
                alert(ACYM_JS_TXT.email);
                return false;
            }

            $(this).attr('disabled', 'true');
            let ajaxUrl = AJAX_URL_UPDATEME + 'subscription&task=subscribe&email=' + emailUser + '&cms=' + ACYM_CMS;

            $.get(ajaxUrl);
            $('.acy_button_submit').trigger('click');
        });
    }

    function setWalkthroughList() {
        reloadDeleteAddress();

        $('#acym__walkthrough__list__new').on('click', function () {
            $(this).hide();
            $('#acym__walkthrough__list__add-zone').show();
            $('#acym__walkthrough__list__new-address').focus();
        });

        $('#acym__walkthrough__list__add').on('click', function () {
            let $address = $('#acym__walkthrough__list__new-address');
            let enteredAddress = $address.val();
            if (acym_helper.emailValid(enteredAddress)) {
                let email = '<input type="hidden" name="addresses[]" value="' + enteredAddress + '"/>' + enteredAddress;
                let deleteIcon = '<i class="acymicon-remove acym__walkthrough__list__receivers__remove"></i>';

                $('#acym__walkthrough__list__receivers').append('<tr><td>' + email + '</td><td>' + deleteIcon + '</td></tr>');
                $address.val('');

                $('#acym__walkthrough__list__add-zone').hide();
                $('#acym__walkthrough__list__new').show();

                reloadDeleteAddress();
            } else {
                alert(ACYM_JS_TXT.email);
                return false;
            }
        });

        $('#acym__walkthrough__list__new-address').on('keypress', function (e) {
            if ('Enter' === e.key) {
                $('#acym__walkthrough__list__add').trigger('click');
                return false;
            }
        });
    }

    function reloadDeleteAddress() {
        $('.acym__walkthrough__list__receivers__remove').off('click').on('click', function () {
            $(this).closest('tr').remove();
        });
    }

    function setChoiceWalkthroughResult() {
        $('.acym__walkthrough__result__choice__one').off('click').on('click', function () {
            if ($(this).hasClass('selected')) return true;

            $('.acym__walkthrough__result__choice__one').removeClass('selected');
            $(this).addClass('selected');

            if ($(this).attr('id') === 'acym__walkthrough__result__choice__no') {
                $('#acym__walkthrough__result__spam').addClass('visible');
            } else {
                $('#acym__walkthrough__result__spam').removeClass('visible');
            }

            $('input[name="result"]').val($(this).attr('data-value'));
            $('[data-task="saveStepResult"]').attr('disabled', false);
        });
    }

    function setStepFailToggle() {
        $('#acym__walkthrough__skip__fail').off('click').on('click', function () {
            $('[required]').removeAttr('required');
            $('[type="email"]').attr('type', 'text');
            $('#acym__walkthrough__skip').trigger('click');
        });
        $('.acym__walkthrough__fail__choice').off('click').on('click', function () {
            let $divToShow = $('.acym__walkthrough__fail__' + $(this).attr('data-show'));
            if ($divToShow.is(':visible')) return true;
            $('.selected').removeClass('selected');
            $(this).addClass('selected');
            $('.acym__walkthrough__fail__toggle-div').hide();
            $divToShow.show();
            $('[name="choice"]').val($(this).attr('data-show'));
            if ($('.acym__walkthrough__fail__contact').is(':visible')) {
                $('[name="email"]').attr('required', 'true');
            } else {
                $('[name="email"]').removeAttr('required');
            }
        });
    }

    $.walkthroughList = function () {
        if ($('.acym__walkthrough__list__receivers__remove').length === 0) {
            alert(ACYM_JS_TXT.ACYM_AT_LEAST_ONE_USER);
            return false;
        }
        return true;
    };

    function setPreventSubmit() {
        $('.acym__walkthrough__fail__gmail input').off('keypress').on('keypress', function (e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                return false;
            }
        });
    }

    function attachLicence() {
        $('#acym__walk_through_license__button__license').on('click', function () {
            let $iconWait = jQuery('#acym__walkthrough__step_license__wait_attach_license_icon');
            $iconWait.removeClass('is-hidden');

            const data = {
                ctrl: 'dashboard',
                task: 'stepLicenseAttachLicense',
                licenseKey: jQuery('#acym__configuration__license-key').val()
            };

            acym_helper.post(ACYM_AJAX_URL, data).then(response => {
                let labelLicenseStatus = document.getElementById('acym__walk_through_license__licenseStatus');

                if (!response.error) {
                    $('#acym__walk_through_license__button__cron').removeAttr('disabled');
                    $('.acym__tooltip_button__cron').css('display', 'none');
                    labelLicenseStatus.innerHTML = ACYM_JS_TXT.ACYM_LICENSE_ACTIVATED;
                    labelLicenseStatus.classList.add('acym__color__green');
                    labelLicenseStatus.classList.remove('acym__color__red');
                } else {
                    labelLicenseStatus.innerHTML = response.message;
                    labelLicenseStatus.classList.remove('acym__color__green');
                    labelLicenseStatus.classList.add('acym__color__red');
                }
                $iconWait.addClass('is-hidden');
            });
        });
    }

    function activateCron() {
        $('#acym__walk_through_license__button__cron').on('click', function () {
            if (jQuery('#acym__walk_through_license__button__cron').attr('disabled') === 'disabled') {
                return;
            }

            let $iconWait = jQuery('#acym__walkthrough__step_license__wait_active_cron_icon');
            $iconWait.removeClass('is-hidden');

            const data = {
                ctrl: 'dashboard',
                task: 'stepLicenseActivateCron',
                licenseKey: jQuery('#acym__configuration__license-key').val()
            };

            acym_helper.post(ACYM_AJAX_URL, data).then(response => {
                let cronStatus = document.getElementById('acym__walk_through_license__cron_label');

                if (!response.error) {
                    cronStatus.innerHTML = ACYM_JS_TXT.ACYM_ACTIVATED;
                    cronStatus.classList.add('acym__color__green');
                    cronStatus.classList.remove('acym__color__red');
                } else {
                    cronStatus.innerHTML = response.message;
                    cronStatus.classList.remove('acym__color__green');
                    cronStatus.classList.add('acym__color__red');
                }
                $iconWait.addClass('is-hidden');
            });
        });
    }
});
