<?php

trait TimeAutomationTriggers
{
    protected function initAutomationTriggers(): void
    {
        $this->automationTriggers['classic'] = [
            'asap' => acym_translation('ACYM_EACH_TIME'),
            'day' => acym_translation('ACYM_EVERY_DAY_AT'),
            'weeks_on' => acym_translation('ACYM_EVERY_WEEK_ON'),
            'on_day_month' => acym_translation('ACYM_ONTHE'),
            'every' => acym_translation('ACYM_EVERY'),
        ];
    }

    public function displayTriggerOptions_asap(string $fieldName): void
    {
        echo '<input type="hidden" name="'.acym_escape($fieldName).'" value="y">';
    }

    public function displayTriggerOptions_day(string $fieldName, array $defaultValues): void
    {
        ?>
		<div class="grid-x grid-margin-x" style="height: 40px;">
			<div class="cell medium-shrink">
                <?php
                acym_select(
                    $this->getTriggerHours(),
                    $fieldName.'[hour]',
                    empty($defaultValues['day']) ? acym_date('now', 'H') : $defaultValues['day']['hour'],
                    ['data-class' => 'intext_select acym__select'],
                    'value',
                    'text',
                    null,
                    false,
                    true
                );
                ?>
			</div>
			<div class="cell medium-shrink acym_vcenter">:</div>
			<div class="cell medium-auto">
                <?php
                acym_select(
                    $this->getTriggerMinutes(),
                    $fieldName.'[minutes]',
                    empty($defaultValues['day']) ? acym_date('now', 'i') : $defaultValues['day']['minutes'],
                    ['data-class' => 'intext_select acym__select'],
                    'value',
                    'text',
                    null,
                    false,
                    true
                );
                ?>
			</div>
		</div>
        <?php
    }

    public function displayTriggerOptions_weeks_on(string $fieldName, array $defaultValues): void
    {
        ?>
		<div class="grid-x">
			<div class="cell">
                <?php acym_selectMultiple(
                    $this->getTriggerDays(),
                    $fieldName.'[day]',
                    empty($defaultValues['weeks_on']) ? ['monday'] : $defaultValues['weeks_on']['day'],
                    ['data-class' => 'acym__select'],
                    'value',
                    'text',
                    true
                ); ?>
			</div>
			<div class="cell margin-top-1 acym_vcenter">
                <?php $this->displayTriggerTimeSentence($fieldName, empty($defaultValues['weeks_on']) ? [] : $defaultValues['weeks_on']); ?>
			</div>
		</div>
        <?php
    }

    public function displayTriggerOptions_on_day_month(string $fieldName, array $defaultValues): void
    {
        $numbers = [
            'first' => acym_translation('ACYM_FIRST'),
            'second' => acym_translation('ACYM_SECOND'),
            'third' => acym_translation('ACYM_THIRD'),
            'fourth' => acym_translation('ACYM_FOURTH'),
            'last' => acym_translation('ACYM_LAST'),
        ];
        ?>
		<div class="grid-x grid-margin-x margin-y">
			<div class="cell medium-4">
                <?php acym_select(
                    $numbers,
                    $fieldName.'[number]',
                    empty($defaultValues['on_day_month']) ? null : $defaultValues['on_day_month']['number'],
                    ['data-class' => 'acym__select'],
                    'value',
                    'text',
                    null,
                    false,
                    true
                ); ?>
			</div>
			<div class="cell medium-4">
                <?php acym_select(
                    $this->getTriggerDays(),
                    $fieldName.'[day]',
                    empty($defaultValues['on_day_month']) ? null : $defaultValues['on_day_month']['day'],
                    [
                        'data-class' => 'acym__select',
                        'style' => 'margin: 0 10px;',
                    ],
                    'value',
                    'text',
                    null,
                    false,
                    true
                ); ?>
			</div>
			<div class="cell medium-4 acym_vcenter"><?php echo acym_escapeHtml(acym_translation('ACYM_DAYOFMONTH')); ?></div>
			<div class="cell acym_vcenter">
                <?php $this->displayTriggerTimeSentence($fieldName, empty($defaultValues['on_day_month']) ? [] : $defaultValues['on_day_month']); ?>
			</div>
		</div>
        <?php
    }

