<?php

use AcyMailing\Classes\AutomationClass;
use AcyMailing\Classes\UserClass;
use AcyMailing\Helpers\ScenarioHelper;

trait MemberpressAutomationTriggers
{
    public function onAcymInitWordpressAddons()
    {
        add_action('mepr-event-subscription-created', [$this, 'onMemberPressSubscriptionCreate']);
    }

    public function onMemberPressSubscriptionCreate($event)
    {
        $subscription = $event->get_data();

        $userClass = new UserClass();
        $user = $userClass->getOneByCMSId($subscription->user_id);

        if (empty($user)) return;

        $automationClass = new AutomationClass();
        $automationClass->trigger('member_transaction_complete', ['userId' => $user->id, 'subscription_id' => $subscription->product_id]);

        $scenarioHelper = new ScenarioHelper();
        $scenarioHelper->trigger('member_transaction_complete', ['userId' => $user->id, 'subscription_id' => $subscription->product_id]);
    }

    public function onAcymDeclareTriggers(&$triggers, &$defaultValues)
    {
        $membership = acym_loadObjectList(
            'SELECT post_meta.meta_value AS `text`, `ID` AS `value` FROM #__posts AS post
             JOIN #__postmeta AS post_meta ON post.ID = post_meta.post_id AND post_meta.meta_key = "_mepr_product_pricing_title"
             WHERE post_type = "memberpressproduct" AND post_status = "publish"'
        );

        $firstGroup = new stdClass();
        $firstGroup->text = acym_translation('ACYM_ANY_MEMBERSHIP');
        $firstGroup->value = 0;
        array_unshift($membership, $firstGroup);

        $triggers['user']['member_transaction_complete'] = new stdClass();
        $triggers['user']['member_transaction_complete']->name = acym_translation('ACYM_ON_MEMBER_TRANSACTION_COMPLETE');
        $triggers['user']['member_transaction_complete']->option = '<div class="grid-x grid-margin-x" style="height: 40px;">';
        $triggers['user']['member_transaction_complete']->option .= '<div class="cell medium-shrink acym_vcenter">'.acym_translation('ACYM_ON_MEMBERSHIP').'</div>';
        $triggers['user']['member_transaction_complete']->option .= '<div class="cell medium-5">'.acym_select(
                $membership,
                '[triggers][user][member_transaction_complete][membership]',
                empty($defaultValues['member_transaction_complete']['membership']) ? 0 : $defaultValues['member_transaction_complete']['membership'],
                ['data-class' => 'acym__select']
            ).'</div>';
        $triggers['user']['member_transaction_complete']->option .= '</div>';
    }

    public function onAcymDeclareTriggersScenario(&$triggers, &$defaultValues)
    {
        $this->onAcymDeclareTriggers($triggers, $defaultValues);
    }

    public function onAcymExecuteTrigger(&$step, &$execute, &$data)
    {
        if (empty($data['userId'])) return;

        $triggers = $step->triggers;

        if (!empty($triggers['member_transaction_complete']) && !empty($data['subscription_id'])) {
            if (empty($triggers['member_transaction_complete']['membership'])) {
                $execute = true;
            } elseif ($triggers['member_transaction_complete']['membership'] == $data['subscription_id']) {
                $execute = true;
            }
        }
    }

    public function onAcymDeclareSummary_triggers(object $automation): void
    {
        if (empty($automation->triggers['member_transaction_complete']) || !is_array($automation->triggers['member_transaction_complete'])) {
            return;
        }

        if (empty($automation->triggers['member_transaction_complete']['membership'])) {
            $membershipName = strtolower(acym_translation('ACYM_ANY_MEMBERSHIP'));
        } else {
            $membershipName = $this->getMemberPressMembershipNameById($automation->triggers['member_transaction_complete']['membership']);
        }

        $automation->triggers['member_transaction_complete'] = acym_translationSprintf(
            'ACYM_TRIGGER_PLUGIN_NEW_SUBSCRIPTION_CREATED_FOR',
            'MemberPress',
            $membershipName
        );
    }

    private function getMemberPressMembershipNameById($id)
    {
        return acym_loadResult(
            'SELECT post_meta.meta_value FROM #__posts AS post
             JOIN #__postmeta AS post_meta ON post.ID = post_meta.post_id AND post_meta.meta_key = "_mepr_product_pricing_title" AND post_meta.post_id = '.intval($id).'
             WHERE post_type = "memberpressproduct" AND post_status = "publish"'
        );
    }
}
