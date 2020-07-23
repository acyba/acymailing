jQuery(document).ready(function ($) {
    function Init() {
        setReplyToInformation();
        setToggleMailServer();
        setSelectedFunction();
        setSubscribeUser();
        setWalkthroughList();
        setStepFailToggle();
        setChoiceWalkthroughResult();
        setPreventSubmit();
        acym_helperEditorWysid.initEditor();
        acym_helperDynamic.setModalDynamics();
    }

    Init();

    function setReplyToInformation() {
        $('#acym__walk-through-1__content__toggle-reply-to__checkbox').off('change').on('change', function () {
            $('.acym__walk-through-1__content__reply-to').toggle();
        });
    }

    function setToggleMailServer() {
        let $yourServer = $('.acym__walk-through-2__choose_your-server');
        let $externalServer = $('.acym__walk-through-2__choose_external-server');
        let $smtpServer = $('.acym__walk-through-2__smtp-server');
        let $elasticEmail = $('.acym__walk-through-2__elastic-email');
        let $spanServer = $('.acym__walk-through-2__toggle-mail .acym__walk_through_toggle-span');
        let $spanSmtpElastic = $('.acym__walk-through-2__choose_external-server .acym__walk_through_toggle-span');
        $spanServer.off('click').on('click', function () {
            $spanServer.removeClass('walk-through_selected');
            $(this).addClass('walk-through_selected');

            let newValue;
            if ($(this).attr('id') === 'your-server') {
                $yourServer.show();
                $externalServer.hide();
                newValue = $('.your_server_selected').attr('id');
            } else {
                $yourServer.hide();
                $externalServer.show();
                newValue = $('.acym__walk-through-2__choose_external-server .walk-through_selected').attr('id');
            }
            $('#acym__walk-through-2__way-mail').val(newValue);
        });
        $spanSmtpElastic.off('click').on('click', function () {
            $spanSmtpElastic.removeClass('walk-through_selected');
            $(this).addClass('walk-through_selected');
            $(this).attr('id') === 'smtp' ? $smtpServer.show() && $elasticEmail.hide() : $smtpServer.hide() && $elasticEmail.show();
            $('#acym__walk-through-2__way-mail').attr('value', $(this).attr('id'));
        });
    }

    function setSelectedFunction() {
        let $buttons = $('.acym__walk-through-2__button');
        $buttons.off('click').on('click', function (e) {
            e.preventDefault();
            $buttons.addClass('unselected').removeClass('your_server_selected');
            $(this).removeClass('unselected').addClass('your_server_selected');
            $('#acym__walk-through-2__way-mail').attr('value', $(this).attr('id'));
        });
    }

    function setSubscribeUser() {
        $('#acym__subscribe__news').on('click', function () {
            let emailUser = $('input[type="email"]').val();
            if (!acym_helper.emailValid(emailUser)) {
                alert(ACYM_JS_TXT.email);
                return false;
            }

            $(this).attr('disabled', 'true');
            let ajaxUrl = AJAX_URL_UPDATEME + 'subscription&task=subscribe&email=' + emailUser + '&cms=' + CMS_ACYM;

            $.get(ajaxUrl);
            $('.acy_button_submit').click();
        });
    }

    function setWalkthroughList() {
        reloadDeleteAddress();

        $('#acym__walkthrough__list__new').on('click', function () {
            $(this).hide();
            $('#acym__walkthrough__list__add-zone').show();
        });

        $('#acym__walkthrough__list__add').on('click', function () {
            let $address = $('#acym__walkthrough__list__new-address');
            let enteredAddress = $address.val();
            if (acym_helper.emailValid(enteredAddress)) {
                let email = '<input type="hidden" name="addresses[]" value="' + enteredAddress + '"/>' + enteredAddress;
                let deleteIcon = '<i class="acymicon-remove acym__walkthrough__list__receivers__remove"></i>';

                $('#acym__walkthrough__list__receivers').append('<tr><td>' + email + '</td><td>' + deleteIcon + '</td></tr>');
                $address.val('');

                $('#acym__walkthrough__list__add-zone').hide();
                $('#acym__walkthrough__list__new').show();

                reloadDeleteAddress();
            } else {
                alert(ACYM_JS_TXT.email);
                return false;
            }
        });

        $('#acym__walkthrough__list__new-address').on('keypress', function (e) {
            if (13 === e.which) {
                $('#acym__walkthrough__list__add').click();
                return false;
            }
        });
    }

    function reloadDeleteAddress() {
        $('.acym__walkthrough__list__receivers__remove').off('click').on('click', function () {
            $(this).closest('tr').remove();
        });
    }

    function setChoiceWalkthroughResult() {
        $('.acym__walkthrough__result__choice__one').off('click').on('click', function () {
            if ($(this).hasClass('selected')) return true;

            $('.acym__walkthrough__result__choice__one').removeClass('selected');
            $(this).addClass('selected');

            if ($(this).attr('id') === 'acym__walkthrough__result__choice__no') {
                $('#acym__walkthrough__result__spam').addClass('visible');
            } else {
                $('#acym__walkthrough__result__spam').removeClass('visible');
            }

            $('input[name="result"]').val($(this).attr('data-value'));
            $('[data-task="saveStepResult"]').attr('disabled', false);
        });
    }

    function setStepFailToggle() {
        $('#acym__walkthrough__skip__fail').off('click').on('click', function () {
            $('[required]').removeAttr('required');
            $('[type="email"]').attr('type', 'text');
            $('#acym__walkthrough__skip').click();
        });
        $('.acym__walkthrough__fail__choice').off('click').on('click', function () {
            let $divToShow = $('.acym__walkthrough__fail__' + $(this).attr('data-show'));
            if ($divToShow.is(':visible')) return true;
            $('.selected').removeClass('selected');
            $(this).addClass('selected');
            $('.acym__walkthrough__fail__toggle-div').hide();
            $divToShow.show();
            $('[name="choice"]').val($(this).attr('data-show'));
            if ($('.acym__walkthrough__fail__contact').is(':visible')) {
                $('[name="email"]').attr('required', 'true');
            } else {
                $('[name="email"]').removeAttr('required');
            }
        });
    }

    $.walkthroughList = function () {
        if ($('.acym__walkthrough__list__receivers__remove').length === 0) {
            alert(ACYM_JS_TXT.ACYM_AT_LEAST_ONE_USER);
            return false;
        }
        return true;
    };

    function setPreventSubmit() {
        $('.acym__walkthrough__fail__gmail input').off('keypress').on('keypress', function (e) {
            if (e.keyCode == 13) {
                e.preventDefault();
                return false;
            }
        });
    }
});
