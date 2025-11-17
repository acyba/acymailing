if (typeof submitAcymForm !== 'function') {
    let currentlySubmittingForm = '';
    let currentAction = '';
    window.acyFormName = '';

    blockPasteEvent();

    function submitAcymForm(task, formName, submitFunction) {
        if (currentlySubmittingForm === formName) {
            return false;
        }

        currentAction = task;
        window.acyFormName = formName;
        submitFunction = submitFunction === undefined ? 'acymSubmitSubForm' : submitFunction;

        const recaptchaid = formName ? formName + '-captcha' : 'acym-captcha';
        const initRecaptcha = document.querySelector('#' + recaptchaid + '[class="acyg-recaptcha"][data-size="invisible"]');

        if (!initRecaptcha || typeof grecaptcha !== 'object') {
            return window[submitFunction]();
        }

        if (initRecaptcha.getAttribute('data-captchaname') === 'acym_ireCaptcha') {
            initRecaptcha.className = 'g-recaptcha';
            let invisibleRecaptcha = document.querySelector('#' + recaptchaid + '[class="g-recaptcha"][data-size="invisible"]');

            if (!invisibleRecaptcha) {
                return window[submitFunction]();
            }

            let grcID = invisibleRecaptcha.getAttribute('grcID');

            if (!grcID) {
                grcID = grecaptcha.render(recaptchaid, {
                    'sitekey': invisibleRecaptcha.getAttribute('data-sitekey'),
                    'callback': submitFunction,
                    'size': 'invisible',
                    'expired-callback': 'resetRecaptcha'
                });

                invisibleRecaptcha.setAttribute('grcID', grcID);
            }

            let response = grecaptcha.getResponse(grcID);
            if (response) {
                return window[submitFunction]();
            } else {
                grecaptcha.execute(grcID);
                return false;
            }
        } else {
            let captcha = document.getElementById(recaptchaid);
            if (!captcha) {
                return window[submitFunction]();
            }

            grecaptcha.ready(function () {
                grecaptcha.execute(captcha.getAttribute('data-sitekey'), {action: 'submit'}).then(function (token) {
                    const input = document.createElement('input');
                    input.setAttribute('type', 'hidden');
                    input.setAttribute('name', 'g-recaptcha-response');
                    input.setAttribute('value', token);
                    document.getElementById(window.acyFormName).appendChild(input);
                    return window[submitFunction]();
                });
            });

            return false;
        }
    }

    function resetRecaptcha() {
        let recaptchaid = 'acym-captcha';
        if (window.acyFormName) recaptchaid = window.acyFormName + '-captcha';

        let invisibleRecaptcha = document.querySelector('#' + recaptchaid + '[class="g-recaptcha"][data-size="invisible"]');
        if (!invisibleRecaptcha) return;

        let grcID = invisibleRecaptcha.getAttribute('grcID');
        grecaptcha.reset(grcID);
    }

    function acym_resetInvalidClass() {
        let invalidFields = document.querySelectorAll('#' + window.acyFormName + ' .acym_invalid_field');
        if (invalidFields.length !== 0) {
            for (let i = 0 ; i < invalidFields.length ; i++) {
                invalidFields[i].classList.remove('acym_invalid_field');
            }
        }

        let errorZones = document.querySelectorAll('#' + window.acyFormName + ' .acym__field__error__block');
        if (errorZones.length !== 0) {
            for (let i = 0 ; i < errorZones.length ; i++) {
                errorZones[i].classList.remove('acym__field__error__block__active');
            }
        }

        let displayedMessages = document.querySelectorAll('#' + window.acyFormName + ' .acym__message__invalid__field');
        if (displayedMessages.length !== 0) {
            for (let i = 0 ; i < displayedMessages.length ; i++) {
                displayedMessages[i].classList.remove('acym__message__invalid__field__active');
            }
        }
        let displayedCross = document.querySelectorAll('#' + window.acyFormName + ' .acym__cross__invalid');
        if (displayedCross.length !== 0) {
            for (let i = 0 ; i < displayedCross.length ; i++) {
                displayedCross[i].classList.remove('acym__cross__invalid__active');
            }
        }
    }

    function acym_checkEmailField(varform, name, validation) {
        let emailField = varform.elements[name];
        if (emailField) {
            if (emailField.value !== acymModule['EMAILCAPTION']) {
                emailField.value = emailField.value.replace(/ /g, '');
            }

            const filter = acymModule['emailRegex'];
            if (emailField.value === acymModule['EMAILCAPTION'] || !filter.test(emailField.value)) {
                acymAddInvalidClass(emailField.name, validation, acymModule['VALID_EMAIL']);
            }
        }
    }

    function acym_checkEmailConfirmationField(varform, name, validation) {
        let emailField = varform.elements['user[email]'];
        let emailConfirmationField = varform.elements[name];
        if (emailConfirmationField) {
            if (emailField.value !== emailConfirmationField.value || emailConfirmationField.value === '') {
                acymAddInvalidClass(name, validation, acymModule['VALID_EMAIL_CONFIRMATION']);
            }
        }
    }

    function acym_handleRequiredRadio(validation) {
        let requiredRadio = document.querySelectorAll('#' + window.acyFormName + ' [type="radio"][data-required]');
        if (requiredRadio.length === 0) return;

        let lastName = '';
        let checked = 0;
        let required;
        for (let i = 0 ; i < requiredRadio.length ; i++) {
            required = JSON.parse(requiredRadio[i].getAttribute('data-required'));

            if (lastName !== '' && lastName !== requiredRadio[i].getAttribute('name')) {
                if (checked === 0) {
                    let previousRequired = JSON.parse(requiredRadio[i - 1].getAttribute('data-required'));
                    acymAddInvalidClass(lastName, validation, previousRequired.message);
                } else {
                    checked = 0;
                }
            }

            if (requiredRadio[i].checked) {
                checked++;
            }
            lastName = requiredRadio[i].getAttribute('name');
        }

        if (checked === 0) {
            acymAddInvalidClass(lastName, validation, required.message);
        }
    }

    function acym_handleRequiredCheckbox(validation) {
        let requiredCheckbox = document.querySelectorAll('#' + window.acyFormName + ' [type="checkbox"][data-required]');
        if (requiredCheckbox.length === 0) return;

        let lastName = '';
        let checked = 0;
        let required;
        for (let i = 0 ; i < requiredCheckbox.length ; i++) {
            required = JSON.parse(requiredCheckbox[i].getAttribute('data-required'));
            let slicedName = requiredCheckbox[i].getAttribute('name').slice(0, requiredCheckbox[i].getAttribute('name').lastIndexOf('['));

            if (lastName !== '' && lastName !== slicedName) {
                if (checked === 0) {
                    let previousRequired = JSON.parse(requiredCheckbox[i - 1].getAttribute('data-required'));
                    acymAddInvalidClass(lastName, validation, previousRequired.message);
                } else {
                    checked = 0;
                }
            }

            if (requiredCheckbox[i].checked) {
                checked++;
            }
            lastName = slicedName;
        }

        if (checked === 0) {
            acymAddInvalidClass(lastName, validation, required.message);
        }
    }

    function acym_handleRequiredDate(validation) {
        let requiredDate = document.querySelectorAll('#' + window.acyFormName + ' [acym-field-type="date"][data-required]');
        if (requiredDate.length === 0) return;

        let lastName = '';
        let checked = 0;
        for (let i = 0 ; i < requiredDate.length ; i++) {
            let currentField = requiredDate[i];
            let required = JSON.parse(currentField.getAttribute('data-required'));
            let slicedName = currentField.getAttribute('name').slice(0, currentField.getAttribute('name').lastIndexOf('['));

            if (lastName !== '' && lastName !== slicedName) {
                if (checked < 3) {
                    checked = 0;
                    let previousRequired = JSON.parse(requiredDate[i - 1].getAttribute('data-required'));
                    acymAddInvalidClass(requiredDate[i - 1].name, validation, previousRequired.message);
                } else if (checked > 0) {
                    checked = 0;
                }
            }

            if (currentField.value.length > 0) {
                checked++;
            } else {
                acymAddInvalidClass(currentField.name, validation, required.message);
            }
            lastName = requiredDate[i].getAttribute('name').slice(0, requiredDate[i].getAttribute('name').lastIndexOf('['));
        }
    }

    function acym_handleOtherRequiredFields(validation) {
        let requiredFields = document.querySelectorAll('#'
                                                       + window.acyFormName
                                                       + ' [data-required]:not([type="checkbox"]):not([type="radio"]):not([acym-field-type="date"]):not([name="captcha_code"])');
        if (requiredFields.length === 0) return;

        for (let i = 0 ; i < requiredFields.length ; i++) {
            let required = JSON.parse(requiredFields[i].getAttribute('data-required'));
            if (([
                     'text',
                     'textarea',
                     'single_dropdown',
                     'multiple_dropdown',
                     'phone'
                 ].indexOf(required.type) !== -1) && (requiredFields[i].value === '' || requiredFields[i].value == '0')) {
                acymAddInvalidClass(requiredFields[i].name, validation, required.message);
            }

            if (required.type === 'file' && requiredFields[i].files.length === 0) {
                acymAddInvalidClass(requiredFields[i].name, validation, required.message);
            }
        }
    }

    function acym_handleAuthorizedContent(validation) {
        let authorizeContent = document.querySelectorAll('#' + window.acyFormName + ' [data-authorized-content]');
        if (authorizeContent.length === 0) return;

        for (let i = 0 ; i < authorizeContent.length ; i++) {
            let json = authorizeContent[i].getAttribute('data-authorized-content');
            let authorized;
            let defaultAuthorizeValue = [];
            defaultAuthorizeValue.push(0);

            // Duplicate our acym_helper.parseJSON here because we don't have access to it
            try {
                let begin = json.indexOf('{');
                let beginBrackets = json.indexOf('[');

                if ((!isNaN(begin) && begin > 0) && (!isNaN(beginBrackets) && beginBrackets > 0)) {
                    json = json.substring(begin);
                }

                if (json !== undefined || json !== '') {
                    authorized = JSON.parse(json);
                } else {
                    authorized = defaultAuthorizeValue;
                }
            } catch (error) {
                authorized = defaultAuthorizeValue;
                console.log(error.stack);
            }

            let reg = '';
            if (authorized[0] === 'number') {
                reg = /^[0-9]+$/;
            } else if (authorized[0] === 'letters') {
                reg = /^[a-zA-Z]+$/;
            } else if (authorized[0] === 'numbers_letters') {
                reg = /^[a-zA-Z0-9]+$/;
            } else if (authorized[0] === 'regex') {
                // We trim the / char from the regex
                reg = new RegExp(authorized['regex'].replace(/^\//, '').replace(/\/$/, ''));
            }

            if (reg !== '' && authorizeContent[i].value.length > 0 && !reg.test(authorizeContent[i].value)) {
                acymAddInvalidClass(authorizeContent[i].name, validation, authorized['message']);
            }
        }
    }

    function acymSubmitSubForm() {
        const varform = document.getElementById(window.acyFormName);
        const validation = {errors: 0};

        acym_resetInvalidClass();
        acym_checkEmailField(varform, 'user[email]', validation);
        acym_checkEmailConfirmationField(varform, 'user[email_confirmation]', validation);
        acym_handleRequiredRadio(validation);
        acym_handleRequiredCheckbox(validation);
        acym_handleRequiredDate(validation);
        acym_handleOtherRequiredFields(validation);
        acym_handleAuthorizedContent(validation);

        if (validation.errors > 0) {
            return false;
        }

        // If there are no hidden lists, it means the user must select at least one list to subscribe/unsubscribe
        if (varform.elements['hiddenlists'].value.length < 1) {
            let listschecked = false;
            let allLists = varform.elements['subscription[]'];
            if (allLists && (typeof allLists.value == 'undefined' || allLists.value.length === 0)) {
                for (let b = 0 ; b < allLists.length ; b++) {
                    if (allLists[b].checked) listschecked = true;
                }
            } else if (allLists && allLists.checked) {
                listschecked = true;
            }

            if (!listschecked) {
                if (currentAction !== 'unsubscribe') {
                    alert(acymModule['NO_LIST_SELECTED']);
                } else {
                    alert(acymModule['NO_LIST_SELECTED_UNSUB']);
                }
                return false;
            }
        }

        if (currentAction !== 'unsubscribe') {
            let termsandconditions = varform.elements['terms'];
            if (termsandconditions && !termsandconditions.checked) {
                if (typeof acymModule != 'undefined') {
                    alert(acymModule['ACCEPT_TERMS']);
                }
                return false;
            }

            if (typeof acymModule != 'undefined' && typeof acymModule['excludeValues' + window.acyFormName] != 'undefined') {
                for (let fieldName in acymModule['excludeValues' + window.acyFormName]) {
                    if (!acymModule['excludeValues' + window.acyFormName].hasOwnProperty(fieldName)) continue;
                    if (!varform.elements[`user[${fieldName}]`] || varform.elements[`user[${fieldName}]`].value != acymModule[`excludeValues${window.acyFormName}`][fieldName]) {
                        continue;
                    }

                    varform.elements['user[' + fieldName + ']'].value = '';
                }
            }
        }

        // Handle google analytics
        if (typeof ga != 'undefined') {
            let gaType = currentAction === 'unsubscribe' ? 'unsubscribe' : 'subscribe';
            ga('send', 'pageview', gaType);
        }

        // Set the form's task field to subscribe / unsubscribe
        const taskField = varform.task;
        taskField.value = currentAction;

        let formType = '';
        if (undefined != varform.elements['acymformtype']) {
            formType = varform.elements['acymformtype'].value;
        }

        let redirect = '';
        if (undefined != varform.elements['redirect']) {
            redirect = varform.elements['redirect'].value;
        }

        // If no ajax, submit the form
        if (
            !varform.elements['ajax']
            || !varform.elements['ajax'].value
            || varform.elements['ajax'].value === '0'
            || varform.elements['ajax'].value === 0
        ) {
            if ('shortcode' == formType && '' == redirect) {
                varform.elements['redirect'].value = window.location.href;
            }
            acymApplyCookie();

            return true;
        }

        const form = document.getElementById(window.acyFormName);
        const formData = new FormData(form);

        // Change the acyba form's opacity to show we are doing stuff
        form.className += ' acym_module_loading';
        form.style.filter = 'alpha(opacity=50)';
        form.style.opacity = '0.5';
        currentlySubmittingForm = window.acyFormName;

        // Delete the previous error messages if the user re-submits the form
        const previousErrorMessages = document.querySelectorAll('.responseContainer.acym_module_error.message_' + window.acyFormName);
        Array.prototype.forEach.call(previousErrorMessages, function (node) {
            node.parentNode.removeChild(node);
        });

        fetch(form.getAttribute('action'), {
            method: 'POST',
            body: formData
        })
            .then(response => response.json())
            .then(data => {
                acymDisplayAjaxResponse(data.message, data.type);
            })
            .catch(err => {
                console.error(err);
                acymDisplayAjaxResponse('Ajax Request Failure', 'error');
            })
            .finally(() => {
                if (currentlySubmittingForm === window.acyFormName) {
                    currentlySubmittingForm = '';
                }
            });

        // We prevent the form from submitting as we are doing it using ajax
        return false;
    }

    function acymAddInvalidClass(elemName, validation, message) {
        let elToInvalidate = document.querySelectorAll('#' + window.acyFormName + ' [name^="' + elemName + '"]');
        let container = elToInvalidate[0].closest('.onefield');
        for (let i = 0 ; i < elToInvalidate.length ; i++) {
            elToInvalidate[i].classList.add('acym_invalid_field');
        }

        if (container && container.length !== 0) {
            let displayMessage = container.querySelector('.acym__message__invalid__field');
            let displayCross = container.querySelector('.acym__cross__invalid');
            if (displayMessage) displayMessage.classList.add('acym__message__invalid__field__active');
            if (displayCross) displayCross.classList.add('acym__cross__invalid__active');
            if (message.length > 0) {
                let errorZone = container.querySelector('.acym__field__error__block');
                errorZone.innerText = message;
                errorZone.classList.add('acym__field__error__block__active');
            }
        }

        validation.errors++;
    }

    function acymDisplayAjaxResponse(message, type, replace) {
        //create a new div class=responseContainer as we didn't have one already to display the answer
        let responseContainer = document.createElement('div');
        let fulldiv = document.getElementById('acym_fulldiv_' + window.acyFormName);

        if (fulldiv.firstChild && !fulldiv.classList.contains('acym__subscription__form__popup__overlay')) {
            fulldiv.insertBefore(responseContainer, fulldiv.firstChild);
        } else if (fulldiv.classList.contains('acym__subscription__form__popup__overlay')) {
            fulldiv.querySelector('.acym__subscription__form__popup').appendChild(responseContainer);
        } else {
            fulldiv.appendChild(responseContainer);
        }

        //We reset the class name to responseContainer
        responseContainer.className = 'responseContainer';

        const form = document.getElementById(window.acyFormName);

        let successMode = 'replace';
        if (form.elements['successmode'] != undefined) {
            successMode = form.elements['successmode'].value;
        }

        //We can remove the loading class from the form
        let elclass = form.className;
        let rmclass = 'acym_module_loading';
        let res = elclass.replace(' ' + rmclass, '', elclass);
        if (res == elclass) res = elclass.replace(rmclass + ' ', '', elclass);
        if (res == elclass) res = elclass.replace(rmclass, '', elclass);
        form.className = res;

        //We can set the content of responseContainer with the new message
        responseContainer.innerHTML = message;

        //We set the container class
        if (type === 'success') {
            responseContainer.className += ' acym_module_success';
        } else {
            responseContainer.className += ' acym_module_error';
            form.style.opacity = '1';
        }

        if (replace || (type === 'success' && successMode !== 'toptemp')) {
            form.style.display = 'none';
        }
        responseContainer.className += ' message_' + window.acyFormName;
        responseContainer.className += ' slide_open';

        if (type === 'success' && (successMode === 'replacetemp' || successMode === 'toptemp')) {
            setTimeout(() => {
                responseContainer.remove();
                form.style.filter = 'alpha(opacity=100)';
                form.style.opacity = '1';
                if (successMode === 'replacetemp') {
                    form.style.display = '';
                }
            }, 3000);
        }

        if (type === 'success') {
            acymApplyCookie();
        }
    }

    function acymApplyCookie() {
        const fulldiv = document.getElementById('acym_fulldiv_' + window.acyFormName);

        if (fulldiv.classList.contains('acym__subscription__form-erase')) {
            const form = document.getElementById(window.acyFormName);
            let cookieExpiration = form.getAttribute('acym-data-cookie');
            if (undefined === cookieExpiration) cookieExpiration = 1;
            let exdate = new Date();
            exdate.setDate(exdate.getDate() + parseInt(cookieExpiration));
            document.cookie = 'acym_form_' + form.getAttribute('acym-data-id') + '=' + Date.now() + ';expires=' + exdate.toUTCString() + ';path=/';
            setTimeout(() => {
                fulldiv.remove();
            }, 2000);
        }
    }

    function blockPasteEvent() {
        let emailConfirmationFields = document.querySelectorAll('input[name="user[email_confirmation]"]');
        emailConfirmationFields.forEach(emailConfirmationField => {
            emailConfirmationField.addEventListener('paste', event => {
                event.preventDefault();
            });
        });
    }
}
