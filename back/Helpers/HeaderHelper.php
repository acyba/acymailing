<?php

namespace AcyMailing\Helpers;

use AcyMailing\Core\AcymObject;

class HeaderHelper extends AcymObject
{
    public function display(array $breadcrumb): string
    {
        $header = '<div class="cell large-6 xlarge-7 xxlarge-8 grid-x acym_vcenter">';
        $header .= $this->getBreadcrumb($breadcrumb);
        $header .= '</div>';

        // Begin Tools
        $header .= '<div class="cell large-6 xlarge-5 xxlarge-4 grid-x">';

        $header .= '<div id="checkVersionArea" class="cell auto grid-x align-right check-version-area acym_vcenter padding-right-1">';
        $header .= $this->checkVersionArea();
        $header .= '</div>';

        $header .= '<div class="cell shrink grid-x align-right acym_vcenter">';
        $header .= $this->getCheckVersionButton();
        $header .= $this->getHelpWedButton();
        $header .= $this->getDocumentationButton();
        $header .= $this->getNotificationCenter();
        $header .= '</div>';

        // End Tools
        $header .= '</div>';

        $header = '<div id="acym_header" class="grid-x margin-bottom-1">'.$header.'</div>';

        return $this->getLastNews().$header;
    }

    private function getLastNews(): string
    {
        $lastNewsCheck = $this->config->get('last_news_check', 0);
        if ($lastNewsCheck < time() - 7200) {
            $context = stream_context_create(['http' => ['timeout' => 1]]);
            $news = @file_get_contents(ACYM_ACYMAILING_WEBSITE.'acymnews.xml', false, $context);
            $this->config->save(
                [
                    'last_news_check' => time(),
                    'last_news' => base64_encode($news),
                ],
                false
            );
        } else {
            $news = $this->config->get('last_news', '');
            if (!empty($news)) $news = base64_decode($news);
        }
        if (empty($news)) {
            return '';
        }

        $news = @simplexml_load_string($news);
        if (empty($news->news)) {
            return '';
        }

        $currentLanguage = acym_getLanguageTag();
        $latestNews = null;
        $doNotRemind = json_decode($this->config->get('remindme', '[]'));

        foreach ($news->news as $oneNews) {
            // If we found a news more recent, it means we found the latest available one
            if (!empty($latestNews) && strtotime($latestNews->date) > strtotime($oneNews->date)) break;

            // If the news isn't published or that the language isn't correct, leave
            $language = strtolower($oneNews->language);
            if (empty($oneNews->published) || ($language != strtolower($currentLanguage) && ($language != 'default' || !empty($latestNews)))) {
                continue;
            }

            // If the extension isn't correct, leave
            if (!empty($oneNews->extension) && strtolower($oneNews->extension) !== 'acymailing') continue;

            // If the CMS isn't correct, leave
            if (!empty($oneNews->cms) && strtolower($oneNews->cms) != strtolower('{__CMS__}')) continue;

            // If the level of the extension isn't correct, leave
            if (!empty($oneNews->level) && strtolower($oneNews->level) != strtolower($this->config->get('level'))) continue;

            // If the version of the extension isn't correct, leave
            if (!empty($oneNews->version)) {
                [$version, $operator] = explode('_', $oneNews->version);
                if (!version_compare($this->config->get('version'), $version, $operator)) continue;
            }

            if (!empty($oneNews->mailermethod)) {
                $sendMethod = $this->config->get('mailer_method', '');
                if ($oneNews->mailermethod !== $sendMethod) continue;
            }

            if (in_array($oneNews->name, $doNotRemind)) continue;

            $latestNews = $oneNews;
        }

        if (empty($latestNews)) {
            return '';
        }

        $newsDisplay = '<div id="acym__header__banner__news" data-news="'.acym_escape($latestNews->name).'">';
        $newsDisplay .= $latestNews->content;
        $newsDisplay .= '</div>';

        return $newsDisplay;
    }

    private function getBreadcrumb(array $breadcrumb): string
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
                            <ul class="breadcrumbs grid-x">'.implode('<li class="breadcrumbs__separator"><i class="acymicon-keyboard-arrow-right"></i></li>', $links).'</ul>
                        </nav>
                    </div>';

