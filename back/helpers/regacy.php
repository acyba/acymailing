<?php

namespace AcyMailing\Helpers;

use AcyMailing\Classes\ListClass;
use AcyMailing\Classes\UserClass;
use AcyMailing\Libraries\acymObject;

class RegacyHelper extends acymObject
{
    // Display options
    var $options = [];

    // What we want to add to the registration form
    var $label = '';
    var $lists = null;

    public function prepareLists($options)
    {
        $this->options = $options;

        // 1 - Make sure we need to display some lists
        $visibleLists = $this->config->get('regacy_lists');
        if (empty($visibleLists)) return false;
        $visibleLists = explode(',', $visibleLists);
        acym_arrayToInteger($visibleLists);

        $listsClass = new ListClass();
        $allLists = $listsClass->getAllWithoutManagement();

        // Display only published and visible lists, except if we're on the back-end
        $isAdmin = acym_isAdmin();
        foreach ($visibleLists as $i => $oneListId) {
            if (in_array($oneListId, array_keys($allLists)) && $allLists[$oneListId]->active && ($allLists[$oneListId]->visible || $isAdmin)) continue;
            unset($visibleLists[$i]);
        }
        if (empty($visibleLists)) return false;


        // 2 - Get the lists we should check by default
        $checkedLists = explode(',', $this->config->get('regacy_checkedlists'));
        acym_arrayToInteger($checkedLists);
        $userClass = new UserClass();

        if ('wordpress' === ACYM_CMS) {
            // If editing a user, get its lists
            $currentCMSId = acym_getVar('int', 'user_id', 0);
            if (empty($currentCMSId)) $currentCMSId = acym_currentUserId();
        } else {
            if (acym_isAdmin()) {
                // If editing a user, get its lists
                $currentCMSId = acym_getVar('int', 'id', 0);
            } else {
                // If the user is logged in, take its subscription
                $currentCMSId = acym_currentUserId();
            }
        }

        if (!empty($currentCMSId)) {
            $currentUser = $userClass->getOneByCMSId($currentCMSId);
            if (!empty($currentUser)) {
                $checkedLists = [];
                $currentSubscription = $userClass->getSubscriptionStatus($currentUser->id, $visibleLists);

                foreach ($currentSubscription as $listId => $oneSubsciption) {
                    if ($oneSubsciption->status == '1') $checkedLists[] = $listId;
                }
            }
        }


        // 3 - Prepare the HTML block we'll insert in the form
        $this->label = $this->config->get('regacy_text');
        if (empty($this->label)) $this->label = 'ACYM_SUBSCRIPTION';
        $this->label = acym_translation($this->label);

        $this->lists = [];

        foreach ($visibleLists as $oneListId) {
            $this->lists[$oneListId] = ['name' => $allLists[$oneListId]->name, 'checked' => in_array($oneListId, $checkedLists)];
        }

        if ('joomla' === ACYM_CMS || !empty($options['formatted'])) $this->_formatResults();

        return true;
    }

    private function _formatResults()
    {
        $result = '<table class="acym__regacy__lists" style="border:0">';
        foreach ($this->lists as $id => $oneList) {
            $checked = $oneList['checked'] ? 'checked="checked"' : '';
            $result .= '<tr style="border:0">
                            <td style="border:0">
                                <input type="checkbox" id="acym__regacy__lists-'.intval($id).'" class="acym_checkbox" name="regacy_visible_lists_checked[]" '.$checked.' value="'.intval($id).'"/>
                            </td>
                            <td style="border:0; padding-left:10px;" nowrap="nowrap">
                                <label for="acym__regacy__lists-'.intval($id).'" class="acym__regacy__lists__label">'.acym_escape($oneList['name']).'</label>
                            </td>
                        </tr>';
        }
        $result .= '</table>';
        $result .= '<input type="hidden" value="'.implode(',', array_keys($this->lists)).'" name="regacy_visible_lists" />';
        $result .= '<input type="hidden" value="'.ACYM_CMS.' registration form" name="acy_source" />';
        $this->lists = $result;
    }
}