    public function displayTriggerOptions_every(string $fieldName, array $defaultValues): void
    {
        $every = [
            '3600' => acym_translation('ACYM_HOURS'),
            '86400' => acym_translation('ACYM_DAYS'),
            '604800' => acym_translation('ACYM_WEEKS'),
            '2628000' => acym_translation('ACYM_MONTHS'),
        ];

        $defaultEvery = empty($defaultValues['every']['number']) ? '1' : $defaultValues['every']['number'];
        ?>
		<div class="grid-x grid-margin-x">
			<div class="cell medium-shrink">
				<input type="number"
				       min="1"
				       name="<?php echo acym_escape($fieldName.'[number]'); ?>"
				       class="intext_input"
				       value="<?php echo intval($defaultEvery); ?>">
			</div>
			<div class="cell medium-auto">
                <?php acym_select(
                    $every,
                    $fieldName.'[type]',
                    empty($defaultValues['every']) ? '604800' : $defaultValues['every']['type'],
                    ['data-class' => 'intext_select acym__select'],
                    'value',
                    'text',
                    null,
                    false,
                    true
                ); ?>
			</div>
		</div>
        <?php
    }

    private function displayTriggerTimeSentence(string $fieldName, array $defaults): void
    {
        $hourSelect = '<div class="margin-left-1 margin-right-1">'.acym_select(
                $this->getTriggerHours(),
                $fieldName.'[hour]',
                $defaults['hour'] ?? $this->config->get('daily_hour', '12'),
                ['data-class' => 'intext_select acym__select']
            ).'</div>';

        $minuteSelect = '<div class="margin-left-1 margin-right-1">'.acym_select(
                $this->getTriggerMinutes(),
                $fieldName.'[minutes]',
                $defaults['minutes'] ?? $this->config->get('daily_minute', '00'),
                ['data-class' => 'intext_select acym__select']
            ).'</div>';

        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Both selects are built and escaped by acym_select just above, and the translation itself holds HTML entities on purpose.
        echo acym_translationSprintf('ACYM_AT_DATE_TIME', $hourSelect, $minuteSelect);
    }

    private function getTriggerHours(): array
    {
        $hours = [];
        for ($i = 0; $i <= 23; $i++) {
            $hours[$i] = $i < 10 ? '0'.$i : $i;
        }

        return $hours;
    }

    private function getTriggerMinutes(): array
    {
        $minutes = [];
        for ($i = 0; $i <= 59; $i++) {
            $minutes[$i] = $i < 10 ? '0'.$i : $i;
        }

        return $minutes;
    }

    private function getTriggerDays(): array
    {
        return [
            'monday' => acym_translation('ACYM_MONDAY'),
            'tuesday' => acym_translation('ACYM_TUESDAY'),
            'wednesday' => acym_translation('ACYM_WEDNESDAY'),
            'thursday' => acym_translation('ACYM_THURSDAY'),
            'friday' => acym_translation('ACYM_FRIDAY'),
            'saturday' => acym_translation('ACYM_SATURDAY'),
            'sunday' => acym_translation('ACYM_SUNDAY'),
        ];
    }

