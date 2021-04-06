const acym_editorWysidColorPicker = {
    setColorPickerForContextModal: function ($element, cssPropertyPrevious, $elementFocus, $previousElement, cssPropertyNew, allowEmpty = false, showAlpha = false) {
        $element.spectrum({
            color: $previousElement.css(cssPropertyPrevious),
            preferredFormat: 'hex',
            showButtons: false,
            showInput: true,
            allowEmpty: allowEmpty,
            showAlpha: showAlpha,
            move: function (color) {
                $elementFocus.css(cssPropertyNew, acym_editorWysidColorPicker.getHexStringFromColor(color, showAlpha));
            },
            change: function (color) {
                $elementFocus.css(cssPropertyNew, acym_editorWysidColorPicker.getHexStringFromColor(color, showAlpha));
            },
            containerClassName: 'acym__context__color__picker'
        });
    },
    getHexStringFromColor: function (color, showAlpha) {
        if (color === null) return 'rgba(0,0,0,0)';

        if (showAlpha) return color.toRgbString();

        return color.toHexString();
    },
    setRowColorPickerWYSID: function ($current) {
        let $colorPicker = jQuery('#acym__wysid__context__block__background-color');
        $colorPicker.spectrum({
            color: $current.css('background-color'),
            preferredFormat: 'hex',
            showButtons: false,
            showInput: true,
            showInitial: true,
            showPalette: true,
            palette: [
                ['#fff'],
                ['#000']
            ],
            maxSelectionSize: 1,
            move: function (color) {
                $current.css('background-color', color.toHexString()).attr('bgcolor', color.toHexString());
                $current.css('background-image', '');
            },
            change: function (color) {
                $current.css('background-color', color.toHexString()).attr('bgcolor', color.toHexString());
                $current.css('background-image', '');
            }
        });
    },
    setGeneralColorPickerWYSID: function () {
        let $template = jQuery('.acym__wysid__template__content');
        let $generalColorPicker = jQuery('#acym__wysid__background-colorpicker');

        /**
         * color: Add current template's background color in the color picker
         * move/change: Modify template's background color on move / change
         */
        $generalColorPicker.spectrum({
            color: $template.css('background-color'),
            preferredFormat: 'hex',
            showButtons: false,
            showInput: true,
            showPalette: true,
            palette: [
                ['#fff'],
                ['#000']
            ],
            maxSelectionSize: 1,
            move: function (color) {
                acym_editorWysidFontStyle.setSettingsElementStyle('#acym__wysid__background-colorpicker', 'background-color', color.toHexString());
            },
            change: function (color) {
                acym_editorWysidFontStyle.setSettingsElementStyle('#acym__wysid__background-colorpicker', 'background-color', color.toHexString());
            }
        });
    },
    setSettingsColorPickerWYSID: function (colorHex) {
        let $colorPicker = jQuery('#acym__wysid__right__toolbar__settings__color');
        let $element = jQuery('#acym__wysid__right__toolbar__settings__font--select').val();

        $colorPicker.spectrum({

            color: colorHex != '' ? colorHex : 'black',
            preferredFormat: 'hex',
            showButtons: false,
            showInput: true,
            showPalette: true,
            palette: [
                ['#fff'],
                ['#000']
            ],
            maxSelectionSize: 1,
            move: function (color) {
                acym_editorWysidFontStyle.setSettingsElementStyle($element, 'color', color.toHexString());
            },
            change: function (color) {
                acym_editorWysidFontStyle.setSettingsElementStyle($element, 'color', color.toHexString());
            }
        });
    }
};
