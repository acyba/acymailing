jQuery(document).ready(function ($) {
    function Init() {
        setColorPicker();
    }

    Init();

    function setColorPicker() {
        let $colorField = $('#acym__list__settings__color-picker');
        if (typeof $colorField.spectrum == 'function') {
            $colorField
                .spectrum({
                    showInput: true,
                    preferredFormat: 'hex',
                });
        }
    }
});
