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

            const credentialsField = jQuery(`[name^="config[${sendingMethod}"]`);

            for (let i = 0 ; i < credentialsField.length ; i++) {
                let field = credentialsField[i];
                if (field.getAttribute('type') == 'radio' && field.checked) {
                    data[field.getAttribute('name')] = field.value;
                } else if (field.getAttribute('type') != 'radio') {
                    data[field.getAttribute('name')] = field.value;
                }
            }

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
    setButtonCopyFromPlugin: function () {
        jQuery('.acym__configuration__copy__mail__settings').off('click').on('click', function () {

            const $icon = jQuery(this).closest('.acym__sending__methods__copy__data').find('.acym__configuration__sending__method-icon');
            $icon.addClass('acymicon-circle-o-notch acymicon-spin');

            let data = {
                plugin: jQuery(this).attr('acym-data-plugin'),
                method: jQuery(this).attr('acym-data-method')
            };

            let ajaxUrl = ACYM_AJAX_URL + '&ctrl=configuration&task=copySettingsSendingMethod';

            acym_helper.get(ajaxUrl, data).then(response => {
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
    }
};
