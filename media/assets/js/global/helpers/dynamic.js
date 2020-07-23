const acym_helperDynamic = {
    initPopup: function () {
        setTimeout(function () {
            jQuery('#acym__wysid__modal__dynamic-text__ui__iframe').contents().find('body').css('padding', 0).css('margin', 0).css('height', '100%');
            jQuery('#acym__wysid__modal__dynamic-text__ui__iframe').contents().find('body #acym_wrapper').css('min-height', '100%');
        }, 1000);
    },
    setModalDynamics: function () {
        jQuery('.acym__wysid__modal__dynamic-text--close').off('click').on('click', function () {
            jQuery('#acym__wysid__modal__dynamic-text').hide();
        });

        jQuery('#dtext_subject_button').off('click').on('click', function (e) {
            e.preventDefault();
            jQuery('#acym__wysid__modal__dynamic-text__ui__iframe').contents().find('input[name="dtextcode"]').val('');
            jQuery('#acym__wysid__modal__dynamic-text').show();
        });
    }
};