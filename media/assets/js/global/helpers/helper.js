const acym_helper = {
    ctrlMails: ACYM_IS_ADMIN ? 'mails' : 'frontmails',
    ctrlDynamics: ACYM_IS_ADMIN ? 'dynamics' : 'frontdynamics',
    ctrlLists: ACYM_IS_ADMIN ? 'lists' : 'frontlists',
    ctrlUsers: ACYM_IS_ADMIN ? 'users' : 'frontusers',
    ctrlCampaigns: ACYM_IS_ADMIN ? 'campaigns' : 'frontcampaigns',
    emailValid: function (email) {
        return email.match(ACYM_REGEX_EMAIL) !== null;
    },
    escape: function (str) {
        str = str.replace(/"/g, '&quot;');
        return str;
    },
    parseJson: function (json, defaultValue) {
        try {
            let begin = json.indexOf('{');
            let beginBrackets = json.indexOf('[');

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
            if (jQuery(this).attr('data-force-submit') !== undefined) jQuery('[required]').removeAttr('required');

            if (jQuery(this).hasClass('disabled-button')) {
                return false;
            }

            //stop previous submit
            e.preventDefault();

            // We may want to add a condition on a submit button, something not checkable with the form validation script
            let condition = jQuery(this).attr('data-condition');
            if (condition && typeof jQuery[condition] === 'function' && !jQuery[condition]()) {
                return false;
            }

            let confirmationMessage = jQuery(this).attr('data-confirmation-message');
            if (typeof confirmationMessage !== typeof undefined && !acym_helper.confirm(ACYM_JS_TXT[confirmationMessage])) {
                return false;
            }

            if (jQuery(this).attr('acym-data-before')) {
                let result = eval(jQuery(this).attr('acym-data-before'));
                if (result === false) return false;
            }

            let $form = jQuery('#acym_form');
            let task = jQuery(this).attr('data-task');
            let controller = jQuery(this).attr('data-ctrl');

            if (controller !== undefined) {
                $form.find('[name=ctrl]').val(controller);
            }
            $form.find('[name="task"]').val(task);

            let step = jQuery(this).attr('data-step');
            if (step != null) {
                $form.find('[name="nextstep"]').val(step);
            }

            //for the automations
            let and = jQuery(this).attr('data-and');
            if (and != null) {
                $form.append('<input type="hidden" value="' + and + '" name="and_action">');
            }

            // This garbage code is for the required fields and the Edge/IE fucking compatibility
            let $buttonSubmit = $form.find('#formSubmit');
            if ($buttonSubmit[0] == undefined) {
                let evt = new MouseEvent('click');
                document.querySelector('#formSubmit').dispatchEvent(evt);
            } else {
                $buttonSubmit.click();
            }
        });
    },
    setDeleteOptionsGlobal: function () {
        jQuery('.acym__listing__block__delete__trash').off('click').on('click', function (e) {
            e.preventDefault();
            let $blockDelete = jQuery(this).closest(' .acym__listing__block__delete');
            $blockDelete.css('width', '80px');
            $blockDelete.find('div>i').css('width', '0');
            $blockDelete.animate({maxWidth: '80px'}, 'fast', function () {
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
        jQuery('.acym__listing__block__delete__cancel').off('click');
        $blockDelete.animate({maxWidth: '40px'}, 'fast', function () {
            $blockDelete.find('i').css('width', '40px');
        });
    },
    confirm: function (text) {
        return confirm(text);
    },
    setMessageClose: function () {
        jQuery('.acym_message i.acymicon-remove').off('click').on('click', function () {
            jQuery(this).parent().remove();
        });
    },
    preventEnter: function () {
        jQuery('#acym_wrapper').on('keypress', ':input:not(textarea, input:text)', function (event) {
            if (event.keyCode === 13 && !jQuery('[name$="_pagination_page"], [name="pagination_page_ajax"]').is(':visible')) {
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
    }
};
