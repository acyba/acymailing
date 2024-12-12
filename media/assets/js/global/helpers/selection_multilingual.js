const acym_helperSelectionMultilingual = {
    mainLanguage: '',
    currentLanguage: '',
    previousLanguage: '',
    translation: {},
    defaultTranslation: {},
    $translationInput: '',
    mainParams: {},
    init: function (type) {
        const mainLangElement = jQuery(`#acym__multilingual__selection-${type} .acym__multilingual__selection__main-language`);
        if (mainLangElement.length) {
            this.currentLanguage = this.mainLanguage = mainLangElement.val();
        } else {
            this.currentLanguage = this.mainLanguage = 'defaultLanguage';
        }

        this.$translationInput = jQuery(`#acym__multilingual__selection-${type} .acym__multilingual__selection__translation`);
        this.$defaultTranslationInput = jQuery(`#acym__multilingual__selection-${type} .acym__multilingual__selection__translation__default`);

        if (this.$translationInput.length && this.$translationInput.val() !== undefined) {
            try {
                this.translation = acym_helper.empty(this.$translationInput.val()) ? {} : acym_helper.parseJson(this.$translationInput.val());
            } catch (e) {
                this.translation = {};
            }
        } else {
            this.translation = {};
        }

        if (this.$defaultTranslationInput.length && this.$defaultTranslationInput.val() !== undefined) {
            try {
                this.defaultTranslation = acym_helper.empty(this.$defaultTranslationInput.val())
                                          ? {}
                                          : acym_helper.parseJson(this.$defaultTranslationInput.val());
            } catch (e) {
                this.defaultTranslation = {};
            }
        } else {
            this.defaultTranslation = {};
        }

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
        jQuery(`#acym__multilingual__selection-${type} .acym__multilingual__selection__one`).off('click').on('click', function () {
            if (jQuery(this).hasClass('acym__multilingual__selection__one__selected')) return;

            let code = jQuery(this).attr('data-acym-code');
            if (acym_helper.empty(code)) return;

            jQuery(`#acym__multilingual__selection-${type} .acym__multilingual__selection__one`).removeClass('acym__multilingual__selection__one__selected');
            jQuery(this).addClass('acym__multilingual__selection__one__selected');

            if (typeof acym_helperSelectionMultilingual[`changeLanguage_${type}`] === 'function') {
                acym_helperSelectionMultilingual.previousLanguage = acym_helperSelectionMultilingual.currentLanguage;
                acym_helperSelectionMultilingual.currentLanguage = code;
                acym_helperSelectionMultilingual[`changeLanguage_${type}`](code);
            }
        });
    },
    changeLanguage_list: function (code) {
        if (this.translation[this.currentLanguage] === undefined && code !== this.mainLanguage) {
            this.translation[this.currentLanguage] = {
                name: '',
                display_name: '',
                description: ''
            };
        }

        let name = code === this.mainLanguage ? this.mainParams.name : this.translation[this.currentLanguage].name;
        let description = code === this.mainLanguage ? this.mainParams.description : this.translation[this.currentLanguage].description;
        let frontLabel = code === this.mainLanguage ? this.mainParams.display_name : this.translation[this.currentLanguage].display_name;

        jQuery('[name="list[name]"]').val(name);
        jQuery('[name="list[display_name]"]').val(frontLabel);
        jQuery('[name="list[description]"]').val(description);
    },
    updateTranslation_list() {
        jQuery('[name="list[name]"], [name="list[description]"], [name="list[display_name]"]').off('keyup').on('keyup', function () {
            let mainLanguage = acym_helperSelectionMultilingual.mainLanguage === acym_helperSelectionMultilingual.currentLanguage;
            let name = jQuery(this).attr('name');
            let column = name.substring(name.indexOf('[') + 1, name.lastIndexOf(']'));
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
        this.mainParams.display_name = jQuery('[name="list[display_name]"]').val();
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
        this.mainLanguage = acym_helperSelectionMultilingual.mainLanguage || {};
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
    },
    setMainParams_configuration_subscription: function () {
        this.mainParams.unsub_survey = [];
        jQuery('.acym__customs__answer__answer').each((index, element) => {
            this.mainParams.unsub_survey[index] = jQuery(element).val();
        });
        this.mainLanguage = acym_helperSelectionMultilingual.mainLanguage || {};
        if (!this.translation[this.mainLanguage]) {
            this.translation[this.mainLanguage] = {};
        }
        this.translation[this.mainLanguage]['unsub_survey'] = this.mainParams.unsub_survey;

        for (let language in this.translation) {
            if (this.translation.hasOwnProperty(language)) {
                this.synchronizeUnsubSurvey(language);
            }
        }
    },
    updateTranslation_configuration_subscription() {
        jQuery(document)
            .off('keyup', '.acym__customs__answer__answer')
            .on('keyup', '.acym__customs__answer__answer', function () {
                const $inputValue = jQuery(this).val();
                const $index = jQuery('.acym__customs__answer__answer').index(this);

                if (!acym_helperSelectionMultilingual.translation[acym_helperSelectionMultilingual.currentLanguage]) {
                    acym_helperSelectionMultilingual.translation[acym_helperSelectionMultilingual.currentLanguage] = {};
                }
                if (!acym_helperSelectionMultilingual.translation[acym_helperSelectionMultilingual.currentLanguage]['unsub_survey']) {
                    acym_helperSelectionMultilingual.translation[acym_helperSelectionMultilingual.currentLanguage]['unsub_survey'] = [];
                }

                let unsubSurvey = acym_helperSelectionMultilingual.translation[acym_helperSelectionMultilingual.currentLanguage]['unsub_survey'];
                if (!Array.isArray(unsubSurvey)) {
                    unsubSurvey = [unsubSurvey];
                }
                unsubSurvey[$index] = $inputValue;

                acym_helperSelectionMultilingual.translation[acym_helperSelectionMultilingual.currentLanguage]['unsub_survey'] = unsubSurvey;
                acym_helperSelectionMultilingual.$translationInput.val(JSON.stringify(acym_helperSelectionMultilingual.translation));
            });
    },
    changeLanguage_configuration_subscription: function (code) {
        if (!this.translation[this.mainLanguage]) {
            this.translation[this.mainLanguage] = {unsub_survey: []};
        }

        for (let language in this.translation) {
            if (this.translation.hasOwnProperty(language)) {
                this.synchronizeUnsubSurvey(language);
            }
        }

        if (code === this.mainLanguage) {
            const mainLanguageUnsubSurvey = this.translation[this.mainLanguage]['unsub_survey'];
            jQuery('.acym__customs__answer__answer').each(function (index) {
                jQuery(this).val(mainLanguageUnsubSurvey[index] || '');
            });
        } else {
            if (this.translation[code] && this.translation[code].unsub_survey) {
                const updatedUnsubSurvey = this.translation[code].unsub_survey;
                jQuery('.acym__customs__answer__answer').each(function (index) {
                    jQuery(this).val(updatedUnsubSurvey[index] || '');
                });
            }
        }

        if (acym_helperSelectionMultilingual.currentLanguage === acym_helperSelectionMultilingual.mainLanguage) {
            if (!acym_helperSelectionMultilingual.mainParams.unsub_survey) {
                acym_helperSelectionMultilingual.mainParams.unsub_survey = [];
            }
            jQuery('.acym__customs__answer__answer').each((index, element) => {
                acym_helperSelectionMultilingual.mainParams.unsub_survey[index] = jQuery(element).val();
            });
        }

        jQuery('form').on('submit', function () {
            let mainLanguageElement = jQuery(`#acym__multilingual__selection-configuration_subscription .acym__multilingual__selection__one[data-acym-code="${acym_helperSelectionMultilingual.mainLanguage}"]`);
            mainLanguageElement.trigger('click');
        });

        jQuery('[name="config[unsub_survey]"]').val(JSON.stringify(this.translation[this.mainLanguage]['unsub_survey']));
        jQuery('[name="config[unsub_survey_translation]"]').val(JSON.stringify(this.translation));
    },
    synchronizeUnsubSurvey: function (language) {
        if (!this.translation[language]['unsub_survey']) {
            this.translation[language]['unsub_survey'] = [];
        }
        let unsubSurvey = this.translation[language]['unsub_survey'];

        while (unsubSurvey.length < this.mainParams.unsub_survey.length) {
            unsubSurvey.push('');
        }
        if (unsubSurvey.length > this.mainParams.unsub_survey.length) {
            unsubSurvey.splice(this.mainParams.unsub_survey.length);
        }

        for (let i = 0 ; i < unsubSurvey.length ; i++) {
            if (unsubSurvey[i] === '') {
                unsubSurvey[i] = this.translation[this.mainLanguage]['unsub_survey'][i];
            }
        }

        this.translation[language]['unsub_survey'] = unsubSurvey;
    }

};
