<?php
/**
 * @copyright      Copyright (C) 2009-2022 ACYBA SAS - All rights reserved.
 * @license        GNU/GPLv3 https://www.gnu.org/licenses/gpl-3.0.html
 */

use AcyMailing\Classes\ListClass;
use AcyMailing\Classes\UserClass;

defined('_JEXEC') or die('Restricted access');

if (!defined('DS')) define('DS', DIRECTORY_SEPARATOR);

if (!@include_once rtrim(JPATH_ADMINISTRATOR, DS).DS.'components'.DS.'com_acym'.DS.'Core'.DS.'init.php') return;

global $_PLUGINS;
if (!class_exists('cbTabHandler') || !method_exists($_PLUGINS, 'registerFunction') || class_exists('getAcymTab')) return;

$_PLUGINS->registerFunction('onUserActive', 'userActivated', 'getAcymTab');
$_PLUGINS->registerFunction('onAfterDeleteUser', 'userDelete', 'getAcymTab');
$_PLUGINS->registerFunction('onBeforeUserBlocking', 'onBeforeUserBlocking', 'getAcymTab');

class getAcymTab extends cbTabHandler
{
    private bool $installed;
    private string $errorMessage = 'This plugin can not work without the AcyMailing extension.<br/>Please download it from <a href="https://www.acymailing.com">https://www.acymailing.com</a> and install it first.';

    public function __construct()
    {
        $this->installed = file_exists(rtrim(JPATH_ADMINISTRATOR, DS).DS.'components'.DS.'com_acym');
        parent::__construct();
    }

    public function getDisplayRegistration($tab, $cbUser, $ui, $postdata)
    {
        $return = [];

        $listsClass = new ListClass();
        $allLists = $listsClass->getAllWithoutManagement();

        $visibleLists = $this->getParam('lists', '');
        $allvisiblelists = explode(',', $visibleLists);

        $visibleListsArray = [];
        foreach ($allLists as $oneList) {
            if (1 === intval($oneList->active) && 1 === intval($oneList->visible) && in_array($oneList->id, $allvisiblelists)) {
                $visibleListsArray[] = $oneList->id;
            }
        }

        if (empty($visibleListsArray)) {
            return $return;
        }

        // Checked lists by default
        $checkedLists = $this->getParam('listschecked', '');
        $checkedListsArray = explode(',', $checkedLists);

        $this->addStyle();

        $label = $this->getParam('subcaption', '');
        if (empty($label)) $label = 'ACYM_SUBSCRIPTION';

        $listsHtml = '<table class="acym_cb_registration">';
        foreach ($visibleListsArray as $listId) {
            $check = in_array($listId, $checkedListsArray) ? 'checked="checked"' : '';
            $listsHtml .= '<tr>
                                <td>
                                    <input type="checkbox" class="acym_checkbox" id="acym_'.$listId.'" name="acymcb[list][]" '.$check.' value="'.intval($listId).'"/>
                                </td>
                                <td>
                                    <label for="acym_'.$listId.'">'.$allLists[$listId]->name.'</label>
                                </td>
                            </tr>';
        }
        $listsHtml .= '</table><input type="hidden" name="visibleLists" value="'.implode(',', $visibleListsArray).'" />';
        $return[] = cbTabs::_createPseudoField($tab, acym_translation($label), $listsHtml, '', 'acymailingdataLists', false);

        return $return;
    }

    public function getDisplayTab($tab, $cbUser, $ui)
    {
        // Compatibility with CB profile pro
        if (file_exists(JPATH_SITE.DS.'components'.DS.'com_cbprofilepro')) {
            if (!empty($_REQUEST['task']) && $_REQUEST['task'] == 'userdetails') {
                return $this->getEditTab($tab, $cbUser, $ui);
            }
            if (empty($cbUser->user_id) && empty($cbUser->id)) {
                return $this->getDisplayRegistration($tab, $cbUser, $ui, '');
            }
        }

        $currentUserid = acym_currentUserId();
        if (empty($currentUserid) || $currentUserid != $cbUser->user_id) return '';

        $userClass = new UserClass();
        $acyUser = $userClass->getOneByEmail($cbUser->email);

        if (empty($acyUser->id)) return '';

        $config = acym_config();

        // Display a confirmation link if needed
        $requireConfirmation = $config->get('require_confirmation');
        if (0 === intval($acyUser->confirmed) && 1 === intval($requireConfirmation)) {
            $myLink = acym_frontendLink('frontusers&task=confirm&id='.$acyUser->id.'&key='.urlencode($acyUser->key));
            acym_display('<a target="_blank" href="'.$myLink.'">'.acym_translation('ACYM_CONFIRM_SUBSCRIPTION').'</a>', 'warning');
        }

        $lists = $this->getParam('listsprofile', '');

        return $this->displayLists('profile', $acyUser, $lists);
    }

