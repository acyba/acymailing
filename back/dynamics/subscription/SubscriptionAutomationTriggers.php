<?php

use AcyMailing\Classes\FollowupClass;
use AcyMailing\Classes\AutomationClass;

trait SubscriptionAutomationTriggers
{
    private $subscribeTrigger = 'user_subscribe';
    private $unsubscribeTrigger = 'user_unsubscribe';

    private $triggers = [
        'user_subscribe' => 'ACYM_WHEN_USER_SUBSCRIBES',
        'user_unsubscribe' => 'ACYM_WHEN_USER_UNSUBSCRIBES',
    ];

    public function onAcymDeclareTriggers(&$triggers, &$defaultValues)
    {
        foreach ($this->triggers as $key => $name) {
            $triggers['user'][$key] = new stdClass();
            $triggers['user'][$key]->name = '<div class="cell shrink">'.acym_translation($name).'</div>';
            $triggers['user'][$key]->option = '<input type="hidden" name="[triggers][user]['.$key.'][]" value="">';
        }
    }

    public function onAcymExecuteTrigger(&$step, &$execute, &$data)
    {
        if (empty($data['userId'])) return;

        foreach ($this->triggers as $identifier => $name) {
            if (empty($step->triggers[$identifier])) continue;

            $execute = true;
            break;
        }
    }

    public function onAcymDeclareSummary_triggers(&$automation)
    {
        if (!empty($automation->triggers[$this->subscribeTrigger])) {
            $automation->triggers[$this->subscribeTrigger] = acym_translation('ACYM_WHEN_USER_SUBSCRIBES');
        }

        if (!empty($automation->triggers[$this->unsubscribeTrigger])) {
            $automation->triggers[$this->unsubscribeTrigger] = acym_translation('ACYM_WHEN_USER_UNSUBSCRIBES');
        }
    }

    public function onAcymAfterUserSubscribe(&$user, $lists)
    {
        $automationClass = new AutomationClass();
        $automationClass->trigger($this->subscribeTrigger, ['userId' => $user->id]);

        $followupClass = new FollowupClass();
        $followupClass->addFollowupEmailsQueue($this->subscribeTrigger, $user->id, ['sub_lists' => $lists]);
    }

    public function onAcymAfterUserUnsubscribe(&$user, $lists)
    {
        $automationClass = new AutomationClass();
        $automationClass->trigger($this->unsubscribeTrigger, ['userId' => $user->id]);
    }
}
