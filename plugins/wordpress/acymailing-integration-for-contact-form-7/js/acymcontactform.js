document.addEventListener('wpcf7submit', function (event) {
    let allAcyFields = jQuery('[name^="acymhiddenlists_"]');

    for (let i = 0 ; i < allAcyFields.length ; i++) {
        const currentfield = jQuery(allAcyFields[i]);
        const formcontainer = currentfield.closest('form');

        // If the form has the class "invalid", continue
        if (formcontainer.hasClass('invalid')) {
            continue;
        }

        const listField = currentfield.attr('data-acymfield');
        const urlAction = jQuery('[name="acymaction_' + listField + '"]').val();

        let mailField = currentfield.attr('data-acymmail');
        const nameField = currentfield.attr('data-acymname');
        if (undefined === mailField || mailField.length === 0) {
            let inputs = event.detail.inputs;
            for (let k = 0 ; k < inputs.length ; k++) {
                if (jQuery('[name="' + inputs[k].name + '"]').attr('type') === 'email') {
                    mailField = inputs[k].name;
                    break;
                }
            }
        }

        if (mailField.length === 0 || listField.length === 0 || urlAction.length === 0) {
            return;
        }

        const emailAddress = jQuery('[name="' + mailField + '"]').val();
        let nameValue = '';

        if (undefined !== nameField && nameField.length !== 0) {
            nameValue = jQuery('[name="' + nameField + '"]').val();
        }

        const hiddenLists = currentfield.val();
        const allListsField = jQuery('[name="' + listField + '[]"]');
        const allLists = [];
        for (let j = 0 ; j < allListsField.length ; j++) {
            if (allListsField[j].checked) allLists.push(allListsField[j].value);
        }

        if (allLists.length === 0 && hiddenLists.length === 0) {
            continue;
        }

        jQuery.ajax({
            type: 'POST',
            url: urlAction,
            data: {
                'user[email]': emailAddress,
                'user[name]': nameValue,
                'hiddenlists': hiddenLists,
                'subscription': allLists,
                'acy_source': 'Contact Form 7'
            },
            timeout: 5000,
            error: function (request, status, error) {
                console.log('Error subscribing user: '.request.responseText);
            }
        });
    }
}, false);

jQuery(function ($) {
    jQuery('.tag-generator-panel [name="displayLists[]"], .tag-generator-panel [name="defaultLists[]"], .tag-generator-panel [name="autoLists[]"]')
        .on('change', function () {
            const displayLists = jQuery('.tag-generator-panel [name="displayLists[]"]').val();
            const defaultLists = jQuery('.tag-generator-panel [name="defaultLists[]"]').val();
            const autoLists = jQuery('.tag-generator-panel [name="autoLists[]"]').val();

            let finalVal = '';
            if (null != displayLists) finalVal += 'displayLists:' + displayLists.join(',') + '\n';
            if (null != defaultLists) finalVal += 'defaultLists:' + defaultLists.join(',') + '\n';
            if (null != autoLists) finalVal += 'autoLists:' + autoLists.join(',') + '\n';

            jQuery('[name="values"]').val(finalVal).change();
        });
});
