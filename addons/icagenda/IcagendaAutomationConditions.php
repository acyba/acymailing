<?php

trait IcagendaAutomationConditions
{
    public function onAcymDeclareConditions(&$conditions)
    {
        $conditions['user']['icagenda'] = new stdClass();
        $conditions['user']['icagenda']->name = 'iCagenda';
        $conditions['user']['icagenda']->option = '<div class="cell grid-x grid-margin-x">';

        // Event
        $conditions['user']['icagenda']->option .= '<div class="intext_select_automation cell">';
        $conditions['user']['icagenda']->option .= acym_select(
            [],
            'acym_condition[conditions][__numor__][__numand__][icagenda][event]',
            null,
            [
                'class' => 'acym__select acym_select2_ajax',
                'data-placeholder' => acym_translation('ACYM_ANY_EVENT'),
                'data-params' => [
                    'plugin' => __CLASS__,
                    'trigger' => 'searchEvent',
                ],
            ]
        );
        $conditions['user']['icagenda']->option .= '</div>';

        $conditions['user']['icagenda']->option .= '</div>';

        $conditions['user']['icagenda']->option .= '<div class="cell grid-x grid-margin-x">';
        $conditions['user']['icagenda']->option .= acym_dateField(
            'acym_condition[conditions][__numor__][__numand__][icagenda][datemin]',
            '',
            'cell shrink'
        );
        $conditions['user']['icagenda']->option .= '<span class="acym__title acym__title__secondary acym_vcenter margin-bottom-0 cell shrink"><</span>';
        $conditions['user']['icagenda']->option .= '<span class="acym_vcenter">'.acym_translation('ACYM_SUBSCRIPTION_DATE').'</span>';
        $conditions['user']['icagenda']->option .= '<span class="acym__title acym__title__secondary acym_vcenter margin-bottom-0 cell shrink"><</span>';
        $conditions['user']['icagenda']->option .= acym_dateField(
            'acym_condition[conditions][__numor__][__numand__][icagenda][datemax]',
            '',
            'cell shrink'
        );
        $conditions['user']['icagenda']->option .= '</div>';
    }

    /**
     * Function called with ajax to search in events
     */
    public function searchEvent()
    {
        $id = acym_getVar('int', 'id');
        if (!empty($id)) {
            $subject = acym_loadResult('SELECT `title` FROM #__icagenda_events WHERE `id` = '.intval($id));
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
        $search = acym_getVar('cmd', 'search', '');
        $elements = acym_loadObjectList(
            'SELECT `id`, `title` FROM `#__icagenda_events` WHERE state != -2 AND `title` LIKE '.acym_escapeDB('%'.$search.'%').' ORDER BY `title` ASC'
        );

        foreach ($elements as $oneElement) {
            $return[] = [$oneElement->id, $oneElement->id.' - '.$oneElement->title];
        }

        echo json_encode($return);
        exit;
    }

    public function onAcymProcessCondition_icagenda(&$query, $options, $num, &$conditionNotValid)
    {
        $this->processConditionFilter_icagenda($query, $options, $num);
        $affectedRows = $query->count();
        if (empty($affectedRows)) $conditionNotValid++;
    }

    private function processConditionFilter_icagenda(&$query, $options, $num)
    {
        $query->join['icagenda'.$num] = '`#__icagenda_registration` AS icagenda'.$num.' ON icagenda'.$num.'.email = user.email';

        if (!empty($options['event'])) $query->where[] = 'icagenda'.$num.'.eventid = '.intval($options['event']);

        if (!empty($options['datemin'])) {
            $options['datemin'] = acym_replaceDate($options['datemin']);
            if (!is_numeric($options['datemin'])) $options['datemin'] = strtotime($options['datemin']);
            if (!empty($options['datemin'])) {
                $query->where[] = 'icagenda'.$num.'.modified > '.acym_escapeDB(date('Y-m-d H:i:s', $options['datemin']));
            }
        }

        if (!empty($options['datemax'])) {
            $options['datemax'] = acym_replaceDate($options['datemax']);
            if (!is_numeric($options['datemax'])) $options['datemax'] = strtotime($options['datemax']);
            if (!empty($options['datemax'])) {
                $query->where[] = 'icagenda'.$num.'.modified < '.acym_escapeDB(date('Y-m-d H:i:s', $options['datemax']));
            }
        }
    }

    public function onAcymDeclareSummary_conditions(&$automationCondition)
    {
        $this->summaryConditionFilters($automationCondition);
    }

    private function summaryConditionFilters(&$automationCondition)
    {
        if (!empty($automationCondition['icagenda'])) {
            acym_loadLanguageFile('com_icagenda', JPATH_SITE);

            if (empty($automationCondition['icagenda']['event'])) {
                $event = acym_translation('ACYM_ANY_EVENT');
            } else {
                $event = acym_loadResult('SELECT `title` FROM #__icagenda_events WHERE `id` = '.intval($automationCondition['icagenda']['event']));
            }

            $finalText = acym_translationSprintf('ACYM_REGISTERED_TO', $event);

            $dates = [];
            if (!empty($automationCondition['icagenda']['datemin'])) {
                $dates[] = acym_translation('ACYM_AFTER').' '.acym_replaceDate($automationCondition['icagenda']['datemin'], true);
            }

            if (!empty($automationCondition['icagenda']['datemax'])) {
                $dates[] = acym_translation('ACYM_BEFORE').' '.acym_replaceDate($automationCondition['icagenda']['datemax'], true);
            }

            if (!empty($dates)) {
                $finalText .= ' '.implode(' '.acym_translation('ACYM_AND').' ', $dates);
            }

            $automationCondition = $finalText;
        }
    }
}
