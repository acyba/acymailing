jQuery(function($) {

    function Init() {
        setSendSettingsAutoSelect();
        acym_helperSelectionMultilingual.init('campaign');

        $('.acym__campaign__sendsettings__params__one select').addClass('shrink');
    }

    Init();

    function setSendSettingsAutoSelect() {
        let $selectTriggers = $('[name="acym_triggers"]');
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
});