    private function getCurrentUser($cbUser)
    {
        $userClass = new UserClass();
        $acyUser = $userClass->getOneByEmail($cbUser->email);

        if (!empty($cbUser->id)) {
            $currentUser = $userClass->getOneByCMSId($cbUser->id);
        }

        if (!empty($currentUser)) {
            // The user may have changed its email address from CB, delete the previous AcyMailing user if we found two. It's like a merge of two Acy users
            if (!empty($acyUser->id) && $acyUser->id != $currentUser->id) {
                $userClass->delete($acyUser->id);
            }
            $acyUser = $currentUser;
        }

        return $acyUser;
    }

    public function saveRegistrationTab($tab, &$cbUser, $ui, $postdata)
    {
        if (!$this->installed || (empty($cbUser->id) && empty($cbUser->user_id))) return;

        $acyUser = $this->getCurrentUser($cbUser);

        if (empty($acyUser)) {
            $acyUser = new stdClass();
        }

        $acyUser->email = $cbUser->email;
        if (!empty($cbUser->name)) {
            $acyUser->name = $cbUser->name;
        }

        if (!empty($cbUser->user_id)) {
            $acyUser->cms_id = $cbUser->user_id;
        } elseif (!empty($cbUser->id)) {
            $acyUser->cms_id = $cbUser->id;
        }

        if (isset($cbUser->confirmed)) {
            $acyUser->confirmed = intval($cbUser->confirmed);
        }

        if (!empty($cbUser->block)) {
            $acyUser->active = 0;
        }

        $enabled = $this->getParam('enabled', 0);
        if (intval($enabled) === 1) {
            $acyUser->active = 1;
        }

        $userClass = new UserClass();
        $userClass->checkVisitor = false;
        $userClass->sendConf = false;
        acym_setVar('acy_source', 'community_builder');
        $acyUser->id = $userClass->save($acyUser);

        // Subscription...
        $currentSubscription = $userClass->getSubscriptionStatus($acyUser->id);

        $listClass = new ListClass();
        $allLists = $listClass->getAllWithoutManagement(true);

        $addlists = [];

        if (!empty($postdata['acymcb']['list'])) {
            foreach ($postdata['acymcb']['list'] as $listId) {
                if (!empty($allLists[$listId]->active) && empty($currentSubscription[$listId]->status)) $addlists[] = $listId;
            }
        }

        $userClass->subscribe($acyUser->id, $addlists);

        // We should remove the user subscription if he unchecked some lists and already exists
        $updateRegister = $this->getParam('updateonregister', 0);
        if (0 === intval($updateRegister) || empty($currentSubscription)) return;

        $allvisiblelists = acym_getVar('string', 'visibleLists');
        $allvisiblelistsArray = explode(',', $allvisiblelists);
        acym_arrayToInteger($allvisiblelistsArray);

        $checkedByUser = empty($postdata['acymcb']['list']) ? [] : $postdata['acymcb']['list'];

        $unsubLists = [];
        foreach ($allvisiblelistsArray as $listId) {
            //The user is not subscribed to the list
            if (empty($currentSubscription[$listId]->status)) continue;
            if (empty($allLists[$listId]->active) || empty($allLists[$listId]->visible)) continue;

            if (!in_array($listId, $checkedByUser)) {
                $unsubLists[] = $listId;
            }
        }

        $userClass->unsubscribe($acyUser->id, $unsubLists);
    }

    public function userDelete($cbUser, $success)
    {
        if (!$this->installed) return $this->errorMessage;
        if (!$success) return '';

        $userClass = new UserClass();
        $user = $userClass->getOneByEmail($cbUser->email);
        if (!empty($user->id)) {
            $userClass->delete($user->id);
        }

        return '';
    }

    public function userActivated($cbUser, $success)
    {
        if (!$this->installed) return $this->errorMessage;
        if (!$success) return '';

        $userClass = new UserClass();
        $user = $userClass->getOneByEmail($cbUser->email);
        if (!empty($user->id)) {
            if (empty($cbUser->block)) {
                acym_query('UPDATE `#__acym_user` SET `active` = 1 WHERE `id` = '.intval($user->id));
            }
            $userClass->confirm($user->id);
        }

        return '';
    }

    public function onBeforeUserBlocking($cbUser, $block)
    {
        if (empty($cbUser->id)) return;
        acym_query('UPDATE `#__acym_user` SET `active` = '.(1 - intval($block)).' WHERE `cms_id` = '.intval($cbUser->id));
    }

