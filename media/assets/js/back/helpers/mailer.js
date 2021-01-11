const acym_helperMailer = {
    setTestCredentialsSendingMethods: function () {
        jQuery('.acym__configuration__sending__method-test').off('click').on('click', function () {
            const sendingMethod = this.getAttribute('sending-method-id');
            if (sendingMethod === undefined) return false;

            const $icon = jQuery(this).closest('.send_settings').find('.acym__configuration__sending__method-test__icon');
            const $message = jQuery(this).closest('.send_settings').find('.acym__configuration__sending__method-test__message');

            $icon.addClass('acymicon-circle-o-notch acymicon-spin');
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
    }
};
