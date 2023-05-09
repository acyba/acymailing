<?php

trait SubscriptionFollowup
{
    public function matchFollowupsConditions(&$followups, $userId, $params)
    {
        foreach ($followups as $key => $followup) {
            if (empty($followup->condition['lists_status']) || empty($followup->condition['lists'])) {
                continue;
            }

            $status = $followup->condition['lists_status'] === 'is';
            if ($followup->trigger === $this->subscribeTrigger) {
                $user = false;
                foreach ($followup->condition['lists'] as $list) {
                    if (in_array($list, $params['sub_lists'])) {
                        $user = true;
                        break;
                    }
                }
            } else {
                $lists = implode(',', $followup->condition['lists']);
                $user = acym_loadObject('SELECT * FROM #__acym_user_has_list WHERE user_id = '.intval($userId).' AND status = 1 AND list_id IN ('.$lists.')');
            }

            if (($status && empty($user)) || (!$status && !empty($user))) {
                unset($followups[$key]);
            }
        }
    }

    public function getFollowupTriggers(&$triggers)
    {
        $triggers[$this->subscribeTrigger] = acym_translation('ACYM_USER_SUBSCRIBES');
    }

    public function getFollowupTriggerBlock(&$blocks)
    {
        $blocks[] = [
            'name' => acym_translation('ACYM_USER_SUBSCRIBES'),
            'description' => acym_translation('ACYM_USER_SUBSCRIBES_DESC'),
            'icon' => 'acymicon-user-check',
            'link' => acym_completeLink('campaigns&task=edit&step=followupCondition&trigger='.$this->subscribeTrigger),
            'level' => 2,
            'alias' => $this->subscribeTrigger,
        ];
    }
}