    private function displayLists($mode, $acyUser, $lists)
    {
        $listClass = new ListClass();
        $allLists = $listClass->getAllWithoutManagement(true);

        $userClass = new UserClass();
        $userLists = $userClass->getSubscriptionStatus($acyUser->id);


        $visibleListsArray = [];
        $visibleLists = explode(',', $lists);
        foreach ($allLists as $listId => $oneList) {
            if (0 === intval($oneList->active) || 0 === intval($oneList->visible) || !in_array($listId, $visibleLists)) continue;
            if ('profile' === $mode && (empty($userLists[$listId]) || 1 !== intval($userLists[$listId]->status))) continue;

            $visibleListsArray[] = $listId;
        }

        if (empty($visibleListsArray)) return '';

        $return = '';

        $this->addStyle();

        $introText = $this->getParam('introtext', '');
        if (!empty($introText)) {
            $return .= '<div class="acym_introtext">'.$introText.'</div>';
        }

        $return .= '<table class="acym_cb_subscription '.$mode.'">';

        if ('edition' === $mode) {
            $return .= '<th>'.acym_translation('ACYM_SUBSCRIPTION').'</th>
                        <th>'.acym_translation('ACYM_LIST').'</th>';
        }

        $k = 0;
        foreach ($visibleListsArray as $listId) {
            $return .= '<tr class="acym_list row'.$k.'">';
            if ('edition' === $mode) {
                $return .= '<td class="acym_list_status">'.acym_boolean(
                        'acymcb[list]['.$listId.']',
                        !empty($userLists[$listId]) && 1 === intval($userLists[$listId]->status)
                    ).'</td>';
            }
            $return .= '<td class="acym_list_name">'.$allLists[$listId]->name.'</td>';
            $return .= '</tr>';
            $k = 1 - $k;
        }

        $return .= '</table>';

        return $return;
    }

    public function getEditTab($tab, $cbUser, $ui)
    {
        if (!$this->installed) return $this->errorMessage;

        $config = acym_config();

        $userClass = new UserClass();
        $acyUser = $userClass->getOneByEmail($cbUser->email);

        if (!empty($acyUser->id)) {
            if (!empty($cbUser->id) && intval($acyUser->cms_id) !== intval($cbUser->id)) {
                // Update the field so that it's linked to this user so during the update we can keep the same user.
                $acyUser->cms_id = intval($cbUser->id);
                $userClass->save($acyUser);
            }

            // Checking if user is confirmed in Acymailing
            $requireConfirmation = $config->get('require_confirmation');
            if (0 === intval($acyUser->confirmed) && 1 === intval($requireConfirmation)) {
                $myLink = acym_frontendLink('frontusers&task=confirm&id='.$acyUser->id.'&key='.urlencode($acyUser->key));
                acym_display('<a target="_blank" href="'.$myLink.'">'.acym_translation('ACYM_CONFIRM_SUBSCRIPTION').'</a>', 'warning');
            }
        }

        // If we come from the Admin interface
        $lists = $this->getParam(acym_isAdmin() ? 'listsprofileback' : 'listsprofile', '');

        return $this->displayLists('edition', $acyUser, $lists);
    }

    public function saveEditTab($tab, &$cbUser, $ui, $postdata)
    {
        $acyUser = $this->getCurrentUser($cbUser);

        if (empty($acyUser)) {
            $acyUser = new stdClass();
        }

        if (!empty($cbUser->name)) $acyUser->name = $cbUser->name;
        if (!empty($cbUser->email)) $acyUser->email = $cbUser->email;
        $acyUser->active = empty($cbUser->block) ? 1 : 0;
        $acyUser->confirmed = $cbUser->confirmed;
        $acyUser->cms_id = $cbUser->id;

        $userClass = new UserClass();
        $acyUser->id = $userClass->save($acyUser);

        if (empty($acyUser->id)) return;

        if (!empty($postdata['acymcb']['list'])) {
            $currentSubscriptions = $userClass->getSubscriptionStatus($acyUser->id);

            $addLists = [];
            $unsubLists = [];
            foreach ($postdata['acymcb']['list'] as $listID => $status) {
                if ('1' === $status) {
                    $addLists[] = $listID;
                } elseif (!empty($currentSubscriptions[$listID]->status)) {
                    $unsubLists[] = $listID;
                }
            }

            $userClass->subscribe($acyUser->id, $addLists);
            $userClass->unsubscribe($acyUser->id, $unsubLists);
        }
    }

    public function lists($name, $value, $controlName)
    {
        if (!$this->installed) return $this->errorMessage;
        $value = str_replace('|*|', ',', $value);

        return acym_displayParam(
            'lists',
            $value,
            $controlName.'['.$name.']'
        );
    }

    private function getParam($name, $default)
    {
        $param = $this->params->get($name, $default);
        $param = str_replace('|*|', ',', $param);

        return $param;
    }

    private function addStyle()
    {
        $css = $this->getParam(
            'css',
            '.acym_cb_registration input[type=\'checkbox\'] {
                        margin-top: 0;
                        vertical-align: unset;
                        margin-right: 5px;
                    }
                    .acym_cb_registration label {
                        margin-bottom: 0;
                    }
                    
                    .acym_introtext {
                        margin-left: 1rem;
                    }
                    .acym_cb_subscription {
                        margin: 1rem;
                    }
                    .acym_cb_subscription.edition {
                        margin-left: auto;
                        margin-right: auto;
                    }
                    .acym_cb_subscription th, .acym_cb_subscription td {
                        padding: 4px 10px;
                    }
                    .acym_cb_subscription label {
                        margin-bottom: 0;
                        margin-left: 5px;
                        margin-right: 8px;
                    }
                    .acym_cb_subscription input[type=\'radio\'] {
                        margin-top: 0;
                        vertical-align: unset;
                    }'
        );
        if (!empty($css)) acym_addStyle(true, $css);
    }
}
