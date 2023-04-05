<?php

namespace AcyMailing\Controllers\Users;

trait Subscription
{
    public function resetSubscription()
    {
        $userId = acym_getVar('int', 'userId');

        if (empty($userId)) {
            $this->listing();

            return;
        }

        $list = acym_getVar('int', 'acym__entity_select__selected');
        $this->currentClass->resetSubscription($userId, [$list]);

        $this->edit();
    }

    public function unsubscribeUser()
    {
        $userId = acym_getVar('int', 'userId');

        if (empty($userId)) {
            $this->listing();

            return;
        }

        $lists = json_decode(acym_getVar('string', 'acym__entity_select__selected', '{}'));
        if (!is_array($lists)) {
            $lists = (array)$lists;
        }

        $this->currentClass->unsubscribe($userId, $lists);

        $this->edit();
    }

    public function unsubscribeUserFromAll()
    {
        $userId = acym_getVar('int', 'userId');

        if (empty($userId)) {
            $this->listing();

            return;
        }

        $lists = [];
        $subscriptions = $this->currentClass->getSubscriptionStatus($userId);
        foreach ($subscriptions as $i => $oneList) {
            if ($oneList->status == 1) {
                $lists[] = $oneList->list_id;
            }
        }

        $this->currentClass->unsubscribe($userId, $lists);

        $this->edit();
    }

    public function resubscribeUserToAll()
    {
        $userId = acym_getVar('int', 'userId');

        if (empty($userId)) {
            $this->listing();

            return;
        }

        $lists = [];
        $subscriptions = $this->currentClass->getSubscriptionStatus($userId);
        foreach ($subscriptions as $i => $oneList) {
            if ($oneList->status == 0) {
                $lists[] = $oneList->list_id;
            }
        }

        $this->currentClass->subscribe($userId, $lists);

        $this->edit();
    }

    public function subscribeUser($returnOnEdit = true)
    {
        $userId = acym_getVar('int', 'userId');
        $lists = json_decode(acym_getVar('string', 'acym__entity_select__selected', '{}'));

        if (empty($userId)) {
            $this->listing();

            return;
        }

        if (!is_array($lists)) {
            $lists = (array)$lists;
        }

        $this->currentClass->subscribe($userId, $lists);

        if ($returnOnEdit) $this->edit();
    }
}
