<?php

trait EventsManagerAutomationConditions
{
    public function onAcymDeclareConditions(&$conditions)
    {
        $conditions['user']['eventsmanager'] = new stdClass();
        $conditions['user']['eventsmanager']->name = 'Events Manager - Booking';
        $conditions['user']['eventsmanager']->option = '<div class="cell grid-x grid-margin-x">';

        $conditions['user']['eventsmanager']->option .= '<div class="intext_select_automation cell">';

        $conditions['user']['eventsmanager']->option .= acym_select(
            $this->getBookingStatuses(),
            'acym_condition[conditions][__numor__][__numand__][eventsmanager][status]',
            '1',
            ['class' => 'acym__select']
        );
        $conditions['user']['eventsmanager']->option .= '</div>';

        $conditions['user']['eventsmanager']->option .= '<div class="intext_select_automation cell">';

        $this->prepareWPCategories('event-categories');
        $categories = $this->categories;
        array_unshift($categories, (object)['id' => 0, 'title' => acym_translation('ACYM_ANY_CATEGORY')]);
        $conditions['user']['eventsmanager']->option .= acym_select(
            $categories,
            'acym_condition[conditions][__numor__][__numand__][eventsmanager][category]',
            null,
            ['class' => 'acym__select'],
            'id',
            'title'
        );
        $conditions['user']['eventsmanager']->option .= '</div>';

        $conditions['user']['eventsmanager']->option .= '<div class="intext_select_automation cell">';

        $selectOptions = [
            'class' => 'acym__select acym_select2_ajax',
            'data-placeholder' => acym_translation('ACYM_ANY_EVENT'),
            'data-params' => [
                'plugin' => __CLASS__,
                'trigger' => 'searchEvent',
            ],
        ];

        $selectOptions['acym-automation-reload'] = [
            'plugin' => __CLASS__,
            'trigger' => 'getTicketsSelection',
            'change' => '#em_tochange___numor_____numand__',
            'name' => 'acym_condition[conditions][__numor__][__numand__][eventsmanager][ticket]',
            'paramFields' => [
                'event' => 'acym_condition[conditions][__numor__][__numand__][eventsmanager][event]',
            ],
        ];

        $conditions['user']['eventsmanager']->option .= acym_select(
            [],
            'acym_condition[conditions][__numor__][__numand__][eventsmanager][event]',
            null,
            $selectOptions
        );
        $conditions['user']['eventsmanager']->option .= '</div>';

        $conditions['user']['eventsmanager']->option .= '<div class="intext_select_automation cell" id="em_tochange___numor_____numand__">';
        $conditions['user']['eventsmanager']->option .= '<input type="hidden" name="acym_condition[conditions][__numor__][__numand__][eventsmanager][ticket]" />';
        $conditions['user']['eventsmanager']->option .= '</div>';

        $conditions['user']['eventsmanager']->option .= '</div>';


        $conditions['user']['eventsmanager']->option .= '<div class="cell grid-x grid-margin-x">';
        $conditions['user']['eventsmanager']->option .= acym_dateField('acym_condition[conditions][__numor__][__numand__][eventsmanager][datemin]', '', 'cell shrink');
        $conditions['user']['eventsmanager']->option .= '<span class="acym__title acym__title__secondary acym_vcenter margin-bottom-0 cell shrink"><</span>';
        $conditions['user']['eventsmanager']->option .= '<span class="acym_vcenter">'.acym_translation('ACYM_REGISTRATION_DATE').'</span>';
        $conditions['user']['eventsmanager']->option .= '<span class="acym__title acym__title__secondary acym_vcenter margin-bottom-0 cell shrink"><</span>';
        $conditions['user']['eventsmanager']->option .= acym_dateField('acym_condition[conditions][__numor__][__numand__][eventsmanager][datemax]', '', 'cell shrink');
        $conditions['user']['eventsmanager']->option .= '</div>';
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
            WHERE post_title LIKE '.acym_escapeDB('%'.$search.'%').' AND post_type = "event" 
            ORDER BY post_title ASC'
        );

        foreach ($elements as $oneElement) {
            $return[] = [$oneElement->ID, $oneElement->ID.' - '.$oneElement->post_title];
        }

