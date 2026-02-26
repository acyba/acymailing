<?php

trait TimeAutomationTriggers
{
    public function onAcymDeclareTriggers(&$triggers, &$defaultValues)
    {
        $dailyHour = $this->config->get('daily_hour', '12');
        $dailyMinute = $this->config->get('daily_minute', '00');

        $triggers['classic']['asap'] = new stdClass();
        $triggers['classic']['asap']->name = acym_translation('ACYM_EACH_TIME');
        $triggers['classic']['asap']->option = '<input type="hidden" name="[triggers][classic][asap]" value="y">';

        $hour = [];
        $minutes = [];
        $i = 0;
        while ($i <= 59) {
            if ($i <= 23) {
                $hour[$i] = $i < 10 ? '0'.$i : $i;
            }
            $minutes[$i] = $i < 10 ? '0'.$i : $i;
            $i++;
        }


        $triggers['classic']['day'] = new stdClass();
        $triggers['classic']['day']->name = acym_translation('ACYM_EVERY_DAY_AT');
        $triggers['classic']['day']->option = '<div class="grid-x grid-margin-x" style="height: 40px;">';
        $triggers['classic']['day']->option .= '<div class="cell medium-shrink">'.acym_select(
                $hour,
                '[triggers][classic][day][hour]',
                empty($defaultValues['day']) ? date('H') : $defaultValues['day']['hour'],
                ['data-class' => 'intext_select acym__select']
            ).'</div>';
        $triggers['classic']['day']->option .= '<div class="cell medium-shrink acym_vcenter">:</div>';
        $triggers['classic']['day']->option .= '<div class="cell medium-auto">'.acym_select(
                $minutes,
                '[triggers][classic][day][minutes]',
                empty($defaultValues['day']) ? date('i') : $defaultValues['day']['minutes'],
                ['data-class' => 'intext_select acym__select']
            ).'</div>';
        $triggers['classic']['day']->option .= '</div>';

        $days = [
            'monday' => acym_translation('ACYM_MONDAY'),
            'tuesday' => acym_translation('ACYM_TUESDAY'),
            'wednesday' => acym_translation('ACYM_WEDNESDAY'),
            'thursday' => acym_translation('ACYM_THURSDAY'),
            'friday' => acym_translation('ACYM_FRIDAY'),
            'saturday' => acym_translation('ACYM_SATURDAY'),
            'sunday' => acym_translation('ACYM_SUNDAY'),
        ];

        $triggers['classic']['weeks_on'] = new stdClass();
        $triggers['classic']['weeks_on']->name = acym_translation('ACYM_EVERY_WEEK_ON');
        $triggers['classic']['weeks_on']->option = '<div class="grid-x">';
        $triggers['classic']['weeks_on']->option .= '<div class="cell">'.acym_selectMultiple(
                $days,
                '[triggers][classic][weeks_on][day]',
                empty($defaultValues['weeks_on']) ? ['monday'] : $defaultValues['weeks_on']['day'],
                ['data-class' => 'acym__select']
            ).'</div>';
        $triggers['classic']['weeks_on']->option .= '<div class="cell margin-top-1 acym_vcenter">';
        $triggers['classic']['weeks_on']->option .= acym_translationSprintf(
            'ACYM_AT_DATE_TIME',
            '<div class="margin-left-1 margin-right-1">'.acym_select(
                $hour,
                '[triggers][classic][weeks_on][hour]',
                !isset($defaultValues['weeks_on']['hour']) ? $dailyHour : $defaultValues['weeks_on']['hour'],
                ['data-class' => 'intext_select acym__select']
            ).'</div>',
            '<div class="margin-left-1 margin-right-1">'.acym_select(
                $minutes,
                '[triggers][classic][weeks_on][minutes]',
                !isset($defaultValues['weeks_on']['minutes']) ? $dailyMinute : $defaultValues['weeks_on']['minutes'],
                ['data-class' => 'intext_select acym__select']
            ).'</div>'
        );
        $triggers['classic']['weeks_on']->option .= '</div>';
        $triggers['classic']['weeks_on']->option .= '</div>';


        $triggers['classic']['on_day_month'] = new stdClass();
        $triggers['classic']['on_day_month']->name = acym_translation('ACYM_ONTHE');
        $triggers['classic']['on_day_month']->option = '<div class="grid-x grid-margin-x margin-y">';
        $triggers['classic']['on_day_month']->option .= '<div class="cell medium-4">'.acym_select(
                [
                    'first' => acym_translation('ACYM_FIRST'),
                    'second' => acym_translation('ACYM_SECOND'),
                    'third' => acym_translation('ACYM_THIRD'),
                    'fourth' => acym_translation('ACYM_FOURTH'),
                    'last' => acym_translation('ACYM_LAST'),
                ],
                '[triggers][classic][on_day_month][number]',
                empty($defaultValues['on_day_month']) ? null : $defaultValues['on_day_month']['number'],
                ['data-class' => 'acym__select']
            ).'</div>';
        $triggers['classic']['on_day_month']->option .= '<div class="cell medium-4">'.acym_select(
                $days,
                '[triggers][classic][on_day_month][day]',
                empty($defaultValues['on_day_month']) ? null : $defaultValues['on_day_month']['day'],
                [
                    'data-class' => 'acym__select',
                    'style' => 'margin: 0 10px;',
                ]
            ).'</div>';
        $triggers['classic']['on_day_month']->option .= '<div class="cell medium-4 acym_vcenter">'.acym_translation('ACYM_DAYOFMONTH').'</div>';
        $triggers['classic']['on_day_month']->option .= '<div class="cell acym_vcenter">';
        $triggers['classic']['on_day_month']->option .= acym_translationSprintf(
            'ACYM_AT_DATE_TIME',
            '<div class="margin-left-1 margin-right-1">'.acym_select(
                $hour,
                '[triggers][classic][on_day_month][hour]',
                !isset($defaultValues['on_day_month']['hour']) ? $dailyHour : $defaultValues['on_day_month']['hour'],
                ['data-class' => 'intext_select acym__select']
            ).'</div>',
            '<div class="margin-left-1 margin-right-1">'.acym_select(
                $minutes,
                '[triggers][classic][on_day_month][minutes]',
                !isset($defaultValues['on_day_month']['minutes']) ? $dailyMinute : $defaultValues['on_day_month']['minutes'],
                ['data-class' => 'intext_select acym__select']
            ).'</div>'
        );
        $triggers['classic']['on_day_month']->option .= '</div>';
        $triggers['classic']['on_day_month']->option .= '</div>';


        $every = [
            '3600' => acym_translation('ACYM_HOURS'),
            '86400' => acym_translation('ACYM_DAYS'),
            '604800' => acym_translation('ACYM_WEEKS'),
            '2628000' => acym_translation('ACYM_MONTHS'),
        ];

        $defaultEvery = empty($defaultValues['every']['number']) ? '1' : $defaultValues['every']['number'];
        $triggers['classic']['every'] = new stdClass();
        $triggers['classic']['every']->name = acym_translation('ACYM_EVERY');
        $triggers['classic']['every']->option = '<div class="grid-x grid-margin-x">';
        $triggers['classic']['every']->option .= '<div class="cell medium-shrink">';
        $triggers['classic']['every']->option .= '<input type="number" min="1" name="[triggers][classic][every][number]" class="intext_input" value="'.intval($defaultEvery).'">';
        $triggers['classic']['every']->option .= '</div>';
        $triggers['classic']['every']->option .= '<div class="cell medium-auto">'.acym_select(
                $every,
                '[triggers][classic][every][type]',
                empty($defaultValues['every']) ? '604800' : $defaultValues['every']['type'],
                ['data-class' => 'intext_select acym__select']
            ).'</div>';
        $triggers['classic']['every']->option .= '</div>';
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

                // Set the next execution time
                $nextExecutionDate[] = $execution + 2628000;
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
                    $nextDate = new \DateTime(acym_date($step->last_execution, 'Y-m-d H:m:i', false), new \DateTimeZone('UTC'));
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

        $days = [
            'monday' => acym_translation('ACYM_MONDAY'),
            'tuesday' => acym_translation('ACYM_TUESDAY'),
            'wednesday' => acym_translation('ACYM_WEDNESDAY'),
            'thursday' => acym_translation('ACYM_THURSDAY'),
            'friday' => acym_translation('ACYM_FRIDAY'),
            'saturday' => acym_translation('ACYM_SATURDAY'),
            'sunday' => acym_translation('ACYM_SUNDAY'),
        ];

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
