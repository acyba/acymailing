<?php

use AcyMailing\Classes\FollowupClass;
use AcyMailing\Classes\ListClass;

trait SubscriptionAutomationActions
{
    public function onAcymDeclareActions(&$actions)
    {
        $listClass = new ListClass();

        $listActions = [
            'sub' => acym_translation('ACYM_SUBSCRIBE_USERS_TO'),
            'remove' => acym_translation('ACYM_REMOVE_USERS_FROM'),
            'unsub' => acym_translation('ACYM_UNSUBSCRIBE_USERS_TO'),
        ];
        $lists = $listClass->getAllForSelect();

        $actions['acy_list'] = new stdClass();
        $actions['acy_list']->name = acym_translation('ACYM_ACYMAILING_LIST');
        $actions['acy_list']->option = '<div class="intext_select_automation cell">';
        $actions['acy_list']->option .= acym_select(
            $listActions,
            'acym_action[actions][__and__][acy_list][list_actions]',
            null,
            ['class' => 'acym__select']
        );
        $actions['acy_list']->option .= '</div>';
        $actions['acy_list']->option .= '<div class="intext_select_automation cell">';
        $actions['acy_list']->option .= acym_select(
            $lists,
            'acym_action[actions][__and__][acy_list][list_id]',
            null,
            ['class' => 'acym__select']
        );
        $actions['acy_list']->option .= '</div>';

        $actions['subscribe_followup'] = new stdClass();
        $actions['subscribe_followup']->name = acym_translation('ACYM_SUBSCRIBE_FOLLOW_UP');

        $followupClass = new FollowupClass();
        $allListFollowups = $followupClass->getAll();

        $actions['subscribe_followup']->option = '<div class="intext_select_automation cell">'.acym_select(
                $allListFollowups,
                'acym_action[actions][__and__][subscribe_followup][followup_id]',
                null,
                [
                    'class' => 'acym__select',
                    'data-placeholder' => (!empty($listFollowups) ? acym_translation('ACYM_SELECT_FOLLOWUP', true) : acym_translation('ACYM_FOLLOWUP_NOT_FOUND', true)),
                ],
                'id',
                'name'
            ).'</div>';

        $actions['unsubscribe_followup'] = new stdClass();
        $actions['unsubscribe_followup']->name = acym_translation('ACYM_UNSUBSCRIBE_FOLLOW_UP');
        $actions['unsubscribe_followup']->option = '<div class="intext_select_automation cell">';
        $actions['unsubscribe_followup']->option .= acym_select(
            $allListFollowups,
            'acym_action[actions][__and__][unsubscribe_followup][followup_id]',
            null,
            [
                'class' => 'acym__select',
                'data-placeholder' => acym_translation(empty($listFollowups) ? 'ACYM_FOLLOWUP_NOT_FOUND' : 'ACYM_SELECT_FOLLOWUP', true),
            ],
            'id',
            'name'
        );
        $actions['unsubscribe_followup']->option .= '</div>';
    }

    public function onAcymProcessAction_acy_list(&$query, $action)
    {
        if ($action['list_actions'] === 'sub') {
            $queryToProcess = 'INSERT IGNORE #__acym_user_has_list (`user_id`, `list_id`, `status`, `subscription_date`) ('.$query->getQuery(
                    [
                        'user.id',
                        $action['list_id'],
                        '1',
                        acym_escapeDB(acym_date(time(), 'Y-m-d H:i:s')),
                    ]
                ).') ON DUPLICATE KEY UPDATE status = 1';
        } elseif ($action['list_actions'] === 'remove') {
            $queryToProcess = 'DELETE FROM #__acym_user_has_list WHERE list_id = '.intval($action['list_id']).' AND user_id IN ('.$query->getQuery(['user.id']).')';
        } elseif ($action['list_actions'] === 'unsub') {
            $queryToProcess = 'UPDATE #__acym_user_has_list SET status = 0 WHERE list_id = '.intval($action['list_id']).' AND user_id IN ('.$query->getQuery(['user.id']).')';
        }

        $nbAffected = acym_query($queryToProcess);

        return acym_translationSprintf('ACYM_ACTION_LIST_'.strtoupper($action['list_actions']), $nbAffected);
    }

