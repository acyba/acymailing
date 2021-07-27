<?php

use AcyMailing\Libraries\acymPlugin;

class plgAcymTime extends acymPlugin
{
    public function __construct()
    {
        parent::__construct();
        $this->pluginDescription->name = acym_translation('ACYM_TIME');
    }

    public function dynamicText($mailId)
    {
        return $this->pluginDescription;
    }

    public function textPopup()
    {
        $text = '<div class="acym__popup__listing text-center grid-x">
                    <h1 class="acym__title acym__title__secondary text-center cell">'.acym_translation('ACYM_TIME_FORMAT').'</h1>';

        $others = [];
        $others['{date:1}'] = 'ACYM_DATE_FORMAT_LC1';
        $others['{date:2}'] = 'ACYM_DATE_FORMAT_LC2';
        $others['{date:3}'] = 'ACYM_DATE_FORMAT_LC3';
        $others['{date:4}'] = 'ACYM_DATE_FORMAT_LC4';
        $others['{date:%m/%d/%Y}'] = '%m/%d/%Y';
        $others['{date:%d/%m/%y}'] = '%d/%m/%y';
        $others['{date:%A}'] = '%A';
        $others['{date:%B}'] = '%B';


        $k = 0;
        foreach ($others as $tagname => $tag) {
            $text .= '<div class="grid-x medium-12 cell acym__row__no-listing acym__listing__row__popup text-left" onclick="setTag(\''.$tagname.'\', jQuery(this));" >
                        <div class="cell medium-6 small-12 acym__listing__title acym__listing__title__dynamics">'.acym_translation($tag).'</div>
                        <div class="cell medium-6 small-12 acym__listing__title acym__listing__title__dynamics">'.acym_getDate(time(), acym_translation($tag)).'</div>
                     </div>';
            $k = 1 - $k;
        }

        $text .= '</div>';

        echo $text;
    }

    public function replaceContent(&$email, $send = true)
    {
        $extractedTags = $this->pluginHelper->extractTags($email, 'date');
        if (empty($extractedTags)) {
            return;
        }

        $tags = [];
        foreach ($extractedTags as $i => $oneTag) {
            if (isset($tags[$i])) {
                continue;
            }

            $time = time();
            if (!empty($oneTag->senddate) && !empty($email->sending_date)) {
                $time = $email->sending_date;
            }
            if (!empty($oneTag->add)) {
                $time += intval($oneTag->add);
            }
            if (!empty($oneTag->remove)) {
                $time -= intval($oneTag->remove);
            }

            if (empty($oneTag->id) || is_numeric($oneTag->id)) {
                $oneTag->id = acym_translation('ACYM_DATE_FORMAT_LC'.$oneTag->id);
            }
            $oneTag->id = str_replace(
                ['%A', '%d', '%B', '%m', '%Y', '%y', '%H', '%M', '%S', '%a', '%I', '%p', '%w'],
                ['l', 'd', 'F', 'm', 'Y', 'y', 'H', 'i', 's', 'D', 'h', 'a', 'w'],
                $oneTag->id
            );
            $tags[$i] = acym_date($time, $oneTag->id, true);
        }

        $this->pluginHelper->replaceTags($email, $tags);
    }

    public function onAcymDeclareTriggers(&$triggers, &$defaultValues)
    {
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
                'data-class="intext_select acym__select"'
            ).'</div>';
        $triggers['classic']['day']->option .= '<div class="cell medium-shrink acym_vcenter">:</div>';
        $triggers['classic']['day']->option .= '<div class="cell medium-auto">'.acym_select(
                $minutes,
                '[triggers][classic][day][minutes]',
                empty($defaultValues['day']) ? date('i') : $defaultValues['day']['minutes'],
                'data-class="intext_select acym__select"'
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
        $triggers['classic']['weeks_on']->option = acym_selectMultiple(
            $days,
            '[triggers][classic][weeks_on][day]',
            empty($defaultValues['weeks_on']) ? ['monday'] : $defaultValues['weeks_on']['day'],
            ['data-class' => 'acym__select']
        );

        $triggers['classic']['on_day_month'] = new stdClass();
        $triggers['classic']['on_day_month']->name = acym_translation('ACYM_ONTHE');
        $triggers['classic']['on_day_month']->option = '<div class="grid-x grid-margin-x" style="height: 40px;">';
        $triggers['classic']['on_day_month']->option .= '<div class="cell medium-4">'.acym_select(
                [
                    'first' => acym_translation('ACYM_FIRST'),
                    'second' => acym_translation('ACYM_SECOND'),
                    'third' => acym_translation('ACYM_THIRD'),
                    'last' => acym_translation('ACYM_LAST'),
                ],
                '[triggers][classic][on_day_month][number]',
                empty($defaultValues['on_day_month']) ? null : $defaultValues['on_day_month']['number'],
                'data-class="acym__select"'
            ).'</div>';
        $triggers['classic']['on_day_month']->option .= '<div class="cell medium-4">'.acym_select(
                $days,
                '[triggers][classic][on_day_month][day]',
                empty($defaultValues['on_day_month']) ? null : $defaultValues['on_day_month']['day'],
                'data-class="acym__select" style="margin: 0 10px;"'
            ).'</div>';
        $triggers['classic']['on_day_month']->option .= '<div class="cell medium-4 acym_vcenter">'.acym_translation('ACYM_DAYOFMONTH').'</div>';
        $triggers['classic']['on_day_month']->option .= '</div>';

        $every = [
            '3600' => acym_translation('ACYM_HOURS'),
            '86400' => acym_translation('ACYM_DAYS'),
            '604800' => acym_translation('ACYM_WEEKS'),
            '2628000' => acym_translation('ACYM_MONTHS'),
        ];

        $triggers['classic']['every'] = new stdClass();
        $triggers['classic']['every']->name = acym_translation('ACYM_EVERY');
        $triggers['classic']['every']->option = '<div class="grid-x grid-margin-x">';
        $triggers['classic']['every']->option .= '<div class="cell medium-shrink"><input type="number" name="[triggers][classic][every][number]" class="intext_input" value="'.(empty($defaultValues['every']) ? '1' : $defaultValues['every']['number']).'"></div>';
        $triggers['classic']['every']->option .= '<div class="cell medium-auto">'.acym_select(
                $every,
                '[triggers][classic][every][type]',
                empty($defaultValues['every']) ? '604800' : $defaultValues['every']['type'],
                'data-class="intext_select acym__select"'
            ).'</div>';
        $triggers['classic']['every']->option .= '</div>';
    }

