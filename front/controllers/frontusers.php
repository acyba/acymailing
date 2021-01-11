<?php

namespace AcyMailing\FrontControllers;

use AcyMailing\Classes\FieldClass;
use AcyMailing\Classes\ListClass;
use AcyMailing\Classes\MailClass;
use AcyMailing\Classes\UserClass;
use AcyMailing\Controllers\UsersController;
use AcyMailing\Helpers\CaptchaHelper;
use AcyMailing\Helpers\EntitySelectHelper;
use AcyMailing\Helpers\ToolbarHelper;
use AcyMailing\Helpers\UserHelper;
use AcyMailing\Libraries\acymParameter;

class FrontusersController extends UsersController
{
    public function __construct()
    {
        $this->authorizedFrontTasks = [
            'subscribe',
            'unsubscribe',
            'unsubscribeAll',
            'saveSubscriptions',
            'unsubscribePage',
            'confirm',
            'profile',
            'prepareParams',
            'savechanges',
            'exportdata',
        ];
        $this->urlFrontMenu = 'index.php?option=com_acym&view=frontusers&layout=listing';
        parent::__construct();
    }

    protected function prepareUsersFields(&$data)
    {
        $data['fields'] = [];

        if (empty($data['allUsers'])) return;

        $fieldClass = new FieldClass();
        $fieldsToDisplay = $fieldClass->getAllFieldsFrontendListing();
        if (empty($fieldsToDisplay['ids'])) return;

        $userIds = [];
        foreach ($data['allUsers'] as $user) {
            $userIds[] = $user->id;
        }

        $fieldValue = $fieldClass->getAllFieldsListingByUserIds($userIds, $fieldsToDisplay['ids'], 'field.frontend_listing = 1');
        foreach ($data['allUsers'] as &$user) {
            $user->fields = [];
            foreach ($fieldsToDisplay['ids'] as $fieldId) {
                $user->fields[$fieldId] = empty($fieldValue[$fieldId.'-'.$user->id]) ? '' : $fieldValue[$fieldId.'-'.$user->id];
            }
        }

        $data['fields'] = $fieldsToDisplay['names'];
    }

    protected function prepareUsersSubscriptions(&$data)
    {
        $usersId = [];
        foreach ($data['allUsers'] as $oneUser) {
            $usersId[] = $oneUser->id;
        }

        $subscriptions = [];

        if (!empty($usersId)) {
            $subscriptionsArray = $this->currentClass->getUsersSubscriptionsByIds($usersId, acym_currentUserId());

            foreach ($subscriptionsArray as $oneSubscription) {
                $subscriptions[$oneSubscription->user_id][$oneSubscription->id] = $oneSubscription;
            }
        }

        $data['usersSubscriptions'] = $subscriptions;
    }

    protected function prepareFieldsEdit(&$data, $fieldVisibility = 'frontend_edition')
    {
        if (!acym_level(2) && acym_isAdmin()) {
            acym_redirect(acym_rootURI(), 'ACYM_ONLY_AVAILABLE_ENTERPRISE_VERSION', 'warning');
        }

        parent::prepareFieldsEdit($data, $fieldVisibility);
    }

    protected function prepareUsersListing(&$data)
    {
        if (!acym_level(2)) {
            acym_redirect(acym_rootURI(), 'ACYM_ONLY_AVAILABLE_ENTERPRISE_VERSION', 'warning');
        }

        // Prepare the pagination
        $usersPerPage = $data['pagination']->getListLimit();
        $page = acym_getVar('int', 'users_pagination_page', 1);
        $currentUserId = acym_currentUserId();

        if (empty($currentUserId)) return;

        $matchingUsers = $this->getMatchingElementsFromData(
            [
                'search' => $data['search'],
                'elementsPerPage' => $usersPerPage,
                'offset' => ($page - 1) * $usersPerPage,
                'status' => $data['status'],
                'ordering' => $data['ordering'],
                'ordering_sort_order' => $data['orderingSortOrder'],
                'creator_id' => $currentUserId,
            ],
            $data['status'],
            $page
        );

        // Prepare the pagination
        $data['pagination']->setStatus($matchingUsers['total'], $page, $usersPerPage);

        $data['allUsers'] = $matchingUsers['elements'];
        $data['userNumberPerStatus'] = $matchingUsers['status'];
    }