        return $header;
    }

    public function checkVersionArea(bool $reloading = false): string
    {
        $currentLevel = $this->config->get('level', '');
        $currentVersion = $this->config->get('version', '');
        $latestVersion = $this->config->get('latestversion', '');

        $version = '<div id="acym_level_version_area" class="text-right">';
        $version .= '<div id="acym_level">'.ACYM_NAME.' '.$currentLevel.' ';

        if (version_compare($currentVersion, $latestVersion, '>=')) {
            $version .= acym_tooltip(
                [
                    'hoveredText' => '<span class="acym__color__green">'.$currentVersion.'</span>',
                    'textShownInTooltip' => acym_translation('ACYM_UP_TO_DATE'),
                ]
            );
        } elseif (!empty($latestVersion)) {
            if ('wordpress' === ACYM_CMS) {
                $downloadLink = admin_url().'update-core.php';
            } else {
                $downloadLink = ACYM_ACYMAILING_WEBSITE.'account/license/" target="_blank';
            }
            $version .= acym_tooltip(
                [
                    'hoveredText' => '<span class="acy_updateversion acym__color__red">'.$currentVersion.'</span>',
                    'textShownInTooltip' => acym_translationSprintf('ACYM_CLICK_UPDATE', $latestVersion),
                    'titleShownInTooltip' => acym_translation('ACYM_OLD_VERSION'),
                    'link' => $downloadLink,
                ]
            );
        }

        $version .= '</div></div>';

        $expirationDate = $this->config->get('expirationdate', 0);
        if ((empty($expirationDate) || $expirationDate == -1) && empty($this->config->get('acymailer_apikey', ''))) return $version;

        $version .= '<div id="acym_expiration" class="text-right cell">';
        //__START__production_
        if (acym_level(ACYM_ESSENTIAL) && ACYM_PRODUCTION) {
            if ($expirationDate == -2) {
                $version .= '<div class="acylicence_expired">
                            <a class="acy_attachlicence acymbuttons acym__color__red acym_link_license_tab" 
                                href="'.acym_completeLink('configuration', false, false, true).'">'.acym_translation('ACYM_ATTACH_LICENCE').'</a>
                        </div>';
            } elseif ($expirationDate < time()) {
                //TODO redirect to the subscription page directly instead of the subscriptions listing
                // We'll need to return the subscription id when calling the API
                $version .= acym_tooltip(
                    [
                        'hoveredText' => '<span class="acy_subscriptionexpired acym__color__red">'.acym_translation('ACYM_SUBSCRIPTION_EXPIRED').'</span>',
                        'textShownInTooltip' => acym_translation('ACYM_SUBSCRIPTION_EXPIRED_LINK'),
                        'link' => ACYM_ACYMAILING_WEBSITE.'account/license/',
                    ]
                );
            } else {
                $version .= '<div class="acylicence_valid">
                            <span class="acy_subscriptionok acym__color__green">'.acym_translationSprintf(
                        'ACYM_VALID_UNTIL',
                        acym_getDate($expirationDate, acym_translation('ACYM_DATE_FORMAT_LC4'))
                    ).'</span>
                        </div>';
            }
        }
        //__END__production_

        $creditRemainingSendingMethod = '';
        acym_trigger('onAcymGetCreditRemainingSendingMethod', [&$creditRemainingSendingMethod, $reloading]);
        if (!empty($creditRemainingSendingMethod)) {
            $version .= '<div class="acy_sending_method_credits">
                            <span>'.$creditRemainingSendingMethod.'</span>
                        </div>';
        }

        $version .= '</div>';

        return $version;
    }

    private function getCheckVersionButton(): string
    {
        if (ACYM_CMS == 'wordpress' && !acym_level(ACYM_ESSENTIAL)) {
            return '';
        }

        $lastLicenseCheck = $this->config->get('lastlicensecheck', 0);
        $time = time();
        $checking = ($time > $lastLicenseCheck + 604800) ? $checking = '1' : '0';
        if (empty($lastLicenseCheck)) $lastLicenseCheck = $time;

        return acym_tooltip(
            [
                'hoveredText' => '<a id="checkVersionButton" type="button" class="grid-x align-center button_header medium-shrink acym_vcenter" data-check="'.acym_escape(
                        $checking
                    ).'"><i class="cell shrink acymicon-autorenew"></i></a>',
                'textShownInTooltip' => acym_translation('ACYM_LAST_CHECK').' <span id="acym__check__version__last__check">'.acym_date($lastLicenseCheck, 'Y/m/d H:i').'</span>',
            ]
        );
    }

    private function getDocumentationButton(): string
    {
        return acym_tooltip(
            [
                'hoveredText' => '<a type="button" class="grid-x align-center button_header medium-shrink acym_vcenter" target="_blank" href="'.ACYM_DOCUMENTATION.'"><i class="cell shrink acymicon-book"></i></a>',
                'textShownInTooltip' => acym_translation('ACYM_DOCUMENTATION'),
            ]
        );
    }

    private function getHelpWedButton(): string
    {
        if (ACYM_CMS !== 'wordpress' || acym_level(ACYM_ESSENTIAL)) {
            return '';
        }

        return '<a type="button" class="grid-x align-center button_header medium-shrink acym_vcenter" target="_blank" href="https://wordpress.org/support/plugin/acymailing/">
                    <i class="cell shrink acymicon-wordpress"></i>
                </a>';
    }

    public function getNotificationCenter(): string
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
                $iconToDisplay = 'acymicon-bell';
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
                $iconToDisplay = 'acymicon-bell acym__color__blue';
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

    public function getNotificationCenterInner(array $notifications): string
    {
        $notificationCenter = '';
        if (empty($notifications)) {
            $notificationCenter .= '<div class="cell grid-x acym__header__notification__one acym__header__notification__one__empty acym_vcenter">';
            $notificationCenter .= '<h2 class="cell text-center">'.acym_translation('ACYM_YOU_DONT_HAVE_NOTIFICATIONS').'</h2>';
            $notificationCenter .= '</div>';
        } else {
            $notificationCenter .= '<div class="cell grid-x acym__header__notification__toolbox"><p class="cell auto">'.acym_translation(
                    'ACYM_NOTIFICATIONS'
                ).'</p><div class="cell shrink cursor-pointer acym__header__notification__toolbox__remove text-right" data-id="all">'.acym_translation('ACYM_DELETE_ALL').'</div></div>';
            foreach ($notifications as $key => $notif) {
                $fullMessageHover = $notif['message'];

                if (strlen($notif['message']) > 150) {
                    $tag = new \stdClass();
                    $tag->wrap = 150;

                    $pluginHelper = new PluginHelper();
                    $notif['message'] = $pluginHelper->wrapText($notif['message'], $tag);
                }
                $fullMessageHover = $fullMessageHover != $notif['message'] ? 'data-acym-full="'.acym_escape($fullMessageHover).'"' : '';

                $logo = $notif['level'] === 'info' ? 'acymicon-bell' : ($notif['level'] == 'warning' ? 'acymicon-exclamation-triangle' : 'acymicon-exclamation-circle');
                $read = $notif['read'] ? 'acym__header__notification__one__read' : '';
                $notificationCenter .= '<div class="'.$read.' cell grid-x acym__header__notification__one acym_vcenter acym_vcenter acym__header__notification__one__'.$notif['level'].'">';
                $notificationCenter .= '<div class="cell small-3 align-center grid-x acym__header__notification__one__icon"><i class="cell '.$logo.'"></i></div>';
                $notificationCenter .= '<div class="cell grid-x small-8"><p class="cell acym__header__notification__message" '.$fullMessageHover.'>'.$notif['message'];
                $notificationCenter .= '<div class="cell acym__header__notification__one__date">'.acym_date($notif['date']).'</div></div>';
                $notificationCenter .= '<i class="cell small-1 acym__header__notification__one__delete acymicon-close" data-id="'.acym_escape($key).'"></i>';
                $notificationCenter .= '</div>';
            }
        }

        return $notificationCenter;
    }

    public function addNotification(object $notif): string
    {
        if ($notif->level === 'success') {
            $_SESSION['acym_success'] = $notif->message;

            return '';
        }

        $notifications = json_decode($this->config->get('notifications', '[]'), true);
        if (!is_array($notifications)) {
            $notifications = [];
        }

        $notif->message = str_replace('<br />', "\r\n", $notif->message);
        $notif->message = strip_tags($notif->message, '<a>');

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