    public function onAcymExecuteTrigger(&$step, &$execute, &$data)
    {
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

            // The UTC timestamp of the current day based on the CMS timezone, at the specified hour
            $dayBasedOnCMSTimezoneAtSpecifiedHour = acym_getTimeFromCMSDate($dayBasedOnCMSTimezone.' '.$triggers['day']['hour'].':'.$triggers['day']['minutes']);

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
            foreach ($triggers['weeks_on']['day'] as $day) {
                // The day it is currently based on the timezone specified in the CMS configuration
                $dayBasedOnCMSTimezone = acym_date('now', 'Y-m-d');

                // The UTC timestamp of the current day based on the CMS timezone, at the specified hour
                $dayBasedOnCMSTimezoneAtSpecifiedHour = acym_getTimeFromCMSDate($dayBasedOnCMSTimezone.' '.$dailyHour.':'.$dailyMinute);

                // Only store the next Execution date if it's in the future
                if ($day == strtolower(acym_date('now', 'l', true, false))) {
                    if ($time < $dayBasedOnCMSTimezoneAtSpecifiedHour) {
                        $nextExecutionDate[] = $dayBasedOnCMSTimezoneAtSpecifiedHour;
                    } else {
                        $nextExecutionDate[] = $dayBasedOnCMSTimezoneAtSpecifiedHour + 604800;

                        // Current day is selected, the time is passed and it's the first trigger
                        if (empty($step->last_execution) || acym_date($step->last_execution, 'Y-m-d') !== $dayBasedOnCMSTimezone) $execute = true;
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
            $today = acym_getTime('today '.$dailyHour.':'.$dailyMinute);

            // Get the current month's day
            $execution = acym_getTime($triggers['on_day_month']['number'].' '.$triggers['on_day_month']['day'].' of this month '.$dailyHour.':'.$dailyMinute);

            //If it's before today, get the next date
            if ($execution < $today) {
                $execution = acym_getTime($triggers['on_day_month']['number'].' '.$triggers['on_day_month']['day'].' of next month '.$dailyHour.':'.$dailyMinute);
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
                    $nextDate = new \DateTime(acym_date($step->last_execution, 'Y-m-d'), new \DateTimeZone('UTC'));
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

    public function onAcymDeclareSummary_triggers(&$automation)
    {
        if (!empty($automation->triggers['type_trigger'])) unset($automation->triggers['type_trigger']);
        if (!empty($automation->triggers['asap'])) $automation->triggers['asap'] = acym_translation('ACYM_EACH_TIME');
        if (!empty($automation->triggers['day'])) {
            $automation->triggers['day'] = acym_translationSprintf(
                'ACYM_TRIGGER_DAY_SUMMARY',
                $automation->triggers['day']['hour'],
                $automation->triggers['day']['minutes']
            );
        }
        if (!empty($automation->triggers['weeks_on'])) {
            $automation->triggers['weeks_on'] = acym_translationSprintf(
                'ACYM_TRIGGER_WEEKS_ON_SUMMARY',
                implode(', ', $automation->triggers['weeks_on']['day'])
            );
        }
        if (!empty($automation->triggers['on_day_month'])) {
            $automation->triggers['on_day_month'] = acym_translationSprintf(
                'ACYM_TRIGGER_ON_DAY_MONTH_SUMMARY',
                $automation->triggers['on_day_month']['number'],
                $automation->triggers['on_day_month']['day']
            );
        }
        if (!empty($automation->triggers['every'])) {
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
}
