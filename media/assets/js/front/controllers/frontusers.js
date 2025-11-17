document.addEventListener('DOMContentLoaded', function () {
    initUser();
    blockPasteEvent();
    handleUnsubscribeReasonChange();
    handleDisplayedCheckedLists();
    handleLanguageChange();
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
                if (hiddenInputs[i].name === 'data[listsub][' + selectedListId + '][status]') {
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
    const varform = document.getElementById(window.acyFormName);
    const validation = {errors: 0};

    acym_resetInvalidClass();

    acym_checkEmailField(varform, 'data[user][email]', validation);
    acym_checkEmailField(varform, 'user[email]', validation);
    acym_handleRequiredRadio(validation);
    acym_handleRequiredCheckbox(validation);
    acym_handleRequiredDate(validation);
    acym_handleOtherRequiredFields(validation);
    acym_handleAuthorizedContent(validation);
    acym_checkEmailConfirmationField(varform, 'user[email_confirmation]', validation);

    if (validation.errors > 0) {
        return false;
    }

    const formData = new FormData(varform);
    // Change the acyba form's opacity to show we are doing stuff
    varform.className += ' acym_module_loading';
    varform.style.filter = 'alpha(opacity=50)';
    varform.style.opacity = '0.5';

    const message = document.querySelector('.message_' + window.acyFormName);
    if (message) {
        message.parentNode.removeChild(message);
    }

    fetch(varform.getAttribute('action'), {
        method: 'POST',
        body: formData
    })
        .then(response => response.json())
        .then(data => {
            acymDisplayAjaxResponse(decodeURIComponent(data.message), data.type, false);
        })
        .catch(() => {
            acymDisplayAjaxResponse(ACYM_JS_TXT.ACYM_ERROR, 'error', false);
        });

    return false;
}

function acymSubmitForm(task, button) {
    let taskField = button.closest('form').querySelector('[name="task"]');
    let submitButton = button.closest('form').querySelector('#formSubmit');

    if (undefined === taskField || undefined === submitButton) {
        alert(ACYM_JS_TXT.ACYM_COULD_NOT_SUBMIT_FORM_CONTACT_ADMIN_WEBSITE);
        return;
    }

    taskField.value = task;
    submitButton.click();
}

function acym_checkEmailConfirmationField(varform, name, validation) {
    let emailField = varform.elements['user[email]'];
    let emailConfirmationField = varform.elements[name];
    if (emailConfirmationField) {
        if (emailField.value !== emailConfirmationField.value) {
            acymAddInvalidClass(name, validation, acymModule['VALID_EMAIL_CONFIRMATION']);
        }
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

function handleUnsubscribeReasonChange() {
    const select = document.querySelector('select[name="unsubscribe_selector_reason"]');
    if (!select) return;

    const customInput = document.getElementById('acym__custom__unsubscribe__reason');
    const customInputLabel = document.querySelector('.acym__custom__reason__label');
    const hiddenInput = document.querySelector('input[name="unsubscribe_reason"]');

    select.addEventListener('change', function () {
        if (this.selectedIndex === 0) {
            hiddenInput.value = '';
            customInput.classList.add('is-hidden');
            customInputLabel.classList.add('is-hidden');
        } else if (this.selectedIndex === this.options.length - 1) {
            customInput.classList.remove('is-hidden');
            customInputLabel.classList.remove('is-hidden');
            hiddenInput.value = '';
        } else {
            customInput.classList.add('is-hidden');
            customInputLabel.classList.add('is-hidden');
            hiddenInput.value = this.selectedIndex;
        }
        updateFieldStyle(select, hiddenInput.value !== '');
    });

    customInput.addEventListener('input', function () {
        hiddenInput.value = this.value;
        updateFieldStyle(select, hiddenInput.value !== '');
    });
}

function updateFieldStyle(field, isValid) {
    if (isValid) {
        field.classList.add('acym__unsub__reason__selected');
    } else {
        field.classList.remove('acym__unsub__reason__selected');
    }
}

function handleDisplayedCheckedLists() {
    const hiddenCheckedLists = document.getElementById('displayed_checked_lists');
    if (!hiddenCheckedLists) {
        return;
    }

    const displayedCheckedList = [];
    const displayedLists = document.querySelectorAll('.acym__unsubscribe__list__switch input.switch-input');

    displayedLists.forEach(item => {
        const hiddenInput = item.closest('.acym__unsubscribe__list__switch').querySelector('input[type="hidden"]');
        item.addEventListener('change', () => {
            hiddenInput.value = item.checked ? '1' : '0';
        });

        if (item.checked) {
            hiddenInput.value = '1';
            displayedCheckedList.push(hiddenInput.name.match(/\d+/)[0]);
        } else {
            hiddenInput.value = '0';
        }
    });

    hiddenCheckedLists.value = displayedCheckedList.join(',');
}

function handleLanguageChange() {
    const select = document.getElementById('acym__unsubscribe__language__select');
    let link = window.location.href;
    const languageParam = link.match(/&language=[^&]+/);
    if (null !== languageParam && languageParam.length > 0) {
        link = link.replace(languageParam[0], '');
    }
    if (null !== select) {
        select.addEventListener('change', function () {
            link += '&language=' + this.value;
            window.location.href = link;
        });
    }
}
