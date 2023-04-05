<?php

use AcyMailing\Classes\UserClass;
use AcyMailing\Classes\ListClass;

trait SubscriptionMailboxAction
{
    public function onAcymMailboxActionDefine(&$actions)
    {
        $listClass = new ListClass();
        $lists = $listClass->getAllWithIdName();

        $actions['acy_list_subscribe'] = new stdClass();
        $actions['acy_list_subscribe']->name = acym_translation('ACYM_SUBSCRIBE_USER');
        $actions['acy_list_subscribe']->option = '<div class="intext_select_mailbox cell">';
        $actions['acy_list_subscribe']->option .= acym_select(
            $lists,
            'acym_action[__num__][acy_list_subscribe][list_id]',
            null,
            ['class' => 'acym__select']
        );
        $actions['acy_list_subscribe']->option .= '</div>';

        $actions['acy_list_unsubscribe'] = new stdClass();
        $actions['acy_list_unsubscribe']->name = acym_translation('ACYM_UNSUB_USER');
        $actions['acy_list_unsubscribe']->option = '<div class="intext_select_mailbox cell">';
        $actions['acy_list_unsubscribe']->option .= acym_select(
            $lists,
            'acym_action[__num__][acy_list_unsubscribe][list_id]',
            null,
            ['class' => 'acym__select']
        );
        $actions['acy_list_unsubscribe']->option .= '</div>';
    }

    public function onAcymMailboxActionSummaryListing(&$action, &$result)
    {
        $listClass = new ListClass();
        if (!empty($action['acy_list_subscribe'])) {
            $list = $listClass->getOneById($action['acy_list_subscribe']['list_id']);

            if (empty($list)) {
                return;
            }

            $result[] = acym_translationSprintf('ACYM_SUBSCRIBE_USER_X', $list->name);
        }

        if (!empty($action['acy_list_unsubscribe'])) {
            $list = $listClass->getOneById($action['acy_list_unsubscribe']['list_id']);

            if (empty($list)) {
                return;
            }

            $result[] = acym_translationSprintf('ACYM_UNSUB_USER_X', $list->name);
        }
    }

    public function onAcymMailboxAction_acy_list_subscribe(&$action, &$report, &$executedActions, $mailboxHelper)
    {
        $userClass = new UserClass();
        $user = new \stdClass();
        $user->email = $mailboxHelper->_message->header->from_email;
        $userDatabase = $userClass->getOneByEmail($user->email);
        if (empty($userDatabase->id)) {
            $user->id = $userClass->save($user);
            if (empty($user->id)) {
                $report[] = [
                    'message' => acym_translation('ACYM_ERROR_SAVING'),
                    'success' => false,
                ];
                $executedActions = false;

                return;
            }
        } else {
            $user->id = $userDatabase->id;
        }

        $subscribed = $userClass->subscribe($user->id, $action['list_id']);

        if (!$subscribed) {
            $report[] = [
                'message' => acym_translationSprintf('ACYM_COULD_NOT_SUBSCRIBE_TO_X', $action['list_id']),
                'success' => false,
            ];
            $executedActions = false;

            return;
        }

        $report[] = [
            'message' => acym_translationSprintf('ACYM_SUBSCRIBE_USER_X', $action['list_id']),
            'success' => true,
        ];
    }

    public function onAcymMailboxAction_acy_list_unsubscribe(&$action, &$report, &$executedActions, $mailboxHelper)
    {
        $user = new \stdClass();
        $user->email = $mailboxHelper->_message->header->from_email;

        $userClass = new UserClass();
        $userDatabase = $userClass->getOneByEmail($user->email);

        if (empty($userDatabase->id)) {
            $report[] = [
                'message' => acym_translationSprintf('ACYM_SEND_ERROR_USER', $mailboxHelper->_message->header->from_email),
                'success' => false,
            ];

            $executedActions = false;

            return;
        }
        $affected = $userClass->unsubscribe($userDatabase->id, $action['list_id']);
        $message = $affected
            ? acym_translationSprintf('ACYM_UNSUB_USER_X_FROM_X', $userDatabase->email, $action['list_id'])
            : acym_translationSprintf('ACYM_COULD_NOT_UNSUB_USER_X_FROM_X', $userDatabase->email, $action['list_id']);
        $report[] = [
            'message' => $message,
            'success' => $affected,
        ];

        $executedActions = $affected;
    }
}
