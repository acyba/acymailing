<?php

use Joomla\CMS\Component\ComponentHelper;

trait JeventsAutomationTriggers
{
    protected function initAutomationTriggers(): void
    {
        $this->automationTriggers['classic']['jevents_reminder'] = acym_translationSprintf(
            'ACYM_COMBINED_TRANSLATIONS',
            'JEvents',
            acym_translation('ACYM_REMINDER')
        );
    }

    public function displayTriggerOptions_jevents_reminder(string $fieldName, array $defaultValues): void
    {
        $params = $this->getTriggerParams();
        $defaults = empty($defaultValues['jevents_reminder']) ? [] : $defaultValues['jevents_reminder'];
        ?>
		<div class="grid-x cell acym_vcenter">
			<div class="grid-x cell grid-margin-x acym_vcenter margin-bottom-1">
				<div class="cell medium-shrink">
					<input type="number"
					       name="<?php echo acym_escape($fieldName.'[number]'); ?>"
					       class="intext_input"
					       value="<?php echo acym_escape(empty($defaults) ? '1' : $defaults['number']); ?>">
				</div>
				<div class="cell medium-shrink">
                    <?php acym_select(
                        $params['every'],
                        $fieldName.'[time]',
                        empty($defaults) ? '86400' : $defaults['time'],
                        ['data-class' => 'intext_select acym__select'],
                        'value',
                        'text',
                        null,
                        false,
                        true
                    ); ?>
				</div>
			</div>
			<div class="grid-x cell grid-margin-x acym_vcenter margin-bottom-1">
				<div class="cell medium-shrink">
                    <?php acym_select(
                        $params['when'],
                        $fieldName.'[when]',
                        empty($defaults) ? 'before' : $defaults['when'],
                        ['data-class' => 'intext_select acym__select'],
                        'value',
                        'text',
                        null,
                        false,
                        true
                    ); ?>
				</div>
				<div class="cell medium-shrink"><?php echo acym_escapeHtml(acym_translation('ACYM_AN_EVENT_IN')); ?></div>
				<div class="cell medium-auto">
                    <?php acym_select(
                        $params['categories'],
                        $fieldName.'[cat]',
                        empty($defaults) ? '' : $defaults['cat'],
                        ['data-class' => 'intext_select_larger intext_select acym__select'],
                        'value',
                        'text',
                        null,
                        false,
                        true
                    ); ?>
				</div>
			</div>
		</div>
        <?php
    }

    public function onAcymExecuteTrigger(&$step, &$execute, &$data)
    {
        $time = $data['time'];
        $triggers = $step->triggers;

        if (!empty($triggers['jevents_reminder']['number'])) {
            $triggerReminder = $triggers['jevents_reminder'];

            $timestamp = ($triggerReminder['number'] * $triggerReminder['time']);

            if ($triggerReminder['when'] == 'before') {
                $timestamp += $time;
            } else {
                $timestamp -= $time;
            }


            $join = [];
            $where = [];

            if (!empty($triggerReminder['cat'])) {
                $multicat = ComponentHelper::getParams('com_jevents')->get('multicategory', 0);
                if ($multicat == 1) {
                    $join[] = 'JOIN #__jevents_catmap AS cats ON rpt.eventid = cats.evid ';
                    $where[] = 'cats.catid = '.intval($triggerReminder['cat']);
                } else {
                    $join[] = 'LEFT JOIN #__jevents_vevent AS event ON `rpt`.`eventid` = `event`.`ev_id`';
                    $where[] = '`event`.`catid` = '.intval($triggerReminder['cat']);
                }
            }
            $join[] = 'LEFT JOIN #__jevents_vevdetail AS eventd ON `rpt`.`eventdetail_id` = `eventd`.`evdet_id`';

            $where[] = '`rpt`.`startrepeat` >= '.acym_escapeDB(acym_date($timestamp, 'Y-m-d H:i:s'));
            $where[] = '`rpt`.`startrepeat` <= '.acym_escapeDB(acym_date($timestamp + $this->config->get('cron_frequency', '900'), 'Y-m-d H:i:s'));
            $where[] = '`eventd`.`state` = 1';

            $events = acym_loadObjectList('SELECT * FROM `#__jevents_repetition` AS rpt '.implode(' ', $join).' WHERE '.implode(' AND ', $where));
            if (!empty($events)) $execute = true;
        }
    }

    public function onAcymDeclareSummary_triggers(object $automation): void
    {
        if (empty($automation->triggers['jevents_reminder']) || !is_array($automation->triggers['jevents_reminder'])) {
            return;
        }

        $params = $this->getTriggerParams();

        $final = $automation->triggers['jevents_reminder']['number'].' ';
        $final .= $params['every'][$automation->triggers['jevents_reminder']['time']].' ';
        $final .= $params['when'][$automation->triggers['jevents_reminder']['when']].' ';
        $final .= acym_translation('ACYM_AN_EVENT_IN').' '.strtolower($params['categories'][$automation->triggers['jevents_reminder']['cat']]);

        $automation->triggers['jevents_reminder'] = $final;
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
        $result['categories'] = acym_loadObjectList('SELECT `id`, `title` FROM #__categories WHERE `extension` = "com_jevents"', 'id');

        foreach ($result['categories'] as $key => $category) {
            $result['categories'][$key] = $category->title;
        }

        $result['categories'] = ['' => acym_translation('ACYM_ANY_CATEGORY')] + $result['categories'];

        return $result;
    }
}