    protected function prepareToolbar(&$data)
    {
        $toolbarHelper = new ToolbarHelper();
        $toolbarHelper->addSearchBar($data['search'], 'users_search', 'ACYM_SEARCH');
        $toolbarHelper->addButton(acym_translation('ACYM_IMPORT'), ['data-task' => 'import'], 'download');
        $entityHelper = new EntitySelectHelper();
        $otherContent = acym_modal(
            '<i class="acymicon-bell1"></i>'.acym_translation('ACYM_SUBSCRIBE').' (<span id="acym__users__listing__number_to_add_to_list">0</span>)',
            $entityHelper->entitySelect(
                'list',
                ['join' => ''],
                $entityHelper->getColumnsForList(),
                [
                    'text' => acym_translation('ACYM_SUBSCRIBE_USERS_TO_THESE_LISTS'),
                    'action' => 'addToList',
                ]
            ),
            null,
            '',
            'class="button button-secondary disabled cell medium-6 large-shrink" id="acym__users__listing__button--add-to-list"'
        );
        $toolbarHelper->addOtherContent($otherContent);
        $toolbarHelper->addButton(acym_translation('ACYM_CREATE'), ['data-task' => 'edit'], 'user-plus', true);

        $data['toolbar'] = $toolbarHelper;
    }

    private function displayMessage($message, $ajax, $type = 'error')
    {
        if ($ajax) {
            echo '{"message":"'.acym_translation($message, true).'","type":"'.$type.'","code":"1"}';
        } else {
            acym_header('Content-type:text/html; charset=utf-8');
            echo "<script>alert(\"".acym_translation($message, true)."\"); window.history.go(-1);</script>";
        }
        exit;
    }

    public function subscribe()
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
            acym_header('Content-type:text/html; charset=utf-8');
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

        if (empty($currentUserid) && $this->config->get('captcha', '') == 1) {
            $captchaHelper = new CaptchaHelper();
            if (!$captchaHelper->check()) {
                $this->displayMessage('ACYM_WRONG_CAPTCHA', $ajax);
            }
        }

        $formData = acym_getVar('array', 'user', [], '');
        $user = new \stdClass();
        if (!empty($formData['email'])) $user->email = $formData['email'];

