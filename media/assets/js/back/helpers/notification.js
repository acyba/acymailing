const acym_helperNotification = {
    setNotificationCenter: function () {
        acym_helperNotification.removeNotifications();
        let $button = jQuery('.acym__header__notification');

        if ($button.find('i').hasClass('acymicon-check-circle')) {
            setTimeout(function () {
                $button.find('> i').attr('class', 'acymicon-bell-o');
                $button.find('.acym__tooltip__text').remove();
                $button.removeAttr('data-acym-tooltip')
                       .removeAttr('data-acym-tooltip-position')
                       .removeClass('acym__header__notification__button__success acym__header__notification__pulse');
            }, 8000);
        }

        $button.off('click').on('click', function () {
            let $notifButton = jQuery(this);
            let $notificationCenter = jQuery('.acym__header__notification__center');
            let notifButtonOffset = $notifButton.offset();

            let left = (notifButtonOffset.left - $notificationCenter.width()) + $notifButton.width() + 'px';
            let top = (notifButtonOffset.top + $notifButton.height()) + 10 + 'px';
            $notificationCenter.css({
                'top': top,
                'left': left
            }).addClass('acym__header__notification__center__visible');

            let ajaxUrl = ACYM_AJAX_URL + '&ctrl=configuration&task=markNotificationRead';

            jQuery.get(ajaxUrl, function (res) {
                res = acym_helper.parseJson(res);
                if (undefined === res.error) {
                    jQuery('.acym__header__notification')
                        .removeClass(
                            'acym__header__notification__pulse acym__header__notification__button__success acym__header__notification__button__info acym__header__notification__button__warning acym__header__notification__button__error')
                        .find('> i')
                        .attr('class', 'acymicon-bell-o');
                } else {
                    console.log(res.error);
                }
            });

            $button.off('click').on('click', function (e) {
                if (jQuery(e.target).hasClass('acym__header__notification__center__visible') || jQuery(e.target)
                    .closest('.acym__header__notification__center__visible').length > 0) {
                    return true;
                }
                acym_helperNotification.removeNotificationsCenter();
            });
            setTimeout(function () {
                jQuery(window).off('click').on('click', function (e) {
                    if (jQuery(e.target).hasClass('acym__header__notification__center__visible') || jQuery(e.target)
                        .closest('.acym__header__notification__center__visible').length > 0) {
                        return true;
                    }
                    acym_helperNotification.removeNotificationsCenter();
                });
            }, 100);
        });

        jQuery('.acym__message__close').off('click').on('click', function () {
            let $closeButton = jQuery(this);
            let id = $closeButton.attr('data-id');
            if (id !== undefined && id != 0) {
                let ajaxUrl = ACYM_AJAX_URL + '&ctrl=configuration&task=markNotificationRead&id=' + id;
                jQuery.get(ajaxUrl, function (res) {
                    res = acym_helper.parseJson(res);

                    if (undefined === res.error) {
                        $closeButton.closest('.acym__message').remove();
                    } else {
                        console.log(res.error);
                    }
                });
            } else {
                $closeButton.closest('.acym__message').remove();
            }
        });
    },
    removeNotifications: function () {
        jQuery('.acym__header__notification__one__delete, .acym__header__notification__toolbox__remove').off('click').on('click', function () {
            let id = jQuery(this).hasClass('acym__header__notification__toolbox__remove') ? 'all' : jQuery(this).attr('data-id');
            let ajaxUrl = ACYM_AJAX_URL + '&ctrl=configuration&task=removeNotification&id=' + id;

            jQuery.get(ajaxUrl, function (res) {
                res = acym_helper.parseJson(res);
                if (undefined === res.error) {
                    jQuery('.acym__header__notification__center').html(res.data);
                    jQuery('.acym__header__notification').find('> i').attr('class', 'acymicon-bell-o');
                    acym_helperNotification.removeNotifications();
                } else {
                    console.log(res.error);
                }
            });
        });
    },
    removeNotificationsCenter: function () {
        jQuery('.acym__header__notification__center').removeClass('acym__header__notification__center__visible');
        jQuery(window).off('click');
        acym_helperNotification.setNotificationCenter();
    },
    addNotification: function (message, type, clear) {
        if (clear === undefined) clear = false;
        if (clear) {
            jQuery('.acym__message').remove();
        }

        if (type === undefined || jQuery.inArray(type, [
            'success',
            'warning',
            'error'
        ]) == -1) {
            type = 'info';
        }

        let headerNotif = acym_helperNotification.addHeaderNotification(message, type);
        jQuery('#acym_header').after(headerNotif);

        let ajaxUrl = ACYM_AJAX_URL + '&ctrl=configuration&task=addNotification&message=' + message + '&level=' + type;

        jQuery.get(ajaxUrl, function (res) {
            res = acym_helper.parseJson(res);
            if (res.error === undefined) {
                jQuery('.acym__header__notification').replaceWith(res.data);
                acym_helperNotification.setNotificationCenter();
            } else {
                console.log(res.error);
            }
        });
    },
    addHeaderNotification: function (message, type) {
        let structure = '<div class="acym__message grid-x acym__message__'
                        + type
                        + '">'
                        + '<div class="cell auto">'
                        + '<p>'
                        + message
                        + '</p>'
                        + '</div>'
                        + '<i data-id="0" class="cell shrink acym__message__close acymicon-remove"></i>'
                        + '</div>';

        return structure;
    },
};
