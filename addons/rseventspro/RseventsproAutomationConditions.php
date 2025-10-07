<?php

trait RseventsproAutomationConditions
{
    public function onAcymDeclareConditions(&$conditions)
    {
        acym_loadLanguageFile('com_rseventspro', JPATH_SITE);

        $conditions['user']['rseventspro'] = new stdClass();
        $conditions['user']['rseventspro']->name = 'RSEvents!Pro';
        $conditions['user']['rseventspro']->option = '<div class="cell grid-x grid-margin-x">';

        // Status
        $status = [];
        $status[] = acym_selectOption('0', 'ACYM_ANY_STATUS');
        $status[] = acym_selectOption('1', 'COM_RSEVENTSPRO_RULE_STATUS_INCOMPLETE');
        $status[] = acym_selectOption('2', 'COM_RSEVENTSPRO_RULE_STATUS_COMPLETE');
        $status[] = acym_selectOption('3', 'COM_RSEVENTSPRO_RULE_STATUS_DENIED');

        $conditions['user']['rseventspro']->option .= '<div class="intext_select_automation cell">';
        $conditions['user']['rseventspro']->option .= acym_select(
            $status,
            'acym_condition[conditions][__numor__][__numand__][rseventspro][status]',
            '0',
            ['class' => 'acym__select']
        );
        $conditions['user']['rseventspro']->option .= '</div>';

        // Event
        $conditions['user']['rseventspro']->option .= '<div class="intext_select_automation cell">';
        $conditions['user']['rseventspro']->option .= acym_select(
            [],
            'acym_condition[conditions][__numor__][__numand__][rseventspro][event]',
            null,
            [
                'class' => 'acym__select acym_select2_ajax',
                'data-placeholder' => acym_translation('ACYM_ANY_EVENT'),
                'data-params' => [
                    'plugin' => __CLASS__,
                    'trigger' => 'searchEvent',
                ],
                'acym-automation-reload' => [
                    'plugin' => __CLASS__,
                    'trigger' => 'getTicketsSelection',
                    'change' => '#rseventspro_tochange___numor_____numand__',
                    'name' => 'acym_condition[conditions][__numor__][__numand__][rseventspro][ticket]',
                    'paramFields' => [
                        'event' => 'acym_condition[conditions][__numor__][__numand__][rseventspro][event]',
                    ],
                ],
            ]
        );
        $conditions['user']['rseventspro']->option .= '</div>';

        // Ticket
        $conditions['user']['rseventspro']->option .= '<div class="intext_select_automation cell" id="rseventspro_tochange___numor_____numand__">';
        $conditions['user']['rseventspro']->option .= '<input type="hidden" name="acym_condition[conditions][__numor__][__numand__][rseventspro][ticket]" />';
        $conditions['user']['rseventspro']->option .= '</div>';

        $conditions['user']['rseventspro']->option .= '</div>';

        $conditions['user']['rseventspro']->option .= '<div class="cell grid-x grid-margin-x">';
        $conditions['user']['rseventspro']->option .= acym_dateField('acym_condition[conditions][__numor__][__numand__][rseventspro][datemin]', '', 'cell shrink');
        $conditions['user']['rseventspro']->option .= '<span class="acym__title acym__title__secondary acym_vcenter margin-bottom-0 cell shrink"><</span>';
        $conditions['user']['rseventspro']->option .= '<span class="acym_vcenter">'.acym_translation('COM_RSEVENTSPRO_MY_SUBSCRIPTION_DATE').'</span>';
        $conditions['user']['rseventspro']->option .= '<span class="acym__title acym__title__secondary acym_vcenter margin-bottom-0 cell shrink"><</span>';
        $conditions['user']['rseventspro']->option .= acym_dateField('acym_condition[conditions][__numor__][__numand__][rseventspro][datemax]', '', 'cell shrink');
        $conditions['user']['rseventspro']->option .= '</div>';
    }

    public function onAcymDeclareConditionsScenario(&$conditions)
    {
        $this->onAcymDeclareConditions($conditions);
    }

    /**
     * Function called with ajax to search in events
     */
    public function searchEvent()
    {
        $id = acym_getVar('int', 'id');
        if (!empty($id)) {
            $subject = acym_loadResult('SELECT `name` FROM #__rseventspro_events WHERE `id` = '.intval($id));
            if (empty($subject)) $subject = '';
            echo json_encode(
                [
                    [
                        'value' => $id,
                        'text' => $id.' - '.$subject,
                    ],
                ]
            );
            exit;
        }

        $return = [];
        $search = acym_getVar('string', 'search', '');
        $elements = acym_loadObjectList('SELECT `id`, `name` FROM `#__rseventspro_events` WHERE `name` LIKE '.acym_escapeDB('%'.$search.'%').' ORDER BY `name` ASC');

        foreach ($elements as $oneElement) {
            $return[] = [$oneElement->id, $oneElement->id.' - '.$oneElement->name];
        }

        echo json_encode($return);
        exit;
    }

