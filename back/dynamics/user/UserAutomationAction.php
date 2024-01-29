<?php

trait UserAutomationActions
{
    public function onAcymDeclareActions(&$actions)
    {
        $allGroups = acym_getGroups();
        $groups = ['none' => acym_translation('ACYM_SELECT_GROUP')];
        foreach ($allGroups as $group) {
            $groups[$group->id] = $group->text;
        }
        unset($groups[ACYM_ADMIN_GROUP]);

        $actions['acy_group_action'] = new stdClass();
        $actions['acy_group_action']->name = acym_translation('ACYM_ACTION_ON_GROUPS');
        $actions['acy_group_action']->option = '<div class="grid-x>';

        $actions['acy_group_action']->option .= '<div class="intext_select_automation cell">';
        $actions['acy_group_action']->option .= '<label class="cell acym_vcenter">'.acym_translation('ACYM_ACTION_ON_GROUPS_FROM').'</label>';
        $actions['acy_group_action']->option .= acym_select(
            $groups,
            'acym_action[actions][__and__][acy_group_action][remove]',
            'none',
            ['class' => 'acym__select']
        );
        $actions['acy_group_action']->option .= '</div>';

        $actions['acy_group_action']->option .= '<div class="intext_select_automation cell">';
        $actions['acy_group_action']->option .= '<label class="cell shrink margin-left-1 margin-right-1">'.acym_translation('ACYM_ACTION_ON_GROUPS_TO').'</label>';
        $actions['acy_group_action']->option .= acym_select(
            $groups,
            'acym_action[actions][__and__][acy_group_action][add]',
            'none',
            [
                'class' => 'acym__select',
                'data-toggle-select' => '{"none":"#warning_delete_group"}',
            ]
        );
        $actions['acy_group_action']->option .= '</div>';

        $actions['acy_group_action']->option .= '<div id="warning_delete_group" class="cell">';
        $actions['acy_group_action']->option .= '<label class="cell shrink acym__color__red">'.acym_translation('ACYM_ACTION_ON_GROUPS_FROM_WARNING').'</label>';
        $actions['acy_group_action']->option .= '</div>';

        $actions['acy_group_action']->option .= '</div>';
    }

    public function onAcymProcessAction_acy_group_action(&$query, $action)
    {
        $nbAffected = 0;

        $queryBuilder = clone $query;
        $queryBuilder->where[] = 'user.cms_id != 0';
        $queryBuilder->where[] = 'user.cms_id IS NOT NULL';
        $userIds = acym_loadResultArray($queryBuilder->getQuery(['user.cms_id']));

        if (empty($userIds)) {
            return '';
        }

        foreach ($userIds as $userId) {
            $userAffected = false;

            if (ACYM_CMS === 'joomla') {
                if (!empty($action['add']) && $action['add'] !== 'none') {
                    $affected = acym_query('INSERT IGNORE INTO #__user_usergroup_map (`user_id`, `group_id`) VALUES ('.intval($userId).', '.intval($action['add']).')');
                    if ($affected) {
                        $userAffected = true;
                    }
                }

                if (!empty($action['remove']) && $action['remove'] !== 'none') {
                    $affected = acym_query('DELETE FROM #__user_usergroup_map WHERE `group_id` = '.intval($action['remove']).' AND `user_id` = '.intval($userId));
                    if ($affected) {
                        $userAffected = true;
                    }
                }
            } else {
                $user = new WP_User($userId);
                if (empty($user->ID)) {
                    continue;
                }

                if (!empty($action['add']) && $action['add'] !== 'none' && !in_array($action['add'], $user->roles)) {
                    $user->add_role($action['add']);
                    $userAffected = true;
                }

                if (!empty($action['remove']) && $action['remove'] !== 'none' && in_array($action['remove'], $user->roles)) {
                    $user->remove_role($action['remove']);
                    $userAffected = true;
                }
            }

            if ($userAffected) {
                $nbAffected++;
            }
        }

        return acym_translationSprintf('ACYM_ACTION_ON_GROUPS_RESULT', $nbAffected);
    }

    public function onAcymDeclareSummary_actions(&$automationAction)
    {
        if (empty($automationAction['acy_group_action'])) {
            return;
        }

        $allGroups = acym_getGroups();
        $summary = '';

        if (!empty($automationAction['acy_group_action']['remove']) && $automationAction['acy_group_action']['remove'] !== 'none') {
            $groupIdToRemove = $automationAction['acy_group_action']['remove'];
            $groupNameToRemove = isset($allGroups[$groupIdToRemove]) ? $allGroups[$groupIdToRemove]->text : acym_translation('ACYM_UNKNOWN_GROUP');
            $summary .= acym_translationSprintf('ACYM_GROUP_ACTION_SUMARY_DELETE', $groupNameToRemove).'<br />';
        }

        if (!empty($automationAction['acy_group_action']['add']) && $automationAction['acy_group_action']['add'] !== 'none') {
            $groupIdToAdd = $automationAction['acy_group_action']['add'];
            $groupNameToAdd = isset($allGroups[$groupIdToAdd]) ? $allGroups[$groupIdToAdd]->text : acym_translation('ACYM_UNKNOWN_GROUP');
            $summary .= acym_translationSprintf('ACYM_GROUP_ACTION_SUMARY_ADD', $groupNameToAdd);
        }

        $automationAction = $summary;
    }
}
