const acym_helperBack = {
    config_get: function (field) {
        let ajaxUrl = ACYM_AJAX_URL + '&ctrl=configuration&task=getAjax&field=' + field;
        return jQuery.ajax({
            type: 'GET',
            url: ajaxUrl,
            dataType: 'json'
        });
    },
    setDoNotRemindMe: function () {
        jQuery('#acym__reviews__footer__link').on('click', function () {
            let ajaxUrl = ACYM_TOGGLE_URL + '&task=setDoNotRemindMe&value=' + encodeURIComponent(jQuery(this).attr('title'));
            jQuery.get(ajaxUrl, function (response) {
                response = acym_helper.parseJson(response);
                if ('' === response.error) {
                    jQuery('#acym__reviews__footer').html(response.message);
                } else {
                    console.log(response.error);
                }
            });
        });

        jQuery('.acym__do__not__remindme').on('click', function () {
            let identifier = jQuery(this).attr('title');
            jQuery('[data-news="' + identifier + '"]').remove();
            let ajaxUrl = ACYM_TOGGLE_URL + '&task=setDoNotRemindMe&value=' + encodeURIComponent(identifier);
            jQuery.get(ajaxUrl, function (response) {
                response = acym_helper.parseJson(response);
                if ('' !== response.error) {
                    console.log(response.error);
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
