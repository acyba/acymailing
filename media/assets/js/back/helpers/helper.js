const acym_helperBack = {
    setDoNotRemindMe: function () {
        jQuery('#acym__reviews__footer__link').on('click', function () {
            let ajaxUrl = ACYM_TOGGLE_URL + '&task=setDoNotRemindMe&value=' + encodeURIComponent(jQuery(this).attr('title'));
            jQuery.post(ajaxUrl, function (response) {
                response = acym_helper.parseJson(response);
                if (response.error) {
                    console.log(response.message);
                } else {
                    jQuery('#acym__reviews__footer').html(response.message);
                }
            });
        });

        jQuery('.acym__do__not__remindme, .acym__do__not__remindme__multilingual').on('click', function () {
            let identifier = jQuery(this).attr('title');
            jQuery('[data-news="' + identifier + '"]').remove();
            let ajaxUrl = ACYM_TOGGLE_URL + '&task=setDoNotRemindMe&value=' + encodeURIComponent(identifier);
            jQuery.post(ajaxUrl, function (response) {
                response = acym_helper.parseJson(response);
                if (response.error) {
                    console.log(response.message);
                } else {
                    location.reload();
                }
            });
        });

        jQuery('#acym__multilingual__reminder').on('click', function () {
            localStorage.setItem('acyconfiguration', 'languages');
        });
    }
};
