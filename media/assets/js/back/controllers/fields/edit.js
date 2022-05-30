jQuery(document).ready(function ($) {
    //All the elements needed for every custom fields
    let allElements = {
        text: [
            '#acym__fields__required',
            '#acym__fields__editable-user-creation',
            '#acym__fields__editable-user-modification',
            '#acym__fields__error-message',
            '#acym__fields__authorized-content',
            '#acym__fields__error-message-invalid',
            '#acym__fields__default-value',
            '#acym__fields__max_characters',
            '#acym__fields__size'
        ],
        textarea: [
            '#acym__fields__required',
            '#acym__fields__editable-user-creation',
            '#acym__fields__editable-user-modification',
            '#acym__fields__error-message',
            '#acym__fields__default-value',
            '#acym__fields__max_characters',
            '#acym__fields__rows',
            '#acym__fields__columns'
        ],
        radio: [
            '#acym__fields__required',
            '#acym__fields__editable-user-creation',
            '#acym__fields__editable-user-modification',
            '#acym__fields__error-message',
            '#acym__fields__default-value',
            '#acym__fields__value',
            '#acym__fields__from-db'
        ],
        checkbox: [
            '#acym__fields__required',
            '#acym__fields__editable-user-creation',
            '#acym__fields__editable-user-modification',
            '#acym__fields__error-message',
            '#acym__fields__default-value',
            '#acym__fields__value',
            '#acym__fields__from-db'
        ],
        single_dropdown: [
            '#acym__fields__required',
            '#acym__fields__editable-user-creation',
            '#acym__fields__editable-user-modification',
            '#acym__fields__error-message',
            '#acym__fields__default-value',
            '#acym__fields__value',
            '#acym__fields__from-db',
            '#acym__fields__size'
        ],
        multiple_dropdown: [
            '#acym__fields__required',
            '#acym__fields__editable-user-creation',
            '#acym__fields__editable-user-modification',
            '#acym__fields__error-message',
            '#acym__fields__default-value',
            '#acym__fields__value',
            '#acym__fields__from-db',
            '#acym__fields__size'
        ],
        date: [
            '#acym__fields__required',
            '#acym__fields__editable-user-creation',
            '#acym__fields__editable-user-modification',
            '#acym__fields__error-message',
            '#acym__fields__format'
        ],
        file: [
            '#acym__fields__required',
            '#acym__fields__editable-user-creation',
            '#acym__fields__editable-user-modification',
            '#acym__fields__error-message',
            '#acym__fields__size'
        ],
        phone: [
            '#acym__fields__required',
            '#acym__fields__editable-user-creation',
            '#acym__fields__editable-user-modification',
            '#acym__fields__error-message',
            '#acym__fields__default-value',
            '#acym__fields__max_characters',
            '#acym__fields__size'
        ],
        custom_text: [
            '#acym__fields__editable-user-creation',
            '#acym__fields__editable-user-modification',
            '#acym__fields__custom-text'
        ],
        language: [
            '#acym__fields__required',
            '#acym__fields__editable-user-creation',
            '#acym__fields__editable-user-modification',
            '#acym__fields__size'
        ]
    };

    let categoriesByElement = {
        '#acym__fields__error-message': '#acym__fields__edit__section__title--properties',
        '#acym__fields__editable-user-creation': '#acym__fields__edit__section__title--properties',
        '#acym__fields__editable-user-modification': '#acym__fields__edit__section__title--properties',
        '#acym__fields__authorized-content': '#acym__fields__edit__section__title--content',
        '#acym__fields__error-message-invalid': '#acym__fields__edit__section__title--content',
        '#acym__fields__default-value': '#acym__fields__edit__section__title--content',
        '#acym__fields__format': '#acym__fields__edit__section__title--content',
        '#acym__fields__max_characters': '#acym__fields__edit__section__title--content',
        '#acym__fields__rows': '#acym__fields__edit__section__title--style',
        '#acym__fields__columns': '#acym__fields__edit__section__title--style',
        '#acym__fields__size': '#acym__fields__edit__section__title--style',
        '#acym__fields__value': '#acym__fields__edit__section__title--values',
        '#acym__fields__from-db': '#acym__fields__edit__section__title--values'
    };

    function Init() {
        setFieldTypeContent();
        setOnChangeFieldTypeContent();
        setSortableValuesField();
        setValueCustomFields();
        setDatabaseField();
        setDisplayOnlyIf();
        setValuesOptions();
        acym_helperSelectionMultilingual.init('field');
    }

    Init();

    function setFieldTypeContent() {
        let currentElement = allElements[$('#fieldtype').val()];
        $('.acym__fields__change').hide();
        $('.acym__fields__edit__section__title').hide();
        for (let i = 0 ; i < currentElement.length ; i++) {
            $(currentElement[i]).show();
            $(categoriesByElement[currentElement[i]]).show();
        }
    }

    function setOnChangeFieldTypeContent() {
        $('#fieldtype').on('change', function () {
            setFieldTypeContent();
        });
    }

    function setSortableValuesField() {
        $('.acym__fields__values__listing__sortable').sortable({
            item: '.acym__fields__value__sortable',
            handle: '.acym__sortable__field__edit__handle',
            animation: 150
        });
    }

    function setValueCustomFields() {
        $('#acym__fields__value__add-value').off('click').on('click', function () {
            let htmlSelect = '<select acym-data-infinite class="acym__fields__edit__select acym__select" name="field[value][disabled][]">';
            htmlSelect += '<option value="n">' + ACYM_JS_TXT.ACYM_NO + '</option>';
            htmlSelect += '<option value="y">' + ACYM_JS_TXT.ACYM_YES + '</option>';
            htmlSelect += '</select>';

            let newContent = '<div class="grid-x cell acym__fields__value__sortable acym__content margin-bottom-1 grid-margin-x margin-y">';
            newContent += '<div class="medium-1 cell acym_vcenter align-center acym__field__sortable__listing__handle">';
            newContent += '<div class="grabbable acym__sortable__field__edit__handle grid-x">';
            newContent += '<i class="acymicon-ellipsis-h cell acym__color__dark-gray"></i>';
            newContent += '<i class="acymicon-ellipsis-h cell acym__color__dark-gray"></i>';
            newContent += '</div>';
            newContent += '</div>';
            newContent += '<input type="text" name="field[value][value][]" class="cell medium-4 acym__fields__value__value" value="">';
            newContent += '<input type="text" name="field[value][title][]" class="cell medium-4 acym__fields__value__title" value="">';
            newContent += '<div class="cell medium-2">' + htmlSelect + '</div>';
            newContent += '<i class="cell acymicon-close small-1 acym__color__red cursor-pointer acym__field__delete__value"></i>';
            newContent += '</div>';

            $('.acym__fields__values__listing__sortable').append(newContent);
            acym_helperSelect2.setSelect2();

            setSortableValuesField();
            setValuesOptions();
        });
    }

    function setValuesOptions() {
        let previousValue = '';
        $('.acym__fields__value__value').on('focus', function () {
            previousValue = this.value;
        }).off('change').on('change', function () {
            if (acym_helper.empty(previousValue)) return;

            let confirmMessage = ACYM_JS_TXT.ACYM_CF_VALUE_CHANGED + '\n\n';
            confirmMessage += ACYM_JS_TXT.ACYM_OLD_VALUE + ': ' + previousValue + '\n';
            confirmMessage += ACYM_JS_TXT.ACYM_NEW_VALUE + ': ' + this.value;

            if (!acym_helper.confirm(confirmMessage)) {
                this.value = previousValue;
            }
        });

        $('.acym__field__delete__value').off('click').on('click', function () {
            if (acym_helper.confirm(ACYM_JS_TXT.ACYM_ARE_YOU_SURE)) {
                $(this).parent().remove();
            }
        });
    }

    function setDatabaseField() {
        $('select[name="fieldDB[database]"]').off('change').on('change', function () {
            let ajaxUrl = ACYM_AJAX_URL + '&ctrl=fields&task=getTables&database=' + $(this).val();
            $.post(ajaxUrl, function (response) {
                let tables = acym_helper.parseJson(response);
                $('select[name="fieldDB[table]"]').html('');
                for (let i = 0 ; i < tables.length ; i++) {
                    $('select[name="fieldDB[table]"]').append('<option value="' + tables[i] + '">' + tables[i] + '</option>');
                }
            });
        });
        $('select[name="fieldDB[table]"]').off('change').on('change', function () {
            let ajaxUrl = ACYM_AJAX_URL + '&ctrl=fields&task=setColumns&table=' + $(this).val() + '&database=' + $('select[name="fieldDB[database]"]').val();
            $.post(ajaxUrl, function (response) {
                let columns = acym_helper.parseJson(response);
                $('.acym__fields__database__columns').html('');
                for (let i = 0 ; i < columns.length ; i++) {
                    let column = columns[i];
                    if ('ACYM_CHOOSE_COLUMN' === columns[i]) {
                        $('.acym__fields__database__columns').append('<option value="">' + ACYM_JS_TXT.ACYM_CHOOSE_COLUMN + '</option>');
                    } else {
                        $('.acym__fields__database__columns').append('<option value="' + column + '">' + column + '</option>');
                    }
                }
            });
        });
    }

    function setDisplayOnlyIf() {
        $('#acym__display_if_and-or').off('change').on('change', function () {
            let allFields = $('.acym__fields__display-if__list-fields option');
            let selectFields = '<select acym-data-infinite class="acym__fields__edit__select acym__select" name="field[option][display_field][]">';
            allFields.each(function () {
                selectFields += '<option value="' + $(this).html() + '">' + $(this).html() + '</option>';
            });
            selectFields += '</select>';
            let selectSign = '<select name="field[option][display_sign][]" class="acym__fields__edit__select">'
                             + '<option value="=">=</option>'
                             + '<option value="!=">!=</option>'
                             + '<option value=">">></option>'
                             + '<option value="<"><</option>'
                             + '<option value="<="><=</option>'
                             + '<option value=">=">>=</option>'
                             + '</select>';
            $('.acym__display_if_add')
                .before('<input type="hidden" name="field[option][display_and_or][]" value="'
                        + $(this).find('option:selected').val()
                        + '"><div class="cell grid-x grid-margin-x"><h6 class="cell">'
                        + $(this).find('option:selected').text()
                        + '</h6>'
                        + '<div class="cell medium-5">'
                        + selectFields
                        + '</div>'
                        + '<div class="cell medium-2">'
                        + selectSign
                        + '</div>'
                        + '<input class="medium-5 cell" type="text" name="field[option][display_value][]">'
                        + '</div>');
            acym_helperSelect2.setSelect2();
            $(this).val('none');
        });
    }
});
