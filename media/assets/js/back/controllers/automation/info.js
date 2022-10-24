jQuery(function($) {
    const previousTypeTrigger = $('#acym__automation__trigger__type__input').val();

    function Init() {
        setDescriptionButton();
        setChooseTrigger();
        setDroppableTriggers('classic');
        setDeleteTrigger('classic');
        setElementTrigger('classic');
        setDroppableTriggers('action');
        setDeleteTrigger('action');
        setElementTrigger('action');
        setTriggerChanged();
    }

    Init();

    function setDescriptionButton() {
        $('#acym__automation__info__desc__button').off('click').on('click', function () {
            let $textarea = $('[name="automation[description]"]');
            if ($textarea.is(':visible')) {
                $textarea.hide();
                $(this).find('i').removeClass('acymicon-keyboard_arrow_up').addClass('acymicon-keyboard_arrow_down');
            } else {
                $textarea.show();
                $(this).find('i').removeClass('acymicon-keyboard_arrow_down').addClass('acymicon-keyboard_arrow_up');
            }
        });
    }

    function setChooseTrigger() {
        $('#acym__automation__info__choose__trigger__type p').off('click').on('click', function () {
            $('#acym__automation__trigger__type__input').val($(this).attr('data-trigger-type')).trigger('change');
            $('.selected-trigger').removeClass('selected-trigger');
            $(this).addClass('selected-trigger');
            $('.acym__automation__info__choose__trigger').hide();
            $('#acym__automation__info__choose__trigger__' + $(this).attr('data-trigger-type')).show();
        });
    }

    function setDroppableTriggers(type) {
        $('.acym__automation__trigger__droppable__' + type).draggable({
            cursor: 'dragging',
            revert: function (isValidDrop) {
                //If you drop an element on an invalid position, it returns to its first place with an animation.
                if (!isValidDrop) {
                    $('.acym__automation__droppable__drag').remove();
                    return true;
                }
            },
            revertDuration: 300,
            start: function (event, ui) {
                //We start to display the trigger in the drop zone
                $('.acym__automation__user-trigger__' + type)
                    .append('<div class="acym__automation__droppable__drag margin-top-1">' + $(ui.helper).html() + '</div>');
            }
        });

        $('.acym__automation__droppable__' + type).droppable({
            drop: function (event, ui) {
                $(this).find('.acym__automation__drag-here-text').remove();
                //When we drop the trigger in the right zone
                //We check if they're more than one trigger
                let $userTriggers = $('.acym__automation__user-trigger__' + type);
                if ($('.acym__automation__user-trigger__' + type + ' .acym__automation__droppable__trigger').length > 0) {
                    $userTriggers.append('<div class="acym_trigger_delimiter">' + ACYM_JS_TXT.ACYM_OR + '</div>');
                }
                //we create a new trigger
                $userTriggers.append('<div class="acym__automation__droppable__trigger margin-top-1"><div class="acym__automation__one__trigger">'
                                     + $(ui.helper).html()
                                     + '</div><i data-trigger-show="'
                                     + $(ui.helper).attr('data-trigger')
                                     + '" class="acymicon-close acym__color__red acym__automation__delete__trigger cursor-pointer"></i></div>');
                //We remove the placeholder
                $('.acym__automation__droppable__drag').remove();

                let dataTrigger = $(ui.helper).attr('data-trigger');

                //we hide the trigger from the the selection
                $('.acym__automation__all-trigger__' + type)
                    .append('<div class="acym__automation__trigger__droppable__'
                            + type
                            + ' margin-top-1 cell" style="display: none" data-trigger="'
                            + dataTrigger
                            + '">'
                            + $(ui.helper).html()
                            + '</div>');
                $(ui.helper).remove();

                //We generate all the select and set name
                setElementTrigger(type);
                setDeleteTrigger(type);
            }
        });
    }

    function setElementTrigger(type) {
        $('.acym__automation__user-trigger__' + type + ' [data-class]').each(function () {
            $(this).addClass($(this).attr('data-class'));
            $(this).removeAttr('data-class');
        });
        acym_helperSelect2.setSelect2();
        acym_helperSelect2.setAjaxSelect2();
        //we add automation to the name to save it
        $('.acym__automation__user-trigger__' + type + ' [name]').each(function () {
            if (!$(this).attr('name').includes('stepAutomation')) {
                $(this).attr('name', 'stepAutomation' + $(this).attr('name'));
            }
        });
    }

    function setDeleteTrigger(type) {
        $('.acym__automation__delete__trigger').off('click').on('click', function () {
            let $container = $(this).closest('.acym__automation__droppable__trigger');
            let $previousDelimiter = $container.prev('.acym_trigger_delimiter');

            if ($previousDelimiter.length) {
                $previousDelimiter.remove();
            } else {
                $container.next('.acym_trigger_delimiter').remove();
            }

            //if we delete a trigger we delete the div and replace the trigger in the draggable zone
            $('[data-trigger=' + $(this).attr('data-trigger-show') + ']').show();
            $container.remove();

            setDroppableTriggers(type);
        });
    }

    function setTriggerChanged() {
        $('#acym__automation__trigger__type__input').on('change', function () {
            if (previousTypeTrigger === 'user' && $(this).val() === 'classic') {
                $(`[data-task='saveInfo']`).attr('data-confirmation-message', 'ACYM_CONDITIONS_AND_FILTERS_WILL_BE_DELETED');
            } else {
                $(`[data-task='saveInfo']`).removeAttr('data-confirmation-message');
            }
        });
    }
});
