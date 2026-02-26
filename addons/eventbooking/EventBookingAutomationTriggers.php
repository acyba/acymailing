<?php

trait EventBookingAutomationTriggers
{
    public function onAcymDeclareTriggers(&$triggers, &$defaultValues)
    {
        $params = $this->getTriggerParams();

        $triggers['classic']['eventbooking_reminder'] = new stdClass();
        $triggers['classic']['eventbooking_reminder']->name = acym_translationSprintf('ACYM_COMBINED_TRANSLATIONS', 'EventBooking', acym_translation('ACYM_REMINDER'));
        $triggers['classic']['eventbooking_reminder']->option = '<div class="grid-x cell acym_vcenter"><div class="grid-x cell grid-margin-x acym_vcenter margin-bottom-1">';
        $triggers['classic']['eventbooking_reminder']->option .= '<div class="cell medium-shrink">
                                                                <input 
                                                                    type="number" 
                                                                    name="[triggers][classic][eventbooking_reminder][number]" 
                                                                    class="intext_input" 
                                                                    value="'.(empty($defaultValues['eventbooking_reminder']) ? '1'
                : $defaultValues['eventbooking_reminder']['number']).'">
                                                            </div>';
        $triggers['classic']['eventbooking_reminder']->option .= '<div class="cell medium-shrink">'.acym_select(
                $params['every'],
                '[triggers][classic][eventbooking_reminder][time]',
                empty($defaultValues['eventbooking_reminder']) ? '86400' : $defaultValues['eventbooking_reminder']['time'],
                ['data-class' => 'intext_select acym__select']
            ).'</div></div>';
        $triggers['classic']['eventbooking_reminder']->option .= '<div class="grid-x cell grid-margin-x acym_vcenter margin-bottom-1"><div class="cell medium-shrink">'.acym_select(
                $params['when'],
                '[triggers][classic][eventbooking_reminder][when]',
                empty($defaultValues['eventbooking_reminder']) ? 'before' : $defaultValues['eventbooking_reminder']['when'],
                ['data-class' => 'intext_select acym__select']
            ).'</div>';
        $triggers['classic']['eventbooking_reminder']->option .= '<div class="cell medium-shrink">'.acym_translation('ACYM_AN_EVENT_IN').'</div>';
        $triggers['classic']['eventbooking_reminder']->option .= '<div class="cell medium-auto">'.acym_select(
                $params['categories'],
                '[triggers][classic][eventbooking_reminder][cat]',
                empty($defaultValues['eventbooking_reminder']) ? '' : $defaultValues['eventbooking_reminder']['cat'],
                ['data-class' => 'intext_select_larger intext_select acym__select']
            ).'</div>';
        $triggers['classic']['eventbooking_reminder']->option .= '</div></div>';
    }

    public function onAcymExecuteTrigger(&$step, &$execute, &$data)
    {
        $time = $data['time'];
        $triggers = $step->triggers;

        if (!empty($triggers['eventbooking_reminder']['number'])) {
            $triggerReminder = $triggers['eventbooking_reminder'];

            $timestamp = ($triggerReminder['number'] * $triggerReminder['time']);

            if ($triggerReminder['when'] == 'before') {
                $timestamp += $time;
            } else {
                $timestamp -= $time;
            }


            $join = [];
            $where = [];

            if (!empty($triggerReminder['cat'])) {
                $join[] = 'LEFT JOIN #__eb_event_categories as cat ON `event`.`id` = `cat`.`event_id`';
                $where[] = '`cat`.`category_id` = '.intval($triggerReminder['cat']);
            }

            $where[] = '`event`.`event_date` >= '.acym_escapeDB(acym_date($timestamp, 'Y-m-d H:i:s', true));
            $where[] = '`event`.`event_date` <= '.acym_escapeDB(acym_date($timestamp + $this->config->get('cron_frequency', '900'), 'Y-m-d H:i:s', true));
            $where[] = '`event`.`published` = 1';

            $events = acym_loadObjectList('SELECT * FROM `#__eb_events` as event '.implode(' ', $join).' WHERE '.implode(' AND ', $where));
            if (!empty($events)) $execute = true;
        }
    }

    public function onAcymDeclareSummary_triggers(object $automation): void
    {
        if (empty($automation->triggers['eventbooking_reminder']) || !is_array($automation->triggers['eventbooking_reminder'])) {
            return;
        }

        $params = $this->getTriggerParams();

        $final = $automation->triggers['eventbooking_reminder']['number'].' ';
        $final .= $params['every'][$automation->triggers['eventbooking_reminder']['time']].' ';
        $final .= $params['when'][$automation->triggers['eventbooking_reminder']['when']].' ';
        $final .= acym_translation('ACYM_AN_EVENT_IN').' '.strtolower($params['categories'][$automation->triggers['eventbooking_reminder']['cat']]);

        $automation->triggers['eventbooking_reminder'] = $final;
    }

    private function getTriggerParams()
    {
        $result = [];

        $result['every'] = [
            '3600' => acym_translation('ACYM_HOURS'),
            '86400' => acym_translation('ACYM_DAYS'),
        ];

        $result['when'] = [
            'before' => acym_translation('ACYM_BEFORE'),
            'after' => acym_translation('ACYM_AFTER'),
        ];
        $result['categories'] = acym_loadObjectList('SELECT `id`, `name` FROM #__eb_categories', 'id');

        foreach ($result['categories'] as $key => $category) {
            $result['categories'][$key] = $category->name;
        }

        $result['categories'] = ['' => acym_translation('ACYM_ANY_CATEGORY')] + $result['categories'];

        return $result;
    }
}
