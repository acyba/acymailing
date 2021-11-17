(function ($) {
    let $modal = $('.acym_deactivate_modal');
    let $body = $('body');
    let $textarea = $('#acym_feedback_otherReason');
    let feedbackValue = '';

    $modal.appendTo($body);
    registerEventHandlers();

    function registerEventHandlers() {
        let redirectLink = document.querySelector('#deactivate-acymailing').href;
        $('#deactivate-acymailing').on('click', function (evt) {
            evt.preventDefault();
            showModal();
        });
        $('.acym_deactivate_button_deactivate').on('click', evt => {
            let otherReason = document.querySelector('#acym_feedback_otherReason').value.trim();
            if (feedbackValue.length > 0 || otherReason.length > 0) {
                ajaxUrl = ACYM_AJAX_URL + '&ctrl=deactivate&task=saveFeedback';
                jQuery.post(ajaxUrl, {
                    reason: feedbackValue,
                    otherReason: otherReason
                }, response => {
                    deactivateModule(redirectLink);
                }).fail(() => {
                    deactivateModule(redirectLink);
                });
            } else {
                deactivateModule(redirectLink);
            }
        });
        $('.dashicons').on('click', function (ev) {
            closeModal();
        });
        $('input[type=radio]').change(function (evt) {
            if (evt.target.checked == true) {
                feedbackValue = evt.target.value.trim();
                document.querySelector('.acym_deactivate_button_deactivate').innerHTML = ACYM_JS_TXT.ACYM_SUBMIT_AND_DEACTIVATE;
            }
        });
        $('#acym_deactivate_modal_list_otherReason').change(() => {
            $textarea.show();
        });
        $modal.on('click', function (evt) {
            let $target = $(evt.target);
            if ($target.hasClass('acym_deactivate_modal_body') || $target.hasClass('acym_deactivate_modal_footer') || $target.hasClass(
                'acym_deactivate_modal_header')) {
                return;
            }
            if (!$target.hasClass('acym_deactivate_modal_button_close') && ($target.parents('.acym_deactivate_modal_body').length > 0 || $target.parents(
                '.acym_deactivate_modal_footer').length > 0) || ($target.parents('.acym_deactivate_modal_header').length > 0)) {
                return;
            }
            closeModal();
            return false;
        });
    }

    function showModal() {
        $modal.addClass('active');
        $('body').addClass('has-modal');
    }

    function closeModal() {
        $modal.removeClass('active');
        $('body').removeClass('has-modal');
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
