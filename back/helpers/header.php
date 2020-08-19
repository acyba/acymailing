<?php

class acymheaderHelper extends acymObject
{
    public function display($breadcrumb)
    {
        $header = '<div class="cell large-6 xlarge-7 xxlarge-8 grid-x">';
        $header .= $this->getBreadcrumb($breadcrumb);
        $header .= '</div>';

        // Begin Tools
        $header .= '<div class="cell large-6 xlarge-5 xxlarge-4 grid-x">';

        $header .= '<div id="checkVersionArea" class="cell auto grid-x align-right check-version-area acym_vcenter padding-right-1">';
        $header .= $this->checkVersionArea();
        $header .= '</div>';

        $header .= '<div class="cell shrink grid-x align-right">';
        $header .= $this->getCheckVersionButton();
        $header .= $this->getDocumentationButton();
        $header .= $this->getNotificationCenter();
        $header .= '</div>';

        // End Tools
        $header .= '</div>';

        $header = '<div id="acym_header" class="grid-x hide-for-small-only margin-bottom-1">'.$header.'</div>';

        return $this->getLastNews().$header;
    }

    private function getLastNews()
    {
        $news = @simplexml_load_file(ACYM_ACYMAILLING_WEBSITE.'acymnews.xml');
        if (empty($news->news)) return '';

        $currentLanguage = acym_getLanguageTag();
        $latestNews = null;
        $doNotRemind = json_decode($this->config->get('remindme', '[]'));

        foreach ($news->news as $oneNews) {
            // If we found a news more recent, it means we found the latest available one
            if (!empty($latestNews) && strtotime($latestNews->date) > strtotime($oneNews->date)) break;

            // If the news isn't published or that the language isn't correct, leave
            if (empty($oneNews->published) || (strtolower($oneNews->language) != strtolower($currentLanguage) && (strtolower($oneNews->language) != 'default' || !empty($latestNews)))) continue;

            // If the extension isn't correct, leave
            if (!empty($oneNews->extension) && strtolower($oneNews->extension) != 'acymailing') continue;

            // If the CMS isn't correct, leave
            if (!empty($oneNews->cms) && strtolower($oneNews->cms) != strtolower('{__CMS__}')) continue;

            // If the level of the extension isn't correct, leave
            if (!empty($oneNews->level) && strtolower($oneNews->level) != strtolower($this->config->get('level'))) continue;

            // If the version of the extension isn't correct, leave
            if (!empty($oneNews->version)) {
                list($version, $operator) = explode('_', $oneNews->version);
                if (!version_compare($this->config->get('version'), $version, $operator)) continue;
            }

            if (in_array($oneNews->name, $doNotRemind)) continue;

            $latestNews = $oneNews;
        }

        if (empty($latestNews)) return '';

        $newsDisplay = '<div id="acym__header__banner__news" data-news="'.acym_escape($latestNews->name).'">';

        if (!empty($latestNews)) {
            $newsDisplay .= $latestNews->content;
        }

        $newsDisplay .= '</div>';

        return $newsDisplay;
    }

    private function getBreadcrumb($breadcrumb)
    {
        $links = [];
        foreach ($breadcrumb as $oneLevel => $link) {
            if (!empty($link)) {
                $oneLevel = '<a href="'.$link.'">'.$oneLevel.'</a>';
            }
            $links[] = '<li>'.$oneLevel.'</li>';
        }

        if (count($links) > 1) {
            $links[count($links) - 1] = str_replace('<li>', '<li class="last_link cell shrink"><span class="show-for-sr">Current: </span>', $links[count($links) - 1]);
        }

        $header = '<i class="cell shrink acym-logo"></i>';
        $header .= '<div id="acym_global_navigation" class="cell auto">
                        <nav aria-label="You are here:" role="navigation">
                            <ul class="breadcrumbs grid-x">'.implode('', $links).'</ul>
                        </nav>
                    </div>';

        return $header;
    }

