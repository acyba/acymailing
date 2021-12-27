(function ($) {
    let $modal = $('.acym_deactivate_modal');
    let $body = $('body');
    let $textarea = $('#acym_feedback_otherReason');
    let feedbackValue = '';

    $modal.appendTo($body);
    registerEventHandlers();

    function registerEventHandlers() {
        let redirectLink = document.querySelector('#deactivate-acymailing').href;

        $('#deactivate-acymailing').on('click', function (event) {
            event.preventDefault();
            showModal();
        });

        $('.acym_deactivate_button_deactivate').on('click', event => {
            let otherReason = document.querySelector('#acym_feedback_otherReason').value.trim();
            if (feedbackValue.length > 0 && (feedbackValue !== 'ACYM_OTHER' || otherReason.length > 0)) {
                ajaxUrl = ACYM_AJAX_URL + '&ctrl=deactivate&task=saveFeedback';
                jQuery.post(ajaxUrl, {
                    reason: feedbackValue,
                    otherReason: feedbackValue === 'ACYM_OTHER' ? otherReason : ''
                }, response => {
                    deactivateModule(redirectLink);
                }).fail(() => {
                    deactivateModule(redirectLink);
                });
            } else {
                deactivateModule(redirectLink);
            }
        });

        $modal.on('click', function (event) {
            if (event.target !== this) return;
            closeModal();
        });

        $('.dashicons-no-alt, .acym_deactivate_modal_button_close').on('click', function (event) {
            event.preventDefault();
            closeModal();
        });

        $('input[type=radio]').change(function (event) {
            if (!event.target.checked) return;
            feedbackValue = event.target.value.trim();
            updateDeactivationButton();
        });

        $textarea.bind('input propertychange', function () {
            if (feedbackValue !== 'ACYM_OTHER') return;
            updateDeactivationButton();
        });

        $('#acym_deactivate_modal_list_otherReason').change(() => {
            $textarea.show();
        });
    }

    function updateDeactivationButton() {
        let otherReason = document.querySelector('#acym_feedback_otherReason').value.trim();
        let deactivateButton = document.querySelector('.acym_deactivate_button_deactivate');
        if (feedbackValue === 'ACYM_OTHER' && otherReason.length === 0) {
            deactivateButton.innerHTML = ACYM_JS_TXT.ACYM_SKIP_AND_DEACTIVATE;
        } else {
            deactivateButton.innerHTML = ACYM_JS_TXT.ACYM_SUBMIT_AND_DEACTIVATE;
        }
    }

    function showModal() {
        $modal.addClass('active');
        $body.addClass('has-modal');
    }

    function closeModal() {
        $modal.removeClass('active');
        $body.removeClass('has-modal');
        resetModal();
    }

    function deactivateModule(redirectLink) {
        window.location.href = redirectLink;
    }

    function resetModal() {
        $modal.find('input[type="radio"]').prop('checked', false);
        $modal.find('#acym_feedback_otherReason').val('');
        document.querySelector('.acym_deactivate_button_deactivate').innerHTML = ACYM_JS_TXT.ACYM_SKIP_AND_DEACTIVATE;
        $textarea.hide();
    }

})(jQuery);
