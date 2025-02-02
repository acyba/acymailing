const acym_helper = {
    ctrlMails: ACYM_IS_ADMIN ? 'mails' : 'frontmails',
    ctrlDynamics: ACYM_IS_ADMIN ? 'dynamics' : 'frontdynamics',
    ctrlLists: ACYM_IS_ADMIN ? 'lists' : 'frontlists',
    ctrlUsers: ACYM_IS_ADMIN ? 'users' : 'frontusers',
    ctrlCampaigns: ACYM_IS_ADMIN ? 'campaigns' : 'frontcampaigns',
    config_get: function (field) {
        const controller = ACYM_IS_ADMIN ? 'configuration' : 'frontconfiguration';
        return jQuery.ajax({
            type: 'GET',
            url: ACYM_AJAX_URL + '&ctrl=' + controller + '&task=getAjax&field=' + field,
            dataType: 'json'
        });
    },
    emailValid: function (email) {
        return email.match(ACYM_REGEX_EMAIL) !== null;
    },
    escape: function (str) {
        str = str.replace(/"/g, '&quot;');
        return str;
    },
    parseJson: function (json, defaultValue) {
        if (typeof json === 'object' || typeof json === 'undefined') {
            return json;
        }

        try {
            const begin = json.indexOf('{');
            const beginBrackets = json.indexOf('[');

            if ((!isNaN(begin) && begin > 0) && (!isNaN(beginBrackets) && beginBrackets > 0)) {
                json = json.substring(begin);
            }

            if (json !== undefined || json !== '') return JSON.parse(json);
        } catch (error) {
            console.log(error.stack);
        }

        return defaultValue;
    },
    sprintf: function () {
        let args = Object.values(arguments);
        let returnTranslation = args.splice(0, 1)[0];
        if (args.length === 1) {
            returnTranslation = returnTranslation.replace('%s', args[0]);
        } else {
            jQuery.each(args, function (index, value) {
                returnTranslation = returnTranslation.replace('%' + (index + 1) + '$s', value);
            });
        }

        return returnTranslation;
    },
    setSubmitButtonGlobal: function () {
        jQuery('.acy_button_submit').off('click').on('click', function (e) {
            if (jQuery(this).attr('data-force-submit') !== undefined) {
                jQuery('[required]').removeAttr('required');
            }

            if (jQuery(this).hasClass('disabled')) {
                return false;
            }

            //stop previous submit
            e.preventDefault();

            // We may want to add a condition on a submit button, something not checkable with the form validation script
            const condition = jQuery(this).attr('data-condition');
            if (condition && typeof jQuery[condition] === 'function' && !jQuery[condition]()) {
                return false;
            }

            const confirmationMessage = jQuery(this).attr('data-confirmation-message');
            if (typeof confirmationMessage !== typeof undefined && !acym_helper.confirm(ACYM_JS_TXT[confirmationMessage])) {
                return false;
            }

            if (jQuery(this).attr('acym-data-before')) {
                const result = eval(jQuery(this).attr('acym-data-before'));
                if (result === false) {
                    return false;
                }
            }

            const $form = jQuery('#acym_form');
            const task = jQuery(this).attr('data-task');
            const controller = jQuery(this).attr('data-ctrl');

            if (controller !== undefined) {
                $form.find('[name=ctrl]').val(controller);
            }
            $form.find('[name="task"]').val(task);

            const step = jQuery(this).attr('data-step');
            if (step != null) {
                $form.find('[name="nextstep"]').val(step);
            }

            //for the automations
            const and = jQuery(this).attr('data-and');
            if (and != null) {
                $form.append('<input type="hidden" value="' + and + '" name="and_action">');
            }

            // This garbage code is for the required fields and the Edge/IE fucking compatibility
            const $buttonSubmit = $form.find('#formSubmit');
            if ($buttonSubmit[0] == undefined) {
                let evt = new MouseEvent('click');
                document.querySelector('#formSubmit').dispatchEvent(evt);
            } else {
                $buttonSubmit.trigger('click');
            }
        });
    },
    setDeleteOptionsGlobal: function () {
        jQuery('.js-acym__listing__block__delete__trash').off('click').on('click', function (e) {
            e.preventDefault();
            let $blockDelete = jQuery(this).closest(' .acym__listing__block__delete');
            $blockDelete.css('width', '56px');
            $blockDelete.find('.acym__listing__block__delete__trash').css('display', 'none');
            $blockDelete.animate({maxWidth: '56px'}, 'fast', function () {
                jQuery('.acym__listing__block__delete__cancel', this).off('click').on('click', function () {
                    acym_helper.setHideDeleteOptionsGlobal(jQuery(this).closest('.acym__listing__block__delete'));
                });
            });
            jQuery('.acym__listing__block').off('mouseleave').on('mouseleave', function () {
                acym_helper.setHideDeleteOptionsGlobal(jQuery(this).find('.acym__listing__block__delete'));
            });
        });
    },
    setHideDeleteOptionsGlobal: function ($blockDelete) {
        jQuery('.js-acym__listing__block__delete__cancel').off('click');
        $blockDelete.animate({maxWidth: '28px'}, 'fast', function () {
            $blockDelete.find('.acym__listing__block__delete__trash').css('display', 'block');
        });
    },
    confirm: function (text) {
        return confirm(text.replace('<br />', '\n'));
    },
    setMessageClose: function () {
        jQuery('.acym_message i.acymicon-close').off('click').on('click', function () {
            jQuery(this).parent().remove();
        });
    },
    preventEnter: function () {
        jQuery('#acym_wrapper').on('keypress', ':input:not(textarea, input:text)', function (event) {
            if (event.key === 'Enter' && !jQuery('[name$="_pagination_page"], [name="pagination_page_ajax"]').is(':visible')) {
                event.preventDefault();
                return false;
            }
        });
    },
    getIntValueWithoutPixel: function (str) {
        if (undefined === str || '' === str) return 0;
        return parseInt(str.replace(/[^-\d\.]/g, ''));
    },
    empty: function (str) {
        if (str === null) return true;
        if (typeof str === 'undefined') return true;
        if (str === undefined) return true;
        if (str === '') return true;
        if (str.length === 0) return true;
        if (str === 0) return true;
        if (str === '0') return true;

        return str === false;
    },
    alert: function (text) {
        return alert(text);
    },
    getCookie: function (cookieKey) {
        let returnValue = '';
        let cookies = document.cookie.split(';');
        cookies.map(cookie => {
            cookie = cookie.trim().split('=');
            if (cookie[0] === cookieKey) returnValue = cookie[1];
        });
        return returnValue;
    },
    setCookie: function (cookieName, value, expiredays) {
        let newCookie = cookieName + '=' + this.escape(value);

        if (expiredays != null) {
            let exdate = new Date();
            exdate.setDate(exdate.getDate() + expiredays);
            newCookie += ';expires=' + exdate.toUTCString();
        }

        document.cookie = newCookie;
    },
    get: function (url = ACYM_AJAX_URL, data = {}) {
        return jQuery.get(url, data)
                     .then(acym_helper.parseResponse)
                     .fail(acym_helper.handleErrors);
    },
    post: function (url = ACYM_AJAX_URL, data = {}, needAbort = false) {
        const query = jQuery.post(url, data);

        if (needAbort) {
            query.then(acym_helper.parseJson).fail(acym_helper.handleErrors);
            return query;
        }
        return query.then(acym_helper.parseResponse).fail(acym_helper.handleErrors);
    },
    parseResponse: function (response) {
        if (typeof response !== 'object') response = acym_helper.parseJson(response);

        if (response.error && !acym_helper.empty(response.message)) console.error(response.message);

        return response;
    },
    handleErrors: function (xhr, status, error) {
        const response = {};
        response.error = true;
        response.message = error;
        response.data = [];

        console.error(`Ajax error, responded with error ${status} ${error}`);

        return response;
    },
    sameArrays: function (array1, array2) {
        return array1.length === array2.length && array1.every(function (value, index) {
            return value === array2[index];
        });
    },
    setButtonRadio: function () {
        jQuery('.button-radio').off('click').on('click', function (event) {
            event.preventDefault();

            jQuery('[acym-button-radio-group="' + jQuery(this).attr('acym-button-radio-group') + '"]').removeClass('button-radio-selected');
            jQuery(this).addClass('button-radio-selected');

            let callback = jQuery(this).attr('acym-callback');
            setTimeout(() => {
                if (!acym_helper.empty(callback) && typeof window[callback] === 'function') {
                    window[callback]();
                }
            }, 100);
        });
    },
    setKonami: function () {
        const konamiCode = [
            'ArrowUp',
            'ArrowUp',
            'ArrowDown',
            'ArrowDown',
            'ArrowLeft',
            'ArrowRight',
            'ArrowLeft',
            'ArrowRight',
            'b',
            'a'
        ];
        let konamiCodePosition = 0;

        jQuery(document).on('keydown', function (e) {
            if (e.key === konamiCode[konamiCodePosition]) {
                konamiCodePosition++;

                if (konamiCodePosition === konamiCode.length) {
                    jQuery('<style>* {animation: acy-spin 20s linear infinite;}</style>').appendTo('body');
                    konamiCodePosition = 0;
                }
            } else {
                konamiCodePosition = 0;
            }
        });
    },
    setLicenseLink: function () {
        jQuery('.acym_link_license_tab').on('click', function () {
            localStorage.setItem('acyconfiguration', 'license');
        });
    }
};
