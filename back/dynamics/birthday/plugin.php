<?php

use AcyMailing\Classes\FollowupClass;
use AcyMailing\Controllers\CampaignsController;
use AcyMailing\Helpers\AutomationHelper;
use AcyMailing\Libraries\acymPlugin;
use AcyMailing\Classes\CampaignClass;
use AcyMailing\Classes\FieldClass;

class plgAcymBirthday extends acymPlugin
{
    const MAILTYPE = 'birthday';
    const FOLLOWTRIGGER = 'birthday';
    protected $dataSources = [];

    public function onAcymDefineUserStatusCheckTriggers(&$triggers)
    {
        $triggers[] = 'on_birthday';
    }

    public function onAcymDeclareTriggers(&$triggers, &$defaultValues)
    {
        $dataSources = [];
        acym_trigger('onAcymDeclareDataSourcesBirthdayTrigger', [&$dataSources]);

        $this->dataSources = $dataSources;

        $sourceOptions = [];

        $defaultSource = empty($defaultValues['on_birthday']['source']) ? 'acymailing' : $defaultValues['on_birthday']['source'];
        $defaultField = empty($defaultValues['on_birthday']['field']) ? '' : $defaultValues['on_birthday']['field'];
        $defaultDayBefore = empty($defaultValues['on_birthday']['day_before']) ? '0' : $defaultValues['on_birthday']['day_before'];
        $defaultBeforeHour = empty($defaultValues['on_birthday']['hour']) ? '12' : $defaultValues['on_birthday']['hour'];
        $defaultBeforeMinutes = empty($defaultValues['on_birthday']['minutes']) ? '00' : $defaultValues['on_birthday']['minutes'];

        foreach ($this->dataSources as $key => $oneSource) {
            $sourceOptions[] = acym_selectOption($key, $oneSource['source_name']);
        }

        $triggers['user']['on_birthday'] = new stdClass();
        $triggers['user']['on_birthday']->name = acym_translation('ACYM_ON_USER_BIRTHDAY');

        $hour = [];
        $minutes = [];
        $i = 0;
        while ($i <= 59) {
            $j = $i < 10 ? '0'.$i : $i;
            if ($i <= 23) {
                $hour[$j] = $j;
            }
            $minutes[$j] = $j;
            $i++;
        }

        $option = '<div class="grid-x grid-margin-x margin-y">
                        <div class="cell grid-x">
                            <div class="cell medium-shrink" style="display: none">
                                '.acym_translation('ACYM_SOURCE').' : ';

        $option .= acym_select($sourceOptions, '[triggers][user][on_birthday][source]', $defaultSource, 'data-class="intext_select acym__select"');
        $option .= ' 
                            </div>
                        </div>';

        if (empty($this->dataSources[$defaultSource]['fields']) && !empty($this->dataSources[$defaultSource]['no_fields_error_message'])) {
            $option .= '<div class="cell grid-x"><span class="cell small-1 vertical-align-middle"><i class="acymicon-exclamation-circle acym__color__orange"></i></span><span class="cell small-11"><b>'.acym_translation(
                    $this->dataSources[$defaultSource]['no_fields_error_message']
                ).'</b></span></div>';
        } else {
            $option .= '<div class="cell grid-x">
                            <div class="cell medium-shrink">
                                '.acym_translation('ACYM_FIELD').' : ';
            $fieldsOption = $this->getFieldsForTable('acymailing');

            $option .= acym_select(
                $fieldsOption,
                '[triggers][user][on_birthday][field]',
                $defaultField,
                'data-class="intext_select acym__select"'
            );

            $hourSelector = acym_select(
                $hour,
                '[triggers][user][on_birthday][hour]',
                $defaultBeforeHour,
                'data-class="intext_select acym__select"'
            );
            $minuteSelector = acym_select(
                $minutes,
                '[triggers][user][on_birthday][minutes]',
                $defaultBeforeMinutes,
                'data-class="intext_select acym__select"'
            );

            $option .= '
                            </div>
                        </div>
                        <div class="cell grid-x">
                            <div class="cell auto word-break acym__automation__trigger__action__birthday">
                            '.acym_translationSprintf(
                    'ACYM_TRIGGER_EVENT_BEFORE_BIRTHDAY',
                    '<input type="number" name="[triggers][user][on_birthday][day_before]" class="intext_input" min="0" value="'.acym_escape($defaultDayBefore).'">',
                    $hourSelector,
                    $minuteSelector
                ).'
                            </div>
                        </div>
                        <span class="cell margin-top-1 acym__color__dark-gray word-break">'.acym_translation('ACYM_BIRTHDAY_TRIGGER_INFO').'</span>';
        }

