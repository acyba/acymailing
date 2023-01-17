jQuery(function ($) {
    let currentNumber = 0;

    function Init() {
        setConditionUser();
        setConditionSubject();

        setActionChange();
        setNewActionButton();
        rebuildActions();
        testConnection();
    }

    Init();

    function setConditionUser() {
        $('#mailboxconditionssender').on('change', function () {
            $('.acym__mailbox__edition__conditions_sender__option').hide();
            switch ($(this).val()) {
                case 'specific':
                    $('#acym__mailbox__edition__conditions_sender__specific').show();
                    break;
                case 'groups':
                    $('#acym__mailbox__edition__conditions_sender__groups').show();
                    break;
                case 'lists':
                    $('#acym__mailbox__edition__conditions_sender__lists').show();
                    break;
            }
        }).trigger('change');
    }

    function setConditionSubject() {
        $('#mailboxconditionssubject').on('change', function () {
            $('.acym__mailbox__edition__conditions_subject__option').hide();
            const $removeOption = $('#acym__mailbox__edition__conditions_subject__remove');
            switch ($(this).val()) {
                case 'begins':
                case 'contains':
                case 'ends':
                    $('#acym__mailbox__edition__conditions_subject__text').show();
                    $removeOption.show();
                    break;
                case 'regex':
                    $('#acym__mailbox__edition__conditions_subject__regex').show();
                    $removeOption.show();
                    break;
            }
        }).trigger('change');
    }

    function setActionChange() {
        $('[data-action-number="' + currentNumber + '"] .acym__mailbox__edition__action__one__choice').on('change', function () {
            const $container = $(this).parent();
            $container.find('.acym__mailbox__edition__action__one__parameters').hide();
            $container.find('.' + $(this).val()).show();
        });
    }

    function setNewActionButton() {
        $('.acym__mailbox__edition__action__one__parameters').hide();
        $('#acym__mailbox__edition__action__new').off('click').on('click', function () {
            const $actionNumber = $('#acym__mailbox__edition__action__number');
            $actionNumber.val(parseInt($actionNumber.val()) + 1);
            currentNumber = $actionNumber.val();

            $(this).before($('#acym__mailbox__edition__action__template').html().replaceAll('__num__', currentNumber));
            $('[data-action-number="' + currentNumber + '"] .acym__mailbox__edition__action__one__parameters').hide();
            $(this).prev().find('select')
                   .select2({
                       theme: 'foundation',
                       width: '100%'
                   });

            setActionChange();
            setDeleteAction();
        });
    }

    function setDeleteAction() {
        $('.acym__mailbox__edition__action__delete').off('click').on('click', function () {
            $(this).closest('.acym__mailbox__edition__action__one').remove();
        });
    }

    function rebuildActions() {
        const actions = acym_helper.parseJson($('#acym__mailbox__edition__actions').val());

        // Foreach actions
        $.each(actions, function (actionNumber, oneAction) {
            // Create a new block if needed
            if ($('#acym_action0action').val().length > 0) {
                $('#acym__mailbox__edition__action__new').trigger('click');
            }

            $.each(oneAction, function (actionName, actionParams) {
                // Select the action type in the correct dropdown
                const $actionSelect = $('#acym_action' + currentNumber + 'action');
                $actionSelect.val(actionName);
                $actionSelect.trigger('change');

                $.each(actionParams, function (paramName, paramValue) {
                    // Set the param values
                    const $optionField = $('[name="acym_action[' + currentNumber + '][' + actionName + '][' + paramName + ']"]');
                    acym_helperFilter.setFieldValue($optionField, paramValue);

                    // For the select2 to show the new value
                    $optionField.trigger('change');
                });
            });
        });
    }

    function testConnection() {
        const $loader = $('#acym__mailbox__edition__configuration__test-loader');
        const $result = $('#acym__mailbox__edition__configuration__test-result');
        const $iconResult = $('#acym__mailbox__edition__configuration__test-icon');

        $('#acym__mailbox__edition__configuration__test-test').off('click').on('click', function () {
            $loader.css('display', 'flex');
            $result.html('');
            $result.removeAttr('data-acym-tooltip').removeClass('acym__tooltip');
            $iconResult.removeClass('acymicon-check-circle acym__color__green acymicon-times-circle acym__color__red');
            $iconResult.hide();

            const formData = $(this).closest('form').serializeArray();

            const dataToKeep = [
                'id',
                'server',
                'username',
                'password',
                'connection_method',
                'secure_method',
                'self_signed',
                'port'
            ].map((name) => `mailbox[${name}]`);

            const data = formData.filter((data) => dataToKeep.includes(data.name));
            data.push({
                name: 'ctrl',
                value: 'bounces'
            });
            data.push({
                name: 'task',
                value: 'testMailboxAction'
            });

            acym_helper.post(ACYM_AJAX_URL, data)
                       .then(({
                                  error,
                                  message,
                                  data
                              }) => {
                           $loader.hide();
                           $result.html(message);
                           if (error && !!data.report.length) {
                               $result.attr('data-acym-tooltip', data.report.join('<br>'));
                               acym_helperTooltip.setTooltip();
                               $iconResult.addClass('acymicon-times-circle acym__color__red');
                           }
                           $iconResult.addClass('acymicon-check-circle acym__color__green');
                           $iconResult.css('display', 'flex');
                       });
        });
    }
});
