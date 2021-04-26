jQuery(document).ready(function ($) {
    function Configuration() {
        setPlatformSwitchConfiguration();
        setSendingMethodSwitchConfiguration();
        setCheckPortConfiguration();
        setDKIMSelectConfiguration();
        setTimeoutCheckConfiguration();
        setCheckDBConfiguration();
        setOnChangeAutomaticBounce();
        setOrderWarning();
        setSelect2ChooseEmails();
        setAttachLicenseKey();
        setModifyCron();
        setMultilingualOptions();
        setAcl();
        acym_helperMailer.setTestCredentialsSendingMethods();
        acym_helperMailer.setButtonCopyFromPlugin();
        acym_helperMailer.setSynchroExistingUsers();
        acym_helperSelectionPage.setSelectionElement(true, true, setEmbedImageToggle);
        setEmbedImageToggle();
        acym_helperSelectionMultilingual.init('configuration');
        resetSubmitButtons();
        setAllowedHostsMultipleSelect();
    }

    Configuration();

    function setOrderWarning() {
        $('#sendorderid').on('change', function () {
            if (this.value === 'rand') {
                alert(ACYM_JS_TXT.ACYM_NO_RAND_FOR_MULTQUEUE);
            }
        });
    }

    function setPlatformSwitchConfiguration() {
        let $platform = $('input[name="config[sending_platform]"]');
        $platform.on('change', function () {
            let $selected = $('input[name="config[sending_platform]"]:checked').val();

            if ($selected === 'server') {
                $('i[data-radio="config_mailer_methodelasticemail"], label[for="config_mailer_methodelasticemail"]').addClass('is-hidden');
                $('i[data-radio="config_mailer_methodsmtp"], label[for="config_mailer_methodsmtp"]').addClass('is-hidden');
                $('i[data-radio="config_mailer_methodsendgrid"], label[for="config_mailer_methodsendgrid"]').addClass('is-hidden');
                $('i[data-radio="config_mailer_methodsendinblue"], label[for="config_mailer_methodsendinblue"]').addClass('is-hidden');

                $('i[data-radio="config_mailer_methodqmail"], label[for="config_mailer_methodqmail"]').removeClass('is-hidden').show();
                $('i[data-radio="config_mailer_methodsendmail"], label[for="config_mailer_methodsendmail"]').removeClass('is-hidden').show();
                $('i[data-radio="config_mailer_methodphpmail"], label[for="config_mailer_methodphpmail"]').removeClass('is-hidden').show().click();
            } else {
                $('i[data-radio="config_mailer_methodqmail"], label[for="config_mailer_methodqmail"]').addClass('is-hidden');
                $('i[data-radio="config_mailer_methodsendmail"], label[for="config_mailer_methodsendmail"]').addClass('is-hidden');
                $('i[data-radio="config_mailer_methodphpmail"], label[for="config_mailer_methodphpmail"]').addClass('is-hidden');

                $('i[data-radio="config_mailer_methodsendinblue"], label[for="config_mailer_methodsendinblue"]').removeClass('is-hidden').show();
                $('i[data-radio="config_mailer_methodsendgrid"], label[for="config_mailer_methodsendgrid"]').removeClass('is-hidden').show();
                $('i[data-radio="config_mailer_methodelasticemail"], label[for="config_mailer_methodelasticemail"]').removeClass('is-hidden').show();
                $('i[data-radio="config_mailer_methodsmtp"], label[for="config_mailer_methodsmtp"]').removeClass('is-hidden').show().click();
            }
        });

        let $selected = $('input[name="config[mailer_method]"]:checked').val();
        if ($selected === 'phpmail' || $selected === 'sendmail' || $selected === 'qmail') {
            $('#config_sending_platformserver').click();
        } else {
            $('#config_sending_platformexternal').click();
        }

        $platform.trigger('change');
        $('#config_mailer_method' + $selected).click();
    }

    function setSendingMethodSwitchConfiguration() {
        let $method = $('input[name="config[mailer_method]"]');
        $method.on('change', function () {
            $('.send_settings').hide();
            let selected = $('input[name="config[mailer_method]"]:checked').val();
            const $settings = $(`#${selected}_settings`);
            if ($settings.length > 0) {
                $settings.show();
            }
        });

        $method.trigger('change');
    }

    function setCheckPortConfiguration() {
        $('#available_ports_check').off('click').on('click', function (e) {
            e.preventDefault();

            let $container = $(this).parent();
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
                alert(acym_helper.sprintf(ACYM_JS_TXT.ACYM_MAX_EXEC_TIME_GET_ERROR, err));
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
        let $selectMultipleTags = $('#acym__configuration__cron__report--send-to');

        let $placeholderSelect = $selectMultipleTags.attr('placeholder');

        $selectMultipleTags.select2({
            width: '100%',
            placeholder: $placeholderSelect,
            tags: true,
            theme: 'foundation',
            tokenSeparators: [
                ',',
                ' '
            ],
            createTag: function (params) {
                let term = $.trim(params.term);

                if (!acym_helper.emailValid(term)) {
                    return null;
                }

                return {
                    id: term,
                    text: term,
                    newTag: true
                };
            }
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

            $.acymConfigSave();

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

    function setMultilingualOptions() {
        let previousOption = '';
        $('#configmultilingual_default')
            .on('change', function () {
                let newOption = $(this).val();
                let $langSelectionOption = $('#configmultilingual_languages');

                // Disable the selected option and unselect it
                $langSelectionOption
                    .find('option[value="' + newOption + '"]')
                    .prop('disabled', true)
                    .prop('selected', false);

                // Enabled back the previously selected option
                $langSelectionOption
                    .find('option[value="' + previousOption + '"]')
                    .prop('disabled', false);

                // Reload the select
                $langSelectionOption
                    .select2({
                        theme: 'foundation',
                        width: '100%'
                    });

                previousOption = newOption;
            })
            .trigger('change');

        $.acymConfigSave = function () {
            if ($('[name="config[sender_info_translation]"]').length > 0) {
                acym_helperSelectionMultilingual.changeLanguage_configuration(acym_helperSelectionMultilingual.mainLanguage);
            }

            // Update delay fields (bounce and queue process)
            $('input[id^="delayvar"]').trigger('change');

            // ACL handling
            $('input[id^="config_acl_"][id$="custom"]').each(function () {
                let checked = jQuery('input[name="' + jQuery(this).attr('name') + '"]:checked').val();
                if (checked === 'custom') return;

                jQuery(this).closest('.cell').find('div[id^="acl_"][id$="_container"]').remove();
            });

            // Multilingual options
            let currentLanguages = $('#configmultilingual_languages').val();
            let previousLanguages = $('[name="previous_multilingual_languages"]').val();
            if (acym_helper.empty(previousLanguages)) return true;
            previousLanguages = previousLanguages.split(',');

            let removedLanguages = acym_helper.empty(currentLanguages) ? previousLanguages : previousLanguages.filter(x => !currentLanguages.includes(x));
            if (acym_helper.empty(removedLanguages)) return true;

            $.each(removedLanguages, function (key) {
                removedLanguages[key] = $('#configmultilingual_default option[value="' + removedLanguages[key] + '"]').text();
            });

            return acym_helper.confirm(acym_helper.sprintf(ACYM_JS_TXT.ACYM_REMOVE_LANG_CONFIRMATION, removedLanguages.join(', ')));
        };
    }

    function setAcl() {
        let aclZone = $('#acym__configuration__acl__zone');
        $('#acym__configuration__acl__toggle').off('click').on('click', function () {
            aclZone.slideToggle();
        }).click();

        $('input[name^="config[acl_"]').on('change', function () {
            let aclName = $(this).attr('name');
            let $choicesContainer = $('#' + aclName.substring(7, aclName.length - 1) + '_container');
            if (!$choicesContainer.length) return;

            if ($('input[name="' + aclName + '"]:checked').val() === 'all') {
                $choicesContainer.addClass('is-hidden');
            } else {
                $choicesContainer.removeClass('is-hidden');
            }
        });
    }

    function setOptionDisabled(paramsId, optionName) {
        let params = $(`#${paramsId}`).val();
        params = acym_helper.parseJson(params);

        let $selectedCard = $('.acym__sending__methods__choose .acym__selection__card-selected');
        if ($selectedCard.length === 0) return;

        let $embedImageToggle = $(`[name="config[${optionName}]"]`);
        let $info = $embedImageToggle.closest('.acym__configuration__mail__option').find('.acym__configuration__mail__info__disabled');
        let $switchLabel = $embedImageToggle.closest('.acym__configuration__mail__option').find('> .switch-label');
        let $switchPaddle = $embedImageToggle.closest('.acym__configuration__mail__option').find('.switch-paddle');

        if (undefined !== params[$selectedCard.attr('id')] && !params[$selectedCard.attr('id')]) {
            if (parseInt($embedImageToggle.val()) === 1) $switchLabel.click();
            $switchPaddle.addClass('disabled').attr('data-acym-tooltip', $info.find('.acym__tooltip__text ').html());
            $embedImageToggle.next().attr('disabled', 'true');
            $info.closest('.acym__tooltip__info').show();
        } else {
            $embedImageToggle.next().removeAttr('disabled');
            $switchPaddle.removeClass('disabled').removeAttr('data-acym-tooltip');
            $info.closest('.acym__tooltip__info').hide();
        }
        acym_helperTooltip.setTooltip();
    }

    function setEmbedImageToggle() {
        setOptionDisabled('acym__config__mail__embed__image__blocked', 'embed_images');
        setOptionDisabled('acym__config__mail__embed__attachment__blocked', 'embed_files');
    }


    function resetSubmitButtons() {
        $('[data-task="downloadExportChangesFile"]').on('click', function () {
            setTimeout(function () {
                $('#formSubmit')[0].disabled = false;
                $('[name="task"]').val('');
            }, 5);
        });
    }

    function setAllowedHostsMultipleSelect() {
        let $multipleSelect = $('.acym__allowed__hosts__select');

        $multipleSelect.select2({
            width: '100%',
            placeholder: $multipleSelect.attr('placeholder'),
            tags: true,
            theme: 'foundation',
            tokenSeparators: [' '],
            createTag: function (params) {
                let term = jQuery.trim(params.term);

                if (term === '') {
                    return null;
                }

                return {
                    id: term,
                    text: term
                };
            }
        });
    }
});