        $option .= '</div>';

        $triggers['user']['on_birthday']->option = $option;
    }

    public function onAcymExecuteTrigger(&$step, &$execute, &$data)
    {
        if (!empty($step->next_execution) && $step->next_execution > $data['time']) return;

        $triggers = $step->triggers;

        if (empty($triggers['on_birthday'])) return;

        //Values from trigger
        $sourceName = $triggers['on_birthday']['source'];
        $fieldId = $triggers['on_birthday']['field'];
        $dayBefore = $triggers['on_birthday']['day_before'];
        $hour = $triggers['on_birthday']['hour'];
        $minutes = $triggers['on_birthday']['minutes'];

        $dateNowWithTimeZone = acym_date('now', 'Y-m-d h:i:s');
        $now = new DateTime($dateNowWithTimeZone);
        $triggerDate = new DateTime($dateNowWithTimeZone);

        $triggerDate->setTime($hour, $minutes);

        if (!empty($now->date) && !empty($triggerDate->date) && $now->date < $triggerDate->date) {
            $step->next_execution = acym_getTime('today '.$hour.':'.$minutes);

            return;
        } else {
            $step->next_execution = acym_getTime('tomorrow '.$hour.':'.$minutes);
        }

        $dataSources = [];
        acym_trigger('onAcymDeclareDataSourcesBirthdayTrigger', [&$dataSources]);

        $this->dataSources = $dataSources;

        $format = '';
        $query = '';

        if (empty($this->dataSources[$sourceName]['fields'])) return;

        foreach ($this->dataSources[$sourceName]['fields'] as $oneField) {
            if ($oneField['id'] === $fieldId) {
                $format = $oneField['format'];
                $query = $oneField['query'];
            }
        }

        if (empty($format) || empty($query)) return;

        $users = acym_loadObjectList($query);

        if (empty($users)) return;

        foreach ($users as $oneUser) {
            if (empty($oneUser->date)) continue;
            $userBirthday = DateTime::createFromFormat($format, $oneUser->date);
            if (empty($userBirthday)) continue;
            $userBirthday->sub(new DateInterval('P'.$dayBefore.'D'));

            if ($now->format('m-d') === $userBirthday->format('m-d')) {
                $execute = true;
                $data['userIds'][] = $oneUser->user_id;
            }
        }
    }

    protected function getFieldsForTable($dataSource)
    {
        if (empty($this->dataSources[$dataSource]['fields'])) return [];

        $fieldsOption = [];
        foreach ($this->dataSources[$dataSource]['fields'] as $oneField) {
            $fieldsOption[] = acym_selectOption($oneField['id'], $oneField['name']);
        }

        return $fieldsOption;
    }

    public function onAcymDeclareSummary_triggers(&$automation)
    {
        if (!empty($automation->triggers['on_birthday'])) {
            $dataSources = [];
            acym_trigger('onAcymDeclareDataSourcesBirthdayTrigger', [&$dataSources]);

            if (empty($dataSources[$automation->triggers['on_birthday']['source']]['fields']) && !empty($dataSources[$automation->triggers['on_birthday']['source']]['no_fields_error_message'])) {
                $automation->triggers['on_birthday'] = acym_translation($dataSources[$automation->triggers['on_birthday']['source']]['no_fields_error_message']);

                return;
            }

            $fieldToDsiplay = [];
            foreach ($dataSources[$automation->triggers['on_birthday']['source']]['fields'] as $field) {
                if ($field['id'] != $automation->triggers['on_birthday']['field']) continue;
                $fieldToDsiplay = $field;
            }

            $date = empty($automation->triggers['on_birthday']['day_before'])
                ? acym_translation('ACYM_ON_USER_BIRTHDAY')
                : acym_translationSprintf(
                    'ACYM_X_DAYS_BEFORE_BIRTHDAY',
                    $automation->triggers['on_birthday']['day_before']
                );
            $time = acym_translationSprintf('ACYM_AT_DATE_TIME', $automation->triggers['on_birthday']['hour'], $automation->triggers['on_birthday']['minutes']);
            $end = acym_translationSprintf('ACYM_FOR_THE_X_FIELD_X', $dataSources[$automation->triggers['on_birthday']['source']]['source_name'], $fieldToDsiplay['name']);

            $automation->triggers['on_birthday'] = $date.' '.$time.' '.$end;
        }
    }

    public function getNewEmailsTypeBlock(&$extraBlocks)
    {
        if (acym_isAdmin()) {
            $birthdayMailLink = acym_completeLink('campaigns&task=edit&step=chooseTemplate&campaign_type='.self::MAILTYPE);
        } else {
            $birthdayMailLink = acym_frontendLink('frontcampaigns&task=edit&step=chooseTemplate&campaign_type='.self::MAILTYPE);
        }

        $extraBlocks[] = [
            'name' => acym_translation('ACYM_BIRTHDAY'),
            'description' => acym_translation('ACYM_BIRTHDAY_MAIL_DESC'),
            'icon' => 'acymicon-calendar',
            'link' => $birthdayMailLink,
            'level' => 2,
        ];
    }

    public function getCampaignTypes(&$types)
    {
        $types[self::MAILTYPE] = self::MAILTYPE;
    }

    public function getCampaignSpecificSendSettings($type, $sendingParams, &$specificSettings)
    {
        if ($type != self::MAILTYPE) return;

        $defaultNumber = 1;
        if (!empty($sendingParams) && isset($sendingParams[self::MAILTYPE.'_number'])) {
            $defaultNumber = $sendingParams[self::MAILTYPE.'_number'];
        }
        $inputTime = '<input type="number" min="0" stp="1" name="acym_birthday_time_number" class="intext_input" value="'.$defaultNumber.'">';

        $timeSelectOptions = [
            'days' => acym_translation('ACYM_DAYS'),
            'weeks' => acym_translation('ACYM_WEEKS'),
            'months' => acym_translation('ACYM_MONTHS'),
        ];

        $selectedtType = 'days';
        if (!empty($sendingParams) && isset($sendingParams[self::MAILTYPE.'_type'])) {
            $selectedtType = $sendingParams[self::MAILTYPE.'_type'];
        }
        $timeSelect = '<div class="cell medium-2 margin-left-1 margin-right-1">';
        $timeSelect .= acym_select($timeSelectOptions, 'acym_birthday_time_frame', $selectedtType, 'class="acym__select"');
        $timeSelect .= '</div>';

        $timeRelativeOptions = [
            'before' => acym_translation('ACYM_BEFORE'),
            'after' => acym_translation('ACYM_AFTER'),
        ];

        $selectedRelative = 'before';
        if (!empty($sendingParams) && isset($sendingParams[self::MAILTYPE.'_relative'])) {
            $selectedRelative = $sendingParams[self::MAILTYPE.'_relative'];
        }
        $inputRelative = '<div class="cell medium-2 margin-left-1 margin-right-1">';
        $inputRelative .= acym_select($timeRelativeOptions, 'acym_birthday_relative', $selectedRelative, 'class="acym__select"');
        $inputRelative .= '</div>';

        $whenSettings = '<div class="cell grid-x acym_vcenter">';
        $whenSettings .= acym_translationSprintf('ACYM_SEND_IT_BEFORE_USER_BIRTHDAY', $inputTime, $timeSelect, $inputRelative);
        $whenSettings .= '</div>';

        // Birthday field choice
        $fieldClass = new FieldClass();
        $dateFields = $fieldClass->getFieldsByType(['date']);

        $fieldsOptions = [];
        foreach ($dateFields as $oneField) {
            $fieldsOptions[$oneField->id] = acym_translation($oneField->name);
        }
        $selectedField = '';
        if (!empty($sendingParams) && isset($sendingParams[self::MAILTYPE.'_field'])) {
            $selectedField = $sendingParams[self::MAILTYPE.'_field'];
        }
        $inputField = '<div class="cell medium-2 margin-left-1 margin-right-1">';
        $inputField .= acym_select($fieldsOptions, 'acym_birthday_field', $selectedField, 'class="acym__select"');
        $inputField .= '</div><div class="cell medium-8"></div>';

        $additionalSettings = '<div class="cell grid-x acym_vcenter margin-left-3 margin-bottom-1">';
        $additionalSettings .= acym_translationSprintf('ACYM_BIRTHDAY_FIELD', $inputField);
        $additionalSettings .= '</div>';

        $specificSettings[] = [
            'whenSettings' => $whenSettings,
            'additionnalSettings' => $additionalSettings,
        ];
    }

    public function saveCampaignSpecificSendSettings($type, &$specialSendings)
    {
        if ($type != self::MAILTYPE) return;

        $inputTime = acym_getVar('int', 'acym_birthday_time_number', 0);
        $typeTime = acym_getVar('string', 'acym_birthday_time_frame', 'day');
        $relative = acym_getVar('string', 'acym_birthday_relative', 'before');
        $field = acym_getVar('string', 'acym_birthday_field', 0);

        $specialSendings[] = [
            self::MAILTYPE.'_number' => $inputTime,
            self::MAILTYPE.'_type' => $typeTime,
            self::MAILTYPE.'_relative' => $relative,
            self::MAILTYPE.'_field' => $field,
        ];
    }

    public function onAcymSendCampaignSpecial($campaign, &$filters)
    {
        if ($campaign->sending_type != self::MAILTYPE) return;

        $sendingTime = (int)$campaign->sending_params[self::MAILTYPE.'_number'];
        if ($campaign->sending_params[self::MAILTYPE.'_type'] == 'weeks') {
            $sendingTime *= 7;
        } elseif ($campaign->sending_params[self::MAILTYPE.'_type'] == 'months') {
            $sendingTime *= 30;
        }
        $filter = [
            'birthday' => [
                'days' => $sendingTime,
                'field' => $campaign->sending_params[self::MAILTYPE.'_field'],
                'relative' => $campaign->sending_params[self::MAILTYPE.'_relative'],
            ],

        ];
        $filters[] = $filter;
    }

    public function onAcymProcessFilter_birthday(&$query, $options, $num)
    {
        $fieldClass = new FieldClass();
        $birthdayField = $fieldClass->getOneFieldByID($options['field']);

        if (empty($birthdayField)) return;

        $fieldOptions = json_decode($birthdayField->option);
        $tmp = explode('%', $fieldOptions->format);
        $formatArray = [];
        foreach ($tmp as $val) {
            if (empty($val)) continue;
            if ($val == 'y') $val = 'Y';
            $formatArray[] = '%'.$val;
        }
        $format = implode('/', $formatArray);

        $dateNowWithTimeZone = acym_date('now', 'Y-m-d h:i:s');
        $dateToCheck = new DateTime($dateNowWithTimeZone);
        $interval = new DateInterval('P'.intval($options['days']).'D');
        if ($options['relative'] == 'before') {
            $dateToCheck->add($interval);
        } else {
            $dateToCheck->sub($interval);
        }

        $query->join['birthday_field'.$num] = '#__acym_user_has_field AS uf'.$num.' ON uf'.$num.'.user_id = user.id';
        $query->where[] = 'uf'.$num.'.field_id = '.$birthdayField->id;
        $query->where[] = 'MONTH(STR_TO_DATE(uf'.$num.'.value, "'.$format.'")) = MONTH("'.date_format($dateToCheck, 'Y-m-d').'")';
        $query->where[] = 'DAY(STR_TO_DATE(uf'.$num.'.value, "'.$format.'")) = DAY("'.date_format($dateToCheck, 'Y-m-d').'")';
    }

    public function specialActionOnDelete($typeElement, $elements)
    {
        if ($typeElement != 'field') return;
        $campaignClass = new CampaignClass();
        $fieldClass = new FieldClass();
        $birthdayMails = $campaignClass->getCampaignsByTypes([self::MAILTYPE]);
        foreach ($elements as $oneElement) {
            if ($fieldClass->getFieldTypeById($oneElement) !== 'date') continue;
            foreach ($birthdayMails as $oneBirthdayMail) {
                if ($oneBirthdayMail->sending_params['birthday_field'] != $oneElement) continue;
                $oneBirthdayMail->sending_params['birthday_field'] = '';
                $oneBirthdayMail->draft = 1;
                $campaignClass->save($oneBirthdayMail);
            }
        }
    }

    public function onAcymDisplayCampaignListingSpecificTabs(&$tabs)
    {
        $tabs['specificListing&type='.self::MAILTYPE] = 'ACYM_BIRTHDAY_EMAIL';
    }

    public function onAcymSpecificListingActive(&$exists, $task)
    {
        if ($task == self::MAILTYPE) {
            $exists = true;
        }
    }

    public function onAcymCampaignDataSpecificListing(&$data, $type)
    {
        if ($type == self::MAILTYPE) {
            $data['typeWorkflowTab'] = 'specificListing&type='.self::MAILTYPE;
            $data['element_to_display'] = acym_translation('ACYM_BIRTHDAY_EMAIL');
            $campaignController = new CampaignsController();
            $campaignController->prepareEmailsListing($data, $type);
        }
    }

    public function onAcymCampaignAddFiltersSpecificListing(&$filters, $type)
    {
        if ($type == self::MAILTYPE) {
            $filters[] = 'campaign.sending_type = '.acym_escapeDB(self::MAILTYPE);
        }
    }


    public function filterSpecificMailsToSend(&$specialMails, $time)
    {
        $this->filterSpecialMailsDailySend($specialMails, $time, self::MAILTYPE);
    }

    public function getFollowupTriggerBlock(&$blocks)
    {
        $blocks[] = [
            'name' => acym_translation('ACYM_BIRTHDAY'),
            'description' => acym_translation('ACYM_BIRTHDAY_MAIL_FOLLOW_DESC'),
            'icon' => 'acymicon-calendar',
            'link' => acym_completeLink('campaigns&task=edit&step=followupCondition&trigger='.self::FOLLOWTRIGGER),
            'level' => 2,
            'alias' => self::FOLLOWTRIGGER,
        ];
    }

    public function getFollowupTriggers(&$triggers)
    {
        $triggers[self::FOLLOWTRIGGER] = acym_translation('ACYM_BIRTHDAY');
    }

    public function getAcymAdditionalConditionFollowup(&$additionalCondition, $trigger, $followup, $statusArray)
    {
        if ($trigger == self::FOLLOWTRIGGER) {
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
                'class="acym__select" required'
            );
            $fieldsSelect = '<span class="cell large-2 medium-4 margin-left-1">'.$fieldsSelect.'</span>';
            $additionalCondition['birthday_field'] = acym_translationSprintf('ACYM_BIRTHDAY_FIELD', acym_info('ACYM_BIRTHDAY_FIELD_CUSTOM_FIELD_TYPE_DATE').$fieldsSelect);
        }
    }

    public function getFollowupConditionSummary(&$return, $condition, $trigger, $statusArray)
    {
        if ($trigger == self::FOLLOWTRIGGER && !empty($condition['birthday_field'])) {
            $fieldClass = new FieldClass();
            $field = $fieldClass->getOneById($condition['birthday_field']);
            $return[] = acym_translationSprintf('ACYM_BIRTHDAY_FIELD_IS', $field->name);
        }
    }

    public function onAcymGetFollowupDailyBases(&$triggers)
    {
        $triggers[] = self::FOLLOWTRIGGER;
    }

    public function onAcymFollowupDailyBasesNeedToBeTriggered($followup)
    {
        if (self::FOLLOWTRIGGER == $followup->trigger) {
            $automationHelper = new AutomationHelper();

            $fieldClass = new FieldClass();
            $birthdayField = $fieldClass->getOneFieldByID($followup->condition['birthday_field']);

            if (empty($birthdayField)) return;

            $fieldOptions = json_decode($birthdayField->option);
            $tmp = explode('%', $fieldOptions->format);
            $formatArray = [];
            foreach ($tmp as $val) {
                if (empty($val)) continue;
                if ($val == 'y') $val = 'Y';
                $formatArray[] = '%'.$val;
            }
            $format = implode('/', $formatArray);

            $dateNowWithTimeZone = acym_date('now', 'Y-m-d h:i:s');
            $dateToCheck = new DateTime($dateNowWithTimeZone);

            $automationHelper->join['birthday_field'] = '#__acym_user_has_field AS uf ON uf.user_id = user.id';
            $automationHelper->where[] = 'uf.field_id = '.$birthdayField->id;
            $automationHelper->where[] = 'MONTH(STR_TO_DATE(uf.value, "'.$format.'")) = MONTH("'.date_format($dateToCheck, 'Y-m-d').'")';
            $automationHelper->where[] = 'DAY(STR_TO_DATE(uf.value, "'.$format.'")) = DAY("'.date_format($dateToCheck, 'Y-m-d').'")';

            $userIds = acym_loadResultArray($automationHelper->getQuery(['user.id']));

            $followupClass = new FollowupClass();

            foreach ($userIds as $userId) {
                $followupClass->addFollowupEmailsQueue($followup->trigger, $userId);
            }
        }
    }

}
