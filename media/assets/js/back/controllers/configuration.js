jQuery(function ($) {
    function Configuration() {
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
        acym_helperSelectionPage.setSelectionElement(true, true, callbackSendSettingsClicked);
        setEmbedImageToggle();
        acym_helperSelectionMultilingual.init('configuration');
        resetSubmitButtons();
        setAllowedHostsMultipleSelect();
        acym_helperMailer.displayAuth2Params();
        acym_helperMailer.acymailerAddDomains();
        acym_helperMailer.displayCnameRecord();
        acym_helperMailer.deleteDomain();
        acym_helperMailer.domainSuggestion();
        acym_helperMailer.updateStatus();
        catchEnterAcymailerDomains();
        activateAcymailer();
        setAclMultiselect();
        setAttachmentsPositionToggle();
    }

    Configuration();

    function setOrderWarning() {
        $('#sendorderid').on('change', function () {
            if (this.value === 'rand') {
                alert(ACYM_JS_TXT.ACYM_NO_RAND_FOR_MULTQUEUE);
            }
        });
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


            // Multilingual options
            let currentLanguages = $('#configmultilingual_languages').val();
            let previousLanguages = $('[name="previous_multilingual_languages"]').val();
            if (acym_helper.empty(previousLanguages)) {
                return true;
            }
            previousLanguages = previousLanguages.split(',');

            let removedLanguages = acym_helper.empty(currentLanguages) ? previousLanguages : previousLanguages.filter(x => !currentLanguages.includes(x));
            if (acym_helper.empty(removedLanguages)) {
                return true;
            }

            $.each(removedLanguages, function (key) {
                removedLanguages[key] = $('#configmultilingual_default option[value="' + removedLanguages[key] + '"]').text();
            });

            if (acym_helper.confirm(acym_helper.sprintf(ACYM_JS_TXT.ACYM_REMOVE_LANG_CONFIRMATION, removedLanguages.join(', ')))) {
                return true;
            } else {
                return false;
            }
        };
    }

    function setAcl() {
        let aclZone = $('#acym__configuration__acl__zone');
        $('#acym__configuration__acl__toggle').off('click').on('click', function () {
            aclZone.slideToggle();
        }).trigger('click');
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
            if (parseInt($embedImageToggle.val()) === 1) $switchLabel.trigger('click');
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

    function callbackSendSettingsClicked(element) {
        const settings = document.querySelector(`#${element.id}_settings`);
        if (settings) {
            settings.scrollIntoView({
                behavior: 'smooth',
                block: 'center'
            });
        }
        setEmbedImageToggle();
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

    function catchEnterAcymailerDomains() {
        $('#acymailer_domain').on('keypress', function (event) {
            if (event.key !== 'Enter') {
                return;
            }
            event.preventDefault();
            $('#acym__configuration__sending__method-addDomain').trigger('click');
        });
    }

    function activateAcymailer() {
        jQuery('#acym__configuration__activate__acymailer').off('click').on('click', function () {
            jQuery('[data-tab-identifier="mail_settings"]').trigger('click');
            jQuery('#acymailer').trigger('click');
        });
    }

    function setAclMultiselect() {
        const $allAclMultiselect = $('select[name^="config[acl_"]');
        const aclValues = {};

        const getPageFromName = name => name.slice(4, -2);

        $allAclMultiselect.each(function () {
            aclValues[getPageFromName($(this).attr('name'))] = $(this).val();
        });

        $allAclMultiselect.on('change', function () {
            const page = getPageFromName($(this).attr('name'));
            let newValue = $(this).val();
            if (aclValues[page].includes('all') && $(this).val().includes('all') && $(this).val().length > 1) {
                newValue = $(this).val().splice($(this).val().indexOf('all') - 1, 1);
            } else if (!aclValues[page].includes('all') && $(this).val().includes('all')) {
                newValue = ['all'];
            } else if ($(this).val().length === 0) {
                newValue = ['all'];
            }

            $(this).val(newValue).trigger('change.select2');
            aclValues[page] = newValue;
        });
    }

    function setAttachmentsPositionToggle() {
        jQuery('[name="config[embed_files]"]').off('change').on('change', function () {
            // The input hidden value is changed after the on change on itself, which doesn't make sense but it is the case
            setTimeout(() => {
                const $attachmentsPositionContainer = jQuery('#attachments_position');
                if (jQuery(this).val() === '1') {
                    $attachmentsPositionContainer.css('display', 'none');
                } else {
                    $attachmentsPositionContainer.css('display', '');
                }
            }, 10);
        });
    }
});
