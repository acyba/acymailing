<?php

trait JeventsAutomationConditions
{
    public function onAcymDeclareConditions(&$conditions)
    {
        if (!file_exists(JPATH_SITE.DS.'components'.DS.'com_rsvppro')) return;
        acym_loadLanguageFile('com_rsvppro', JPATH_ADMINISTRATOR);

        $conditions['user']['jeventsregistration'] = new stdClass();
        $conditions['user']['jeventsregistration']->name = acym_translationSprintf('ACYM_COMBINED_TRANSLATIONS', 'JEvents', 'RSVP');
        $conditions['user']['jeventsregistration']->option = '<div class="cell grid-x grid-margin-x">';

        $conditions['user']['jeventsregistration']->option .= '<div class="intext_select_automation cell">';
        $ajaxParams = json_encode(
            [
                'plugin' => __CLASS__,
                'trigger' => 'searchEvent',
            ]
        );
        $conditions['user']['jeventsregistration']->option .= acym_select(
            [],
            'acym_condition[conditions][__numor__][__numand__][jeventsregistration][event]',
            null,
            [
                'class' => 'acym__select acym_select2_ajax',
                'data-placeholder' => acym_translation('ACYM_ANY_EVENT'),
                'data-params' => $ajaxParams,
            ]
        );
        $conditions['user']['jeventsregistration']->option .= '</div>';

        $status = [];
        $status[] = acym_selectOption('-1', 'RSVP_ALL_REGISTERED_USERS');
        $status[] = acym_selectOption('1', 'RSVP_IS_CONFIRMED');
        $status[] = acym_selectOption('0', 'RSVP_PENDING');

        $conditions['user']['jeventsregistration']->option .= '<div class="intext_select_automation cell">';
        $conditions['user']['jeventsregistration']->option .= acym_select(
            $status,
            'acym_condition[conditions][__numor__][__numand__][jeventsregistration][status]',
            '-1',
            ['class' => 'acym__select']
        );
        $conditions['user']['jeventsregistration']->option .= '</div>';

        $conditions['user']['jeventsregistration']->option .= '</div>';

        $conditions['user']['jeventsregistration']->option .= '<div class="cell grid-x grid-margin-x">';
        $conditions['user']['jeventsregistration']->option .= acym_dateField('acym_condition[conditions][__numor__][__numand__][jeventsregistration][datemin]', '', 'cell shrink');
        $conditions['user']['jeventsregistration']->option .= '<span class="acym__title acym__title__secondary acym_vcenter margin-bottom-0 cell shrink"><</span>';
        $conditions['user']['jeventsregistration']->option .= '<span class="acym_vcenter">'.acym_translation('RSVP_EVENT_REGISTRATION_DATE').'</span>';
        $conditions['user']['jeventsregistration']->option .= '<span class="acym__title acym__title__secondary acym_vcenter margin-bottom-0 cell shrink"><</span>';
        $conditions['user']['jeventsregistration']->option .= acym_dateField('acym_condition[conditions][__numor__][__numand__][jeventsregistration][datemax]', '', 'cell shrink');
        $conditions['user']['jeventsregistration']->option .= '</div>';
    }

    public function onAcymDeclareConditionsScenario(&$conditions){
        $this->onAcymDeclareConditions($conditions);
    }