        $userClass = new UserClass();
        if (empty($user->email)) {
            $connectedUser = $userClass->identify(true);
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

        $isNew = empty($user->id);
        $result = $userClass->saveForm($ajax);
        $user->id = acym_getVar('int', 'id');
        if (!empty($userClass->errors)) $this->displayMessage(implode('<br /><br />', $userClass->errors), $ajax);
        if ($result === false || empty($user->id)) {
            $this->displayMessage('ACYM_ERROR_SAVE_USER', $ajax);
        }

        //We will now load back the user from the one we saved so that we will have all its infos
        $myuser = $userClass->getOneById($user->id);
        if (empty($myuser->id)) {
            $this->displayMessage('ACYM_ERROR_SAVE_USER', $ajax);
        }

        $msgtype = 'success';
        if (empty($myuser->confirmed) && $this->config->get('require_confirmation', 1) == 1) {
            if ($userClass->confirmationSentSuccess) {
                $msg = 'ACYM_CONFIRMATION_SENT';
                $code = 2;
            } else {
                $msg = $userClass->confirmationSentError;
                $code = 7;
                $msgtype = 'error';
            }
        } else {
            if ($userClass->subscribed) {
                $msg = acym_getVar('string', 'confirmation_message');
                if (empty($msg)) $msg = 'ACYM_SUBSCRIPTION_OK';
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

        acym_redirect($redirectUrl);
    }

    private function unsubscribeDirectly($alreadyExists, $ajax)
    {
        $userClass = new UserClass();
        $visibleSubscription = acym_getVar('array', 'subscription', []);
        $hiddenLists = trim(acym_getVar('string', 'hiddenlists', ''));
        $hiddenSubscription = empty($hiddenLists) ? [] : explode(',', $hiddenLists);
        $unsubscribeLists = array_merge($visibleSubscription, $hiddenSubscription);

        $mailId = acym_getVar('int', 'mail_id', 0);
        if (!empty($mailId)) {
            $mailClass = new MailClass();
            $unsubscribeLists = array_keys($mailClass->getAllListsByMailId($mailId));
        }

        if (empty($unsubscribeLists)) {
            $msg = 'ACYM_NO_SUBSCRIPTION_LINKED_EMAIL';
        } elseif (false === $userClass->unsubscribe($alreadyExists->id, $unsubscribeLists)) {
            $msg = 'ACYM_UNSUBSCRIPTION_NOT_IN_LIST';
        } else {
            $msg = 'ACYM_UNSUBSCRIPTION_OK';
        }

        $msg = acym_translation($msg);

        if ($ajax) {
            echo '{"message":"'.str_replace('"', '\"', $msg).'","type":"success","code":"10"}';
            exit;
        }
        acym_enqueueMessage($msg, 'success');

        $redirectUrl = urldecode(acym_getVar('string', 'redirectunsub', ''));
        if (empty($redirectUrl)) {
            $redirectUrl = acym_rootURI();
        }

        acym_redirect($redirectUrl);
    }

    public function unsubscribe()
    {
        acym_checkRobots();
        $userClass = new UserClass();

        $redirectToUnsubPage = $this->config->get('unsubscribe_page', 1);

        // Do we have to return an ajax response or a web page ?
        $ajax = acym_getVar('int', 'ajax', 0);
        if ($ajax) {
            @ob_end_clean();
            acym_header('Content-type:text/html; charset=utf-8');
        }

        $currentUserid = acym_currentUserId();
        $user = $userClass->identify();
        if (empty($user) && empty($currentUserid) && $this->config->get('captcha', '') == 1) {
            $captchaClass = new CaptchaHelper();
            if (!$captchaClass->check()) {
                $this->displayMessage('ACYM_WRONG_CAPTCHA', $ajax);
            }
        }

        $formData = acym_getVar('array', 'user', []);

        if (empty($formData['email'])) {
            if (empty($user)) {
                return false;
            }
            if (!empty($user->email)) {
                $email = $user->email;
            }
        } else {
            $email = trim(strip_tags($formData['email']));
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
            $this->displayMessage('ACYM_NOT_IN_LIST', $ajax);
        }

        $fromModuleOrWidget = acym_getVar('string', 'acysubmode', '');
        if (!empty($fromModuleOrWidget) && in_array($fromModuleOrWidget, ['form_acym', 'mod_acym', 'widget_acym'])) {
            $this->unsubscribeDirectly($alreadyExists, $ajax);
        } elseif ($redirectToUnsubPage && !$ajax) {
            $this->unsubscribePage($alreadyExists);
        } else {
            $this->unsubscribeDirectly($alreadyExists, $ajax);
        }
    }

    private function getUserFromUnsubPage()
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

    private function unsubscribeAllInner($update = false)
    {
        $userClass = new UserClass();
        $user = $this->getUserFromUnsubPage();

        $allLists = $userClass->getUserSubscriptionById($user->id);
        if (empty($allLists)) {
            acym_enqueueMessage(acym_translation('ACYM_AN_ISSUE_OCCURED_WHILE_SAVING'), 'error');
            acym_redirect(acym_rootURI());
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
        $userClass->unsubscribe($user->id, $lists);
    }

    private function redirectUnsubWorked()
    {
        acym_enqueueMessage(acym_translation('ACYM_SUBSCRIPTION_UPDATED_OK'), 'success');
        acym_redirect(acym_rootURI());
    }

    public function unsubscribeAll()
    {
        $this->unsubscribeAllInner();
        $this->redirectUnsubWorked();
    }

    public function saveSubscriptions()
    {
        $userClass = new UserClass();

        //get the user
        $user = $this->getUserFromUnsubPage();

        //get the list the user want to sub
        $listsChecked = acym_getVar('array', 'lists');
        //if he uncheck all lists we directly unsubscribe to all lists
        if (empty($listsChecked)) {
            $this->unsubscribeAllInner(true);
            $this->redirectUnsubWorked();
        }
        $listsChecked = array_keys($listsChecked);

        //we get the user subscriptions
        $userSubscriptions = $userClass->getUserSubscriptionById($user->id);

        //we subscribe the user to the checked lists
        $userClass->subscribe($user->id, $listsChecked);

        //we unsub to the unchecked lists
        $listsToUnsub = [];
        foreach ($userSubscriptions as $subscription) {
            // The list wasn't displayed || the user checked the list || the user isn't subscribed
            if ($subscription->visible === '0' || in_array($subscription->id, $listsChecked) || $subscription->status !== '1') continue;

            $listsToUnsub[] = $subscription->id;
        }

        $userClass->unsubscribe($user->id, $listsToUnsub);
        $this->redirectUnsubWorked();
    }

    public function unsubscribePage($alreadyExists)
    {
        $userClass = new UserClass();
        $userSubscriptions = $userClass->getUserSubscriptionById($alreadyExists->id);

        $data = [
            'user' => $alreadyExists,
            'mail_id' => acym_getVar('int', 'mail_id', 0),
            'subscriptions' => $userSubscriptions,
        ];

        acym_setVar('layout', 'unsubscribepage');
        parent::display($data);
    }

    public function confirm()
    {
        if (acym_isRobot()) return false;

        // We identify the user
        $userClass = new UserClass();
        $user = $userClass->identify();
        if (empty($user)) {
            acym_enqueueMessage(acym_translation('ACYM_USER_NOT_FOUND'), 'error');
            acym_redirect(acym_rootURI());

            return false;
        }

        if ($this->config->get('confirmation_message', 1)) {
            if ($user->confirmed) {
                acym_enqueueMessage(acym_translation('ACYM_ALREADY_CONFIRMED'), 'info');
            } else {
                acym_enqueueMessage(acym_translation('ACYM_SUBSCRIPTION_CONFIRMED'), 'success');
            }
        }

        //Now we can really confirm the user
        if (!$user->confirmed) {
            $userClass->confirm($user->id);
        }

        // Get the redirect url from the configuration page if specified
        $redirectUrl = $this->config->get('confirm_redirect');
        if (!empty($redirectUrl)) {
            $replace = [];
            //We replace tags in case of the user added some dynamic information to its url.
            foreach ($user as $key => $val) {
                $replace['{user:'.$key.'}'] = $val;
            }
            $redirectUrl = str_replace(array_keys($replace), $replace, $redirectUrl);
            acym_redirect($redirectUrl);

            return;
        }

        acym_redirect(acym_rootURI());
    }

    public function profile()
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

        acym_addScript(false, ACYM_JS.'module.min.js?v='.filemtime(ACYM_MEDIA.'js'.DS.'module.min.js'));

        // Get the page parameters
        $params = new \stdClass();
        $menu = acym_getMenu();
        if (is_object($menu)) {
            $params->source = 'Menu n°'.$menu->id;
            $menuParameters = method_exists($menu, 'getParams') ? $menu->getParams() : $menu->params;
            $menuparams = new acymParameter($menuParameters);

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

        $this->prepareLanguageEdit($data);

        acym_setVar('layout', 'profile');
        parent::display($data);
    }

    public function prepareParams($values)
    {
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
            $subscription = $userClass->getAllListsUserSubscriptionById($user->id);

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
            if ($field->active === '0') continue;
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

    public function savechanges()
    {
        acym_checkToken();
        acym_checkRobots();

        $userClass = new UserClass();
        $userClass->extendedEmailVerif = true;


        $status = $userClass->saveForm();
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

    public function exportdata()
    {
        acym_checkToken();

        $userClass = new UserClass();
        $user = $userClass->identify(true);

        if (empty($user->id)) {
            acym_redirect(acym_rootURI());
        }

        $userHelper = new UserHelper();
        $userHelper->exportdata($user->id);
    }

    public function apply($listing = false)
    {
        $listsToSave = json_decode(acym_getVar('string', 'acym__entity_select__selected'));
        if (empty($listsToSave)) {
            $listClass = new ListClass();
            $listManagementId = $listClass->getfrontManagementList();
            if (empty($listManagementId)) {
                acym_redirect(acym_rootURI(), 'ACYM_UNABLE_TO_CREATE_MANAGEMENT_LIST', 'error');
            }
            $listsToAdd = json_encode([$listManagementId]);
            acym_setVar('acym__entity_select__selected', $listsToAdd);
        }
        parent::apply($listing);
    }

    public function save()
    {
        $this->apply(true);
    }
}
