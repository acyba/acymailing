const acym_editorWysidNotifications = {
    hideNotification: function () {
        jQuery('#acym__wysid__top-toolbar__notification').removeClass('acym__toolbar__message__visible');
        jQuery('#acym__wysid__top-toolbar__notification__message').html('');
        jQuery('#acym__wysid__top-toolbar__actions').show();
        jQuery('#acym__wysid__top-toolbar__notification__close').show();
    },
    addEditorNotification: function (notification, timeout, hideCloseButton, displayValidateButton) {
        timeout = undefined === timeout ? false : timeout;
        hideCloseButton = undefined === hideCloseButton ? false : hideCloseButton;
        displayValidateButton = undefined === displayValidateButton ? false : displayValidateButton;
        let classes = '';
        if (notification.level === 'success') {
            classes = 'acymicon-check-circle';
        } else if (notification.level === 'info') {
            classes = 'acymicon-bell';
        } else if (notification.level === 'warning') {
            classes = 'acymicon-exclamation-triangle';
        } else {
            classes = 'acymicon-exclamation-circle';
        }


        let $toolbar = jQuery('#acym__wysid__top-toolbar');
        let $toolbarMessage = $toolbar.find('#acym__wysid__top-toolbar__notification');
        $toolbar.find('#acym__wysid__top-toolbar__actions').hide();
        $toolbarMessage.addClass('acym__toolbar__message__visible').find('#acym__wysid__top-toolbar__notification__icon').addClass(classes);
        $toolbarMessage.find('#acym__wysid__top-toolbar__notification__message').html(notification.message);
        $toolbarMessage.find('#acym__wysid__top-toolbar__notification__close').off('click').on('click', function () {
            acym_editorWysidNotifications.hideNotification();
        });
        if (hideCloseButton) $toolbarMessage.find('#acym__wysid__top-toolbar__notification__close').hide();
        if (!displayValidateButton) $toolbarMessage.find('#acym__wysid__top-toolbar__keep').hide();
        if (timeout) {
            setTimeout(function () {
                acym_editorWysidNotifications.hideNotification();
            }, timeout);
        }
    }
};
