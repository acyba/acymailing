<?php

namespace AcyMailing\FrontControllers;

use AcyMailing\Classes\FieldClass;
use AcyMailing\Classes\ListClass;
use AcyMailing\Classes\MailClass;
use AcyMailing\Classes\ScenarioClass;
use AcyMailing\Classes\UserClass;
use AcyMailing\Controllers\UsersController;
use AcyMailing\Helpers\CaptchaHelper;
use AcyMailing\Helpers\EntitySelectHelper;
use AcyMailing\Helpers\ExportHelper;
use AcyMailing\Helpers\ToolbarHelper;
use AcyMailing\Helpers\UserHelper;
use AcyMailing\Core\AcymParameter;

class FrontusersController extends UsersController
{
    public function __construct()
    {
        parent::__construct();

        if (ACYM_CMS === 'joomla') {
            $menu = acym_getMenu();
            if (is_object($menu)) {
                $params = method_exists($menu, 'getParams') ? $menu->getParams() : $menu->params;
                $menuParams = new AcymParameter($params);
                $this->menuClass = $menuParams->get('pageclass_sfx', '');
            }
        }

        $this->publicFrontTasks = [
            'subscribe',
            'unsubscribe',
            'unsubscribeAll',
            'saveSubscriptions',
            'unsubscribePage',
            'confirm',
            'profile',
            'savechanges',
            'exportdata',
            'ajaxGetEnqueuedMessages',
            'gdprDelete',
        ];

    }


    private function displayMessage(string $message, bool $ajax, string $type = 'error'): void
    {
        if ($ajax) {
            echo json_encode(
                [
                    'message' => acym_translation($message),
                    'type' => $type,
                    'code' => '1',
                ]
            );
        } else {
            acym_header('Content-type:text/html; charset=utf-8');
            echo '<script>alert("'.acym_translation($message, true).'"); window.history.go(-1);</script>';
        }
        exit;
    }