    public function checkVersionArea()
    {
        $currentLevel = $this->config->get('level', '');
        $currentVersion = $this->config->get('version', '');
        $latestVersion = $this->config->get('latestversion', '');

        $version = '<div id="acym_level_version_area" class="text-right">';
        $version .= '<div id="acym_level">'.ACYM_NAME.' '.$currentLevel.' ';

        if (version_compare($currentVersion, $latestVersion, '>=')) {
            $version .= acym_tooltip('<span class="acym__color__green">'.$currentVersion.'</span>', acym_translation('ACYM_UP_TO_DATE'));
        } elseif (!empty($latestVersion)) {
            if ('wordpress' === ACYM_CMS) {
                $downloadLink = admin_url().'update-core.php';
            } else {
                $downloadLink = ACYM_REDIRECT.'update-acymailing-'.$currentLevel.'&version='.$this->config->get('version').'" target="_blank';
            }
            $version .= acym_tooltip(
                '<span class="acy_updateversion acym__color__red">'.$currentVersion.'</span>',
                acym_translation_sprintf('ACYM_CLICK_UPDATE', $latestVersion),
                '',
                acym_translation('ACYM_OLD_VERSION'),
                $downloadLink
            );
        }

        $version .= '</div></div>';

        if (!acym_level(1)) return $version;

        $expirationDate = $this->config->get('expirationdate', 0);
        if (empty($expirationDate) || $expirationDate == -1) return $version;

        $version .= '<div id="acym_expiration" class="text-right cell">';
        if ($expirationDate == -2) {
            $version .= '<div class="acylicence_expired">
                            <a class="acy_attachlicence acymbuttons acym__color__red" href="'.ACYM_REDIRECT.'acymailing-assign" target="_blank">'.acym_translation('ACYM_ATTACH_LICENCE').'</a>
                        </div>';
        } elseif ($expirationDate < time()) {
            $version .= acym_tooltip(
                '<span class="acy_subscriptionexpired acym__color__red">'.acym_translation('ACYM_SUBSCRIPTION_EXPIRED').'</span>',
                acym_translation('ACYM_SUBSCRIPTION_EXPIRED_LINK'),
                '',
                '',
                ACYM_REDIRECT.'renew-acymailing-'.$currentLevel
            );
        } else {
            $version .= '<div class="acylicence_valid">
                            <span class="acy_subscriptionok acym__color__green">'.acym_translation_sprintf('ACYM_VALID_UNTIL', acym_getDate($expirationDate, acym_translation('ACYM_DATE_FORMAT_LC4'))).'</span>
                        </div>';
        }
        $version .= '</div>';

        return $version;
    }

    private function getCheckVersionButton()
    {
        if (ACYM_CMS == 'wordpress' && !acym_level(1)) return '';
        $lastLicenseCheck = $this->config->get('lastlicensecheck', 0);
        $time = time();
        $checking = ($time > $lastLicenseCheck + 604800) ? $checking = '1' : '0';
        if (empty($lastLicenseCheck)) $lastLicenseCheck = $time;

        return acym_tooltip(
            '<a id="checkVersionButton" type="button" class="grid-x align-center button_header medium-shrink acym_vcenter" data-check="'.acym_escape($checking).'"><i class="cell shrink acymicon-autorenew"></i></a>',
            acym_translation('ACYM_LAST_CHECK').' <span id="acym__check__version__last__check">'.acym_date($lastLicenseCheck, 'Y/m/d H:i').'</span>'
        );
    }

    private function getDocumentationButton()
    {
        return acym_tooltip(
            '<a type="button" class="grid-x align-center button_header medium-shrink acym_vcenter" target="_blank" href="'.ACYM_DOCUMENTATION.'"><i class="cell shrink acymicon-book"></i></a>',
            acym_translation('ACYM_DOCUMENTATION')
        );
    }

