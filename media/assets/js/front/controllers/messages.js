function acymSetCallouts() {
    const callouts = document.getElementsByClassName('acym_callout');

    for (let i = 0 ; i < callouts.length ; i++) {
        const callout = callouts[i];
        const calloutClose = callout.getElementsByClassName('acym_callout_close')[0];

        acymDisplayCallout(callout, i);

        calloutClose.onclick = function (event) {
            const eventElement = event.target;
            const eventCallout = eventElement.closest('.acym_callout');

            acymCloseCallout(eventCallout);
        };
    }
}

function acymCloseCallout(callout) {
    callout.style['margin-left'] = '640px';
    callout.style['margin-right'] = '-640px';
    setTimeout(function () {
        callout.remove();
    }, 1000);
}

function acymDisplayCallout(callout, i) {
    setTimeout(function () {
        callout.style['margin-left'] = '0px';
        callout.style['margin-right'] = '0px';
    }, 1000 * i);
}

document.addEventListener('DOMContentLoaded', function () {
    fetch(ACYM_AJAX + '&page=acymailing_front&ctrl=frontusers&task=ajaxGetEnqueuedMessages', {
        method: 'POST'
    })
        .then(response => response.text())
        .then(response => {
            try {
                const begin = response.indexOf('{');
                const beginBrackets = response.indexOf('[');

                if ((
                    !isNaN(begin) && begin > 0
                    ) && (
                    !isNaN(beginBrackets) && beginBrackets > 0
                    )) {
                    response = response.substring(begin);
                }

                if (response !== undefined || response !== '') {
                    response = JSON.parse(response);
                }
            } catch (error) {
                console.log(error.stack);
            }

            if (!response || !response.data || !response.data.messages || response.data.messages.length === 0) {
                return;
            }

            const messagesContainer = document.createElement('div');
            messagesContainer.innerHTML = response.data.messages;
            while (messagesContainer.children.length > 0) {
                document.body.appendChild(messagesContainer.children[0]);
            }

            acymSetCallouts();
        })
        .catch(error => console.log(error.stack));
});