    public function onAcymProcessAction_subscribe_followup(&$query, &$action)
    {
        $followupClass = new FollowupClass();
        $followup = $followupClass->getOneById($action['followup_id']);
        if (empty($followup->active)) {
            return '';
        }

        $queryToProcess = 'INSERT IGNORE #__acym_user_has_list (`user_id`, `list_id`, `status`, `subscription_date`) ('.$query->getQuery(
                [
                    'user.id',
                    $followup->list_id,
                    '1',
                    acym_escapeDB(acym_date(time(), 'Y-m-d H:i:s')),
                ]
            ).') ON DUPLICATE KEY UPDATE status = 1';

        $nbAffected = acym_query($queryToProcess);

        $followups = $followupClass->getFollowupsWithMailsInfoByIds($action['followup_id']);
        foreach ($followups as $mails) {
            foreach ($mails as $mail) {
                $sendDate = time() + (intval($mail->delay) * intval($mail->delay_unit));
                $sendDate = acym_escapeDB(acym_date($sendDate, 'Y-m-d H:i:s', false));
                $queryToProcess = 'INSERT IGNORE #__acym_queue (`mail_id`, `user_id`, `sending_date`, `priority`, `try`) ('.$query->getQuery(
                        [
                            $mail->mail_id,
                            'user.id',
                            $sendDate,
                            $this->config->get('priority_newsletter', 3),
                            0,
                        ]
                    ).')';

                acym_query($queryToProcess);
            }
        }

        return acym_translationSprintf('ACYM_ACTION_LIST_SUB', $nbAffected);
    }

    public function onAcymProcessAction_unsubscribe_followup(&$query, &$action)
    {
        $followupClass = new FollowupClass();
        $followup = $followupClass->getOneById($action['followup_id']);
        if (empty($followup)) {
            return '';
        }

        $mailIds = $followupClass->getEmailsByIds($action['followup_id']);
        if (!empty($mailIds)) {
            acym_query(
                'DELETE FROM #__acym_queue 
				WHERE user_id IN ('.$query->getQuery(['user.id']).') 
					AND mail_id IN ('.implode(',', $mailIds).')'
            );
        }

        $unsubscribeDate = date('Y-m-d H:i:s', time() - date('Z'));
        $nbAffected = acym_query(
            'UPDATE #__acym_user_has_list 
            SET `status` = 0, `unsubscribe_date` = '.acym_escapeDB($unsubscribeDate).'
			WHERE list_id = '.intval($followup->list_id).' 
				AND user_id IN ('.$query->getQuery(['user.id']).')'
        );

        return acym_translationSprintf('ACYM_ACTION_LIST_UNSUB', $nbAffected);
    }

    public function onAcymDeclareSummary_actions(&$automationAction)
    {
        if (!empty($automationAction['acy_list'])) {
            $listClass = new ListClass();
            $list = $listClass->getOneById($automationAction['acy_list']['list_id']);
            if ($automationAction['acy_list']['list_actions'] == 'sub') $automationAction['acy_list']['list_actions'] = 'ACYM_SUBSCRIBED_TO';
            if ($automationAction['acy_list']['list_actions'] == 'unsub') $automationAction['acy_list']['list_actions'] = 'ACYM_UNSUBSCRIBE_FROM';
            if ($automationAction['acy_list']['list_actions'] == 'remove') $automationAction['acy_list']['list_actions'] = 'ACYM_REMOVE_FROM';
            if (empty($list)) {
                $automationAction = '<span class="acym__color__red">'.acym_translation('ACYM_SELECT_A_LIST').'</span>';
            } else {
                $automationAction = acym_translationSprintf('ACYM_ACTION_LIST_SUMMARY', acym_translation($automationAction['acy_list']['list_actions']), $list->name);
            }
        }

        if (!empty($automationAction['subscribe_followup'])) {
            $followupClass = new FollowupClass();
            $followup = $followupClass->getOneById($automationAction['subscribe_followup']['followup_id']);
            $automationAction = !empty($followup)
                ? acym_translationSprintf('ACYM_ACTION_SUBSCRIBE_FOLLOWUP_SUMMARY', $followup->name)
                : acym_translation('ACYM_FOLLOWUP_NOT_FOUND');
        }

        if (!empty($automationAction['unsubscribe_followup'])) {
            $followupClass = new FollowupClass();
            $followup = $followupClass->getOneById($automationAction['unsubscribe_followup']['followup_id']);
            $automationAction = !empty($followup)
                ? acym_translationSprintf('ACYM_ACTION_UNSUBSCRIBE_FOLLOWUP_SUMMARY', $followup->name)
                : acym_translation('ACYM_FOLLOWUP_NOT_FOUND');
        }
    }
}
