<?php

trait BirthdayAutomationTriggers
{
    private $dataSources = [];

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

        $option .= acym_select(
            $sourceOptions,
            '[triggers][user][on_birthday][source]',
            $defaultSource,
            ['data-class' => 'intext_select acym__select']
        );
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
                ['data-class' => 'intext_select acym__select']
            );

            $hourSelector = acym_select(
                $hour,
                '[triggers][user][on_birthday][hour]',
                $defaultBeforeHour,
                ['data-class' => 'intext_select acym__select']
            );
            $minuteSelector = acym_select(
                $minutes,
                '[triggers][user][on_birthday][minutes]',
                $defaultBeforeMinutes,
                ['data-class' => 'intext_select acym__select']
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

    protected function getFieldsForTable($dataSource): array
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
}
