const acym_helperToggle = {
    initJsToggle: function () {
        acym_helperToggle.setSwitchToggle();
        acym_helperToggle.setToggleArrow();
        acym_helperToggle.setToggleGlobal();
        acym_helperToggle.setToggleCheckboxesGlobal();
        acym_helperToggle.setSelectToggle();
    },
    setToggleGlobal: function () {
        jQuery('.acym_toggleable').off('click').on('click', function () {
            let element = jQuery(this);
            element.attr('class', 'acymicon-circle-o-notch acymicon-spin');

            let table = element.attr('data-acy-table');
            let field = element.attr('data-acy-field');
            let elementid = element.attr('data-acy-elementid');
            let newvalue = element.attr('data-acy-newvalue');

            let url = ACYM_TOGGLE_URL + '&table=' + table + '&field=' + field + '&id=' + elementid + '&value=' + newvalue;

            jQuery.ajax({
                url: url,
                type: 'GET'
            }).done(function (result) {
                let json = jQuery.parseJSON(result);
                let toggleElement = jQuery('[data-acy-table=' + table + '][data-acy-field=' + field + '][data-acy-elementid=' + elementid + ']');

                toggleElement.attr('data-acy-newvalue', json.value).attr('class', json.classes);

                if (typeof json.tooltip !== 'undefined') {
                    toggleElement.closest('.acym__tooltip').find('.acym__tooltip__text').html(json.tooltip);
                }
            });
        });

        jQuery('.acym_toggle_delete').off('click').on('click', function () {
            let element = jQuery(this);

            let confirmation = element.attr('confirmation');
            if (!confirmation || acym_helper.confirm(ACYM_JS_TXT.ACYM_ARE_YOU_SURE)) {
                let table = element.attr('data-acy-table');
                let elementid = element.attr('data-acy-elementid');

                let url = ACYM_TOGGLE_URL + '&task=delete&table=' + table + '&id=' + elementid;
                let method = element.attr('data-acy-method');
                if (method !== undefined) {
                    url += '&method=' + method;
                }

                jQuery.ajax({
                    url: url,
                    type: 'GET'
                }).done(function (result) {
                    if ('mail' === table) {
                        jQuery('#acym_form').submit();
                    } else {
                        jQuery('.grid-x[data-acy-elementid=' + elementid + ']').remove();
                    }
                });
            }
        });

        jQuery('.switch-label').off('click').on('click', function () {
            let $id = jQuery(this).attr('for');
            jQuery('[data-switch="' + $id + '"]').trigger('change');
        });

        jQuery('.acym_toggle_switch').off('change').on('change', function () {
            let $element = jQuery(this);
            let $table = $element.attr('data-acy-table');
            let $field = $element.attr('data-acy-field');
            let $elementid = $element.attr('data-acy-elementid');
            let $newvalue = $element.attr('data-acy-newvalue');

            let url = ACYM_TOGGLE_URL + '&table=' + $table + '&field=' + $field + '&id=' + $elementid + '&value=' + $newvalue;

            jQuery.ajax({
                url: url,
                type: 'GET'
            }).done(function (result) {
                let json = jQuery.parseJSON(result);
                $element.attr('data-acy-newvalue', json.value);
            });
        });
    },
    setToggleCheckboxesGlobal: function () {
        let toggleCheckboxes = jQuery('input.acym_toggle[type="checkbox"]');
        toggleCheckboxes.off('change').on('change', function () {
            let toggleableElements = jQuery('.' + jQuery(this).attr('data-toggle'));
            let isChecked = jQuery(this).is(':checked');
            let valueField = jQuery('#' + jQuery(this).attr('data-value'));

            toggleableElements.each(function () {

                let $toggleableElement = jQuery(this);
                if (isChecked) {
                    $toggleableElement.hide();
                    if (!$toggleableElement.hasClass('acym_apply_data_abide_hide')) {
                        $toggleableElement.find('input').attr('data-abide-ignore', '');
                    }
                    if (valueField) valueField.val(1);
                } else {
                    $toggleableElement.show();
                    if (!$toggleableElement.hasClass('acym_apply_data_abide_hide')) {
                        $toggleableElement.find('input').removeAttr('data-abide-ignore');
                    }
                    if (valueField) valueField.val(0);
                }
            });
        });

        toggleCheckboxes.trigger('change');
    },
    setSwitchToggle: function () {
        let $switchToggle = jQuery('[data-toggle-switch]:not([data-toggle-switch=""])');
        $switchToggle.off('change').on('change', function () {
            let $open = jQuery(this).attr('data-toggle-switch-open');

            // I know it seems weird but the foundation's switch triggers the hidden input's onchange before updating its data :/
            if ((jQuery(this).val() != 1 && $open == 'show') || (jQuery(this).val() == 1 && $open == 'hide')) {
                jQuery('#' + jQuery(this).attr('data-toggle-switch')).show();
            } else {
                jQuery('#' + jQuery(this).attr('data-toggle-switch')).hide();
            }
        });

        $switchToggle.each(function (index) {
            let $open = jQuery(this).attr('data-toggle-switch-open');

            if ((jQuery(this).val() == 1 && $open == 'show') || (jQuery(this).val() != 1 && $open == 'hide')) {
                jQuery('#' + jQuery(this).attr('data-toggle-switch')).show();
            } else {
                jQuery('#' + jQuery(this).attr('data-toggle-switch')).hide();
            }
        });
    },
    setToggleArrow: function () {
        jQuery('.acym__toggle__arrow .acym__toggle__arrow__trigger').off('click').on('click', function () {

            let $textarea = jQuery(this).closest('.acym__toggle__arrow').children('.acym__toggle__arrow__contain');
            if ($textarea.is(':visible')) {
                $textarea.hide();
                jQuery(this).find('i').removeClass('acymicon-keyboard_arrow_up').addClass('acymicon-keyboard_arrow_down');
            } else {
                $textarea.show();
                jQuery(this).find('i').removeClass('acymicon-keyboard_arrow_down').addClass('acymicon-keyboard_arrow_up');
            }
        });
    },
    setSelectToggle: function () {
        let $selectToggle = jQuery('[data-toggle-select]:not([data-toggle-select=""])');
        $selectToggle.on('change', function () {
            let association = acym_helper.parseJson(jQuery(this).attr('data-toggle-select'));

            let currentValue = jQuery(this).val();
            jQuery.each(association, function (value, element) {
                if (value != currentValue) {
                    jQuery(element).hide();
                } else {
                    jQuery(element).show();
                }
            });
        }).trigger('change');
    }
};
