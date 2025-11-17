const acym_helperNotification = {
    setNotificationCenter: function () {
        let $button = jQuery('.acym__header__notification');

        if ($button.find('i').hasClass('acymicon-check-circle')) {
            setTimeout(function () {
                $button.find('> i').attr('class', 'acymicon-bell');
                $button.find('.acym__tooltip__text').remove();
                $button.removeAttr('data-acym-tooltip')
                       .removeAttr('data-acym-tooltip-position')
                       .removeClass('acym__header__notification__button__success acym__header__notification__pulse');
            }, 8000);
        }

        $button.off('click').on('click', function () {
            acym_helperNotification.readFullNotification();
            const $notifButton = jQuery(this);
            const $notificationCenter = jQuery('.acym__header__notification__center');
            const notifButtonOffset = $notifButton.offset();

            let left = (notifButtonOffset.left - $notificationCenter.width()) + $notifButton.width() + 'px';
            if (jQuery('html').attr('dir') === 'rtl') {
                left = '42px';
            }

            $notificationCenter.css({
                'top': (notifButtonOffset.top + $notifButton.height()) + 10 + 'px',
                'left': left
            }).addClass('acym__header__notification__center__visible');

            const ajaxUrl = ACYM_AJAX_URL + '&ctrl=configuration&task=markNotificationRead';

            jQuery.post(ajaxUrl, function (res) {
                jQuery('.acym__header__notification')
                    .removeClass(
                        'acym__header__notification__pulse acym__header__notification__button__success acym__header__notification__button__info acym__header__notification__button__warning acym__header__notification__button__error')
                    .find('> i')
                    .attr('class', 'acymicon-bell');
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
                jQuery.post(ajaxUrl, function (res) {
                    $closeButton.closest('.acym__message').remove();
                });
            } else {
                $closeButton.closest('.acym__message').remove();
            }
        });
    },
    removeNotifications: function () {
        jQuery(document)
            .on('click', '.acym__header__notification__one__delete, .acym__header__notification__toolbox__remove, .acym__dashboard__notification__delete, .acym__do__not__remindme', function () {
                const isDashboardNotif = jQuery(this).hasClass('acym__dashboard__notification__delete') || jQuery(this).hasClass('acym__do__not__remindme');
                let id = jQuery(this).attr('data-id');

                if (!id) {
                    id = jQuery(this).attr('title');
                }

                const ajaxUrl = ACYM_AJAX_URL + '&ctrl=configuration&task=removeNotification&id=' + id;

                jQuery.post(ajaxUrl, function (res) {
                    res = acym_helper.parseJson(res);
                    if (!res.error) {
                        if (res.data.dashboardHtml.length > 0) {
                            jQuery('.acym__dashboard__notifications').html(res.data.dashboardHtml);
                        } else {
                            jQuery('#acym__dashboard__notifications').remove();
                        }

                        if (!isDashboardNotif) {
                            jQuery('.acym__header__notification__center').html(res.data.headerHtml);
                            jQuery('.acym__header__notification').find('> i').attr('class', 'acymicon-bell');
                        }
                    } else {
                        console.log('Error removing notification:', res.message);
                    }
                });

                if (!isDashboardNotif) {
                    jQuery('.acym__header__notification')
                        .removeClass(
                            'acym__header__notification__pulse acym__header__notification__button__success acym__header__notification__button__info acym__header__notification__button__warning acym__header__notification__button__error')
                        .find('> i')
                        .attr('class', 'acymicon-bell');
                }
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

        jQuery.post(ajaxUrl, function (res) {
            res = acym_helper.parseJson(res);
            if (!res.error) {
                jQuery('.acym__header__notification').replaceWith(res.data.notificationCenter);
                acym_helperNotification.setNotificationCenter();
            } else {
                console.log(res.message);
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
                        + '<i data-id="0" class="cell shrink acym__message__close acymicon-close"></i>'
                        + '</div>';

        return structure;
    },
    readFullNotification: function () {
        jQuery('.acym__header__notification__one').off('click').on('click', function () {
            let $messageTag = jQuery(this).find('.acym__header__notification__message');
            let messageInAttribute = $messageTag.attr('data-acym-full');

            if (acym_helper.empty(messageInAttribute)) return true;

            let messageInTag = $messageTag.html();

            $messageTag.html(messageInAttribute);
            $messageTag.attr('data-acym-full', messageInTag);
        });
    }
};