    public function subscribe(): void
    {
        acym_checkRobots();

        if (!acym_getVar('string', 'acy_source') && !empty($_GET['user'])) {
            // Coming from a subscription via url...
            acym_setVar('acy_source', 'url');
        }

        // Do we have to return an ajax response or a web page ?
        $ajax = acym_getVar('int', 'ajax', 0);
        if ($ajax) {
            //in case of the page displays some warnings or whatever
            @ob_end_clean();
            acym_header('Content-type:application/json; charset=utf-8');
        }

        //We only allow logged in users and this user is not logged it...
        $currentUserid = acym_currentUserId();
        if ((int)$this->config->get('allow_visitor', 1) != 1 && empty($currentUserid)) {
            if ($ajax) {
                echo '{"message":"'.acym_translation('ACYM_ONLY_LOGGED', true).'","type":"error","code":"0"}';
                exit;
            } else {
                acym_askLog(false, 'ACYM_ONLY_LOGGED');

                return;
            }
        }

        if (empty($currentUserid) && $this->config->get('captcha', 'none') !== 'none' && acym_level(ACYM_ESSENTIAL)) {
            $captchaHelper = new CaptchaHelper();
            if (!$captchaHelper->check()) {
                $this->displayMessage('ACYM_WRONG_CAPTCHA', $ajax);
            }
        }

        $formData = acym_getVar('array', 'user', [], '');
        $user = new \stdClass();
        if (!empty($formData['email'])) {
            $user->email = $formData['email'];
        }

        $userClass = new UserClass();
        if (empty($user->email)) {
            $connectedUser = $userClass->identify(true, 'userId', 'userKey');
            if (!empty($connectedUser->email)) {
                $user->email = $connectedUser->email;
            }
        }
        $user->email = trim($user->email);

        // Check email validity
        if (empty($user->email) || !acym_isValidEmail($user->email, true)) {
            $this->displayMessage('ACYM_VALID_EMAIL', $ajax);
        }

        // E-mail is valid now...
        // Check if this user already exists or not...
        $alreadyExists = $userClass->getOneByEmail($user->email);
        if (!empty($alreadyExists->id)) {
            $user->id = $alreadyExists->id;
        }

        $successfullySaved = $userClass->saveForm($ajax);
        $user->id = acym_getVar('int', 'userId');

        if (!empty($userClass->errors)) {
            $this->displayMessage(implode('<br /><br />', $userClass->errors), $ajax);
        }

        if (!$successfullySaved || empty($user->id)) {
            $this->displayMessage('ACYM_ERROR_SAVE_USER', $ajax);
        }

        //We will now load back the user from the one we saved so that we will have all its infos
        $myuser = $userClass->getOneById($user->id);
        if (empty($myuser->id)) {
            $this->displayMessage('ACYM_ERROR_SAVE_USER', $ajax);
        }

        $msgtype = 'success';
        if (empty($myuser->confirmed) && $this->config->get('require_confirmation', 1) == 1) {
            if ($userClass->confirmationSentSuccess || empty($userClass->confirmationSentError)) {
                $msg = strip_tags(acym_getVar('string', 'confirmation_message', ''));
                if (empty($msg)) {
                    $msg = 'ACYM_CONFIRMATION_SENT';
                }
                $code = 2;
            } else {
                $msg = $userClass->confirmationSentError;
                $code = 7;
                $msgtype = 'error';
            }
        } else {
            if ($userClass->subscribed) {
                $msg = strip_tags(acym_getVar('string', 'confirmation_message', ''));
                if (empty($msg)) {
                    $msg = 'ACYM_SUBSCRIPTION_OK';
                }
                $code = 3;
            } else {
                $msg = 'ACYM_ALREADY_SUBSCRIBED';
                $code = 5;
                $enqueueMsgType = 'info';
            }
        }

        // Replace tags inside the $msg and redirect link
        $replace = [];
        foreach ($myuser as $oneProp => $oneVal) {
            $replace['{user:'.$oneProp.'}'] = $oneVal;
        }

        $msg = str_replace(array_keys($replace), $replace, acym_translation($msg));

        if ($ajax) {
            //Make sure the message has a valid format for Ajax... so the user can customize it the way he wants without breaking anything
            $msg = str_replace(["\n", "\r", '"', '\\'], [' ', ' ', "'", '\\\\'], $msg);
            echo '{"message":"'.$msg.'","type":"'.$msgtype.'","code":"'.$code.'"}';
            exit;
        } else {
            acym_enqueueMessage($msg, !empty($enqueueMsgType) ? $enqueueMsgType : $msgtype);
        }

        $redirectUrl = urldecode(acym_getVar('string', 'redirect', ''));
        $redirectUrl = str_replace(array_keys($replace), $replace, $redirectUrl);
        if (empty($redirectUrl)) {
            $redirectUrl = acym_rootURI();
        }

        acym_redirect($redirectUrl, '', 'message', true);
    }

    private function unsubscribeDirectly(object $alreadyExists, bool $ajax): void
    {
        $userClass = new UserClass();
        if ($this->config->get('allow_modif', 'data') === 'none') {
            $currentUser = $userClass->identify(true, 'userId', 'userKey');
            if (empty($currentUser->email)) {
                $this->endUnsubscribe('ACYM_LOGIN', $ajax, 'error');

                return;
            }
        }

        if (empty($currentUser) || empty($currentUser->id)) {
            $currentUser = $alreadyExists;
        }

        $visibleSubscription = acym_getVar('array', 'subscription', []);
        $hiddenLists = trim(acym_getVar('string', 'hiddenlists', ''));
        $hiddenSubscription = empty($hiddenLists) ? [] : explode(',', $hiddenLists);
        $unsubscribeLists = array_merge($visibleSubscription, $hiddenSubscription);

        $mailId = acym_getVar('int', 'mail_id', 0);
        if (empty($unsubscribeLists)) {
            $mailClass = new MailClass();
            $mailType = $mailClass->getMailType($mailId);
            if (empty($currentUser) || empty($currentUser->id)) {
                $currentUser = $userClass->identify(true, 'userId', 'userKey');
            }

            if ($mailType === $mailClass::TYPE_SCENARIO) {
                $scenarioClass = new ScenarioClass();
                $scenarioId = $scenarioClass->getScenarioIdByMailId($mailId);
                if (!empty($currentUser->id)) {
                    $scenarioClass->markUserUnsubscribeFromScenario($currentUser->id, $scenarioId);
                }
            }
        }
        if (!empty($mailId)) {
            $mailClass = new MailClass();
            $unsubscribeLists = array_keys($mailClass->getAllListsByMailId($mailId));
        }

        if (empty($unsubscribeLists)) {
            $msg = 'ACYM_NO_SUBSCRIPTION_LINKED_EMAIL';
        } elseif (false === $userClass->unsubscribe([$alreadyExists->id], $unsubscribeLists)) {
            $msg = 'ACYM_UNSUBSCRIPTION_NOT_IN_LIST';
        } else {
            $msg = 'ACYM_UNSUBSCRIPTION_OK';
        }

        if (!empty($mailType) && $mailType === $mailClass::TYPE_SCENARIO) {
            $msg = 'ACYM_UNSUBSCRIPTION_SCENARIO_OK';
        }


        $this->endUnsubscribe($msg, $ajax);
    }