        echo json_encode($return);
        exit;
    }

    public function getTicketsSelection()
    {
        $id = acym_getVar('int', 'event', 0);
        if (empty($id)) exit;

        $elements = acym_loadObjectList(
            'SELECT ticket.`ticket_id`, ticket.`ticket_name` 
            FROM `#__em_tickets` AS ticket 
            JOIN `#__em_events` AS event ON ticket.event_id = event.event_id 
            WHERE event.`post_id` = '.intval($id).' 
            ORDER BY ticket.`ticket_order` ASC'
        );

        $options = [];
        $options[0] = acym_translation('ACYM_ANY');
        foreach ($elements as $oneElement) {
            $options[$oneElement->ticket_id] = $oneElement->ticket_name;
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

    public function onAcymProcessCondition_eventsmanager(&$query, $options, $num, &$conditionNotValid)
    {
        $this->processConditionFilter_eventsmanager($query, $options, $num);
        $affectedRows = $query->count();
        if (empty($affectedRows)) $conditionNotValid++;
    }

    private function processConditionFilter_eventsmanager(&$query, $options, $num)
    {
        $booking = 'booking'.$num;
        $ticket = 'ticketBooking'.$num;
        $event = 'event'.$num;
        $category = 'category'.$num;

        $query->join[$booking] = '#__em_bookings AS '.$booking.' ON '.$booking.'.person_id = user.cms_id ';

        if (!empty($options['ticket'])) {
            $query->join[$ticket] = '#__em_tickets_bookings AS '.$ticket.' ON '.$ticket.'.booking_id = '.$booking.'.booking_id ';
            $query->where[] = $ticket.'.ticket_id = '.intval($options['ticket']);
        } elseif (!empty($options['event'])) {
            $query->join[$event] = '#__em_events AS '.$event.' ON '.$event.'.event_id = '.$booking.'.event_id ';
            $query->where[] = $event.'.post_id = '.intval($options['event']);
        } elseif (!empty($options['category'])) {
            $query->join[$event] = '#__em_events AS '.$event.' ON '.$event.'.event_id = '.$booking.'.event_id ';
            $query->join[$category] = '#__term_relationships AS '.$category.' ON '.$event.'.post_id = '.$category.'.object_id ';
            $query->where[] = $category.'.term_taxonomy_id = '.intval($options['category']);
        }

        if (isset($options['status']) && $options['status'] !== 'all') {
            $query->where[] = $booking.'.booking_status = '.intval($options['status']);
        }

        if (!empty($options['datemin'])) {
            $options['datemin'] = acym_replaceDate($options['datemin']);
            if (!is_numeric($options['datemin'])) $options['datemin'] = strtotime($options['datemin']);
            if (!empty($options['datemin'])) {
                $query->where[] = $booking.'.booking_date > '.acym_escapeDB(date('Y-m-d H:i:s', $options['datemin']));
            }
        }

        if (!empty($options['datemax'])) {
            $options['datemax'] = acym_replaceDate($options['datemax']);
            if (!is_numeric($options['datemax'])) $options['datemax'] = strtotime($options['datemax']);
            if (!empty($options['datemax'])) {
                $query->where[] = $booking.'.booking_date < '.acym_escapeDB(date('Y-m-d H:i:s', $options['datemax']));
            }
        }
    }

    public function onAcymDeclareSummary_conditions(&$automationCondition)
    {
        $this->summaryConditionFilters($automationCondition);
    }

    private function summaryConditionFilters(&$automation)
    {
        if (empty($automation['eventsmanager'])) return;

        if (empty($automation['eventsmanager']['event'])) {
            if (empty($automation['eventsmanager']['category'])) {
                $event = '';
            } else {
                $event = acym_loadResult(
                        'SELECT term.name 
                        FROM #__term_taxonomy AS taxo
                        JOIN #__terms AS term ON taxo.term_id = term.term_id
                        WHERE taxo.term_taxonomy_id = '.intval($automation['eventsmanager']['category'])
                    ).' - ';
            }
            $event .= acym_translation('ACYM_ANY_EVENT');
        } else {
            $event = acym_loadResult(
                'SELECT post_title 
                    FROM #__posts 
                    WHERE ID = '.intval($automation['eventsmanager']['event'])
            );
        }

        if (!empty($automation['eventsmanager']['ticket'])) {
            $ticket = acym_loadResult(
                'SELECT ticket_name 
                    FROM #__em_tickets 
                    WHERE ticket_id = '.intval($automation['eventsmanager']['ticket'])
            );

            $event .= ' - '.$ticket;
        }

        $finalText = acym_translationSprintf('ACYM_REGISTERED_TO', $event);

        $dates = [];
        if (!empty($automation['eventsmanager']['datemin'])) {
            $dates[] = acym_translation('ACYM_AFTER').' '.acym_replaceDate($automation['eventsmanager']['datemin'], true);
        }

        if (!empty($automation['eventsmanager']['datemax'])) {
            $dates[] = acym_translation('ACYM_BEFORE').' '.acym_replaceDate($automation['eventsmanager']['datemax'], true);
        }

        if (!empty($dates)) {
            $finalText .= ' '.implode(' '.acym_translation('ACYM_AND').' ', $dates);
        }

        if (isset($automation['eventsmanager']['status']) && $automation['eventsmanager']['status'] !== 'all') {
            $statuses = $this->getBookingStatuses();
            $finalText .= acym_translation('ACYM_STATUS').': '.$statuses[$automation['eventsmanager']['status']];
        }

        $automation = $finalText;
    }

    private function getBookingStatuses()
    {
        return [
            'all' => acym_translation('ACYM_STATUS'),
            '0' => __('Pending', 'events-manager'),
            '1' => __('Approved', 'events-manager'),
            '2' => __('Rejected', 'events-manager'),
            '3' => __('Cancelled', 'events-manager'),
            '4' => __('Awaiting Online Payment', 'events-manager'),
            '5' => __('Awaiting Payment', 'events-manager'),
        ];
    }
}
