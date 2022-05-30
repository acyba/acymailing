if (typeof submitAcymForm !== 'function') {
    var acytask, acyformName, acysubmitting;

    blockPasteEvent();

    function submitAcymForm(newtask, newformName, submitFunction) {
        if (typeof acysubmitting !== 'undefined' && acysubmitting !== undefined && acysubmitting === newformName) return;

        acytask = newtask;
        acyformName = newformName;
        submitFunction = submitFunction === undefined ? 'acymSubmitSubForm' : submitFunction;

        let recaptchaid = 'acym-captcha';
        if (newformName) recaptchaid = newformName + '-captcha';

        let initRecaptcha = document.querySelector('#' + recaptchaid + '[class="acyg-recaptcha"][data-size="invisible"]');
        if (!initRecaptcha || typeof grecaptcha != 'object') return window[submitFunction]();
        if (initRecaptcha.getAttribute('data-captchaname') === 'acym_ireCaptcha') {
            initRecaptcha.className = 'g-recaptcha';
            let invisibleRecaptcha = document.querySelector('#' + recaptchaid + '[class="g-recaptcha"][data-size="invisible"]');

            if (!invisibleRecaptcha) return window[submitFunction]();

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
            if (!captcha) return window[submitFunction]();
            grecaptcha.ready(function () {
                grecaptcha.execute(captcha.getAttribute('data-sitekey'), {action: 'submit'}).then(function (token) {
                    var input = document.createElement('input');
                    input.setAttribute('type', 'hidden');
                    input.setAttribute('name', 'g-recaptcha-response');
                    input.setAttribute('value', token);
                    document.getElementById(newformName).appendChild(input);
                    return window[submitFunction]();
                });
            });
        }
    }

    function resetRecaptcha() {
        let recaptchaid = 'acym-captcha';
        if (acyformName) recaptchaid = acyformName + '-captcha';

        let invisibleRecaptcha = document.querySelector('#' + recaptchaid + '[class="g-recaptcha"][data-size="invisible"]');
        if (!invisibleRecaptcha) return;

        let grcID = invisibleRecaptcha.getAttribute('grcID');
        grecaptcha.reset(grcID);
    }

    function acym_resetInvalidClass() {
        let invalidFields = document.querySelectorAll('#' + acyformName + ' .acym_invalid_field');
        if (invalidFields.length !== 0) {
            for (let i = 0 ; i < invalidFields.length ; i++) {
                invalidFields[i].classList.remove('acym_invalid_field');
            }
        }

        let errorZones = document.querySelectorAll('#' + acyformName + ' .acym__field__error__block');
        if (errorZones.length !== 0) {
            for (let i = 0 ; i < errorZones.length ; i++) {
                errorZones[i].classList.remove('acym__field__error__block__active');
            }
        }

        let displayedMessages = document.querySelectorAll('#' + acyformName + ' .acym__message__invalid__field');
        if (displayedMessages.length !== 0) {
            for (let i = 0 ; i < displayedMessages.length ; i++) {
                displayedMessages[i].classList.remove('acym__message__invalid__field__active');
            }
        }
        let displayedCross = document.querySelectorAll('#' + acyformName + ' .acym__cross__invalid');
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

            let filter = acymModule['emailRegex'];
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
        let requiredRadio = document.querySelectorAll('#' + acyformName + ' [type="radio"][data-required]');
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
        let requiredCheckbox = document.querySelectorAll('#' + acyformName + ' [type="checkbox"][data-required]');
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
        let requiredDate = document.querySelectorAll('#' + acyformName + ' [acym-field-type="date"][data-required]');
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
                                                       + acyformName
                                                       + ' [data-required]:not([type="checkbox"]):not([type="radio"]):not([acym-field-type="date"])');
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
        let authorizeContent = document.querySelectorAll('#' + acyformName + ' [data-authorized-content]');
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
                reg = new RegExp(authorized['regex']);
            }

            if (reg !== '' && authorizeContent[i].value.length > 0 && !reg.test(authorizeContent[i].value)) {
                acymAddInvalidClass(authorizeContent[i].name, validation, authorized['message']);
            }
        }
    }

    function acymSubmitSubForm() {
        let varform = document[acyformName];
        let validation = {errors: 0};
        let filterEmail = acymModule['emailRegex'];

        // I don't understand this code, I guess it's useful
        if (!varform.elements) {
            //Try to get the right form as we may have several ones on the same page with the same ID... :(
            if (varform[0].elements['user[email]'] && varform[0].elements['user[email]'].value && filterEmail.test(varform[0].elements['user[email]'].value)) {
                varform = varform[0];
            } else {
                varform = varform[varform.length - 1];
            }
        }

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
                if (!listschecked) {
                    if (acytask !== 'unsubscribe') {
                        alert(acymModule['NO_LIST_SELECTED']);
                    } else {
                        alert(acymModule['NO_LIST_SELECTED_UNSUB']);
                    }
                    return false;
                }
            }
        }

        if (acytask !== 'unsubscribe') {
            let termsandconditions = varform.elements['terms'];
            if (termsandconditions && !termsandconditions.checked) {
                if (typeof acymModule != 'undefined') {
                    alert(acymModule['ACCEPT_TERMS']);
                }
                return false;
            }

            if (typeof acymModule != 'undefined' && typeof acymModule['excludeValues' + acyformName] != 'undefined') {
                for (let fieldName in acymModule['excludeValues' + acyformName]) {
                    if (!acymModule['excludeValues' + acyformName].hasOwnProperty(fieldName)) continue;
                    if (!varform.elements['user[' + fieldName + ']'] || varform.elements['user[' + fieldName + ']'].value != acymModule['excludeValues'
                                                                                                                                        + acyformName][fieldName]) {
                        continue;
                    }

                    varform.elements['user[' + fieldName + ']'].value = '';
                }
            }
        }

        // Handle google analytics
        if (typeof ga != 'undefined') {
            let gaType = acytask === 'unsubscribe' ? 'unsubscribe' : 'subscribe';
            ga('send', 'pageview', gaType);
        }

        // Set the form's task field to subscribe / unsubscribe
        let taskField = varform.task;
        taskField.value = acytask;

        // If no ajax, submit the form
        if (!varform.elements['ajax'] || !varform.elements['ajax'].value || varform.elements['ajax'].value === '0' || varform.elements['ajax'].value === 0) {
            acymApplyCookie(acyformName);

            varform.submit();
            return false;
        }

        let form = document.getElementById(acyformName);
        let formData = new FormData(form);

        // Change the acyba form's opacity to show we are doing stuff
        form.className += ' acym_module_loading';
        form.style.filter = 'alpha(opacity=50)';
        form.style.opacity = '0.5';
        acysubmitting = acyformName;

        // Delete the previous error messages if the user re-submits the form
        let previousErrorMessages = document.querySelectorAll('.responseContainer.acym_module_error.message_' + acyformName);
        Array.prototype.forEach.call(previousErrorMessages, function (node) {
            node.parentNode.removeChild(node);
        });

        let xhr = new XMLHttpRequest();
        xhr.open('POST', form.getAttribute('action'));
        xhr.onload = function () {
            let message = 'Ajax Request Failure';
            let type = 'error';
            if (acysubmitting === acyformName) {
                acysubmitting = '';
            }

            if (xhr.status === 200) {
                try {
                    let response = JSON.parse(xhr.responseText);
                    message = response.message;
                    type = response.type;
                } catch {
                    message = xhr.responseText;
                }
            }
            acymDisplayAjaxResponse(message, type, acyformName);
        };
        xhr.send(formData);

        return false;
    }

    function acymAddInvalidClass(elemName, validation, message) {
        let elToInvalidate = document.querySelectorAll('#' + acyformName + ' [name^="' + elemName + '"]');
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

        return true;
    }

    function acymDisplayAjaxResponse(message, type, formName, replace) {
        //create a new div class=responseContainer as we didn't have one already to display the answer
        let responseContainer = document.createElement('div');
        let fulldiv = document.getElementById('acym_fulldiv_' + formName);

        if (fulldiv.firstChild && !fulldiv.classList.contains('acym__subscription__form__popup__overlay')) {
            fulldiv.insertBefore(responseContainer, fulldiv.firstChild);
        } else if (fulldiv.classList.contains('acym__subscription__form__popup__overlay')) {
            fulldiv.querySelector('.acym__subscription__form__popup').appendChild(responseContainer);
        } else {
            fulldiv.appendChild(responseContainer);
        }

        //We reset the class name to responseContainer
        responseContainer.className = 'responseContainer';

        let form = document.getElementById(formName);
        let varform = document[formName];
        let successMode = 'replace';
        if (varform.elements['successmode'] != undefined) {
            successMode = varform.elements['successmode'].value;
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
        responseContainer.className += ' message_' + formName;
        responseContainer.className += ' slide_open';

        if (successMode === 'replacetemp' || successMode === 'toptemp') {
            setTimeout(() => {
                responseContainer.remove();
                form.style.filter = 'alpha(opacity=100)';
                form.style.opacity = '1';
                if (successMode === 'replacetemp') {
                    form.style.display = '';
                }
            }, 3000);
        }

        acymApplyCookie(formName);
    }

    function acymApplyCookie(formName) {
        let fulldiv = document.getElementById('acym_fulldiv_' + formName);

        if (fulldiv.classList.contains('acym__subscription__form-erase')) {
            let form = document.getElementById(formName);
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