    private function endUnsubscribe(string $msg, bool $ajax, string $type = 'success'): void
    {
        $msg = acym_translation($msg);

        if ($ajax) {
            echo json_encode(
                [
                    'message' => $msg,
                    'type' => $type,
                    'code' => '10',
                ]
            );
            exit;
        }
        acym_enqueueMessage($msg, $type);

        $redirectUrl = urldecode(acym_getVar('string', 'redirectunsub', ''));
        if (empty($redirectUrl)) {
            $redirectUrl = acym_rootURI();
        }

        acym_redirect($redirectUrl);
    }

    public function unsubscribe(): void
    {
        acym_checkRobots();
        $userClass = new UserClass();

        $redirectToUnsubPage = $this->config->get('unsubscribe_page', 1);
        $direct = acym_getVar('int', 'direct', 0);

        // Do we have to return an ajax response or a web page ?
        $ajax = acym_getVar('int', 'ajax', 0);
        if ($ajax) {
            @ob_end_clean();
            acym_header('Content-type:application/json; charset=utf-8');
        }

        $currentUserid = acym_currentUserId();
        $user = $userClass->identify(true, 'userId', 'userKey');
        if (empty($user)) {
            $user = $userClass->identify(true, 'user_id', 'user_key');
            $direct = 1;
        }
        if (empty($user) && empty($currentUserid) && $this->config->get('captcha', 'none') !== 'none' && acym_level(ACYM_ESSENTIAL)) {
            $captchaClass = new CaptchaHelper();
            if (!$captchaClass->check()) {
                $this->displayMessage('ACYM_WRONG_CAPTCHA', $ajax);
            }
        }

        $formData = acym_getVar('array', 'user', []);
        $email = '';

        if (!empty($formData['email'])) {
            $email = trim(strip_tags($formData['email']));
        } elseif (empty($user)) {
            return;
        } elseif (!empty($user->email)) {
            $email = $user->email;
        } elseif (!empty(acym_currentUserEmail())) {
            $email = acym_currentUserEmail();
        }

        $currentEmail = acym_currentUserEmail();
        if (empty($email) && !empty($currentEmail)) {
            $email = $currentEmail;
        }

        // Check email validity
        if (empty($email) || !acym_isValidEmail($email)) {
            $this->displayMessage('ACYM_VALID_EMAIL', $ajax);
        }

        $alreadyExists = $userClass->getOneByEmail($email);

        // User not found
        if (empty($alreadyExists->id)) {
            $this->displayMessage('ACYM_SUB_NOT_IN_LIST', $ajax);
        }

        $fromModuleOrWidget = acym_getVar('string', 'acysubmode', '');
        if ((!empty($fromModuleOrWidget) && in_array($fromModuleOrWidget, ['form_acym', 'mod_acym', 'widget_acym'])) || $ajax || !$redirectToUnsubPage || !empty($direct)) {
            $this->unsubscribeDirectly($alreadyExists, $ajax);
        } elseif ($redirectToUnsubPage && !$ajax && !$direct) {
            $this->unsubscribePage($alreadyExists);
        }
    }

    private function getUserFromUnsubPage(): object
    {
        $userID = acym_getVar('int', 'user_id');
        $userClass = new UserClass();

        $user = $userClass->getOneById($userID);

        if (empty($user)) {
            acym_enqueueMessage(acym_translation('ACYM_USER_NOT_FOUND'), 'error');

            acym_redirect(acym_rootURI());
        }

        return $user;
    }

