document.addEventListener('DOMContentLoaded', function () {
    acym_helperUser.setSubscribeUnsubscribeUser();
    acym_helperImport.initImport();
    initUser();
});

function initUser() {
    let dropdowns = document.querySelectorAll('select[name="data[listsubdropdown]"]');

    for (let dropdown in dropdowns) {
        if (!dropdowns.hasOwnProperty(dropdown)) continue;

        dropdowns[dropdown].addEventListener('change', function () {
            let dropdown = document.getElementById('datalistsubdropdown');
            let selectedOption = dropdown.options[dropdown.selectedIndex];
            let selectedListId = selectedOption.value;

            let hiddenInputs = document.getElementsByClassName('listsub-dropdown');
            for (let i = 0 ; i < hiddenInputs.length ; i++) {
                hiddenInputs[i].value = '0';
                if (hiddenInputs[i].name == 'data[listsub][' + selectedListId + '][status]') {
                    hiddenInputs[i].value = '1';
                }
            }
        });
    }

    var showSusbcriptionZone = document.querySelector('.acym__user__show-subscription');
    if (showSusbcriptionZone) {
        showSusbcriptionZone.addEventListener('click', function () {
            let buttonShowSubscription = this;
            let subscriptions = buttonShowSubscription.parentElement;
            let buttonText = subscriptions.querySelector('.acym__user__show-subscription-bt');
            subscriptions = subscriptions.querySelectorAll('.acym_subscription_more');
            if (buttonShowSubscription.getAttribute('data-iscollapsed') == 0) {
                buttonShowSubscription.setAttribute('data-iscollapsed', '1');
                buttonText.innerText = '<';
                for (let additionalList in subscriptions) {
                    if (!subscriptions.hasOwnProperty(additionalList)) continue;
                    subscriptions[additionalList].style.display = 'inline-block';
                }
            } else {
                buttonShowSubscription.setAttribute('data-iscollapsed', '0');
                buttonText.innerText = '+' + buttonShowSubscription.getAttribute('acym-data-value');
                for (let additionalList in subscriptions) {
                    if (!subscriptions.hasOwnProperty(additionalList)) continue;
                    subscriptions[additionalList].style.display = 'none';
                }
            }
        });
    }
}

function acym_checkChangeForm() {
    let varform = document.acyprofileform;
    let emailField = varform.elements['data[user][email]'];
    if (emailField) {
        if (emailField.value != acymModule['EMAILCAPTION']) {
            emailField.value = emailField.value.replace(/ /g, '');
        }

        let filter = acymModule['emailRegex'];
        if (!emailField || (emailField.value == acymModule['EMAILCAPTION']) || !filter.test(emailField.value)) {
            alert(acymModule['VALID_EMAIL']);
            emailField.className = emailField.className + ' invalid';
            return false;
        }
    }

    //required fields
    let lastName, checked, i, required, reg;
    let requiredRadio = document.querySelectorAll('[type="radio"][data-required]');
    if (requiredRadio.length > 0) {
        lastName = '';
        checked = 0;
        for (i = 0 ; i < requiredRadio.length ; i++) {
            required = JSON.parse(requiredRadio[i].getAttribute('data-required'));
            if (lastName !== '' && lastName != requiredRadio[i].getAttribute('name') && checked == 0) {
                required = JSON.parse(requiredRadio[i - 1].getAttribute('data-required'));
                alert(required.message);
                return false;
            } else if (lastName !== '' && lastName != requiredRadio[i].getAttribute('name') && checked > 0) {
                checked = 0;
            }
            if (requiredRadio[i].checked) {
                checked++;
            }
            lastName = requiredRadio[i].getAttribute('name');
        }
        if (checked === 0) {
            alert(required.message);
            return false;
        }
    }

    let requiredCheckbox = document.querySelectorAll('[type="checkbox"][data-required]');
    if (requiredCheckbox.length > 0) {
        lastName = '';
        checked = 0;
        for (i = 0 ; i < requiredCheckbox.length ; i++) {
            required = JSON.parse(requiredCheckbox[i].getAttribute('data-required'));
            if (lastName !== '' && lastName != requiredCheckbox[i].getAttribute('name') && checked == 0) {
                required = JSON.parse(requiredCheckbox[i - 1].getAttribute('data-required'));
                alert(required.message);
                return false;
            } else if (lastName !== '' && lastName != requiredCheckbox[i].getAttribute('name') && checked > 0) {
                checked = 0;
            }
            if (requiredCheckbox[i].checked) {
                checked++;
            }
            lastName = requiredCheckbox[i].getAttribute('name');
        }
        if (checked === 0) {
            alert(required.message);
            return false;
        }
    }

    let requiredFields = document.querySelectorAll('[data-required]');
    if (requiredFields.length > 0) {
        for (i = 0 ; i < requiredFields.length ; i++) {
            required = JSON.parse(requiredFields[i].getAttribute('data-required'));
            if ((required.type
                 === 'text'
                 || required.type
                 === 'textarea'
                 || required.type
                 === 'single_dropdown'
                 || required.type
                 === 'multiple_dropdown'
                 || required.type
                 === 'phone'
                 || required.type
                 === 'file') && requiredFields[i].value === '') {
                alert(required.message);
                return false;
            } else if (required.type === 'file' && requiredFields[i].files.length === 0) {
                alert(required.message);
                return false;
            }
        }
    }


    let authorizeContent = document.querySelectorAll('[data-authorized-content]');
    if (authorizeContent.length > 0) {
        for (i = 0 ; i < authorizeContent.length ; i++) {
            let authorized = JSON.parse(authorizeContent[i].getAttribute('data-authorized-content'));
            reg = '';
            if (authorized[0] === 'number') {
                reg = /^[0-9]+$/;
            } else if (authorized[0] === 'letters') {
                reg = /^[a-zA-Z]+$/;
            } else if (authorized[0] === 'numbers_letters') {
                reg = /^[a-zA-Z0-9]+$/;
            } else if (authorized[0] === 'regex') {
                reg = new RegExp(authorized['regex']);
            }

            if (reg != '' && !reg.test(authorizeContent[i].value)) {
                alert(authorized['message']);
                return false;
            }
        }
    }

    let formData = new FormData(varform);
    // Change the acyba form's opacity to show we are doing stuff
    varform.className += ' acym_module_loading';
    varform.style.filter = 'alpha(opacity=50)';
    varform.style.opacity = '0.5';

    let message = document.querySelector('.message_acyprofileform');
    if (message) message.parentNode.removeChild(message);

    let xhr = new XMLHttpRequest();
    xhr.open('POST', varform.getAttribute('action'));
    xhr.onload = function () {
        let message = 'Ajax Request Failure';
        let type = 'error';

        if (xhr.status === 200) {
            let response = JSON.parse(xhr.responseText);
            message = response.message;
            type = response.type;
        }
        acymDisplayAjaxResponse(decodeURIComponent(message), type, 'acyprofileform', false);
    };
    xhr.send(formData);

    return false;
}

function acymSubmitForm(task) {
    let taskField = document.querySelector('[name="task"]');
    let submitButton = document.getElementById('formSubmit');

    if (undefined === taskField || undefined === submitButton) {
        alert(ACYM_JS_TXT.ACYM_COULD_NOT_SUBMIT_FORM_CONTACT_ADMIN_WEBSITE);
        return;
    }

    taskField.value = task;
    submitButton.click();
}
