<?php

trait ModernEventsCalendarAutomationConditions
{
    public function onAcymDeclareConditions(&$conditions)
    {
        if (!$this->fullInstalled) return;

        $conditions['user']['moderneventscalendar'] = new stdClass();
        $conditions['user']['moderneventscalendar']->name = 'Modern Events Calendar';
        $conditions['user']['moderneventscalendar']->option = '<div class="cell grid-x grid-margin-x">';
        $conditions['user']['moderneventscalendar']->option .= '<div class="intext_select_automation cell">';
        $selectOptions = [
            'class' => 'acym__select acym_select2_ajax',
            'data-placeholder' => acym_translation('ACYM_ANY_EVENT'),
            'data-params' => [
                'plugin' => __CLASS__,
                'trigger' => 'searchEvent',
            ],
        ];
        $conditions['user']['moderneventscalendar']->option .= acym_select(
            [],
            'acym_condition[conditions][__numor__][__numand__][moderneventscalendar][event]',
            null,
            $selectOptions
        );
        $conditions['user']['moderneventscalendar']->option .= '</div>';
        $conditions['user']['moderneventscalendar']->option .= '</div>';

        $conditions['user']['moderneventscalendar']->option .= '<div class="cell grid-x grid-margin-x">';
        $conditions['user']['moderneventscalendar']->option .= acym_dateField(
            'acym_condition[conditions][__numor__][__numand__][moderneventscalendar][datemin]',
            '',
            'cell shrink'
        );
        $conditions['user']['moderneventscalendar']->option .= '<span class="acym__title acym__title__secondary acym_vcenter margin-bottom-0 cell shrink"><</span>';
        $conditions['user']['moderneventscalendar']->option .= '<span class="acym_vcenter">'.acym_translation('ACYM_REGISTRATION_DATE').'</span>';
        $conditions['user']['moderneventscalendar']->option .= '<span class="acym__title acym__title__secondary acym_vcenter margin-bottom-0 cell shrink"><</span>';
        $conditions['user']['moderneventscalendar']->option .= acym_dateField(
            'acym_condition[conditions][__numor__][__numand__][moderneventscalendar][datemax]',
            '',
            'cell shrink'
        );
        $conditions['user']['moderneventscalendar']->option .= '</div>';
    }

    public function onAcymDeclareConditionsScenario(&$conditions)
    {
        $this->onAcymDeclareConditions($conditions);
    }

    public function searchEvent()
    {
        $id = acym_getVar('int', 'id');
        if (!empty($id)) {
            $subject = acym_loadResult(
                'SELECT post_title 
                FROM #__posts 
                WHERE ID = '.intval($id)
            );
            if (empty($subject)) $subject = '';
            echo json_encode([
                [
                    'value' => $id,
                    'text' => $id.' - '.$subject,
                ],
            ]);
            exit;
        }

        $return = [];
        $search = acym_getVar('string', 'search', '');
        $elements = acym_loadObjectList(
            'SELECT ID, post_title 
            FROM #__posts 
            WHERE post_title LIKE '.acym_escapeDB('%'.$search.'%').' AND post_type = "mec-events" 
            ORDER BY post_title ASC'
        );

        foreach ($elements as $oneElement) {
            $return[] = [$oneElement->ID, $oneElement->ID.' - '.$oneElement->post_title];
        }

        echo json_encode($return);
        exit;
    }

    public function onAcymProcessCondition_moderneventscalendar(&$query, $options, $num, &$conditionNotValid)
    {
        if (!$this->fullInstalled) return;

        $this->processConditionFilter_moderneventscalendar($query, $options, $num);
        $affectedRows = $query->count();
        if (empty($affectedRows)) $conditionNotValid++;
    }

    private function processConditionFilter_moderneventscalendar(&$query, $options, $num)
    {
        if (!$this->fullInstalled) return;

        $mecPost = 'mecP'.$num;
        $mecUser = 'mecU'.$num;
        $query->join[$mecUser] = '#__users AS '.$mecUser.' ON '.$mecUser.'.user_email = user.email';
        $query->join[$mecPost] = '#__posts AS '.$mecPost.' ON '.$mecPost.'.post_author = '.$mecUser.'.ID AND '.$mecPost.'.post_type = "mec-books"';

        if (!empty($options['event'])) {
            $mecPmEvent = 'mecPmEvent'.$num;
            $query->join[$mecPmEvent] = '#__postmeta AS '.$mecPmEvent.' 
                ON '.$mecPmEvent.'.post_id = '.$mecPost.'.ID 
                AND '.$mecPmEvent.'.meta_key = "mec_event_id" 
                AND '.$mecPmEvent.'.meta_value = '.(int)$options['event'];
        }

        if (!empty($options['datemin']) || !empty($options['datemax'])) {
            $mecPmDate = 'mecPmDate'.$num;
            $mecDateMin = '';
            $mecDateMax = '';

            if (!empty($options['datemin'])) {
                $options['datemin'] = acym_replaceDate($options['datemin']);
                if (!is_numeric($options['datemin'])) $options['datemin'] = strtotime($options['datemin']);
                if (!empty($options['datemin'])) {
                    $mecDateMin = ' AND '.$mecPmDate.'.meta_value > '.acym_escapeDB(acym_date($options['datemin'], 'Y-m-d H:i:s'));
                }
            }

            if (!empty($options['datemax'])) {
                $options['datemax'] = acym_replaceDate($options['datemax']);
                if (!is_numeric($options['datemax'])) $options['datemax'] = strtotime($options['datemax']);
                if (!empty($options['datemax'])) {
                    $mecDateMax = ' AND '.$mecPmDate.'.meta_value < '.acym_escapeDB(acym_date($options['datemax'], 'Y-m-d H:i:s'));
                }
            }

            $query->join[$mecPmDate] = '#__postmeta AS '.$mecPmDate.' 
                ON '.$mecPmDate.'.post_id = '.$mecPost.'.ID 
                AND '.$mecPmDate.'.meta_key = "mec_booking_time" 
                '.$mecDateMin.$mecDateMax;
        }
    }

    public function onAcymDeclareSummary_conditions(&$automationCondition)
    {
        $this->summaryConditionFilters($automationCondition);
    }

    private function summaryConditionFilters(&$automation)
    {
        if (!$this->fullInstalled) return;

        if (empty($automation['moderneventscalendar'])) return;

        if (empty($automation['moderneventscalendar']['event'])) {
            $event = acym_translation('ACYM_ANY_EVENT');
        } else {
            $event = acym_loadResult(
                'SELECT post_title 
                    FROM #__posts 
                    WHERE ID = '.intval($automation['moderneventscalendar']['event'])
            );
        }

        $finalText = acym_translationSprintf('ACYM_REGISTERED_TO', $event);

        $dates = [];
        if (!empty($automation['moderneventscalendar']['datemin'])) {
            $dates[] = acym_translation('ACYM_AFTER').' '.acym_replaceDate($automation['moderneventscalendar']['datemin'], true);
        }

        if (!empty($automation['moderneventscalendar']['datemax'])) {
            $dates[] = acym_translation('ACYM_BEFORE').' '.acym_replaceDate($automation['moderneventscalendar']['datemax'], true);
        }

        if (!empty($dates)) {
            $finalText .= ' '.implode(' '.acym_translation('ACYM_AND').' ', $dates);
        }

        $automation = $finalText;
    }
}
