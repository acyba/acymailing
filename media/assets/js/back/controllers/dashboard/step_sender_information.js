jQuery(function ($) {
    function Init() {
        stepSenderInformation();
    }

    Init();

    function stepSenderInformation() {
        jQuery('#acym__walkthrough__sender_information__submit').on('click', function () {
            const fromName = jQuery('input[name="from_name"]').val();
            const fromEmail = jQuery('input[name="from_email"]').val();

            if (fromName === '' || fromEmail === '') {
                alert(ACYM_JS_TXT.ACYM_FILL_ALL_INFORMATION);
                return;
            }

            $('#acym_form').trigger('submit');
        });
    }
});
