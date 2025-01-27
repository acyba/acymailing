<?php

namespace AcyMailing\Controllers\Users;

use AcyMailing\Classes\ListClass;
use AcyMailing\Classes\UserClass;

trait Subscription
{
    public function resetSubscription()
    {
        $userId = acym_getVar('int', 'userId');

        if (empty($userId)) {
            $this->listing();

            return;
        }

        $userClass = new UserClass();
        if (!$userClass->hasUserAccess($userId)) {
            die('Access denied for subscription reset of this user');
        }

        $list = acym_getVar('int', 'acym__entity_select__selected');
        $userClass->resetSubscription($userId, [$list]);

        $this->edit();
    }

    public function unsubscribeUser()
    {
        $userId = acym_getVar('int', 'userId');
        if (empty($userId)) {
            $this->listing();

            return;
        }

        $userClass = new UserClass();
        if (!$userClass->hasUserAccess($userId)) {
            die('Access denied for unsubscribing this user');
        }

        $lists = json_decode(acym_getVar('string', 'acym__entity_select__selected', '{}'));
        if (!is_array($lists)) {
            $lists = (array)$lists;
        }

        $userClass->unsubscribe($userId, $lists);

        $this->edit();
    }

    public function unsubscribeUserFromAll()
    {
        $userId = acym_getVar('int', 'userId');

        if (empty($userId)) {
            $this->listing();

            return;
        }

        $userClass = new UserClass();
        if (!$userClass->hasUserAccess($userId)) {
            die('Access denied for unsubscribing this user');
        }

        $lists = [];
        $subscriptions = $userClass->getSubscriptionStatus($userId);
        foreach ($subscriptions as $i => $oneList) {
            if ($oneList->status == 1) {
                $lists[] = $oneList->list_id;
            }
        }

        $userClass->unsubscribe($userId, $lists);

        $this->edit();
    }

    public function resubscribeUserToAll()
    {
        $userId = acym_getVar('int', 'userId');
        if (empty($userId)) {
            $this->listing();

            return;
        }

        $userClass = new UserClass();
        if (!$userClass->hasUserAccess($userId)) {
            die('Access denied for resubscribing this user');
        }

        $lists = [];
        $subscriptions = $userClass->getSubscriptionStatus($userId);
        foreach ($subscriptions as $i => $oneList) {
            if ($oneList->status == 0) {
                $lists[] = $oneList->list_id;
            }
        }

        $userClass->subscribe($userId, $lists);

        $this->edit();
    }

    public function subscribeUser($returnOnEdit = true, $frontCreation = false)
    {
        $userId = acym_getVar('int', 'userId');
        $lists = json_decode(acym_getVar('string', 'acym__entity_select__selected', '{}'));

        if (empty($userId)) {
            $this->listing();

            return;
        }

        $userClass = new UserClass();
        if (!$frontCreation && !$userClass->hasUserAccess($userId)) {
            die('Access denied for subscribing this user');
        }

        if (!is_array($lists)) {
            $lists = (array)$lists;
        }
        $userClass->subscribe($userId, $lists);

        if ($returnOnEdit) {
            $this->edit();
        }
    }
}
