<?php

trait BirthdayAutomationTriggers
{
    private $dataSources = [];

    public function onAcymDefineUserStatusCheckTriggers(&$triggers)
    {
        $triggers[] = 'on_birthday';
    }

    protected function initAutomationTriggers(): void
    {
        $this->automationTriggers['user']['on_birthday'] = acym_translation('ACYM_ON_USER_BIRTHDAY');
    }

    public function displayTriggerOptions_on_birthday(string $fieldName, array $defaultValues): void
    {
        $dataSources = [];
        acym_trigger('onAcymDeclareDataSourcesBirthdayTrigger', [&$dataSources]);

        $this->dataSources = $dataSources;

        $sourceOptions = [];
        foreach ($this->dataSources as $key => $oneSource) {
            $sourceOptions[] = acym_selectOption($key, $oneSource['source_name']);
        }

        $defaultSource = empty($defaultValues['on_birthday']['source']) ? 'acymailing' : $defaultValues['on_birthday']['source'];
        $defaultField = empty($defaultValues['on_birthday']['field']) ? '' : $defaultValues['on_birthday']['field'];
        $defaultDayBefore = empty($defaultValues['on_birthday']['day_before']) ? '0' : $defaultValues['on_birthday']['day_before'];
        $defaultBeforeHour = empty($defaultValues['on_birthday']['hour']) ? '12' : $defaultValues['on_birthday']['hour'];
        $defaultBeforeMinutes = empty($defaultValues['on_birthday']['minutes']) ? '00' : $defaultValues['on_birthday']['minutes'];

        $hasFields = !empty($this->dataSources[$defaultSource]['fields']);
        $noFieldsErrorMessage = empty($this->dataSources[$defaultSource]['no_fields_error_message'])
            ? ''
            : $this->dataSources[$defaultSource]['no_fields_error_message'];
        ?>
		<div class="grid-x grid-margin-x grid-margin-y">
			<div class="cell grid-x">
				<div class="cell medium-shrink" style="display: none">
                    <?php echo acym_escapeHtml(acym_translation('ACYM_SOURCE')); ?> :
                    <?php acym_select(
                        $sourceOptions,
                        $fieldName.'[source]',
                        $defaultSource,
                        ['data-class' => 'intext_select acym__select'],
                        'value',
                        'text',
                        null,
                        false,
                        true
                    ); ?>
				</div>
			</div>
            <?php if (!$hasFields && !empty($noFieldsErrorMessage)) { ?>
				<div class="cell grid-x">
					<span class="cell small-1 vertical-align-middle"><i class="acymicon-exclamation-circle acym__color__orange"></i></span>
					<span class="cell small-11"><b><?php echo acym_escapeHtml(acym_translation($noFieldsErrorMessage)); ?></b></span>
				</div>
            <?php } else { ?>
				<div class="cell grid-x">
					<div class="cell medium-shrink">
                        <?php echo acym_escapeHtml(acym_translation('ACYM_FIELD')); ?> :
                        <?php acym_select(
                            $this->getFieldsForTable('acymailing'),
                            $fieldName.'[field]',
                            $defaultField,
                            ['data-class' => 'intext_select acym__select'],
                            'value',
                            'text',
                            null,
                            false,
                            true
                        ); ?>
					</div>
				</div>
				<div class="cell grid-x">
					<div class="cell auto word-break acym__automation__trigger__action__birthday">
                        <?php $this->displayBirthdayDelaySentence($fieldName, $defaultDayBefore, $defaultBeforeHour, $defaultBeforeMinutes); ?>
					</div>
				</div>
				<span class="cell margin-top-1 acym__color__dark-gray word-break">
                    <?php echo acym_escapeHtml(acym_translation('ACYM_BIRTHDAY_TRIGGER_INFO')); ?>
				</span>
            <?php } ?>
		</div>
        <?php
    }

    /**
     * "Trigger X day(s) before the date at HH:MM", where the position of the three fields inside the
     * sentence depends on the language, so the sentence cannot be split in escaped parts
     */
    private function displayBirthdayDelaySentence(string $fieldName, string $dayBefore, string $hour, string $minutes): void
    {
        $hours = [];
        $minutesOptions = [];
        $i = 0;
        while ($i <= 59) {
            $j = $i < 10 ? '0'.$i : $i;
            if ($i <= 23) {
                $hours[$j] = $j;
            }
            $minutesOptions[$j] = $j;
            $i++;
        }

        $dayBeforeInput = '<input type="number" name="'.acym_escape($fieldName.'[day_before]').'" class="intext_input" min="0" value="'.acym_escape($dayBefore).'">';

        $hourSelect = acym_select(
            $hours,
            $fieldName.'[hour]',
            $hour,
            ['data-class' => 'intext_select acym__select']
        );

        $minuteSelect = acym_select(
            $minutesOptions,
            $fieldName.'[minutes]',
            $minutes,
            ['data-class' => 'intext_select acym__select']
        );

        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- The three fields are built and escaped just above, and the translation itself holds HTML entities on purpose.
        echo acym_translationSprintf('ACYM_TRIGGER_EVENT_BEFORE_BIRTHDAY', $dayBeforeInput, $hourSelect, $minuteSelect);
    }

    public function onAcymExecuteTrigger(&$step, &$execute, &$data)
    {
        if (!empty($step->next_execution) && $step->next_execution > $data['time']) {
            return;
        }

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

    public function onAcymDeclareSummary_triggers(object $automation): void
    {
        if (empty($automation->triggers['on_birthday']['source'])) {
            return;
        }

        $dataSources = [];
        acym_trigger('onAcymDeclareDataSourcesBirthdayTrigger', [&$dataSources]);

        if (
            empty($dataSources[$automation->triggers['on_birthday']['source']]['fields'])
            && !empty($dataSources[$automation->triggers['on_birthday']['source']]['no_fields_error_message'])
        ) {
            $automation->triggers['on_birthday'] = acym_translation($dataSources[$automation->triggers['on_birthday']['source']]['no_fields_error_message']);

            return;
        }

        $fieldToDisplay = [];
        foreach ($dataSources[$automation->triggers['on_birthday']['source']]['fields'] as $field) {
            if ($field['id'] != $automation->triggers['on_birthday']['field']) continue;
            $fieldToDisplay = $field;
        }

        $date = empty($automation->triggers['on_birthday']['day_before'])
            ? acym_translation('ACYM_ON_USER_BIRTHDAY')
            : acym_translationSprintf(
                'ACYM_X_DAYS_BEFORE_BIRTHDAY',
                $automation->triggers['on_birthday']['day_before']
            );
        $time = acym_translationSprintf('ACYM_AT_DATE_TIME', $automation->triggers['on_birthday']['hour'], $automation->triggers['on_birthday']['minutes']);
        $end = acym_translationSprintf('ACYM_FOR_THE_X_FIELD_X', $dataSources[$automation->triggers['on_birthday']['source']]['source_name'], $fieldToDisplay['name']);

        $automation->triggers['on_birthday'] = $date.' '.$time.' '.$end;
    }
}
