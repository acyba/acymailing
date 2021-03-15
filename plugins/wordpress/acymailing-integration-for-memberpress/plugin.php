<?php

use AcyMailing\Classes\AutomationClass;
use AcyMailing\Classes\UserClass;
use AcyMailing\Libraries\acymPlugin;
use AcyMailing\Types\OperatorinType;

class plgAcymMemberpress extends acymPlugin
{
    public function __construct()
    {
        parent::__construct();
        $this->cms = 'WordPress';
        $this->installed = acym_isExtensionActive('memberpress/memberpress.php');
        $this->pluginDescription->name = 'MemberPress';
        $this->pluginDescription->category = 'User management';
        $this->pluginDescription->features = '["content","automation"]';
        $this->pluginDescription->description = '- Insert user information in your emails<br />- Filter users based on their membership subscription';
    }

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
                'data-class="acym__select"'
            ).'</div>';
        $triggers['user']['member_transaction_complete']->option .= '</div>';
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

    public function onAcymDeclareSummary_triggers(&$automation)
    {
        if (empty($automation->triggers['member_transaction_complete'])) return;

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

    public function onAcymDeclareConditions(&$conditions)
    {
        $allGroups = acym_loadObjectList(
            'SELECT post_meta.meta_value AS `text`, `ID` AS `value` FROM #__posts AS post
             JOIN #__postmeta AS post_meta ON post.ID = post_meta.post_id AND post_meta.meta_key = "_mepr_product_pricing_title"
             WHERE post_type = "memberpressproduct" AND post_status = "publish"'
        );
        if (empty($allGroups)) return;

        $conditions['user']['memberpress'] = new stdClass();
        $conditions['user']['memberpress']->name = acym_translationSprintf('ACYM_INTEGRATION_PLUGIN_MEMBERSHIP', 'MemberPress');
        $conditions['user']['memberpress']->option = '<div class="cell grid-x grid-margin-x">';

        $operatorIn = new OperatorinType();

        $conditions['user']['memberpress']->option .= '<div class="intext_select_automation cell acym__small_select">';
        $conditions['user']['memberpress']->option .= $operatorIn->display('acym_condition[conditions][__numor__][__numand__][memberpress][type]');
        $conditions['user']['memberpress']->option .= '</div>';

        $firstGroup = new stdClass();
        $firstGroup->text = acym_translation('ACYM_ANY_MEMBERSHIP');
        $firstGroup->value = 0;
        array_unshift($allGroups, $firstGroup);

        $conditions['user']['memberpress']->option .= '<div class="intext_select_automation cell">';
        $conditions['user']['memberpress']->option .= acym_select(
            $allGroups,
            'acym_condition[conditions][__numor__][__numand__][memberpress][membership]',
            '',
            'class="acym__select"'
        );
        $conditions['user']['memberpress']->option .= '</div>';

        $activeMemberPress = [
            acym_selectOption('', acym_translation('ACYM_ACTIVATED_OR_DEACTIVATED')),
            acym_selectOption(1, acym_translation('ACYM_ACTIVE')),
            acym_selectOption(-1, acym_translation('ACYM_DEACTIVATE')),
        ];

        $conditions['user']['memberpress']->option .= '<div class="intext_select_automation cell">';
        $conditions['user']['memberpress']->option .= acym_select(
            $activeMemberPress,
            'acym_condition[conditions][__numor__][__numand__][memberpress][active]',
            '',
            'class="acym__select"'
        );
        $conditions['user']['memberpress']->option .= '</div>';

        $statusMemberPress = [];
        $statusMemberPress[] = acym_selectOption('', acym_translation('ACYM_ANY_STATUS'));
        $statusMemberPress[] = acym_selectOption('active', acym_translation('ACYM_ENABLED'));
        $statusMemberPress[] = acym_selectOption('pending', acym_translation('ACYM_PENDING'));
        $statusMemberPress[] = acym_selectOption('suspended', acym_translation('ACYM_SUSPENDED'));
        $statusMemberPress[] = acym_selectOption('cancelled', acym_translation('ACYM_STOPPED'));

        $conditions['user']['memberpress']->option .= '<div class="intext_select_automation cell">';
        $conditions['user']['memberpress']->option .= acym_select(
            $statusMemberPress,
            'acym_condition[conditions][__numor__][__numand__][memberpress][status]',
            '',
            'class="acym__select"'
        );
        $conditions['user']['memberpress']->option .= '</div>';

        $conditions['user']['memberpress']->option .= '<div class="cell grid-x margin-top-1 margin-bottom-1">';
        $conditions['user']['memberpress']->option .= acym_dateField('acym_condition[conditions][__numor__][__numand__][memberpress][signup_date_inf]', '', 'cell shrink');
        $conditions['user']['memberpress']->option .= '<span class="acym__title acym__title__secondary acym_vcenter margin-bottom-0 cell shrink margin-left-1 margin-right-1"><</span>';
        $conditions['user']['memberpress']->option .= '<span class="acym_vcenter">'.acym_translation('ACYM_SUBSCRIPTION_DATE').'</span>';
        $conditions['user']['memberpress']->option .= '<span class="acym__title acym__title__secondary acym_vcenter margin-bottom-0 cell shrink margin-left-1 margin-right-1"><</span>';
        $conditions['user']['memberpress']->option .= acym_dateField('acym_condition[conditions][__numor__][__numand__][memberpress][signup_date_sup]', '', 'cell shrink');
        $conditions['user']['memberpress']->option .= '</div>';

        $conditions['user']['memberpress']->option .= '<div class="cell grid-x">';
        $conditions['user']['memberpress']->option .= acym_dateField('acym_condition[conditions][__numor__][__numand__][memberpress][expiration_date_inf]', '', 'cell shrink');
        $conditions['user']['memberpress']->option .= '<span class="acym__title acym__title__secondary acym_vcenter margin-bottom-0 cell shrink margin-left-1 margin-right-1"><</span>';
        $conditions['user']['memberpress']->option .= '<span class="acym_vcenter">'.acym_translation('ACYM_END_DATE').'</span>';
        $conditions['user']['memberpress']->option .= '<span class="acym__title acym__title__secondary acym_vcenter margin-bottom-0 cell shrink margin-left-1 margin-right-1"><</span>';
        $conditions['user']['memberpress']->option .= acym_dateField('acym_condition[conditions][__numor__][__numand__][memberpress][expiration_date_sup]', '', 'cell shrink');
        $conditions['user']['memberpress']->option .= '</div>';

        $conditions['user']['memberpress']->option .= '</div>';
    }

    public function onAcymProcessCondition_memberpress(&$query, $options, $num, &$conditionNotValid)
    {
        $this->processConditionFilter_memberpress($query, $options, $num);
        $affectedRows = $query->count();
        if (empty($affectedRows)) $conditionNotValid++;
    }

    private function processConditionFilter_memberpress(&$query, $options, $num)
    {
        $joinSubscription = '`#__mepr_subscriptions` AS memberpress_sub'.$num.' ON memberpress_sub'.$num.'.`user_id` = user.`cms_id`';
        $leftJoinTransaction = '`#__mepr_transactions` AS memberpress_transaction'.$num.' ON memberpress_transaction'.$num.'.`subscription_id` = memberpress_sub'.$num.'.`id`';
        if (!empty($options['membership'])) $joinSubscription .= ' AND memberpress_sub'.$num.'.`product_id` = '.intval($options['membership']);
        if (!empty($options['status'])) $joinSubscription .= ' AND memberpress_sub'.$num.'.`status` = '.acym_escapeDB($options['status']);

        if (!empty($options['signup_date_inf'])) {
            $options['signup_date_inf'] = acym_replaceDate($options['signup_date_inf']);
            if (!is_numeric($options['signup_date_inf'])) $options['signup_date_inf'] = strtotime($options['signup_date_inf']);
            $leftJoinTransaction .= ' AND memberpress_transaction'.$num.'.`created_at` > '.acym_escapeDB(acym_date($options['signup_date_inf'], 'Y-m-d H:i', false));
        }
        if (!empty($options['signup_date_sup'])) {
            $options['signup_date_sup'] = acym_replaceDate($options['signup_date_sup']);
            if (!is_numeric($options['signup_date_sup'])) $options['signup_date_sup'] = strtotime($options['signup_date_sup']);
            $leftJoinTransaction .= ' AND memberpress_transaction'.$num.'.`created_at` < '.acym_escapeDB(acym_date($options['signup_date_sup'], 'Y-m-d H:i', false));
        }

        if (!empty($options['expiration_date_inf'])) {
            $options['expiration_date_inf'] = acym_replaceDate($options['expiration_date_inf']);
            if (!is_numeric($options['expiration_date_inf'])) $options['expiration_date_inf'] = strtotime($options['expiration_date_inf']);
            $leftJoinTransaction .= ' AND (memberpress_transaction'.$num.'.`expires_at` > '.acym_escapeDB(acym_date($options['expiration_date_inf'], 'Y-m-d H:i', false));
            $leftJoinTransaction .= ' OR memberpress_transaction'.$num.'.`expires_at` = "0000-00-00 00:00:00")';
        }
        if (!empty($options['expiration_date_sup'])) {
            $options['expiration_date_sup'] = acym_replaceDate($options['expiration_date_sup']);
            if (!is_numeric($options['expiration_date_sup'])) $options['expiration_date_sup'] = strtotime($options['expiration_date_sup']);
            $leftJoinTransaction .= ' AND memberpress_transaction'.$num.'.`expires_at` < '.acym_escapeDB(acym_date($options['expiration_date_sup'], 'Y-m-d H:i', false));
            $leftJoinTransaction .= ' AND memberpress_transaction'.$num.'.`expires_at` > "0000-00-00 00:00:00"';
        }

        $query->join['memberpress_sub'.$num] = $joinSubscription;
        $query->leftjoin['memberpress_transaction'.$num] = $leftJoinTransaction;

        if (in_array($options['active'], [1, -1])) {
            $like = $options['active'] == -1 ? 'NOT LIKE' : 'LIKE';
            $query->join['memberpress_member'.$num] = '#__mepr_members AS memberpress_member'.$num.' ON memberpress_member'.$num.'.`user_id` = memberpress_sub'.$num.'.`user_id` 
                                                       AND memberpress_member'.$num.'.`memberships` '.$like.' '.acym_escapeDB('%'.$options['membership'].'%');
        }

        $query->where['member'] = 'user.`cms_id` > 0';
        $operator = (empty($options['type']) || $options['type'] === 'in') ? 'IS NOT NULL' : 'IS NULL';
        $query->where[] = 'memberpress_sub'.$num.'.`user_id` '.$operator;
    }

    public function onAcymDeclareFilters(&$filters)
    {
        $this->filtersFromConditions($filters);
    }

    public function onAcymProcessFilterCount_memberpress(&$query, $options, $num)
    {
        $this->onAcymProcessFilter_memberpress($query, $options, $num);

        return acym_translationSprintf('ACYM_SELECTED_USERS', $query->count());
    }

    public function onAcymProcessFilter_memberpress(&$query, $options, $num)
    {
        $this->processConditionFilter_memberpress($query, $options, $num);
    }

    public function onAcymDeclareSummary_conditions(&$automationCondition)
    {
        $this->summaryConditionFilters($automationCondition);
    }

    public function onAcymDeclareSummary_filters(&$automationFilter)
    {
        $this->summaryConditionFilters($automationFilter);
    }

    private function summaryConditionFilters(&$automationCondition)
    {
        if (empty($automationCondition['memberpress'])) return;

        if (empty($automationCondition['memberpress']['membership'])) {
            $element = acym_translation('ACYM_ANY_MEMBERSHIP');
        } else {
            $element = acym_loadResult(
                'SELECT post_meta.meta_value FROM #__posts AS post
			 	 JOIN #__postmeta AS post_meta ON post.ID = post_meta.post_id 
			 	 	AND post_meta.meta_key = "_mepr_product_pricing_title" 
			 	 	AND post_meta.post_id = '.intval($automationCondition['memberpress']['membership']).'
				 WHERE post_type = "memberpressproduct" AND post_status = "publish"'
            );
        }


        $activeMemberPress = [
            '' => acym_translation('ACYM_ACTIVATED_OR_DEACTIVATED'),
            1 => acym_translation('ACYM_ACTIVATED'),
            -1 => acym_translation('ACYM_DEACTIVATED'),
        ];

        $statusMemberPress = [
            '' => acym_translation('ACYM_ANY_STATUS'),
            'active' => acym_translation('ACYM_ENABLED'),
            'pending' => acym_translation('ACYM_PENDING'),
            'suspended' => acym_translation('ACYM_SUSPENDED'),
            'cancelled' => acym_translation('ACYM_STOPPED'),
        ];

        $status = $statusMemberPress[$automationCondition['memberpress']['status']];
        $statusActive = $activeMemberPress[$automationCondition['memberpress']['active']];

        $finalText = acym_translationSprintf('ACYM_SUBSCRIBED_INTEGRATION_PLUGIN', $element, $statusActive, $status);

        $dates = [];
        if (!empty($automationCondition['memberpress']['signup_date_inf'])) {
            $dates[] = acym_translation('ACYM_AFTER').' '.acym_replaceDate($automationCondition['memberpress']['signup_date_inf'], true);
        }

        if (!empty($automationCondition['memberpress']['signup_date_sup'])) {
            $dates[] = acym_translation('ACYM_BEFORE').' '.acym_replaceDate($automationCondition['memberpress']['signup_date_sup'], true);
        }

        if (!empty($dates)) {
            $finalText .= '<br />'.acym_translation('ACYM_SUBSCRIPTION_DATE').': '.implode(' '.acym_translation('ACYM_AND').' ', $dates);
        }

        $dates = [];
        if (!empty($automationCondition['memberpress']['expiration_date_inf'])) {
            $dates[] = acym_translation('ACYM_AFTER').' '.acym_replaceDate($automationCondition['memberpress']['expiration_date_inf'], true);
        }

        if (!empty($automationCondition['memberpress']['expiration_date_sup'])) {
            $dates[] = acym_translation('ACYM_BEFORE').' '.acym_replaceDate($automationCondition['memberpress']['expiration_date_sup'], true);
        }

        if (!empty($dates)) {
            $finalText .= '<br />'.acym_translation('ACYM_END_DATE').': '.implode(' '.acym_translation('ACYM_AND').' ', $dates);
        }

        $automationCondition = $finalText;
    }

    public function dynamicText()
    {
        return $this->pluginDescription;
    }

    public function textPopup()
    {
        ?>

		<script language="javascript" type="text/javascript">
            <!--
            function changeMemberPressTag(tagname, element) {
                if (!tagname) return;
                setTag('{<?php echo $this->name; ?>:' + tagname + '}', element);
            }
            -->
		</script>

        <?php
        $fields = $this->getMeprCustomFields();

        if (empty($fields)) {
            echo '<h2 class="cell text-center acym__title__primary__color margin-top-2">'.acym_translationSprintf('ACYM_YOU_DONT_HAVE_PLUGIN_CUSTOM_FIELD', 'MemberPress').'</h2>';

            return;
        }

        $text = '<div class="acym__popup__listing text-center grid-x">';

        foreach ($fields as $key => $field) {
            $text .= '<div style="cursor:pointer" class="grid-x medium-12 cell acym__row__no-listing acym__listing__row__popup text-left" onclick="changeMemberPressTag(\''.$field['field_key'].'\', jQuery(this));" >
                        <div class="cell medium-6 small-12 acym__listing__title acym__listing__title__dynamics">'.$field['field_name'].'</div>
                        <div class="cell medium-6 small-12 acym__listing__title acym__listing__title__dynamics">'.$field['field_type'].'</div>
                     </div>';
        }

        $text .= '</div>';

        echo $text;
    }

    public function replaceUserInformation(&$email, &$user, $send = true)
    {
        $extractedTags = $this->pluginHelper->extractTags($email, $this->name);
        $fields = $this->getMeprCustomFields();
        if (empty($extractedTags)) return;

        $userCMS = empty($user->cms_id) ? [] : get_user_meta($user->cms_id);

        $tags = [];
        foreach ($extractedTags as $key => $tag) {
            if (!empty($tags[$key])) continue;

            if (empty($userCMS[$tag->id])) {
                $finalValue = $fields[$tag->id]['default_value'];
            } else {
                $finalValue = $userCMS[$tag->id];
            }

            if (is_array($finalValue)) $finalValue = $finalValue[0];

            $tags[$key] = $this->handleSerialize($finalValue, $fields[$tag->id]);
        }

        $this->pluginHelper->replaceTags($email, $tags);
    }

    private function handleSerialize($value, $field)
    {
        $valueUnserialize = unserialize($value);

        if ($valueUnserialize !== false && !empty($field['options'])) {
            $finalValue = [];
            foreach ($field['options'] as $option) {
                if (in_array($option['option_value'], $valueUnserialize)) $finalValue[] = $option['option_name'];
            }
            $finalValue = implode(', ', $finalValue);
        } elseif ($valueUnserialize !== false) {
            $finalValue = implode(', ', $valueUnserialize);
        } elseif ($valueUnserialize === false && !empty($field['options'])) {
            $finalValue = [];
            foreach ($field['options'] as $option) {
                if ($option['option_value'] == $value) $finalValue[] = $option['option_name'];
            }
            $finalValue = implode(', ', $finalValue);
        } else {
            $finalValue = $value;
        }

        return $finalValue;
    }

    private function getMeprCustomFields()
    {
        $meprOptions = acym_loadResult('SELECT option_value FROM `wp_options` WHERE option_name = "mepr_options"');

        if (empty($meprOptions)) return [];

        $meprOptions = unserialize($meprOptions);

        if (empty($meprOptions['custom_fields'])) return [];

        $return = [];

        foreach ($meprOptions['custom_fields'] as $custom_field) {
            $return[$custom_field['field_key']] = $custom_field;
        }

        return $return;
    }
}
