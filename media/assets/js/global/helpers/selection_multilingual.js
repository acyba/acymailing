const acym_helperSelectionMultilingual = {
    mainLanguage: '',
    currentLanguage: '',
    translation: {},
    defaultTranslation: {},
    $translationInput: '',
    mainParams: {},
    init: function (type) {
        this.currentLanguage = this.mainLanguage = jQuery('#acym__multilingual__selection__main-language').val();
        this.$translationInput = jQuery('#acym__multilingual__selection__translation');
        this.$defaultTranslationInput = jQuery('#acym__multilingual__selection__translation__default');
        this.translation = acym_helper.empty(this.$translationInput.val()) ? {} : acym_helper.parseJson(this.$translationInput.val());
        this.defaultTranslation = acym_helper.empty(this.$defaultTranslationInput.val()) ? {} : acym_helper.parseJson(this.$defaultTranslationInput.val());

        if (JSON.stringify(this.translation) === '[]') this.translation = {};

        //We set the main language params
        if (typeof acym_helperSelectionMultilingual[`setMainParams_${type}`] === 'function') {
            acym_helperSelectionMultilingual[`setMainParams_${type}`]();
        }

        //We set the field to listen
        if (typeof acym_helperSelectionMultilingual[`updateTranslation_${type}`] === 'function') {
            acym_helperSelectionMultilingual[`updateTranslation_${type}`]();
        }

        //We set the selection
        jQuery('.acym__multilingual__selection__one').off('click').on('click', function () {
            if (jQuery(this).hasClass('acym__multilingual__selection__one__selected')) return;

            let code = jQuery(this).attr('data-acym-code');
            if (acym_helper.empty(code)) return;

            jQuery('.acym__multilingual__selection__one').removeClass('acym__multilingual__selection__one__selected');
            jQuery(this).addClass('acym__multilingual__selection__one__selected');

            if (typeof acym_helperSelectionMultilingual[`changeLanguage_${type}`] === 'function') {
                acym_helperSelectionMultilingual.currentLanguage = code;
                acym_helperSelectionMultilingual[`changeLanguage_${type}`](code);
            }
        });
    },
    changeLanguage_list: function (code) {
        if (this.translation[this.currentLanguage] === undefined && code !== this.mainLanguage) {
            this.translation[this.currentLanguage] = {
                name: '',
                description: ''
            };
        }

        let name = code === this.mainLanguage ? this.mainParams.name : this.translation[this.currentLanguage].name;
        let description = code === this.mainLanguage ? this.mainParams.description : this.translation[this.currentLanguage].description;

        jQuery('[name="list[name]"]').val(name);
        jQuery('[name="list[description]"]').val(description);
    },
    updateTranslation_list() {
        jQuery('[name="list[name]"], [name="list[description]"]').off('keyup').on('keyup', function () {
            let mainLanguage = acym_helperSelectionMultilingual.mainLanguage === acym_helperSelectionMultilingual.currentLanguage;

            let column = jQuery(this).attr('name') === 'list[name]' ? 'name' : 'description';

            if (mainLanguage) {
                acym_helperSelectionMultilingual.mainParams[column] = jQuery(this).val();
            } else {
                acym_helperSelectionMultilingual.translation[acym_helperSelectionMultilingual.currentLanguage][column] = jQuery(this).val();
                acym_helperSelectionMultilingual.$translationInput.val(JSON.stringify(acym_helperSelectionMultilingual.translation));
            }
        });
    },
    setMainParams_list: function () {
        this.mainParams.name = jQuery('[name="list[name]"]').val();
        this.mainParams.description = jQuery('[name="list[description]"]').val();
    },
    setMainParams_field: function () {
        this.mainParams.name = jQuery('[name="field[name]"]').val();
    },
    updateTranslation_field() {
        jQuery('[name="field[name]"]').off('keyup').on('keyup', function () {
            let mainLanguage = acym_helperSelectionMultilingual.mainLanguage === acym_helperSelectionMultilingual.currentLanguage;

            if (mainLanguage) {
                acym_helperSelectionMultilingual.mainParams['name'] = jQuery(this).val();
            } else {
                acym_helperSelectionMultilingual.translation[acym_helperSelectionMultilingual.currentLanguage]['name'] = jQuery(this).val();
                acym_helperSelectionMultilingual.$translationInput.val(JSON.stringify(acym_helperSelectionMultilingual.translation));
            }
        });
    },
    changeLanguage_field: function (code) {
        if (this.translation[this.currentLanguage] === undefined && code !== this.mainLanguage) this.translation[this.currentLanguage] = {name: ''};

        let name = code === this.mainLanguage ? this.mainParams.name : this.translation[this.currentLanguage].name;
        jQuery('[name="field[name]"]').val(name);
    },
    setMainParams_campaign: function () {
        let $fromName = jQuery('[name="senderInformation[from_name]"]');
        let $fromEmail = jQuery('[name="senderInformation[from_email]"]');
        let $replyToName = jQuery('[name="senderInformation[reply_to_name]"]');
        let $replyToEmail = jQuery('[name="senderInformation[reply_to_email]"]');

        this.mainParams.from_name = $fromName.val();
        this.mainParams.from_email = $fromEmail.val();
        this.mainParams.reply_to_name = $replyToName.val();
        this.mainParams.reply_to_email = $replyToEmail.val();

        this.defaultTranslation[this.mainLanguage] = {
            from_name: $fromName.attr('placeholder'),
            from_email: $fromEmail.attr('placeholder'),
            reply_to_name: $replyToName.attr('placeholder'),
            reply_to_email: $replyToEmail.attr('placeholder')
        };
    },
    updateTranslation_campaign() {
        jQuery(
            '[name="senderInformation[from_name]"], [name="senderInformation[from_email]"], [name="senderInformation[reply_to_name]"], [name="senderInformation[reply_to_email]"]')
            .off('keyup')
            .on('keyup', function () {
                let mainLanguage = acym_helperSelectionMultilingual.mainLanguage === acym_helperSelectionMultilingual.currentLanguage;

                let column = 'from_name';
                if (jQuery(this).attr('name') === 'senderInformation[from_email]') {
                    column = 'from_email';
                } else if (jQuery(this).attr('name') === 'senderInformation[reply_to_name]') {
                    column = 'reply_to_name';
                } else if (jQuery(this).attr('name') === 'senderInformation[reply_to_email]') {
                    column = 'reply_to_email';
                }

                if (mainLanguage) {
                    acym_helperSelectionMultilingual.mainParams[column] = jQuery(this).val();
                } else {
                    acym_helperSelectionMultilingual.translation[acym_helperSelectionMultilingual.currentLanguage][column] = jQuery(this).val();
                    acym_helperSelectionMultilingual.$translationInput.val(JSON.stringify(acym_helperSelectionMultilingual.translation));
                }
            });
    },
    changeLanguage_campaign: function (code) {
        if (this.translation[this.currentLanguage] === undefined && code !== this.mainLanguage) {
            this.translation[this.currentLanguage] = {
                from_name: '',
                from_email: '',
                reply_to_name: '',
                reply_to_email: ''
            };
        }

        let from_name = code === this.mainLanguage ? this.mainParams.from_name : this.translation[this.currentLanguage].from_name;
        let from_email = code === this.mainLanguage ? this.mainParams.from_email : this.translation[this.currentLanguage].from_email;
        let reply_to_name = code === this.mainLanguage ? this.mainParams.reply_to_name : this.translation[this.currentLanguage].reply_to_name;
        let reply_to_email = code === this.mainLanguage ? this.mainParams.reply_to_email : this.translation[this.currentLanguage].reply_to_email;

        let $fromName = jQuery('[name="senderInformation[from_name]"]');
        let $fromEmail = jQuery('[name="senderInformation[from_email]"]');
        let $replyToName = jQuery('[name="senderInformation[reply_to_name]"]');
        let $replyToEmail = jQuery('[name="senderInformation[reply_to_email]"]');

        $fromName.val(from_name);
        $fromEmail.val(from_email);
        $replyToName.val(reply_to_name);
        $replyToEmail.val(reply_to_email);

        if (!acym_helper.empty(this.defaultTranslation[this.currentLanguage])) {
            let defaultText = ACYM_JS_TXT.ACYM_DEFAULT + ': ';
            if (this.defaultTranslation[this.currentLanguage].from_name !== undefined) {
                $fromName.attr('placeholder', defaultText + this.defaultTranslation[this.currentLanguage].from_name);
            }
            if (this.defaultTranslation[this.currentLanguage].from_email !== undefined) {
                $fromEmail.attr('placeholder', defaultText + this.defaultTranslation[this.currentLanguage].from_email);
            }
            if (this.defaultTranslation[this.currentLanguage].reply_to_name !== undefined) {
                $replyToName.attr('placeholder', defaultText + this.defaultTranslation[this.currentLanguage].reply_to_name);
            }
            if (this.defaultTranslation[this.currentLanguage].reply_to_email !== undefined) {
                $replyToEmail.attr('placeholder', defaultText + this.defaultTranslation[this.currentLanguage].reply_to_email);
            }
        }
    },
    setMainParams_configuration: function () {
        this.mainParams.from_name = jQuery('[name="config[from_name]"]').val();
        this.mainParams.from_email = jQuery('[name="config[from_email]"]').val();
        this.mainParams.replyto_name = jQuery('[name="config[replyto_name]"]').val();
        this.mainParams.replyto_email = jQuery('[name="config[replyto_email]"]').val();
    },
    updateTranslation_configuration() {
        jQuery('[name="config[from_name]"], [name="config[from_email]"], [name="config[replyto_name]"], [name="config[replyto_email]"]')
            .off('keyup')
            .on('keyup', function () {
                let sameReplyTo = jQuery('#from_as_replyto').is(':checked');
                let mainLanguage = acym_helperSelectionMultilingual.mainLanguage === acym_helperSelectionMultilingual.currentLanguage;

                let column = 'from_name';
                if (jQuery(this).attr('name') === 'config[from_email]') {
                    column = 'from_email';
                } else if (jQuery(this).attr('name') === 'config[replyto_name]') {
                    column = 'replyto_name';
                } else if (jQuery(this).attr('name') === 'config[replyto_email]') {
                    column = 'replyto_email';
                }
                let otherColumn = '';

                if (mainLanguage) {
                    acym_helperSelectionMultilingual.mainParams[column] = jQuery(this).val();
                    if (sameReplyTo && column.indexOf('from') !== -1) {
                        otherColumn = column.replace('from', 'replyto');
                        acym_helperSelectionMultilingual.mainParams[otherColumn] = jQuery(this).val();
                    }
                } else {
                    acym_helperSelectionMultilingual.translation[acym_helperSelectionMultilingual.currentLanguage][column] = jQuery(this).val();
                    if (sameReplyTo && column.indexOf('from') !== -1) {
                        otherColumn = column.replace('from', 'replyto');
                        acym_helperSelectionMultilingual.translation[acym_helperSelectionMultilingual.currentLanguage][otherColumn] = jQuery(this).val();
                    }
                    acym_helperSelectionMultilingual.$translationInput.val(JSON.stringify(acym_helperSelectionMultilingual.translation));
                }

                if (otherColumn !== '') {
                    jQuery(`[name="config[${otherColumn}]"]`).val(jQuery(this).val());
                }
            });

        jQuery('#from_as_replyto').on('change', function () {
            if (!jQuery(this).is(':checked') || acym_helperSelectionMultilingual.currentLanguage === acym_helperSelectionMultilingual.mainLanguage) {
                return false;
            }

            jQuery('[name="config[replyto_name]"]')
                .val(acym_helperSelectionMultilingual.translation[acym_helperSelectionMultilingual.currentLanguage].from_name);
            jQuery('[name="config[replyto_email]"]')
                .val(acym_helperSelectionMultilingual.translation[acym_helperSelectionMultilingual.currentLanguage].from_email);
        });
    },
    changeLanguage_configuration: function (code) {
        if (this.translation[this.currentLanguage] === undefined && code !== this.mainLanguage) {
            this.translation[this.currentLanguage] = {
                from_name: '',
                from_email: '',
                replyto_name: '',
                replyto_email: ''
            };
        }

        let from_name = code === this.mainLanguage ? this.mainParams.from_name : this.translation[this.currentLanguage].from_name;
        let from_email = code === this.mainLanguage ? this.mainParams.from_email : this.translation[this.currentLanguage].from_email;
        let replyto_name = code === this.mainLanguage ? this.mainParams.replyto_name : this.translation[this.currentLanguage].replyto_name;
        let replyto_email = code === this.mainLanguage ? this.mainParams.replyto_email : this.translation[this.currentLanguage].replyto_email;
        jQuery('[name="config[from_name]"]').val(from_name);
        jQuery('[name="config[from_email]"]').val(from_email);
        jQuery('[name="config[replyto_name]"]').val(replyto_name);
        jQuery('[name="config[replyto_email]"]').val(replyto_email);
    }
};
