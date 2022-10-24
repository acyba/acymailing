jQuery(function($) {

    function Init() {
        acym_helperUser.setSubscribeUnsubscribeUser();
        setToggleHistory();
    }

    Init();

    function setToggleHistory() {
        $('.acym__users__history__toggle-button').off('click').on('click', function () {
            if ($(this).hasClass('acym__users__history__toggle-button-selected')) return;
            $('.acym__users__history__toggle-button-selected').removeClass('acym__users__history__toggle-button-selected');
            $(this).addClass('acym__users__history__toggle-button-selected');
            $('[data-acym-type]').css('display', 'none');
            $(`[data-acym-type="${$(this).attr('data-acym-toggle-history')}"]`).css('display', 'flex');
        });
    }
});
