<?php

use AcyMailing\Classes\FollowupClass;
use AcyMailing\Helpers\AutomationHelper;
use AcyMailing\Classes\FieldClass;

trait BirthdayFollowup
{
    private $birthdayTrigger = 'birthday';

    public function getFollowupTriggerBlock(&$blocks)
    {
        $blocks[] = [
            'name' => acym_translation('ACYM_BIRTHDAY'),
            'description' => acym_translation('ACYM_BIRTHDAY_MAIL_FOLLOW_DESC'),
            'icon' => 'acymicon-calendar',
            'link' => acym_completeLink('campaigns&task=edit&step=followupCondition&trigger='.$this->birthdayTrigger),
            'level' => 2,
            'alias' => $this->birthdayTrigger,
        ];
    }

    public function getFollowupTriggers(&$triggers)
    {
        $triggers[$this->birthdayTrigger] = acym_translation('ACYM_BIRTHDAY');
    }

    public function getAcymAdditionalConditionFollowup(&$additionalCondition, $trigger, $followup, $statusArray)
    {
        if ($trigger == $this->birthdayTrigger) {
            $fieldClass = new FieldClass();
            $dateFields = $fieldClass->getFieldsByType(['date']);

            $fields = [];
            foreach ($dateFields as $oneField) {
                $fields[$oneField->id] = acym_translation($oneField->name);
            }
            $fieldsSelect = acym_select(
                $fields,
                'followup[condition][birthday_field]',
                !empty($followup->condition) ? $followup->condition['birthday_field'] : '',
                [
                    'class' => 'acym__select',
                    'required' => true,
                ]
            );
            $fieldsSelect = '<span class="cell large-2 medium-4 margin-left-1">'.$fieldsSelect.'</span>';
            $additionalCondition['birthday_field'] = acym_translationSprintf('ACYM_BIRTHDAY_FIELD', acym_info('ACYM_BIRTHDAY_FIELD_CUSTOM_FIELD_TYPE_DATE').$fieldsSelect);
        }
    }

    public function getFollowupConditionSummary(&$return, $condition, $trigger, $statusArray)
    {
        if ($trigger == $this->birthdayTrigger && !empty($condition['birthday_field'])) {
            $fieldClass = new FieldClass();
            $field = $fieldClass->getOneById($condition['birthday_field']);
            $return[] = acym_translationSprintf('ACYM_BIRTHDAY_FIELD_IS', $field->name);
        }
    }

    public function onAcymGetFollowupDailyBases(&$triggers)
    {
        $triggers[] = $this->birthdayTrigger;
    }

    public function onAcymFollowupDailyBasesNeedToBeTriggered($followup)
    {
        if ($this->birthdayTrigger == $followup->trigger) {
            $automationHelper = new AutomationHelper();

            $fieldClass = new FieldClass();
            $birthdayField = $fieldClass->getOneById($followup->condition['birthday_field']);

            if (empty($birthdayField)) return;

            $dateNowWithTimeZone = acym_date('now', 'Y-m-d h:i:s');
            $dateToCheck = new DateTime($dateNowWithTimeZone);

            $automationHelper->join['birthday_field'] = '#__acym_user_has_field AS uf ON uf.user_id = user.id';
            $automationHelper->where[] = 'uf.field_id = '.$birthdayField->id;
            $automationHelper->where[] = 'uf.value LIKE '.acym_escapeDB('%-'.date_format($dateToCheck, 'm-d'));

            $userIds = acym_loadResultArray($automationHelper->getQuery(['user.id']));

            $followupClass = new FollowupClass();

            foreach ($userIds as $userId) {
                $followupClass->addFollowupEmailsQueue($followup->trigger, $userId);
            }
        }
    }
}