    /**
     * Function called with ajax to search in events
     */
    public function searchEvent()
    {
        $id = acym_getVar('int', 'id');
        if (!empty($id)) {
            $subject = acym_loadResult(
                'SELECT evdet.summary 
                FROM #__jev_attendance AS attendance 
                JOIN #__jevents_vevent AS ev ON ev.ev_id = attendance.ev_id 
                JOIN #__jevents_vevdetail AS evdet ON ev.detail_id = evdet.evdet_id 
                WHERE attendance.id = '.intval($id)
            );
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
        $elements = acym_loadObjectList(
            'SELECT attendance.id, attendance.ev_id, evdet.summary 
            FROM #__jev_attendance AS attendance 
            JOIN #__jevents_vevent AS ev ON ev.ev_id = attendance.ev_id 
            JOIN #__jevents_vevdetail AS evdet ON ev.detail_id = evdet.evdet_id 
            WHERE evdet.summary LIKE '.acym_escapeDB('%'.$search.'%').' 
            ORDER BY evdet.summary ASC'
        );

        foreach ($elements as $oneElement) {
            $return[] = [$oneElement->id, $oneElement->ev_id.' - '.$oneElement->summary];
        }

        echo json_encode($return);
        exit;
    }

    public function onAcymProcessCondition_jeventsregistration(&$query, $options, $num, &$conditionNotValid)
    {
        $this->processConditionFilter_jeventsregistration($query, $options, $num);
        $affectedRows = $query->count();
        if (empty($affectedRows)) $conditionNotValid++;
    }

    private function processConditionFilter_jeventsregistration(&$query, $options, $num)
    {
        if (!$this->installed) return;

        $query->join['jeventsregistration'.$num] = '#__jev_attendees AS jev_attendees'.$num.' ON jev_attendees'.$num.'.email_address = user.email OR (jev_attendees'.$num.'.user_id != 0 AND jev_attendees'.$num.'.user_id = user.cms_id)';
        if (!empty($options['event'])) $query->where[] = 'jev_attendees'.$num.'.at_id = '.intval($options['event']);
        if ($options['status'] != -1) {
            $query->where[] = 'jev_attendees'.$num.'.attendstate = '.intval($options['status']);
        }

        if (!empty($options['datemin'])) {
            $options['datemin'] = acym_replaceDate($options['datemin']);
            if (!is_numeric($options['datemin'])) $options['datemin'] = strtotime($options['datemin']);
            if (!empty($options['datemin'])) {
                $query->where[] = 'jev_attendees'.$num.'.created > '.acym_escapeDB(date('Y-m-d H:i:s', $options['datemin']));
            }
        }

        if (!empty($options['datemax'])) {
            $options['datemax'] = acym_replaceDate($options['datemax']);
            if (!is_numeric($options['datemax'])) $options['datemax'] = strtotime($options['datemax']);
            if (!empty($options['datemax'])) {
                $query->where[] = 'jev_attendees'.$num.'.created < '.acym_escapeDB(date('Y-m-d H:i:s', $options['datemax']));
            }
        }
    }

    public function onAcymDeclareSummary_conditions(&$automationCondition)
    {
        $this->summaryConditionFilters($automationCondition);
    }

    private function summaryConditionFilters(&$automation)
    {
        if (!empty($automation['jeventsregistration'])) {
            acym_loadLanguageFile('com_rsvppro', JPATH_ADMINISTRATOR);

            if (empty($automation['jeventsregistration']['event'])) {
                $event = acym_translation('ACYM_ANY_EVENT');
            } else {
                $event = acym_loadResult(
                    'SELECT evdet.summary 
                    FROM #__jev_attendance AS attendance 
                    JOIN #__jevents_vevent AS ev ON ev.ev_id = attendance.ev_id 
                    JOIN #__jevents_vevdetail AS evdet ON ev.detail_id = evdet.evdet_id 
                    WHERE attendance.id = '.intval($automation['jeventsregistration']['event'])
                );
            }

            $status = [
                '-1' => 'ACYM_ANY',
                '1' => 'RSVP_IS_CONFIRMED',
                '0' => 'RSVP_PENDING',
            ];

            $status = acym_translation($status[$automation['jeventsregistration']['status']]);

            $finalText = acym_translationSprintf('ACYM_REGISTERED', $event, $status);

            $dates = [];
            if (!empty($automation['jeventsregistration']['datemin'])) {
                $dates[] = acym_translation('ACYM_AFTER').' '.acym_replaceDate($automation['jeventsregistration']['datemin'], true);
            }

            if (!empty($automation['jeventsregistration']['datemax'])) {
                $dates[] = acym_translation('ACYM_BEFORE').' '.acym_replaceDate($automation['jeventsregistration']['datemax'], true);
            }

            if (!empty($dates)) {
                $finalText .= ' '.implode(' '.acym_translation('ACYM_AND').' ', $dates);
            }

            $automation = $finalText;
        }
    }
}
