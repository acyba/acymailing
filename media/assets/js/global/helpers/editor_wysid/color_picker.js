const acym_editorWysidColorPicker = {
    setColorPickerForContextModal: function ($element, cssPropertyPrevious, $elementFocus, $previousElement, cssPropertyNew, allowEmpty = false, showAlpha = false) {
        let palette = acym_editorWysidColorPicker.getMainColors();
        $element.spectrum({
            color: $previousElement.css(cssPropertyPrevious),
            preferredFormat: 'hex',
            showButtons: false,
            showInput: true,
            allowEmpty: allowEmpty,
            showAlpha: showAlpha,
            showPalette: true,
            palette: palette,
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
        let palette = acym_editorWysidColorPicker.getMainColors();
        $colorPicker.spectrum({
            color: $current.css('background-color'),
            preferredFormat: 'hex',
            showButtons: false,
            showInput: true,
            showInitial: true,
            showPalette: true,
            palette: palette,
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
    setMainColorPickerWYSID: function () {
        let $template = jQuery('.acym__wysid__template__content');
        let mainColors = jQuery('#main_colors');
        let $mainColorPicker1 = jQuery('#acym__wysid__maincolor-colorpicker1');
        let $mainColorPicker2 = jQuery('#acym__wysid__maincolor-colorpicker2');
        let $mainColorPicker3 = jQuery('#acym__wysid__maincolor-colorpicker3');
        let $generalColorPicker = jQuery('#acym__wysid__background-colorpicker');
        let $settingsColorPicker = jQuery('#acym__wysid__right__toolbar__settings__color');
        let palette = acym_editorWysidColorPicker.getMainColors('saved');
        let tmplColor = $template.css('background-color');
        palette.push([tmplColor]);

        let optionsMainColorPickers = {
            preferredFormat: 'hex',
            showButtons: false,
            showInput: true,
            showPalette: true,
            palette: palette,
            maxSelectionSize: 6,
            change: function (color) {
                palette.push([color.toHexString()]);
                if (palette.length > 6) {
                    palette = palette.slice(-6);
                }
                $generalColorPicker.spectrum('option', 'palette', palette);
                $settingsColorPicker.spectrum('option', 'palette', palette);
            }
        };

        let color1, color2, color3;
        if (mainColors.val().length > 0) {
            let colors = mainColors.val().split(',');
            color1 = colors[0];
            color2 = colors[1];
            color3 = colors[2];
        } else {
            color1 = color2 = color3 = tmplColor;
        }
        optionsMainColorPickers.color = color1;
        $mainColorPicker1.spectrum(optionsMainColorPickers);
        optionsMainColorPickers.color = color2;
        $mainColorPicker2.spectrum(optionsMainColorPickers);
        optionsMainColorPickers.color = color3;
        $mainColorPicker3.spectrum(optionsMainColorPickers);
    },
    getMainColors: function (source = 'colorpicker') {
        let palette = [];
        let mainColors = '';
        if (source == 'colorpicker') {
            let mainColor1 = jQuery('#acym__wysid__maincolor-colorpicker1').spectrum('get').toHexString();
            let mainColor2 = jQuery('#acym__wysid__maincolor-colorpicker2').spectrum('get').toHexString();
            let mainColor3 = jQuery('#acym__wysid__maincolor-colorpicker3').spectrum('get').toHexString();
            mainColors = mainColor1 + ',' + mainColor2 + ',' + mainColor3;
        } else {
            mainColors = jQuery('#main_colors').val();
        }
        if (mainColors.length > 0) {
            let colors = mainColors.split(',');
            colors.forEach(function (item) {
                palette.push([item]);
            });
        }

        if (acym_helper.empty(palette)) {
            palette = [
                ['#fff'],
                ['#000']
            ];
        }
        return palette;
    },
    setGeneralColorPickerWYSID: function () {
        let $template = jQuery('.acym__wysid__template__content');
        let $generalColorPicker = jQuery('#acym__wysid__background-colorpicker');
        let palette = acym_editorWysidColorPicker.getMainColors();
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
            palette: palette,
            maxSelectionSize: 1,
            move: function (color) {
                acym_editorWysidFontStyle.saveAndApplyPropertyOnOneType('#acym__wysid__background-colorpicker', 'background-color', color.toHexString());
            },
            change: function (color) {
                acym_editorWysidFontStyle.saveAndApplyPropertyOnOneType('#acym__wysid__background-colorpicker', 'background-color', color.toHexString());
            }
        });
    },
    setSettingsColorPickerWYSID: function () {
        let selectedHtmlElementType = acym_editorWysidFontStyle.currentlySelectedType;
        let colorHex = acym_editorWysidFontStyle.getPropertyOfOneType(selectedHtmlElementType, 'color');
        let palette = acym_editorWysidColorPicker.getMainColors();

        jQuery('#acym__wysid__right__toolbar__settings__color').spectrum({
            color: colorHex != '' ? colorHex : 'black',
            preferredFormat: 'hex',
            showButtons: false,
            showInput: true,
            showPalette: true,
            palette: palette,
            maxSelectionSize: 1,
            move: function (color) {
                acym_editorWysidFontStyle.saveAndApplyPropertyOnOneType(selectedHtmlElementType, 'color', color.toHexString());
            },
            change: function (color) {
                acym_editorWysidFontStyle.saveAndApplyPropertyOnOneType(selectedHtmlElementType, 'color', color.toHexString());
            }
        });
    }
};