    private function unsubscribeAllInner(bool $update = false): void
    {
        $userClass = new UserClass();
        $user = $this->getUserFromUnsubPage();
        $redirectUrl = $this->config->get('unsub_redirect_url', acym_rootURI());

        $allLists = $userClass->getUserSubscriptionById($user->id);
        if (empty($allLists)) {
            acym_enqueueMessage(acym_translation('ACYM_NOT_SUBSCRIBED_ANY_LIST'), 'info');
            acym_redirect(!empty($redirectUrl) ? $redirectUrl : acym_rootURI());
        }

        $lists = [];
        foreach ($allLists as $list) {
            if (!$update || $list->visible == 1) {
                $lists[] = $list->id;
            }
        }

        $userClass->sendNotification($user->id, 'acy_notification_unsuball');
        // A notification is sent when a user unsubscribes from a list, be we already sent a notification for this
        $userClass->blockNotifications = true;
        $userClass->unsubscribe([$user->id], $lists);
    }

    private function redirectUnsubWorked(): void
    {
        acym_enqueueMessage(acym_translation('ACYM_SUBSCRIPTION_UPDATED_OK'));
        $redirectUrl = $this->config->get('unsub_redirect_url');
        if (!empty($redirectUrl)) {
            acym_redirect($redirectUrl);
        } else {
            acym_redirect(acym_rootURI());
        }
    }

    public function unsubscribeAll(): void
    {
        $userClass = new UserClass();
        if (empty($userClass->identify(true, 'user_id', 'user_key'))) {
            acym_enqueueMessage(acym_translation('ACYM_USER_NOT_FOUND'), 'error');

            acym_redirect(acym_rootURI());
        }

        $displayCheckedLists = acym_getVar('string', 'displayed_checked_lists', '');
        if (!empty($displayCheckedLists)) {
            $user = $this->getUserFromUnsubPage();
            $userClass->unsubscribe([$user->id], explode(',', $displayCheckedLists));
        } else {
            $this->unsubscribeAllInner();
        }

        $ajax = acym_getVar('int', 'ajax', 0);
        if (!$ajax) {
            $this->redirectUnsubWorked();
        }
    }

    public function saveSubscriptions(): void
    {
        $userClass = new UserClass();
        if (empty($userClass->identify(true, 'user_id', 'user_key'))) {
            $this->displayMessage('ACYM_USER_NOT_FOUND', false);
        }

        // Get the user
        $user = $this->getUserFromUnsubPage();
        // Get the list the user want to sub
        $listsChecked = acym_getVar('array', 'lists', []);
        $listsChecked = array_filter($listsChecked, function ($status) {
            return intval($status) === 1;
        });
        $listsChecked = array_keys($listsChecked);
        // Get the displayed lists
        $displayedCheckedLists = explode(',', acym_getVar('string', 'displayed_checked_lists', ''));
        // We get the user subscriptions
        $userSubscriptions = $userClass->getUserSubscriptionById($user->id);

        // We subscribe the user to the checked lists
        $userClass->subscribe([$user->id], $listsChecked);

        // We unsub to the unchecked lists
        $listsToUnsub = [];
        foreach ($userSubscriptions as $subscription) {
            // The list wasn't checked && the list is displayed && the user is subscribed to it
            if (
                !in_array($subscription->id, $listsChecked)
                && in_array($subscription->id, $displayedCheckedLists)
                && intval($subscription->status) === 1
            ) {
                $listsToUnsub[] = $subscription->id;
            }
        }

        $userClass->unsubscribe([$user->id], $listsToUnsub);
        $this->redirectUnsubWorked();
    }

