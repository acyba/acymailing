<?php

namespace AcyMailing\Controllers\Users;

use AcyMailing\Classes\UserClass;

trait Subscription
{
    public function resetSubscription(): void
    {
        acym_checkToken();

        $userId = acym_getVar('int', 'userId');

        if (empty($userId)) {
            $this->listing();

            return;
        }

        $userClass = new UserClass();
        if (!$userClass->hasUserAccess($userId)) {
            die('Access denied for subscription reset of this user');
        }

        $lists = json_decode(acym_getVar('string', 'acym__entity_select__selected', '[]'), true);
        if (empty($lists)) {
            $lists = [];
        }
        $userClass->resetSubscription([$userId], $lists);

        $this->edit();
    }

    public function unsubscribeUser(): void
    {
        acym_checkToken();

        $userId = acym_getVar('int', 'userId');
        if (empty($userId)) {
            $this->listing();

            return;
        }

        $userClass = new UserClass();
        if (!$userClass->hasUserAccess($userId)) {
            die('Access denied for unsubscribing this user');
        }

        $lists = json_decode(acym_getVar('string', 'acym__entity_select__selected', '[]'), true);
        if (empty($lists)) {
            $lists = [];
        }
        $userClass->unsubscribe([$userId], $lists);

        $this->edit();
    }

    public function unsubscribeUserFromAll(): void
    {
        acym_checkToken();

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

        $userClass->unsubscribe([$userId], $lists);

        $this->edit();
    }

    public function resubscribeUserToAll(): void
    {
        acym_checkToken();

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

        $userClass->subscribe([$userId], $lists);

        $this->edit();
    }

    public function subscribeUser(bool $returnOnEdit = true, array $lists = [], bool $frontCreation = false): void
    {
        // Called as a task (no argument) → require a CSRF token. Internal calls from apply() are already token-checked.
        if (func_num_args() === 0) {
            acym_checkToken();
        }

        $userId = acym_getVar('int', 'userId');
        if (empty($userId)) {
            $this->listing();

            return;
        }

        $userClass = new UserClass();
        if (!$frontCreation && !$userClass->hasUserAccess($userId)) {
            die('Access denied for subscribing this user');
        }

        // Can be called from the user's edition page when re-subscribing to a list
        if (empty($lists)) {
            $lists = json_decode(acym_getVar('string', 'acym__entity_select__selected', '[]'), true);
            if (empty($lists)) {
                $lists = [];
            }
        }

        $userClass->subscribe([$userId], $lists);

        if ($returnOnEdit) {
            $this->edit();
        }
    }
}
