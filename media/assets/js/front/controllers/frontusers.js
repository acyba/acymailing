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
    let varform = document[acyformName];
    let validation = {errors: 0};

    acym_resetInvalidClass();

    acym_checkEmailField(varform, 'data[user][email]', validation);
    acym_checkEmailField(varform, 'user[email]', validation);
    acym_handleRequiredRadio(validation);
    acym_handleRequiredCheckbox(validation);
    acym_handleRequiredDate(validation);
    acym_handleOtherRequiredFields(validation);
    acym_handleAuthorizedContent(validation);

    if (validation.errors > 0) {
        return false;
    }

    let formData = new FormData(varform);
    // Change the acyba form's opacity to show we are doing stuff
    varform.className += ' acym_module_loading';
    varform.style.filter = 'alpha(opacity=50)';
    varform.style.opacity = '0.5';

    let message = document.querySelector('.message_' + acyformName);
    if (message) message.parentNode.removeChild(message);

    let xhr = new XMLHttpRequest();
    xhr.open('POST', varform.getAttribute('action'));
    xhr.onload = function () {
        let message = ACYM_JS_TXT.ACYM_ERROR;
        let type = 'error';

        if (xhr.status === 200) {
            let response = JSON.parse(xhr.responseText);
            message = response.message;
            type = response.type;
        }
        acymDisplayAjaxResponse(decodeURIComponent(message), type, acyformName, false);
    };
    xhr.send(formData);

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
