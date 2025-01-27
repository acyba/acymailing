<?php

trait TheEventsCalendarAutomationConditions
{
    public function onAcymDeclareConditions(&$conditions)
    {
        if (!$this->rtecInstalled && !$this->eventTicketsInstalled) return;

        $conditions['user']['eventscalendar'] = new stdClass();
        $conditions['user']['eventscalendar']->name = 'The Events Calendar - Registration';
        $conditions['user']['eventscalendar']->option = '<div class="cell grid-x grid-margin-x">';

        $conditions['user']['eventscalendar']->option .= '<div class="intext_select_automation cell">';

        $selectOptions = [
            'class' => 'acym__select acym_select2_ajax',
            'data-placeholder' => acym_translation('ACYM_ANY_EVENT'),
            'data-params' => [
                'plugin' => __CLASS__,
                'trigger' => 'searchEvent',
            ],
        ];

        // If Events tickets installed, add the ticket option
        if ($this->eventTicketsInstalled) {
            $selectOptions['acym-automation-reload'] = [
                'plugin' => __CLASS__,
                'trigger' => 'getTicketsSelection',
                'change' => '#ettec_tochange___numor_____numand__',
                'name' => 'acym_condition[conditions][__numor__][__numand__][eventscalendar][ticket]',
                'paramFields' => [
                    'event' => 'acym_condition[conditions][__numor__][__numand__][eventscalendar][event]',
                ],
            ];
        }

        $conditions['user']['eventscalendar']->option .= acym_select(
            [],
            'acym_condition[conditions][__numor__][__numand__][eventscalendar][event]',
            null,
            $selectOptions
        );
        $conditions['user']['eventscalendar']->option .= '</div>';

        $conditions['user']['eventscalendar']->option .= '<div class="intext_select_automation cell" id="ettec_tochange___numor_____numand__">';
        $conditions['user']['eventscalendar']->option .= '<input type="hidden" name="acym_condition[conditions][__numor__][__numand__][eventscalendar][ticket]" />';
        $conditions['user']['eventscalendar']->option .= '</div>';

        $conditions['user']['eventscalendar']->option .= '</div>';


        $conditions['user']['eventscalendar']->option .= '<div class="cell grid-x grid-margin-x">';
        $conditions['user']['eventscalendar']->option .= acym_dateField('acym_condition[conditions][__numor__][__numand__][eventscalendar][datemin]', '', 'cell shrink');
        $conditions['user']['eventscalendar']->option .= '<span class="acym__title acym__title__secondary acym_vcenter margin-bottom-0 cell shrink"><</span>';
        $conditions['user']['eventscalendar']->option .= '<span class="acym_vcenter">'.acym_translation('ACYM_REGISTRATION_DATE').'</span>';
        $conditions['user']['eventscalendar']->option .= '<span class="acym__title acym__title__secondary acym_vcenter margin-bottom-0 cell shrink"><</span>';
        $conditions['user']['eventscalendar']->option .= acym_dateField('acym_condition[conditions][__numor__][__numand__][eventscalendar][datemax]', '', 'cell shrink');
        $conditions['user']['eventscalendar']->option .= '</div>';
    }

    public function onAcymDeclareConditionsScenario(&$conditions){
        $this->onAcymDeclareConditions($conditions);
    }

    public function getTicketsSelection()
    {
        $id = acym_getVar('int', 'event', 0);
        if (empty($id)) exit;

        $elements = acym_loadObjectList(
            'SELECT ticket.`ID`, ticket.`post_title` 
            FROM `#__posts` AS ticket 
            JOIN #__postmeta AS meta 
                ON meta.post_id = ticket.ID
            WHERE meta.meta_key LIKE "_tribe_%_for_event"
                AND meta.`meta_value` = '.intval($id).' 
            ORDER BY ticket.`post_title` ASC'
        );

        $options = [];
        $options[0] = acym_translation('ACYM_ANY');
        foreach ($elements as $oneElement) {
            $options[$oneElement->ID] = $oneElement->post_title;
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

    /**
     * Function called with ajax to search in events
     */
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
            'SELECT ID, post_title 
            FROM #__posts 
            WHERE post_title LIKE '.acym_escapeDB('%'.$search.'%').' AND post_type = "tribe_events" 
            ORDER BY post_title ASC'
        );

        foreach ($elements as $oneElement) {
            $return[] = [$oneElement->ID, $oneElement->ID.' - '.$oneElement->post_title];
        }

        echo json_encode($return);
        exit;
    }

    public function onAcymProcessCondition_eventscalendar(&$query, $options, $num, &$conditionNotValid)
    {
        $this->processConditionFilter_eventscalendar($query, $options, $num);
        $affectedRows = $query->count();
        if (empty($affectedRows)) $conditionNotValid++;
    }

