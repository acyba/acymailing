jQuery(function ($) {
    const defaultSendingMethodPrefix = '.acym__configuration__mail-settings';
    const smlSendingMethodPrefix = '#acym__configuration__sml__form';

    function Configuration() {
        setBounceOauth();
        setSendingMethodSwitchConfiguration();
        setSmlConfiguration();
        setSendingMethodSwitchConfiguration(smlSendingMethodPrefix, 'sml');
        setEditSml();
        setDeleteSml();
        setShowButtonSml();
        setSendingMethodSwitchConfiguration(defaultSendingMethodPrefix, 'config');
        setCheckPortConfiguration();
        setDKIMSelectConfiguration();
        setCheckDBConfiguration();
        setScanFilesConfiguration();
        setOnChangeAutomaticBounce();
        setSelect2ChooseEmails();
        setAttachLicenseKey();
        setModifyCron();
        setMultilingualOptions();
        setAcl();
        acym_helperMailer.setTestCredentialsSendingMethods();
        acym_helperMailer.setButtonCopyFromPlugin();
        acym_helperMailer.setSynchroExistingUsers();
        acym_helperSelectionPage.setSelectionElement(true, true, callbackSendSettingsClickedDefault, undefined, {prefix: defaultSendingMethodPrefix});
        acym_helperSelectionPage.setSelectionElement(true, true, callbackSendSettingsClickedSml, undefined, {prefix: smlSendingMethodPrefix});
        setEmbedImageToggle();
        acym_helperSelectionMultilingual.init('configuration');
        acym_helperSelectionMultilingual.init('configuration_subscription');
        resetSubmitButtons();
        setAllowedHostsMultipleSelect();
        setSurveyAnswerMultipleSelect();
        acym_helperMailer.acymailerAddDomains();
        acym_helperMailer.displayCnameRecord();
        acym_helperMailer.deleteDomain();
        acym_helperMailer.domainSuggestion();
        acym_helperMailer.updateStatus();
        catchEnterAcymailerDomains();
        activateAcymailer();
        setAclMultiselect();
        setAttachmentsPositionToggle();
        setAutoSendingCounter();
        testConnection();
        synchronizebounceAddressFields();
        setCustomAnswersField();
        setColorPicker();
        initButtonImage();
        setDedicatedSendProcess();
    }

    Configuration();

    function setDedicatedSendProcess() {
        $('[name="config[dedicated_send_process]"]').on('change', function (event) {
            setTimeout(() => {
                if (Number($(this).val()) === 1 && !confirm(ACYM_JS_TXT.ACYM_DEDICATED_SENDING_PROCESS_WARNING)) {
                    $(`[for="${$(this).data('switch')}"]`).trigger('click');
                }
            }, 100);
        });
    }

    function setSmlConfiguration() {
        const $allInputs = $(`${smlSendingMethodPrefix} input[name^="config["]`);
        for (let i = 0 ; i < $allInputs.length ; i++) {
            $allInputs[i].setAttribute('name', $allInputs[i].getAttribute('name').replace('config', 'sml'));
        }
    }

    function setSendingMethodSwitchConfiguration(prefix, inputName) {
        const $method = $(`input[name="${inputName}[mailer_method]"]`);
        $method.on('change', function () {
            $(`${prefix} .send_settings`).hide();
            const selected = $(`input[name="${inputName}[mailer_method]"]:checked`).val();
            const $settings = $(`${prefix} #${selected}_settings`);
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

    function setCheckDBConfiguration() {
        $('#checkdb_button').off('click').on('click', function (e) {
            e.preventDefault();

            $('#checkdb_report').html('<i class="acymicon-circle-o-notch acymicon-spin"></i>');

            $.get(ACYM_AJAX_URL + '&ctrl=configuration&task=checkDBAjax', function (response) {
                $('#checkdb_report').html(response);
            });
        });
    }

    function setScanFilesConfiguration() {
        $('#scanfiles_button').off('click').on('click', function (e) {
            e.preventDefault();

            $('#scanfiles_report').html('<i class="acymicon-circle-o-notch acymicon-spin"></i>');

            const data = {
                ctrl: 'configuration',
                task: 'scanSiteFiles'
            };
            acym_helper.get(ACYM_AJAX_URL, data).then(res => {
                $('#scanfiles_report').html(res.message);
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

            if ($('[name="config[unsub_survey_translation]"]').length > 0) {
                acym_helperSelectionMultilingual.changeLanguage_configuration_subscription(acym_helperSelectionMultilingual.mainLanguage);
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

        const $selectedCard = $('.acym__sending__methods__choose .acym__selection__card-selected');
        if ($selectedCard.length === 0) {
            return;
        }

        const $embedImageToggle = $(`[name="config[${optionName}]"]`);
        const $info = $embedImageToggle.closest('.acym__configuration__mail__option').find('.acym__configuration__mail__info__disabled');
        const $switchLabel = $embedImageToggle.closest('.acym__configuration__mail__option').find('> .switch-label');
        const $switchPaddle = $embedImageToggle.closest('.acym__configuration__mail__option').find('.switch-paddle');

        if (undefined !== params[$selectedCard.attr('data-acym-method')] && !params[$selectedCard.attr('data-acym-method')]) {
            if (parseInt($embedImageToggle.val()) === 1) {
                $switchLabel.trigger('click');
            }
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

    function callbackSendSettingsClickedDefault(element) {
        callbackSendSettingsClicked(element, '.acym__configuration__mail-settings');
        setEmbedImageToggle();
    }

    function callbackSendSettingsClickedSml(element) {
        callbackSendSettingsClicked(element, '#acym__configuration__sml__form');
    }

    function callbackSendSettingsClicked(element, prefix) {
        const settings = document.querySelector(`${prefix} #${element.id}_settings`);
        if (settings) {
            settings.scrollIntoView({
                behavior: 'smooth',
                block: 'center'
            });
        }
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

    function setSurveyAnswerMultipleSelect() {
        const $multipleSelect = $('.acym__survey__answer__select');

        $multipleSelect.select2({
            width: '100%',
            placeholder: $multipleSelect.attr('placeholder'),
            tags: true,
            theme: 'foundation',
            tokenSeparators: [
                '' + ''
            ]
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
            if (newValue === null) {
                newValue = [];
            }

            if (newValue.length === 0) {
                newValue = ['all'];
            } else if (newValue.includes('all')) {
                if (!aclValues[page].includes('all')) {
                    newValue = ['all'];
                } else if (newValue.length > 1) {
                    newValue = newValue.splice(newValue.indexOf('all') - 1, 1);
                }
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

    function setAutoSendingCounter() {
        jQuery('.auto_sending_input').on('change', function () {
            const batchesNumber = parseInt(jQuery('[name="config[queue_batch_auto]"]').val());
            const batchesSize = parseInt(jQuery('[name="config[queue_nbmail_auto]"]').val());
            const secondsBetweenBatches = parseInt(jQuery('[name="config[cron_frequency]"]').val());
            // When the frequency is set to 0 minutes we don't trigger the cron on page load, so we only have our 15 minutes cron + whatever cron the client set on their server
            let frequency = secondsBetweenBatches === 0 ? 900 : secondsBetweenBatches;
            const processTimeLimit = frequency > 600 ? 600 : frequency;
            const waitAmount = parseInt(jQuery('[name="config[email_frequency]"]').val());

            // We take 1 second for the average sending speed of an email
            let timeForOneBatch = batchesSize;
            if (waitAmount > 0) {
                timeForOneBatch = batchesSize * (waitAmount + 1);
            }

            let emailsSentPerBatch = batchesSize;
            if (timeForOneBatch > processTimeLimit) {
                emailsSentPerBatch = batchesSize * processTimeLimit / timeForOneBatch;
            }

            const emailsSentPerHour = emailsSentPerBatch * batchesNumber * 3600 / frequency;
            jQuery('#automatic_sending_speed_preview').html(parseInt(emailsSentPerHour));
            jQuery('#automatic_sending_speed_no_wait').css('display', waitAmount > 0 && timeForOneBatch > processTimeLimit ? 'inline-block' : 'none');
            jQuery('#automatic_sending_speed_too_much').css('display', waitAmount === 0 && timeForOneBatch > processTimeLimit ? 'inline-block' : 'none');
            jQuery('#automatic_sending_speed_too_many_batches').css('display', batchesNumber > 5 ? 'inline-block' : 'none');
        }).trigger('change');
    }

    function testConnection() {
        const $loader = $('#acym__mailbox__edition__configuration__test-loader');
        const $result = $('#acym__mailbox__edition__configuration__test-result');
        const $iconResult = $('#acym__mailbox__edition__configuration__test-icon');
        const $testButton = $('#acym__mailbox__edition__configuration__test-test');

        const resetUI = () => {
            $loader.css('display', 'flex');
            $result.empty();
            $iconResult.hide().removeClass('acymicon-check-circle acym__color__green acymicon-times-circle acym__color__red');
            $result.removeAttr('data-acym-tooltip').removeClass('acym__tooltip');
        };

        const mapFormData = (formData) => {
            const filteredFormData = formData.filter(({name}) => dataToKeep.includes(name));

            const mappedFormData = filteredFormData.map(({
                                                             name,
                                                             value
                                                         }) => {
                if (elementNameMapping[name]) {
                    return {
                        name: elementNameMapping[name],
                        value
                    };
                } else {
                    return {
                        name,
                        value
                    };
                }
            });

            return mappedFormData;
        };

        const elementNameMapping = {
            'config[bounce_server]': 'mailbox[server]',
            'config[bounce_username]': 'mailbox[username]',
            'config[bounce_password]': 'mailbox[password]',
            'config[bounce_connection]': 'mailbox[connection_method]',
            'config[bounce_secured]': 'mailbox[secure_method]',
            'config[bounce_certif]': 'mailbox[self_signed]',
            'config[bounce_port]': 'mailbox[port]'
        };
        const dataToKeep = Object.keys(elementNameMapping).map(key => key);

        $testButton.off('click').on('click', function () {
            resetUI();

            const formData = $(this).closest('form').serializeArray();
            const data = mapFormData(formData).concat([
                {
                    name: 'mailbox[id]',
                    value: 'configuration'
                },
                {
                    name: 'ctrl',
                    value: 'bounces'
                },
                {
                    name: 'task',
                    value: 'testMailboxAction'
                }
            ]);

            acym_helper.post(ACYM_AJAX_URL, data)
                       .then(({
                                  error,
                                  message,
                                  data
                              }) => {
                           $loader.hide();
                           $result.html(message);
                           if (error) {
                               if (data.report && data.report.length) {
                                   $result.attr('data-acym-tooltip', data.report.join('<br>'));
                                   acym_helperTooltip.setTooltip();
                               }
                               $iconResult.addClass('acymicon-times-circle acym__color__red');
                           } else {
                               $iconResult.addClass('acymicon-check-circle acym__color__green');
                           }
                           $iconResult.css('display', 'flex');
                       });
        });
    }

    function synchronizebounceAddressFields() {
        const $inputField1 = $('#bounceAddress1');
        const $inputField2 = $('#bounceAddress2');

        $inputField1.on('input', function () {
            $inputField2.val($(this).val());
        });

        $inputField2.on('input', function () {
            $inputField1.val($(this).val());
        });
    }

    function setCustomAnswersField() {
        const h4Element = $('.acym__multilingual__selection h4').eq(1);
        if (h4Element.length) {
            h4Element.addClass('xlarge-3 medium-5 small-9');
        }

        let counter = $('.acym__customs__answer__answer[data-response]').length;
        $('#acym__custom_answer__add-answer').off('click').on('click', function () {

            let newContent = '<div class="grid-x cell acym__customs__answers acym__content acym_noshadow grid-margin-x margin-y">';
            newContent += '<input type="text" name="config[unsub_survey][]" class="cell medium-10 acym__customs__answer__answer" data-response="'
                          + counter
                          + '" value="">';
            newContent += '<i class="cell acymicon-close small-1 acym__color__red cursor-pointer acym__custom__delete__value"></i>';
            newContent += '</div>';

            $('.acym__customs__answers__listing__sortable').append(newContent);
            counter++;
        });

        $('.acym__customs__answers__listing__sortable').on('click', '.acym__custom__delete__value', function () {
            const parent = $(this).closest('.acym__customs__answers');

            let responseIndex = parseInt(parent.find('.acym__customs__answer__answer').data('response'));

            parent.remove();

            for (let language in acym_helperSelectionMultilingual.translation) {
                acym_helperSelectionMultilingual.translation[language]['unsub_survey'].splice(responseIndex, 1);
            }

            $('.acym__customs__answers__listing__sortable .acym__customs__answers').each(function (index) {
                $(this).find('.acym__customs__answer__answer').data('response', index);
            });
        });
    }

    function setBounceOauth() {
        $('[name="config[bounce_server]"]').off('input').on('input', function () {
            const host = $(this).val().toLowerCase();

            const isOauthHost = [
                'imap.gmail.com',
                'outlook.office365.com'
            ].includes(host);

            if (isOauthHost) {
                $('.acym__bounce__classic__auth__params').hide();
                $('.acym__bounce__oauth2__auth__params').show();

                $('[name="config[bounce_port]"]').val(993);
                $('[name="config[bounce_connection]"]').val('imap');
                $('[name="config[bounce_secured]"]').val('ssl');
                $('[name="config[bounce_certif]"]').val(1);

                if ('outlook.office365.com' === host) {
                    $('#acym__oauth2_bounce_params__tenant').show();
                } else {
                    $('#acym__oauth2_bounce_params__tenant').hide();
                }
            } else {
                $('.acym__bounce__oauth2__auth__params').hide();
                $('.acym__bounce__classic__auth__params').show();
            }
        }).trigger('input');
    }

    function setEditSml() {
        const $editButtons = $('.acym__configuration__sml__edit');
        const $cancelEditButton = $('#acym__configuration__sml__cancel-edit');
        const $currentId = $('#acym__configuration__sml__method__id');
        const methods = acym_helper.parseJson($('#acym__configuration__sml__methods').val());
        const $smlContainer = $(smlSendingMethodPrefix);
        const $buttonToggle = $('#acym__configuration__sml__toggle');

        $editButtons.on('click', function () {
            $buttonToggle.hide();
            $smlContainer.show();
            const methodId = $(this).closest('.acym__configuration__sml__actions').attr('data-acym-method-id');
            $currentId.val(methodId);
            $cancelEditButton.show();
            const method = methods[methodId];
            const container = $(`[name="sml[mailer_method]"][value="${method.mailer_method}"]`).closest('.acym__sending__methods__one');
            container.find('.acym__selection__card').click();

            const $allInputs = $(`${smlSendingMethodPrefix} input[name^="sml["]`);
            for (let i = 0 ; i < $allInputs.length ; i++) {
                const key = $allInputs[i].name.replaceAll(/(sml\[)|\]/gi, '');
                if (key === 'mailer_method') {
                    continue;
                }
                $allInputs[i].value = method[$allInputs[i].name.replaceAll(/(sml\[)|\]/gi, '')];
            }
        });

        $cancelEditButton.on('click', function () {
            $currentId.val('');
            $(`#acym__configuration__sml__name`).val('');
            $smlContainer.hide();
            $buttonToggle.show();
        });
    }

    function setDeleteSml() {
        const $deleteButtons = $('.acym__configuration__sml__delete');
        const $saveButton = $('[data-task="addNewSml"]');
        const $currentId = $('#acym__configuration__sml__method__id');

        $deleteButtons.on('click', function () {
            const methodId = $(this).closest('.acym__configuration__sml__actions').attr('data-acym-method-id');
            $currentId.val(methodId);

            $saveButton.attr('data-task', 'deleteSml');
            $saveButton.click();
        });
    }

    function setShowButtonSml() {
        const $buttonToggle = $('#acym__configuration__sml__toggle');
        const $smlContainer = $(smlSendingMethodPrefix);

        $buttonToggle.on('click', function () {
            $smlContainer.show();
            $buttonToggle.hide();
        });
    }

    function setColorPicker() {
        const $colorField = $('#acym__config__settings__color-picker');
        if (typeof $colorField.spectrum == 'function') {
            $colorField
                .spectrum({
                    showInput: true,
                    preferredFormat: 'hex'
                });
        }
    }

    function initButtonImage() {
        $('#acym__unsubscribe__logo').on('click', function () {
            acym_helperImage.openMediaManager(function (mediaObject) {
                $('#acym__unsubscribe__logo_value').val(mediaObject.url).trigger('change');
                $('.acym__unsub__logo__text')
                    .html(mediaObject.url + ' <i class="acymicon-trash-o margin-left-1 acym__color__red acym__unsub__logo__remove"></i>');
            });
        });

        $('#acym__unsubscribe__image').on('click', function () {
            acym_helperImage.openMediaManager(function (mediaObject) {
                $('#acym__unsubscribe__image_value').val(mediaObject.url).trigger('change');
                $('.acym__unsub__image__text')
                    .html(mediaObject.url + ' <i class="acymicon-trash-o margin-left-1 acym__color__red acym__unsub__image__remove"></i>');
            });
        });

        $(document).on('click', '.acym__unsub__logo__remove', function () {
            $('#acym__unsubscribe__logo_value').val('').trigger('change');
            $('.acym__unsub__logo__text').empty();
        });

        $(document).on('click', '.acym__unsub__image__remove', function () {
            $('#acym__unsubscribe__image_value').val('').trigger('change');
            $('.acym__unsub__image__text').empty();
        });
    }
});