    public function unsubscribePage(object $alreadyExists): void
    {
        $userClass = new UserClass();
        $lang = acym_getVar('string', 'language', acym_getLanguageTag());
        $mailId = acym_getVar('int', 'mail_id', 0);
        $campaignListOnly = $this->config->get('unsubscribe_campaign_list_only', '0') === '1';
        $displaySurvey = $this->config->get('unsubpage_survey', '0') === '1';
        $surveyAnswers = $this->config->get('unsub_survey', '[]');
        $unsubscribeColor = $this->config->get('unsubscribe_color', '#00a4ff');
        $hoverColor = $this->darkenRGBColor($unsubscribeColor, 20);

        $surveyAnswers = json_decode($surveyAnswers, true);

        if (ACYM_CMS === 'joomla' && !empty($_GET['language'])) $lang = $_GET['language'];
        acym_setLanguage($lang);
        acym_loadLanguage($lang);

        $userSubscriptions = $userClass->getUserSubscriptionById($alreadyExists->id, 'id', false, false, true, false, $mailId, $campaignListOnly);

        $data = [
            'user' => $alreadyExists,
            'mail_id' => $mailId,
            'subscriptions' => $userSubscriptions,
            'lang' => $lang,
        ];

        if (acym_isMultilingual()) {
            $allLanguages = acym_getLanguages(false, true);
            if ($this->config->get('unsubpage_languages_multi_only', '0') === '1') {
                $allLanguages = acym_getMultilingualLanguages();
            }

            $data['languages'] = [];
            foreach ($allLanguages as $key => $language) {
                $data['languages'][$key] = $language->name;
            }

            $surveyAnswersTranslations = $this->config->get('unsub_survey_translation', '');

            if (!empty($surveyAnswersTranslations)) {
                $surveyAnswersTranslations = json_decode($surveyAnswersTranslations, true);
                if (isset($surveyAnswersTranslations[$data['lang']])) {
                    $surveyAnswers = $surveyAnswersTranslations[$data['lang']]['unsub_survey'];
                }
            }
        }

        if ($displaySurvey) {
            $data['surveyAnswers'] = $surveyAnswers;
            array_unshift($data['surveyAnswers'], acym_translation('ACYM_SELECT_REASON'));
            $data['surveyAnswers'][] = acym_translation('ACYM_OTHER');
            $data['surveyAnswers'] = array_combine($data['surveyAnswers'], $data['surveyAnswers']);
        }

        if (!empty($this->config->get('unsubscribe_color'))) {
            $unsubscribeColor = $this->config->get('unsubscribe_color');
            $hoverColor = $this->darkenRGBColor($unsubscribeColor, 20);
            $data['unsubscribeColor'] = $unsubscribeColor;
            $data['hoverColor'] = $hoverColor;
        }
        $data['svgImage'] = $this->getSVGImage($unsubscribeColor, $hoverColor);

        acym_setVar('layout', 'unsubscribepage');
        acym_header('Content-Type: text/html; charset=utf-8');

        parent::display($data);
    }

    private function getSVGImage(string $unsubscribeColor, string $hoverColor): string
    {
        if ($this->config->get('display_unsub_image') === '1') {
            $svgPath = ACYM_IMAGES.'unsubscribe/unsub_image.svg';

            $svgContent = acym_fileGetContent($svgPath);

            return str_replace(
                ['#B118C8', '#C794CF'],
                [$unsubscribeColor, $hoverColor],
                $svgContent
            );
        }

        return '';
    }

    public function hexToRGB(string $hexColor): array
    {
        $hexColor = ltrim($hexColor, '#');

        if (strlen($hexColor) === 3) {
            $hexColor = $hexColor[0].$hexColor[0].$hexColor[1].$hexColor[1].$hexColor[2].$hexColor[2];
        }

        $r = hexdec(substr($hexColor, 0, 2));
        $g = hexdec(substr($hexColor, 2, 2));
        $b = hexdec(substr($hexColor, 4, 2));

        return [$r, $g, $b];
    }

    public function darkenRGBColor(string $hexColor, int $percent): string
    {
        $rgbValues = $this->hexToRGB($hexColor);

        $darkenedRGB = array_map(function ($value) use ($percent) {
            return max(0, $value - ($value * $percent / 100));
        }, $rgbValues);

        return sprintf('rgb(%d, %d, %d)', $darkenedRGB[0], $darkenedRGB[1], $darkenedRGB[2]);
    }

    public function confirm(): void
    {
        if (acym_isRobot()) {
            return;
        }

        // We identify the user
        $userClass = new UserClass();
        $user = $userClass->identify(true, 'userId', 'userKey');
        if (empty($user)) {
            acym_enqueueMessage(acym_translation('ACYM_USER_NOT_FOUND'), 'error');
            acym_redirect(acym_rootURI());

            return;
        }

        if ($this->config->get('confirmation_message', 1)) {
            if ($user->confirmed) {
                acym_enqueueMessage(acym_translation('ACYM_ALREADY_CONFIRMED'), 'info');
            } else {
                acym_enqueueMessage(acym_translation('ACYM_SUBSCRIPTION_CONFIRMED'), 'success');
            }
        }

        // Now we can really confirm the user
        if (!$user->confirmed) {
            $userClass->confirm($user->id);
        }

        // Get the redirect url from the configuration page if specified
        $redirectUrl = $this->config->get('confirm_redirect');
        if (!empty($redirectUrl)) {
            $replace = [];
            // We replace tags in case of the user added some dynamic information to its url.
            foreach ($user as $key => $val) {
                $replace['{user:'.$key.'}'] = $val;
            }
            $redirectUrl = str_replace(array_keys($replace), $replace, $redirectUrl);
            acym_redirect($redirectUrl);

            return;
        }

        acym_redirect(acym_rootURI());
    }

