const acym_helperMailer = {
    setTestCredentialsSendingMethods: function () {
        jQuery('.acym__configuration__sending__method-test').off('click').on('click', function () {
            const sendingMethod = this.getAttribute('sending-method-id');
            if (sendingMethod === undefined) return false;

            const $icon = jQuery(this).closest('.acym__sending__methods__credentials__test').find('.acym__configuration__sending__method-icon');
            const $message = jQuery(this).closest('.acym__sending__methods__credentials__test').find('.acym__configuration__sending__method-test__message');

            $icon.removeClass('acym__color__red acym__color__green').addClass('acymicon-circle-o-notch acymicon-spin');
            $message.html('');

            const data = {
                ctrl: 'configuration',
                task: 'testCredentialsSendingMethod',
                sendingMethod: this.getAttribute('sending-method-id')
            };

            const isSml = jQuery(this).closest('#acym__configuration__sml__form').length > 0;
            const $credentialsField = jQuery(`[name^="${isSml ? 'sml' : 'config'}[${sendingMethod}"]`);

            for (let i = 0 ; i < $credentialsField.length ; i++) {
                const field = $credentialsField[i];
                const key = field.getAttribute('name').replace('sml', 'config');

                if (field.getAttribute('type') != 'radio') {
                    data[key] = field.value;
                } else if (field.checked) {
                    data[key] = field.value;
                }
            }

            acym_helper.post(ACYM_AJAX_URL, data).then(response => {
                let classes;
                if (response.error) {
                    classes = 'acymicon-times-circle acym__color__red';
                } else {
                    classes = 'acymicon-check-circle acym__color__green';
                }
                $icon.removeClass('acymicon-circle-o-notch acymicon-spin acymicon-check-circle acymicon-times-circle');
                $icon.addClass(classes);

                $message.html(response.message);
            });
        });
    },
    setButtonCopyFromPlugin: function () {
        jQuery('.acym__configuration__copy__mail__settings').off('click').on('click', function () {

            const $icon = jQuery(this).closest('.acym__sending__methods__copy__data').find('.acym__configuration__sending__method-icon');
            $icon.addClass('acymicon-circle-o-notch acymicon-spin');

            let data = {
                plugin: jQuery(this).attr('acym-data-plugin'),
                method: jQuery(this).attr('acym-data-method')
            };

            let ajaxUrl = ACYM_AJAX_URL + '&ctrl=configuration&task=copySettingsSendingMethod';

            acym_helper.post(ajaxUrl, data).then(response => {
                if (response.error) {
                    acym_helperNotification.addNotification(response.message, 'error');
                    return false;
                }

                for (let [name, value] of Object.entries(response.data)) {
                    let $input = jQuery(`[name="config[${name}]"]`);
                    if ($input.length === 0) continue;

                    if ($input.attr('data-switch') !== undefined && $input.val() != value) {
                        $input.closest('.switch').find('.switch-label').trigger('click');
                    } else if ($input.attr('type') === 'radio') {
                        $input = jQuery(`[name="config[${name}]"][value="${value.toLowerCase()}"]`);
                        if ($input.length > 0) $input.trigger('click');
                    } else {
                        $input.val(value).trigger('change');
                    }
                }
            }).always(response => {
                $icon.removeClass('acymicon-circle-o-notch acymicon-spin');
            });
        });
    },
    setSynchroExistingUsers: function () {
        jQuery('.acym__configuration__sending__synch__users').off('click').on('click', function () {
            const sendingMethod = this.getAttribute('sending-method-id');
            if (sendingMethod === undefined) return false;

            const $icon = jQuery(this).closest('.acym__sending__methods__synch').find('.acym__configuration__sending__method-icon');
            const $message = jQuery(this).closest('.acym__sending__methods__synch').find('.acym__configuration__sending__method-synch__message');

            $icon.removeClass('acym__color__red acym__color__green').addClass('acymicon-circle-o-notch acymicon-spin');
            $message.html('');

            const data = {
                ctrl: 'configuration',
                task: 'synchronizeExistingUsers',
                sendingMethod: sendingMethod
            };

            acym_helper.post(ACYM_AJAX_URL, data).then(response => {
                let classes;
                if (response.error) {
                    classes = 'acymicon-times-circle acym__color__red';
                } else {
                    classes = 'acymicon-check-circle acym__color__green';
                }
                $icon.removeClass('acymicon-circle-o-notch acymicon-spin');
                $icon.addClass(classes);
                $message.html(response.message);
            });
        });
    },
    displayOAuth2Params() {
        const $smtpHost = jQuery('#smtp_host');
        if ($smtpHost.length === 0) {
            return;
        }

        const $connectionType = jQuery('#smtp_type');
        $connectionType.on('change', function () {
            acym_helperMailer.handleOAuth2ParamsAppearance();
        });

        $smtpHost.on('keyup', function () {
            acym_helperMailer.handleOAuth2ParamsAppearance();
        });

        acym_helperMailer.handleOAuth2ParamsAppearance();
    },
    handleOAuth2ParamsAppearance() {
        const auth2SmtpMicrosoft = [
            'smtp-mail.outlook.com',
            'smtp.office365.com'
        ];
        const auth2SmtpGmail = ['smtp.gmail.com'];
        const auth2Smtp = [
            ...auth2SmtpGmail,
            ...auth2SmtpMicrosoft
        ];
        const $connectionTypeContainer = jQuery('#acym__sending__methods__one__settings__type');
        const $passwordFieldContainer = jQuery('.acym__default_auth_sending_params');
        const $oauth2FieldsContainer = jQuery('.acym__oauth2_sending_params');
        const $tenantContainer = jQuery('#smtp_tenant_container');
        const smtpHostValue = jQuery('#smtp_host').val().toLowerCase().trim();

        // Not part of the OAuth compatible servers
        if (!auth2Smtp.includes(smtpHostValue)) {
            $connectionTypeContainer.hide();
            $passwordFieldContainer.show();
            $oauth2FieldsContainer.hide();

            return;
        }

        $connectionTypeContainer.show();

        if (jQuery('#smtp_type').val() === 'oauth') {
            $passwordFieldContainer.hide();
            $oauth2FieldsContainer.show();

            if (auth2SmtpMicrosoft.includes(smtpHostValue)) {
                $tenantContainer.show();
            } else {
                $tenantContainer.hide();
            }
        } else {
            $passwordFieldContainer.show();
            $oauth2FieldsContainer.hide();
        }
    },
    acymailerAddDomains() {
        const $errorContainer = jQuery('#acym__configuration__acymailer__add__error');
        jQuery('#acym__configuration__sending__method-addDomain').off('click').on('click', function () {
            $errorContainer.hide();
            const domainValue = jQuery('#acymailer_domain').val().trim();

            if (acym_helper.empty(domainValue)) return;

            const data = {
                oneDomain: domainValue,
                ctrl: 'dynamics',
                task: 'trigger',
                plugin: 'plgAcymAcymailer',
                trigger: 'ajaxAddDomain'
            };

            const loader = document.querySelector('#acym__configuration__sending__method_add_domain-wait');
            loader.classList.remove('is-hidden');

            acym_helper.post(ACYM_AJAX_URL, data).then(response => {
                if (response.error) {
                    jQuery('#acym__acymailer__unverifiedDomains').hide();
                    jQuery('#acym__configuration__acymailer__add__error__message').text(response.message);
                    $errorContainer.css('display', 'flex');
                    loader.classList.add('is-hidden');
                    return;
                }

                location.reload();
            });
        });
    },
    displayCnameRecord() {
        jQuery('#acym_wrapper').on('click', '.acym__sending__methods__unverifiedDomain-icon', function () {
            const domainInput = jQuery(this)
                .closest('.acym__sending__methods__container__oneUnverifiedDomain')
                .find('.acym__sending__methods__unverifiedDomain');

            const cnames = acym_helper.parseJson(domainInput[0].getAttribute('data-acym-cname'));
            const $table = jQuery('#acym__configuration__sending__method__cnameTable__container');
            const cnamesValue = document.querySelectorAll('.cname-value');
            const cnamesName = document.querySelectorAll('.cname-name');

            for (let index in cnames) {
                cnamesValue[index].innerHTML = cnames[index].value;
                cnamesName[index].innerHTML = cnames[index].name;
            }

            let $selectedDomain = jQuery('.domain_selected');
            if ($selectedDomain.length === 0) {
                // No domain was selected
                $table.slideToggle();
                jQuery(this).addClass('domain_selected');
            } else if (jQuery(this).hasClass('domain_selected')) {
                // We clicked on the selected domain
                $table.slideToggle();
                jQuery(this).removeClass('domain_selected');
            } else {
                // an other domain was selected
                jQuery('.acym__sending__methods__unverifiedDomain-icon').removeClass('domain_selected');
                jQuery(this).addClass('domain_selected');
            }
        });

        // Auto-select the DNS values on click
        jQuery('.cname-name, .cname-value').on('click', function () {
            let range = document.createRange();
            range.selectNode(this);
            window.getSelection().removeAllRanges();
            window.getSelection().addRange(range);
        });
    },
    deleteDomain() {
        jQuery('.acym__config__acymailer__domain--delete').off('click').on('click', function () {
            if (!confirm(ACYM_JS_TXT.ACYM_DELETE_DOMAIN_CONFIRMATION)) {
                return;
            }
            this.classList.remove('acymicon-delete');
            this.classList.add('acymicon-circle-o-notch', 'acymicon-spin');
            const domain = jQuery(this).attr('acym-data-domain').trim();
            const data = {
                oneDomain: domain,
                ctrl: 'dynamics',
                task: 'trigger',
                plugin: 'plgAcymAcymailer',
                trigger: 'onAcymDeleteDomain'
            };

            acym_helper.post(ACYM_AJAX_URL, data).then(response => {
                if (response.error) {
                    this.classList.add('acymicon-delete');
                    this.classList.remove('acymicon-circle-o-notch', 'acymicon-spin');
                    acym_helperNotification.addNotification(response.message, 'error', true);
                    return;
                }
                jQuery(this).closest('.acym__listing__row').remove();
                if (!jQuery('.acym__config__acymailer__status__icon.acymicon-access_time').length) {
                    jQuery('.acym__config__acymailer__warning').remove();
                }
            });
        });
    },
    domainSuggestion() {
        let listSuggestion = jQuery('#acym__acymailer__unverifiedDomains');
        let input = jQuery('#acymailer_domain');
        let suggestions = jQuery('.acym__acymailer__oneSuggestion');
        let errorSpan = jQuery('#acymailer_domain_error');

        listSuggestion.hide();

        input.off('click').on('click', function () {
            listSuggestion.toggle();
            if (listSuggestion.is(':visible')) {
                errorSpan.hide();
            } else {
                errorSpan.show();
            }
        });

        jQuery.each(suggestions, function () {
            jQuery(this).on('mouseenter', function () {
                jQuery(this).addClass('acym__acymailer__suggestion_selected');
            });
            jQuery(this).on('mouseleave', function () {
                jQuery(this).removeClass('acym__acymailer__suggestion_selected');
            });

            jQuery(this).off('click').on('click', function () {
                input.val(jQuery(this).html().trim());
                listSuggestion.hide();
            });
        });
    },
    updateStatus() {
        jQuery('#acym__config__acymailer__update-domain-status').on('click', function () {
            jQuery('.notValidated').replaceWith('<i class="acymicon-circle-o-notch acymicon-spin"></i>');
            acym_helper.get(ACYM_AJAX_URL, {
                sendingMethod: this.getAttribute('sending-method-id'),
                ctrl: 'dynamics',
                task: 'trigger',
                plugin: 'plgAcymAcymailer',
                trigger: 'ajaxCheckDomain'
            }).then(response => {
                if (response.error) {
                    acym_helperNotification.addNotification(response.message, 'error', true);

                    const $currentDiv = jQuery(`div[acym-data-domain="${response.data['domain']}"]`);
                    $currentDiv.parent().find('.acymicon-spin')
                               .replaceWith('<i class="acym__config__acymailer__status__icon acymicon-remove acym__color__red notValidated"></i>');
                    $currentDiv.find('.acym__tooltip__text').html(response.message);
                    return;
                }

                let iconClass = '';
                let tooltip = '';
                Object.entries(response.data.domains).forEach(([key, domain]) => {
                    switch (domain.status) {
                        case 'SUCCESS':
                            iconClass = 'acymicon-check-circle acym__color__green';
                            tooltip = ACYM_JS_TXT.ACYM_VALIDATED;
                            break;
                        case 'FAILED':
                            iconClass = 'acymicon-remove acym__color__red notValidated';
                            tooltip = ACYM_JS_TXT.ACYM_APPROVAL_FAILED;
                            break;
                        default:
                            iconClass = 'acymicon-access_time acym__color__orange notValidated';
                            tooltip = ACYM_JS_TXT.ACYM_PENDING;
                    }
                    const $currentDiv = jQuery(`div[acym-data-domain="${key}"]`);
                    $currentDiv.find('.acymicon-spin').replaceWith('<i class="acym__config__acymailer__status__icon ' + iconClass + '"></i>');
                    $currentDiv.find('.acym__tooltip__text').html(tooltip);
                });
            });
        });
    }
};