    public function onAcymExecuteTrigger(&$step, &$execute, &$data)
    {
        if (!empty($step->is_scenario)) {
            return;
        }

        $time = $data['time'];
        $triggers = $step->triggers;

        // For each trigger of the automation, we'll calculate the next execution date. In the end we take the closest one
        $nextExecutionDate = [];

        // Get the time the auto tasks should be triggered
        $dailyHour = $this->config->get('daily_hour', '12');
        $dailyMinute = $this->config->get('daily_minute', '00');


        // Each time the cron is triggered
        if (!empty($triggers['asap'])) {
            $execute = true;
            $nextExecutionDate[] = $time;
        }

        // Every day at xx:xx
        if (!empty($triggers['day'])) {
            // The day it is currently based on the timezone specified in the CMS configuration
            $dayBasedOnCMSTimezone = acym_date('now', 'Y-m-d');

            $hour = $triggers['day']['hour'];
            $minutes = $triggers['day']['minutes'];

            if (strlen($hour) < 2) $hour = '0'.$hour;
            if (strlen($minutes) < 2) $minutes = '0'.$minutes;

            // The UTC timestamp of the current day based on the CMS timezone, at the specified hour
            $dayBasedOnCMSTimezoneAtSpecifiedHour = acym_getTimeFromCMSDate($dayBasedOnCMSTimezone.' '.$hour.':'.$minutes);

            if ($time < $dayBasedOnCMSTimezoneAtSpecifiedHour) {
                $nextExecutionDate[] = $dayBasedOnCMSTimezoneAtSpecifiedHour;
            } else {
                $nextExecutionDate[] = $dayBasedOnCMSTimezoneAtSpecifiedHour + 86400;

                // First trigger: if the hour is passed we execute
                if (empty($step->last_execution)) $execute = true;
            }
        }

        // Each week on Mondays and Wednesdays for example
        if (!empty($triggers['weeks_on'])) {
            if (isset($triggers['weeks_on']['hour'])) {
                $hour = $triggers['weeks_on']['hour'];
                $minutes = $triggers['weeks_on']['minutes'];
            } else {
                $hour = $dailyHour;
                $minutes = $dailyMinute;
            }

            if (strlen($hour) < 2) $hour = '0'.$hour;
            if (strlen($minutes) < 2) $minutes = '0'.$minutes;

            foreach ($triggers['weeks_on']['day'] as $day) {
                // The day it is currently based on the timezone specified in the CMS configuration
                $dayBasedOnCMSTimezone = acym_date('now', 'Y-m-d');

                // The UTC timestamp of the current day based on the CMS timezone, at the specified hour
                $dayBasedOnCMSTimezoneAtSpecifiedHour = acym_getTimeFromCMSDate($dayBasedOnCMSTimezone.' '.$hour.':'.$minutes);

                // Only store the next Execution date if it's in the future
                if ($day == strtolower(acym_date('now', 'l', true, false))) {
                    if ($time < $dayBasedOnCMSTimezoneAtSpecifiedHour) {
                        $nextExecutionDate[] = $dayBasedOnCMSTimezoneAtSpecifiedHour;
                    } else {
                        $nextExecutionDate[] = $dayBasedOnCMSTimezoneAtSpecifiedHour + 604800;

                        // Current day is selected, the time is passed, and it's the first trigger
                        $lastExecutionIsNotToday = acym_date($step->last_execution, 'Y-m-d') !== $dayBasedOnCMSTimezone;
                        $nextExecutionIsToday = acym_date($step->next_execution, 'Y-m-d') === $dayBasedOnCMSTimezone;
                        if (empty($step->last_execution) || ($lastExecutionIsNotToday && $nextExecutionIsToday)) $execute = true;
                    }
                } else {
                    $days = [
                        'monday',
                        'tuesday',
                        'wednesday',
                        'thursday',
                        'friday',
                        'saturday',
                        'sunday',
                    ];
                    $currentDayOfWeek = acym_date('now', 'N') - 1;
                    $wantedDayOfWeek = array_search($day, $days);

                    $shift = $wantedDayOfWeek - $currentDayOfWeek;
                    if ($shift < 0) $shift += 7;

                    $nextExecutionDate[] = $dayBasedOnCMSTimezoneAtSpecifiedHour + 86400 * $shift;
                }
            }
        }

        // On first Friday of the month for example
        if (!empty($triggers['on_day_month'])) {
            if (isset($triggers['on_day_month']['hour'])) {
                $hour = $triggers['on_day_month']['hour'];
                $minutes = $triggers['on_day_month']['minutes'];
            } else {
                $hour = $dailyHour;
                $minutes = $dailyMinute;
            }

            if (strlen($hour) < 2) $hour = '0'.$hour;
            if (strlen($minutes) < 2) $minutes = '0'.$minutes;

            $today = acym_getTime('today '.$hour.':'.$minutes);

            // Get the current month's day
            $execution = acym_getTime($triggers['on_day_month']['number'].' '.$triggers['on_day_month']['day'].' of this month '.$hour.':'.$minutes);

            //If it's before today, get the next date
            if ($execution < $today) {
                $execution = acym_getTime($triggers['on_day_month']['number'].' '.$triggers['on_day_month']['day'].' of next month '.$hour.':'.$minutes);
            }

            // The next execution date is in the future
            if ($execution > $time) {
                $nextExecutionDate[] = $execution;
            } else {
                // The next execution is today and is passed


                // If it's the first trigger we execute
                if (empty($step->last_execution)) {
                    $execute = true;
                }

                // Set the next execution time: recompute the real "next weekday of next month" occurrence.
                $nextExecutionDate[] = acym_getTime($triggers['on_day_month']['number'].' '.$triggers['on_day_month']['day'].' of next month '.$hour.':'.$minutes);
            }
        }

        // WARNING : KEEP THIS TRIGGER AT THE END, ACTION PERFORMED IF WE EXECUTE
        // Every X hours/days/weeks/months
        if (!empty($triggers['every'])) {
            // First trigger: we execute and set the next execution in X hours/days/weeks/months
            if (empty($step->last_execution)) {
                $execute = true;
            } else {
                if ($triggers['every']['type'] == 2628000) {
                    $nextDate = new \DateTime(acym_date($step->last_execution, 'Y-m-d H:i:s', false), new \DateTimeZone('UTC'));
                    $nextDate = $nextDate->add(new \DateInterval('P'.$triggers['every']['number'].'M'));
                    $nextDate = $nextDate->getTimestamp();
                } else {
                    $nextDate = $step->last_execution + ($triggers['every']['number'] * $triggers['every']['type']);
                }

                if ($nextDate > $time) {
                    $nextExecutionDate[] = $nextDate;
                } else {
                    $execute = true;
                }
            }

            if ($execute) {
                $nextExecutionDate[] = $time + ($triggers['every']['number'] * $triggers['every']['type']);
            }
        }

        if (!empty($nextExecutionDate)) {
            $step->next_execution = min($nextExecutionDate);
        }
    }