    public function profile(): void
    {
        $userClass = new UserClass();
        $user = $userClass->identify(true);

        if (empty($user)) {
            // Check if the subscription is allowed for non registered users
            $allowvisitor = $this->config->get('allow_visitor', 1);
            if (empty($allowvisitor)) {
                acym_askLog(true, 'ACYM_ONLY_LOGGED', 'message');

                return;
            }
        }

        global $acymEmailMisspelledLoaded;
        $spellChecker = empty($acymEmailMisspelledLoaded) && !empty($this->config->get('email_spellcheck'));
        if ($spellChecker) $acymEmailMisspelledLoaded = true;

        if ($spellChecker) {
            acym_addStyle(false, ACYM_CSS.'libraries/email-misspelled.min.css?v='.filemtime(ACYM_MEDIA.'css'.DS.'libraries'.DS.'email-misspelled.min.css'));
            acym_addScript(false, ACYM_JS.'libraries/email-misspelled.min.js?v='.filemtime(ACYM_MEDIA.'js'.DS.'libraries'.DS.'email-misspelled.min.js'));
        }
        acym_addScript(false, ACYM_JS.'module.min.js?v='.filemtime(ACYM_MEDIA.'js'.DS.'module.min.js'));

        // Get the page parameters
        $params = new \stdClass();
        $menu = acym_getMenu();
        if (is_object($menu)) {
            $params->source = 'Menu n°'.$menu->id;
            $menuParameters = method_exists($menu, 'getParams') ? $menu->getParams() : $menu->params;
            $menuparams = new AcymParameter($menuParameters);

            if (!empty($menuparams)) {
                // Joomla specific params
                $params->suffix = $menuparams->get('pageclass_sfx', '');
                $params->page_heading = $menuparams->get('page_heading');
                $params->show_page_heading = $menuparams->get('show_page_heading', 0);

                if ($menuparams->get('menu-meta_description')) {
                    acym_addMetadata('description', $menuparams->get('menu-meta_description'));
                }
                if ($menuparams->get('menu-meta_keywords')) {
                    acym_addMetadata('keywords', $menuparams->get('menu-meta_keywords'));
                }
                if ($menuparams->get('robots')) {
                    acym_addMetadata('robots', $menuparams->get('robots'));
                }

                // Our params
                $params->lists = $menuparams->get('lists', 'none');
                $params->listschecked = $menuparams->get('listschecked', 'none');
                $params->dropdown = $menuparams->get('dropdown');
                $params->hiddenlists = $menuparams->get('hiddenlists', 'None');
                $params->fields = $menuparams->get('fields');
                $params->introtext = $menuparams->get('introtext');
                $params->posttext = $menuparams->get('posttext');
                $params->page_title = $menuparams->get('page_title', '');
            }
        }
        $data = $this->prepareParams($params);

        if (empty($data['user']->language)) {
            $cmsUserLanguage = acym_getCmsUserLanguage();
            $data['user']->language = empty($cmsUserLanguage) ? acym_getLanguageTag() : $cmsUserLanguage;
        }

        $data['captchaHelper'] = new CaptchaHelper();

        acym_setVar('layout', 'profile');
        parent::display($data);
    }