    public function getTicketsSelection()
    {
        acym_loadLanguageFile('com_rseventspro', JPATH_SITE);

        $id = acym_getVar('int', 'event', 0);
        if (empty($id)) exit;

        $elements = acym_loadObjectList('SELECT `id`, `name` FROM `#__rseventspro_tickets` WHERE `ide` = '.intval($id).' ORDER BY `name` ASC');

        $options = [];
        $options[0] = acym_translation('COM_RSEVENTSPRO_SUBSCRIBER_SELECT_TICKETS');
        foreach ($elements as $oneElement) {
            $options[$oneElement->id] = $oneElement->name;
        }

        echo acym_select(
            $options,
            acym_getVar('string', 'name', ''),
            acym_getVar('int', 'value', 0),
            [
                'class' => 'acym__select',
            ]
        );
        exit;
    }

    public function onAcymProcessCondition_rseventspro(&$query, $options, $num, &$conditionNotValid)
    {
        $this->processConditionFilter_rseventspro($query, $options, $num);
        $affectedRows = $query->count();
        if (empty($affectedRows)) $conditionNotValid++;
    }

    private function processConditionFilter_rseventspro(&$query, $options, $num)
    {
        $query->join['rseventspro'.$num] = '`#__rseventspro_users` AS rseventspro'.$num.' ON rseventspro'.$num.'.email COLLATE utf8mb4_unicode_ci = user.email COLLATE utf8mb4_unicode_ci';

        if (!empty($options['status'])) $query->where[] = 'rseventspro'.$num.'.state = '.(intval($options['status']) - 1);
        if (!empty($options['event'])) $query->where[] = 'rseventspro'.$num.'.ide = '.intval($options['event']);
        if (!empty($options['ticket'])) {
            $query->join['rsticket'.$num] = '`#__rseventspro_user_tickets` AS rsticket'.$num.' ON rseventspro'.$num.'.id = rsticket'.$num.'.ids';
            $query->where[] = 'rsticket'.$num.'.idt = '.intval($options['ticket']);
        }

        if (!empty($options['datemin'])) {
            $options['datemin'] = acym_replaceDate($options['datemin']);
            if (!is_numeric($options['datemin'])) $options['datemin'] = strtotime($options['datemin']);
            if (!empty($options['datemin'])) {
                $query->where[] = 'rseventspro'.$num.'.date > '.acym_escapeDB(date('Y-m-d H:i:s', $options['datemin']));
            }
        }

        if (!empty($options['datemax'])) {
            $options['datemax'] = acym_replaceDate($options['datemax']);
            if (!is_numeric($options['datemax'])) $options['datemax'] = strtotime($options['datemax']);
            if (!empty($options['datemax'])) {
                $query->where[] = 'rseventspro'.$num.'.date < '.acym_escapeDB(date('Y-m-d H:i:s', $options['datemax']));
            }
        }
    }

    public function onAcymDeclareSummary_conditions(&$automationCondition)
    {
        $this->summaryConditionFilters($automationCondition);
    }

    private function summaryConditionFilters(&$automationCondition)
    {
        if (!empty($automationCondition['rseventspro'])) {
            acym_loadLanguageFile('com_rseventspro', JPATH_SITE);

            if (empty($automationCondition['rseventspro']['event'])) {
                $event = acym_translation('ACYM_ANY_EVENT');
            } else {
                $event = acym_loadResult('SELECT `name` FROM #__rseventspro_events WHERE `id` = '.intval($automationCondition['rseventspro']['event']));
            }

            $status = [
                '0' => 'ACYM_ANY',
                '1' => 'COM_RSEVENTSPRO_RULE_STATUS_INCOMPLETE',
                '2' => 'COM_RSEVENTSPRO_RULE_STATUS_COMPLETE',
                '3' => 'COM_RSEVENTSPRO_RULE_STATUS_DENIED',
            ];

            $status = acym_translation($status[$automationCondition['rseventspro']['status']]);

            $finalText = acym_translationSprintf('ACYM_REGISTERED', $event, $status);

            $dates = [];
            if (!empty($automationCondition['rseventspro']['datemin'])) {
                $dates[] = acym_translation('ACYM_AFTER').' '.acym_replaceDate($automationCondition['rseventspro']['datemin'], true);
            }

            if (!empty($automationCondition['rseventspro']['datemax'])) {
                $dates[] = acym_translation('ACYM_BEFORE').' '.acym_replaceDate($automationCondition['rseventspro']['datemax'], true);
            }

            if (!empty($dates)) {
                $finalText .= ' '.implode(' '.acym_translation('ACYM_AND').' ', $dates);
            }

            $automationCondition = $finalText;
        }
    }
}
