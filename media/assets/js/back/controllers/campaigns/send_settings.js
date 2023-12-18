jQuery(function ($) {

    function Init() {
        setSendSettingsAutoSelect();
        setAbTestSlider();
        setGoogleAnalyticsFields();
        acym_helperSelectionMultilingual.init('campaign');

        $('.acym__campaign__sendsettings__params__one select').addClass('shrink');
    }

    Init();

    function setSendSettingsAutoSelect() {
        const $selectTriggers = $('[name="acym_triggers"]');
        $selectTriggers.on('change', function () {
            $('.acym__campaign__sendsettings__params__one').hide();
            $('[data-trigger-show="' + $(this).val() + '"]').show();
            $('.acym__campaign__sendsettings__params__one select')
                .select2({
                    theme: 'foundation',
                    width: '100%'
                });
        });
        $selectTriggers.trigger('change');
    }

    function setAbTestSlider() {
        const $sliderValue = $('#acym__campaign__sendsettings__send__abtest-value');
        const $subscribers = $('#acym__campaign__sendsettings__abtest__number-subscribers');
        const totalOfSubscribers = parseInt($subscribers.attr('data-acym-subscribers'));
        $subscribers.text(totalOfSubscribers * $sliderValue.val() / 100);

        $('#acym__campaign__sendsettings__send__abtest-slider').on('moved.zf.slider', function () {
            $(this).find('.slider-value').html(`${$sliderValue.val()}%`);
            $subscribers.text(Math.round(totalOfSubscribers * $sliderValue.val() / 100));
        });
    }

    function setGoogleAnalyticsFields() {
        const utm = $('#utm_settings');
        if (!utm.length) {
            return;
        }

        $('input[name="senderInformation[tracking]"]').on('change', function () {
            utm.toggle();
        });
    }
});