    public function prepareParams(object $values): array
    {
        //TODO problem with the types throughout the entire method
        if (!isset($values->lists)) {
            $values->lists = 'none';
        }
        if (!isset($values->listschecked)) {
            $values->listschecked = 'none';
        }
        if (!isset($values->dropdown)) {
            $values->dropdown = 0;
        }
        if (!isset($values->hiddenlists)) {
            $values->hiddenlists = 'None';
        }
        if (empty($values->fields)) {
            $values->fields = ['1', '2'];
        }
        if (!in_array('2', $values->fields) && !in_array(2, $values->fields)) {
            $values->fields[] = '2';
        }
        if (!isset($values->introtext)) {
            $values->introtext = '';
        }
        if (!isset($values->posttext)) {
            $values->posttext = '';
        }

        foreach (['lists', 'listschecked', 'hiddenlists', 'fields'] as $option) {
            if (is_string($values->$option)) {
                $values->$option = explode(',', $values->$option);
            }
        }

        if ((empty($values->lists) || in_array('None', $values->lists)) && (empty($values->hiddenlists) || strtolower(implode('', $values->hiddenlists)) == 'none')) {
            $values->lists[] = 'All';
        }

        // Get the current user and his subscription
        $userClass = new UserClass();
        $user = $userClass->identify(true);
        if (empty($user)) {
            $listClass = new ListClass();
            $subscription = $listClass->getAll('id');

            if (acym_isMultilingual()) {
                $subscription = $listClass->getTranslatedNameDescription($subscription);
            }

            $user = new \stdClass();
            $user->id = 0;
            $user->key = 0;

            if (!empty($subscription)) {
                foreach ($subscription as $id => $onesub) {
                    $subscription[$id]->status = 1;
                    if (strtolower(implode('', $values->listschecked)) != 'all' && !in_array($id, $values->listschecked)) {
                        $subscription[$id]->status = -1;
                    }
                }
            }

            acym_addBreadcrumb(acym_translation('ACYM_SUBSCRIPTION'));
            if (empty($menu)) {
                acym_setPageTitle(acym_translation('ACYM_SUBSCRIPTION'));
            }
        } else {
            $subscription = $userClass->getAllListsUserSubscriptionById($user->id, 'id', true);

            acym_addBreadcrumb(acym_translation('ACYM_MODIFY_SUBSCRIPTION'));
            if (empty($menu)) {
                acym_setPageTitle(acym_translation('ACYM_MODIFY_SUBSCRIPTION'));
            }
        }

        if (!empty($values->page_title)) {
            acym_addBreadcrumb($values->page_title);
            if (empty($menu)) {
                acym_setPageTitle($values->page_title);
            }
        }

        $allLists = $subscription;

        acym_initModule();

        if (!empty($values->lists) && strtolower(implode('', $values->lists)) != 'all') {
            if (in_array('None', $values->lists)) {
                $subscription = [];
            } else {
                $newSubscription = [];
                foreach ($subscription as $id => $onesub) {
                    if (in_array($id, $values->lists)) {
                        $newSubscription[$id] = $onesub;
                    }
                }
                $subscription = $newSubscription;
            }
        }

        if (!empty($values->hiddenlists)) {
            $hiddenListsArray = [];
            if (strtolower(implode('', $values->hiddenlists)) == 'all') {
                $subscription = [];
                foreach ($allLists as $oneList) {
                    if (!empty($oneList->active)) {
                        $hiddenListsArray[] = $oneList->id;
                    }
                }
            } elseif (strtolower(implode('', $values->hiddenlists)) != 'none') {
                foreach ($allLists as $oneList) {
                    if (!$oneList->active || !in_array($oneList->id, $values->hiddenlists)) {
                        continue;
                    }
                    $hiddenListsArray[] = $oneList->id;
                    unset($subscription[$oneList->id]);
                }
            }
            $values->hiddenlists = $hiddenListsArray;
        }

        // Overriding the list with the URL parameters: &listid= for displayed list, &hiddenlist= for hidden lists
        $defaultSubscription = $subscription;
        $forceLists = acym_getVar('string', 'listid', '');
        if (!empty($forceLists)) {
            $subscription = [];
            $forceLists = explode(',', $forceLists);
            foreach ($forceLists as $oneList) {
                if (!empty($defaultSubscription[$oneList])) {
                    $subscription[$oneList] = $defaultSubscription[$oneList];
                }
            }
        }
        $forceHiddenLists = acym_getVar('string', 'hiddenlist', '');
        if (!empty($forceHiddenLists)) {
            $forceHiddenLists = explode(',', $forceHiddenLists);
            $tmpList = [];
            foreach ($forceHiddenLists as $oneList) {
                if (!empty($defaultSubscription[$oneList]) || in_array($oneList, $values->hiddenlists)) {
                    $tmpList[] = $oneList;
                }
            }
            $values->hiddenlists = $tmpList;
        }

        $displayLists = false;
        foreach ($subscription as $oneSub) {
            if (!empty($oneSub->active) && $oneSub->visible) {
                $displayLists = true;
                break;
            }
        }

        $fieldClass = new FieldClass();
        $allfields = $fieldClass->getFieldsByID($values->fields);
        $fields = [];
        foreach ($allfields as $field) {
            if (intval($field->active) === 0) continue;
            $fields[$field->id] = $field;
        }
        $values->fields = $fields;

        $data = [
            'config' => $this->config,
            'displayLists' => $displayLists,
            'user' => $user,
            'subscription' => $subscription,
            'fieldClass' => $fieldClass,
        ];

        foreach ($values as $key => $value) {
            $data[$key] = $value;
        }

        return $data;
    }