    public function onAcymDeclareSummary_triggers(object $automation): void
    {
        if (!empty($automation->triggers['type_trigger'])) {
            unset($automation->triggers['type_trigger']);
        }

        $days = $this->getTriggerDays();

        $this->summaryAsap($automation);
        $this->summaryDay($automation);
        $this->summaryWeeksOn($automation, $days);
        $this->summaryOnDayMonth($automation, $days);
        $this->summaryEvery($automation);
    }

    private function summaryAsap(object $automation): void
    {
        if (!empty($automation->triggers['asap'])) {
            $automation->triggers['asap'] = acym_translation('ACYM_EACH_TIME');
        }
    }

    private function summaryDay(object $automation): void
    {
        if (empty($automation->triggers['day']) || !is_array($automation->triggers['day'])) {
            return;
        }

        $hour = sprintf('%02d', $automation->triggers['day']['hour']);
        $minutes = sprintf('%02d', $automation->triggers['day']['minutes']);

        $automation->triggers['day'] = acym_translationSprintf('ACYM_TRIGGER_DAY_SUMMARY', $hour, $minutes);
    }

    private function summaryWeeksOn(object $automation, array $days): void
    {
        if (empty($automation->triggers['weeks_on']) || !is_array($automation->triggers['weeks_on'])) {
            return;
        }

        foreach ($automation->triggers['weeks_on']['day'] as $i => $oneDay) {
            $automation->triggers['weeks_on']['day'][$i] = $days[$oneDay];
        }

        if (isset($automation->triggers['weeks_on']['hour'])) {
            $hour = $automation->triggers['weeks_on']['hour'];
            $minutes = $automation->triggers['weeks_on']['minutes'];
        } else {
            $hour = $this->config->get('daily_hour', '12');
            $minutes = $this->config->get('daily_minute', '00');
        }

        $hour = sprintf('%02d', $hour);
        $minutes = sprintf('%02d', $minutes);

        $automation->triggers['weeks_on'] = acym_translationSprintf(
                'ACYM_TRIGGER_WEEKS_ON_SUMMARY',
                implode(', ', $automation->triggers['weeks_on']['day'])
            ).' '.acym_translationSprintf('ACYM_AT_DATE_TIME', $hour, $minutes);
    }

    private function summaryOnDayMonth(object $automation, array $days): void
    {
        if (empty($automation->triggers['on_day_month']) || !is_array($automation->triggers['on_day_month'])) {
            return;
        }

        $numbers = [
            'first' => acym_translation('ACYM_FIRST'),
            'second' => acym_translation('ACYM_SECOND'),
            'third' => acym_translation('ACYM_THIRD'),
            'fourth' => acym_translation('ACYM_FOURTH'),
            'last' => acym_translation('ACYM_LAST'),
        ];

        if (isset($automation->triggers['on_day_month']['hour'])) {
            $hour = $automation->triggers['on_day_month']['hour'];
            $minutes = $automation->triggers['on_day_month']['minutes'];
        } else {
            $hour = $this->config->get('daily_hour', '12');
            $minutes = $this->config->get('daily_minute', '00');
        }

        $hour = sprintf('%02d', $hour);
        $minutes = sprintf('%02d', $minutes);

        $automation->triggers['on_day_month'] = acym_translationSprintf(
                'ACYM_TRIGGER_ON_DAY_MONTH_SUMMARY',
                $numbers[$automation->triggers['on_day_month']['number']],
                $days[$automation->triggers['on_day_month']['day']]
            ).' '.acym_translationSprintf('ACYM_AT_DATE_TIME', $hour, $minutes);
    }

    private function summaryEvery(object $automation): void
    {
        if (empty($automation->triggers['every']) || !is_array($automation->triggers['every'])) {
            return;
        }

        if ($automation->triggers['every']['type'] == 3600) $automation->triggers['every']['type'] = acym_translation('ACYM_HOURS');
        if ($automation->triggers['every']['type'] == 86400) $automation->triggers['every']['type'] = acym_translation('ACYM_DAYS');
        if ($automation->triggers['every']['type'] == 604800) $automation->triggers['every']['type'] = acym_translation('ACYM_WEEKS');
        if ($automation->triggers['every']['type'] == 2628000) $automation->triggers['every']['type'] = acym_translation('ACYM_MONTHS');
        $automation->triggers['every'] = acym_translationSprintf(
            'ACYM_TRIGGER_EVERY_SUMMARY',
            $automation->triggers['every']['number'],
            $automation->triggers['every']['type']
        );
    }
}