    private function processConditionFilter_eventscalendar(&$query, $options, $num)
    {
        if ($this->eventTicketsInstalled) {
            // Event tickets integration, since the ticket field exists
            $userT = 'eventTicketsUser'.$num;
            $entryT = 'eventTickets'.$num;
            $elemT = 'eventTicketsElement'.$num;
            $dateField = $entryT.'.post_date_gmt';

            $query->join[$userT] = '#__postmeta AS '.$userT.' ON '.$userT.'.meta_value = user.email AND '.$userT.'.meta_key = "_tribe_tickets_email"';
            $query->join[$entryT] = '#__posts AS '.$entryT.' ON '.$entryT.'.ID = '.$userT.'.post_id';

            if (!empty($options['ticket'])) {
                $type = '_tribe_%_product';
                $value = 'ticket';
            } elseif (!empty($options['event'])) {
                $type = '_tribe_%_event';
                $value = 'event';
            }

            if (!empty($type) && !empty($value)) {
                $query->join[$elemT] = '#__postmeta AS '.$elemT.' 
                                            ON '.$entryT.'.ID = '.$elemT.'.post_id 
                                            AND '.$elemT.'.meta_key LIKE '.acym_escapeDB($type).' 
                                            AND '.$elemT.'.meta_value = '.intval($options[$value]);
            }
        } elseif ($this->rtecInstalled) {
            // Registration the events calendar, since the ticket field doesn't exist
            $dateField = 'rtec'.$num.'.registration_date';

            $query->join['eventscalendar'.$num] = '#__rtec_registrations AS rtec'.$num.' ON rtec'.$num.'.email = user.email OR (user.cms_id != 0 AND rtec'.$num.'.user_id = user.cms_id)';
            if (!empty($options['event'])) $query->where[] = 'rtec'.$num.'.event_id = '.intval($options['event']);
        } else {
            return;
        }

        if (!empty($options['datemin'])) {
            $options['datemin'] = acym_replaceDate($options['datemin']);
            if (!is_numeric($options['datemin'])) $options['datemin'] = strtotime($options['datemin']);
            if (!empty($options['datemin'])) {
                $query->where[] = $dateField.' > '.acym_escapeDB(date('Y-m-d H:i:s', $options['datemin']));
            }
        }

        if (!empty($options['datemax'])) {
            $options['datemax'] = acym_replaceDate($options['datemax']);
            if (!is_numeric($options['datemax'])) $options['datemax'] = strtotime($options['datemax']);
            if (!empty($options['datemax'])) {
                $query->where[] = $dateField.' < '.acym_escapeDB(date('Y-m-d H:i:s', $options['datemax']));
            }
        }
    }

    public function onAcymDeclareSummary_conditions(&$automationCondition)
    {
        $this->summaryConditionFilters($automationCondition);
    }

    private function summaryConditionFilters(&$automation)
    {
        if (empty($automation['eventscalendar'])) return;

        if (empty($automation['eventscalendar']['ticket'])) {
            if (empty($automation['eventscalendar']['event'])) {
                $event = acym_translation('ACYM_ANY_EVENT');
            } else {
                $event = acym_loadResult(
                    'SELECT post_title 
                    FROM #__posts 
                    WHERE ID = '.intval($automation['eventscalendar']['event'])
                );
            }
        } else {
            $ticket = acym_loadResult(
                'SELECT post_title 
                    FROM #__posts 
                    WHERE ID = '.intval($automation['eventscalendar']['ticket'])
            );
            $event = acym_loadResult(
                'SELECT event.post_title 
                    FROM #__postmeta AS meta 
                    JOIN #__posts AS event ON event.ID = meta.meta_value 
                    WHERE meta.meta_key = "_tribe_rsvp_for_event" AND meta.post_id = '.intval($automation['eventscalendar']['ticket'])
            );

            $event .= ' - '.$ticket;
        }

        $finalText = acym_translationSprintf('ACYM_REGISTERED_TO', $event);

        $dates = [];
        if (!empty($automation['eventscalendar']['datemin'])) {
            $dates[] = acym_translation('ACYM_AFTER').' '.acym_replaceDate($automation['eventscalendar']['datemin'], true);
        }

        if (!empty($automation['eventscalendar']['datemax'])) {
            $dates[] = acym_translation('ACYM_BEFORE').' '.acym_replaceDate($automation['eventscalendar']['datemax'], true);
        }

        if (!empty($dates)) {
            $finalText .= ' '.implode(' '.acym_translation('ACYM_AND').' ', $dates);
        }

        $automation = $finalText;
    }
}
