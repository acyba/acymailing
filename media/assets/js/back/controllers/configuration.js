jQuery(document).ready(function ($) {
    function Configuration() {
        setPlatformSwitchConfiguration();
        setSendingMethodSwitchConfiguration();
        setCheckPortConfiguration();
        setDKIMSelectConfiguration();
        setTimeoutCheckConfiguration();
        setCheckDBConfiguration();
        setOnChangeAutomaticBounce();
        setIntroJSConfig();
        setOrderWarning();
        setSelect2ChooseEmails();
        setAttachLicenseKey();
        setModifyCron();
    }

    Configuration();

    function setOrderWarning() {
        $('#sendorderid').on('change', function () {
            if (this.value == 'rand') {
                alert(ACYM_JS_TXT.ACYM_NO_RAND_FOR_MULTQUEUE);
            }
        });
    }

    function setIntroJSConfig() {
        if ($('.acym__configuration__mail-settings').is(':visible')) {
            acym_helperIntroJS.introContent = [
                {
                    element: '.acym__configuration__mail-settings',
                    text: ACYM_JS_TXT.ACYM_INTRO_MAIL_SETTINGS,
                    position: 'top',
                },
                {
                    element: '.acym__configuration__advanced',
                    text: ACYM_JS_TXT.ACYM_INTRO_ADVANCED,
                    position: 'top',
                },
            ];
            acym_helperIntroJS.setIntrojs('configuration__mail-settings');
        }
        $('[name="config[dkim]"]').on('change', function () {
            acym_helperIntroJS.introContent = [
                {
                    element: '.acym__configuration__dkim',
                    text: ACYM_JS_TXT.ACYM_INTRO_DKIM,
                    position: 'top',
                },
            ];
            acym_helperIntroJS.setIntrojs('configuration__dkim');
        });
        if ($('.acym__configuration__cron').is(':visible')) {
            acym_helperIntroJS.introContent = [
                {
                    element: '.acym__configuration__cron',
                    text: ACYM_JS_TXT.ACYM_INTRO_CRON,
                },
            ];
            acym_helperIntroJS.setIntrojs('configuration__cron');
        }
        if ($('.acym__configuration__subscription').is(':visible')) {
            acym_helperIntroJS.introContent = [
                {
                    element: '.acym__configuration__subscription',
                    text: ACYM_JS_TXT.ACYM_INTRO_SUBSCRIPTION,
                },
            ];
            acym_helperIntroJS.setIntrojs('configuration__subscription');
        }
        if ($('.acym__configuration__check-database').is(':visible')) {
            acym_helperIntroJS.introContent = [
                {
                    element: '.acym__configuration__check-database',
                    text: ACYM_JS_TXT.ACYM_INTRO_CHECK_DATABASE,
                },
            ];
            acym_helperIntroJS.setIntrojs('configuration__check-database');
        }
        $('.acym_tab').on('click', function () {
            setTimeout(function () {
                setIntroJSConfig();
            }, 500);
        });
    }

    function setPlatformSwitchConfiguration() {
        var $platform = $('input[name="config[sending_platform]"]');
        $platform.on('change', function () {
            var $selected = $('input[name="config[sending_platform]"]:checked').val();

            if ($selected == 'server') {
                $('i[data-radio="config_mailer_methodelasticemail"], label[for="config_mailer_methodelasticemail"]').addClass('is-hidden');
                $('i[data-radio="config_mailer_methodsmtp"], label[for="config_mailer_methodsmtp"]').addClass('is-hidden');

                $('i[data-radio="config_mailer_methodqmail"], label[for="config_mailer_methodqmail"]').removeClass('is-hidden').show();
                $('i[data-radio="config_mailer_methodsendmail"], label[for="config_mailer_methodsendmail"]').removeClass('is-hidden').show();
                $('i[data-radio="config_mailer_methodphpmail"], label[for="config_mailer_methodphpmail"]').removeClass('is-hidden').show().click();
            } else {
                $('i[data-radio="config_mailer_methodqmail"], label[for="config_mailer_methodqmail"]').addClass('is-hidden');
                $('i[data-radio="config_mailer_methodsendmail"], label[for="config_mailer_methodsendmail"]').addClass('is-hidden');
                $('i[data-radio="config_mailer_methodphpmail"], label[for="config_mailer_methodphpmail"]').addClass('is-hidden');

                $('i[data-radio="config_mailer_methodelasticemail"], label[for="config_mailer_methodelasticemail"]').removeClass('is-hidden').show();
                $('i[data-radio="config_mailer_methodsmtp"], label[for="config_mailer_methodsmtp"]').removeClass('is-hidden').show().click();
            }
        });

        var $selected = $('input[name="config[mailer_method]"]:checked').val();
        if ($selected == 'phpmail' || $selected == 'sendmail' || $selected == 'qmail') {
            $('#config_sending_platformserver').click();
        } else {
            $('#config_sending_platformexternal').click();
        }

        $platform.trigger('change');
        $('#config_mailer_method' + $selected).click();
    }

    function setSendingMethodSwitchConfiguration() {
        var $method = $('input[name="config[mailer_method]"]');
        $method.on('change', function () {
            $('.send_settings').hide();
            var $selected = $('input[name="config[mailer_method]"]:checked').val();

            if ($selected == 'sendmail') {
                $('#sendmail_settings').show();
            }
            if ($selected == 'smtp') {
                $('#smtp_settings').show();
            }
            if ($selected == 'elasticemail') {
                $('#elastic_settings').show();
            }
        });

        $method.trigger('change');
    }

    function setCheckPortConfiguration() {
        $('#available_ports_check').off('click').on('click', function (e) {
            e.preventDefault();

            var $container = $(this).parent();
            $container.html('<i class="acymicon-circle-o-notch acymicon-spin"></i>');

            $.get(ACYM_AJAX_URL + '&ctrl=configuration&task=ports', function (response) {
                $container.html(response);
            });
        });
    }

    function setDKIMSelectConfiguration() {
        $('.acym_autoselect').off('click').on('click', function () {
            this.select();
        });
    }

    function setTimeoutCheckConfiguration() {
        $('#timeoutcheck_action').off('click').on('click', function (e) {
            e.preventDefault();

            try {
                $('#timeoutcheck').html('<i class="acymicon-circle-o-notch acymicon-spin"></i>');

                $.get(ACYM_AJAX_URL + '&ctrl=configuration&task=detecttimeout', function (response) {
                    $('#timeoutcheck').html('Done!');
                });
            } catch (err) {
                alert('Could not load the max execution time value : ' + err);
            }
        });
    }

    function setCheckDBConfiguration() {
        $('#checkdb_button').off('click').on('click', function (e) {
            e.preventDefault();

            $('#checkdb_report').html('<i class="acymicon-circle-o-notch acymicon-spin"></i>');

            $.get(ACYM_AJAX_URL + '&ctrl=configuration&task=checkDB', function (response) {
                $('#checkdb_report').html(response);
            });
        });
    }

    function setOnChangeAutomaticBounce() {
        $('input[name="config[auto_bounce]"]').off('change').on('change', function () {
            $('#acym__configuration__bounce__auto_bounce__configuration').toggle();
        });
    }


    function setSelect2ChooseEmails() {
        var $selectMultipleTags = $('#acym__configuration__cron__report--send-to');

        var $placeholderSelect = $selectMultipleTags.attr('placeholder');

        $selectMultipleTags.select2({
            width: '100%',
            placeholder: $placeholderSelect,
            tags: true,
            tokenSeparators: [
                ',',
                ' ',
            ],
            createTag: function (params) {
                var term = $.trim(params.term);

                if (!acym_helper.emailValid(term)) {
                    return null;
                }

                return {
                    id: term,
                    text: term,
                    newTag: true,
                };
            },
        });
    }

    function setAttachLicenseKey() {
        $('#acym__configuration__button__license').off('click').on('click', function () {
            let licenseKey = $('#acym__configuration__license-key').val();
            let alreadyLinked = parseInt($(this).attr('data-acym-linked'));

            if (licenseKey === '') {
                alert(ACYM_JS_TXT.ACYM_PLEASE_SET_A_LICENSE_KEY);
                return false;
            }

            let functionToCall = alreadyLinked ? 'unlinkLicense' : 'attachLicense';
            $('[name="task"]').val(functionToCall);

            $('#acym_form').submit();
        });
    }

    function setModifyCron() {
        $('#acym__configuration__button__cron').off('click').on('click', function () {
            let alreadyActive = parseInt($(this).attr('data-acym-active'));

            let functionToCall = alreadyActive ? 'deactivateCron' : 'activateCron';
            $('[name="task"]').val(functionToCall);

            $('#acym_form').submit();
        });
    }
});