    public function savechanges(): void
    {
        acym_checkToken();
        acym_checkRobots();

        $userClass = new UserClass();


        $status = $userClass->saveForm(true);
        if ($status) {
            if ($userClass->confirmationSentSuccess) {
                $this->displayMessage('ACYM_CONFIRMATION_SENT', true, 'success');
            } elseif ($userClass->newUser) {
                $this->displayMessage('ACYM_SUBSCRIPTION_OK', true, 'success');
            } else {
                $this->displayMessage('ACYM_SUBSCRIPTION_UPDATED_OK', true, 'success');
            }
        } elseif (!empty($userClass->requireId)) {
            $this->displayMessage('ACYM_IDENTIFICATION_SENT', true, 'success');
        } elseif (!empty($userClass->errors)) {
            $this->displayMessage(implode('<br/>', $userClass->errors), true);
        } else {
            $this->displayMessage('ACYM_ERROR_SAVING', true);
        }

        exit;
    }

    public function exportdata(): void
    {
        acym_checkToken();

        $userClass = new UserClass();
        $user = $userClass->identify(true, 'userId', 'userKey');

        if (empty($user->id)) {
            acym_redirect(acym_rootURI());
        }

        $userHelper = new UserHelper();
        $userData = $userHelper->getUserData($user);
        $exportData = [];
        foreach ($userData as $oneCategory) {
            if (!isset($exportData[$oneCategory['group_label']])) {
                $exportData[$oneCategory['group_label']] = [];
            }

            $exportData[$oneCategory['group_label']][] = $oneCategory['data'];
        }

        $exportFiles = [
            [
                'name' => 'data.json',
                'data' => json_encode($exportData),
            ],
        ];

        $tempFolder = ACYM_MEDIA.'tmp'.DS;
        acym_createArchive($tempFolder.'export_data_user_'.$user->id, $exportFiles);

        $exportHelper = new ExportHelper();
        $exportHelper->setDownloadHeaders('export_data_user_'.$user->id, 'zip');
        readfile($tempFolder.'export_data_user_'.$user->id.'.zip');

        // Avoid issue when user cancels the download
        ignore_user_abort(true);
        unlink($tempFolder.'export_data_user_'.$user->id.'.zip');
        exit;
    }

    public function gdprDelete(): void
    {
        acym_checkToken();

        $userClass = new UserClass();
        $user = $userClass->identify(true, 'userId', 'userKey');
        if (empty($user->id)) {
            acym_redirect(acym_rootURI());
        }

        $userClass->delete([$user->id], true);
    }

    public function ajaxGetEnqueuedMessages(): void
    {
        acym_session();

        $output = '';
        $types = ['success', 'info', 'warning', 'error'];
        foreach ($types as $type) {
            if (empty($_SESSION['acymessage'.$type])) continue;

            $messages = $_SESSION['acymessage'.$type];
            if (!is_array($messages)) {
                $messages = [$messages];
            }

            $output .= '<div class="acym_callout acym__callout__front__'.$type.'" role="alert">';
            $output .= '<div>'.implode(' ', $messages).'</div>';
            $output .= '<button class="acym_callout_close" aria-label="'.acym_escape(acym_translation('ACYM_CLOSE_NOTIFICATION')).'">x</button></div>';

            unset($_SESSION['acymessage'.$type]);
        }

        if (!empty($output)) {
            $output = '<div id="acym__callout__container">'.$output.'</div>';
        }
        acym_sendAjaxResponse('', ['messages' => $output]);
    }
}