    public function getNotificationCenter()
    {
        $notifications = json_decode($this->config->get('notifications', '{}'), true);
        $message = '';
        //if we have a notifications for success
        $notificationLevel = 0;
        if (!empty($_SESSION['acym_success'])) {
            $message = $_SESSION['acym_success'];
            $_SESSION['acym_success'] = '';
            $notificationLevel = 1;
        }

        if (!empty($notifications)) {
            foreach ($notifications as $notification) {
                if ($notification['read']) continue;
                if ($notification['level'] == 'info' && $notificationLevel < 2) $notificationLevel = 2;
                if ($notification['level'] == 'warning' && $notificationLevel < 3) $notificationLevel = 3;
                if ($notification['level'] == 'error' && $notificationLevel < 4) $notificationLevel = 4;
            }
        }

        $iconToDisplay = '';
        $tooltip = '';

        switch ($notificationLevel) {
            case 0:
                // default icon
                $iconToDisplay = 'acymicon-bell-o';
                $notificationLevel = '';
                break;
            case 1:
                // success
                $iconToDisplay = 'acymicon-check-circle acym__color__green';
                $notificationLevel = 'acym__header__notification__button__success acym__header__notification__pulse';
                $tooltip = 'data-acym-tooltip="'.acym_escape($message).'" data-acym-tooltip-position="left"';
                break;
            case 2:
                // info
                $iconToDisplay = 'acymicon-bell-o acym__color__blue';
                $notificationLevel = 'acym__header__notification__button__info';
                break;
            case 3:
                // warning
                $iconToDisplay = 'acymicon-exclamation-triangle acym__color__orange';
                $notificationLevel = 'acym__header__notification__button__warning';
                break;
            case 4:
                // error
                $iconToDisplay = 'acymicon-exclamation-circle acym__color__red';
                $notificationLevel = 'acym__header__notification__button__error';
                break;
        }

        $notificationCenter = '<div class="cell grid-x align-center acym_vcenter medium-shrink acym__header__notification '.$notificationLevel.' button_header cursor-pointer" '.$tooltip.'>';
        $notificationCenter .= '<i class="'.$iconToDisplay.'"></i>';
        $notificationCenter .= '<div class="cell grid-x acym__header__notification__center align-center">';
        $notificationCenter .= $this->getNotificationCenterInner($notifications);
        $notificationCenter .= '</div>';
        $notificationCenter .= '</div>';

        return $notificationCenter;
    }

    public function getNotificationCenterInner($notifications)
    {
        $notificationCenter = '';
        if (empty($notifications)) {
            $notificationCenter .= '<div class="cell grid-x acym__header__notification__one acym__header__notification__one__empty acym_vcenter">';
            $notificationCenter .= '<h2 class="cell text-center">'.acym_translation('ACYM_YOU_DONT_HAVE_NOTIFICATIONS').'</h2>';
            $notificationCenter .= '</div>';
        } else {
            $notificationCenter .= '<div class="cell grid-x acym__header__notification__toolbox"><p class="cell auto">'.acym_translation('ACYM_NOTIFICATIONS').'</p><div class="cell shrink cursor-pointer acym__header__notification__toolbox__remove text-right">'.acym_translation('ACYM_DELETE_ALL').'</div></div>';
            foreach ($notifications as $key => $notif) {
                if (strlen($notif['message']) > 150) $notif['message'] = substr($notif['message'], 0, 150).'...';
                $logo = $notif['level'] == 'info' ? 'acymicon-bell' : ($notif['level'] == 'warning' ? 'acymicon-exclamation-triangle' : 'acymicon-exclamation-circle');
                $read = $notif['read'] ? 'acym__header__notification__one__read' : '';
                $notificationCenter .= '<div class="'.$read.' cell grid-x acym__header__notification__one acym_vcenter acym_vcenter acym__header__notification__one__'.$notif['level'].'">';
                $notificationCenter .= '<div class="cell small-3 align-center grid-x acym__header__notification__one__icon"><i class="cell '.$logo.'"></i></div>';
                $notificationCenter .= '<div class="cell grid-x small-8"><p class="cell acym__header__notification__message">'.$notif['message'];
                $notificationCenter .= '<div class="cell acym__header__notification__one__date">'.acym_date($notif['date']).'</div></div>';
                $notificationCenter .= '<i class="cell small-1 acym__header__notification__one__delete acymicon-close" data-id="'.acym_escape($key).'"></i>';
                $notificationCenter .= '</div>';
            }
        }

        return $notificationCenter;
    }

    public function addNotification($notif)
    {
        if ($notif->level == 'success') {
            $_SESSION['acym_success'] = $notif->message;

            return true;
        }

        $notifications = json_decode($this->config->get('notifications', '[]'), true);
        if (!is_array($notifications)) {
            $notifications = [];
        }

        $notif->message = strip_tags($notif->message);

        // Prevent duplicated notifications
        foreach ($notifications as $key => $oneNotif) {
            if ($oneNotif['message'] === $notif->message && $oneNotif['level'] === $notif->level) unset($notifications[$key]);
        }
        $notifications = array_values($notifications);

        $notif->id = uniqid();
        array_unshift($notifications, $notif);

        if (count($notifications) > 10) unset($notifications[10]);

        $this->config->save(['notifications' => json_encode($notifications)]);

        return $notif->id;
    }
}
