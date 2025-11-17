<?php

use AcyMailing\Types\OperatorInType;

trait EventBookingAutomationConditions
{
    public function onAcymDeclareConditions(array &$conditions): void
    {
        acym_loadLanguageFile('com_eventbooking', JPATH_SITE);
        acym_loadLanguageFile('com_eventbooking', JPATH_ADMINISTRATOR);
        acym_loadLanguageFile('com_eventbookingcommon', JPATH_ADMINISTRATOR);

        $conditions['user']['ebregistration'] = new stdClass();
        $conditions['user']['ebregistration']->name = acym_translationSprintf('ACYM_COMBINED_TRANSLATIONS', 'Events Booking', acym_translation('EB_REGISTRANTS'));
        $conditions['user']['ebregistration']->option = '<div class="cell grid-x grid-margin-x">';

        $conditions['user']['ebregistration']->option .= '<div class="intext_select_automation cell">';
        $operatorinType = new OperatorInType();
        $conditions['user']['ebregistration']->option .= $operatorinType->display('acym_condition[conditions][__numor__][__numand__][ebregistration][in]');
        $conditions['user']['ebregistration']->option .= '</div>';

        $conditions['user']['ebregistration']->option .= '<div class="intext_select_automation cell">';
        $ajaxParams = json_encode(
            [
                'plugin' => __CLASS__,
                'trigger' => 'searchEvent',
            ]
        );
        $conditions['user']['ebregistration']->option .= acym_select(
            [],
            'acym_condition[conditions][__numor__][__numand__][ebregistration][event]',
            null,
            [
                'class' => 'acym__select acym_select2_ajax',
                'data-placeholder' => acym_translation('ACYM_ANY_EVENT'),
                'data-params' => $ajaxParams,
            ]
        );
        $conditions['user']['ebregistration']->option .= '</div>';

        $conditions['user']['ebregistration']->option .= '<div class="intext_select_automation cell">';
        $ajaxParamsCategory = json_encode(
            [
                'plugin' => __CLASS__,
                'trigger' => 'searchEventCategory',
            ]
        );
        $conditions['user']['ebregistration']->option .= acym_select(
            [],
            'acym_condition[conditions][__numor__][__numand__][ebregistration][category]',
            null,
            [
                'class' => 'acym__select acym_select2_ajax',
                'data-placeholder' => acym_translation('ACYM_ANY_CATEGORY'),
                'data-params' => $ajaxParamsCategory,
            ]
        );
        $conditions['user']['ebregistration']->option .= '</div>';

        $status = [];
        $status[] = acym_selectOption('-1', 'ACYM_STATUS');
        $status[] = acym_selectOption('0', 'EB_PENDING');
        $status[] = acym_selectOption('1', 'EB_PAID');
        $status[] = acym_selectOption('2', 'EB_CANCELLED');
        $status[] = acym_selectOption('3', 'EB_WAITING_LIST');
        $status[] = acym_selectOption('4', 'EB_WAITING_LIST_CANCELLED');

        $conditions['user']['ebregistration']->option .= '<div class="intext_select_automation cell">';
        $conditions['user']['ebregistration']->option .= acym_select(
            $status,
            'acym_condition[conditions][__numor__][__numand__][ebregistration][status]',
            '-1',
            ['class' => 'acym__select']
        );
        $conditions['user']['ebregistration']->option .= '</div>';

        $conditions['user']['ebregistration']->option .= '</div>';

        $conditions['user']['ebregistration']->option .= '<div class="cell grid-x grid-margin-x">';
        $conditions['user']['ebregistration']->option .= acym_dateField('acym_condition[conditions][__numor__][__numand__][ebregistration][datemin]', '', 'cell shrink');
        $conditions['user']['ebregistration']->option .= '<span class="acym__title acym__title__secondary acym_vcenter margin-bottom-0 cell shrink"><</span>';
        $conditions['user']['ebregistration']->option .= '<span class="acym_vcenter">'.acym_translation('EB_REGISTRATION_DATE').'</span>';
        $conditions['user']['ebregistration']->option .= '<span class="acym__title acym__title__secondary acym_vcenter margin-bottom-0 cell shrink"><</span>';
        $conditions['user']['ebregistration']->option .= acym_dateField('acym_condition[conditions][__numor__][__numand__][ebregistration][datemax]', '', 'cell shrink');
        $conditions['user']['ebregistration']->option .= '</div>';
    }

    public function onAcymDeclareConditionsScenario(array &$conditions): void
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
            $subject = acym_loadResult('SELECT `title` FROM #__eb_events WHERE `id` = '.intval($id));
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
        $elements = acym_loadObjectList('SELECT `id`, `title` FROM `#__eb_events` WHERE `title` LIKE '.acym_escapeDB('%'.$search.'%').' ORDER BY `title` ASC');

        foreach ($elements as $oneElement) {
            $return[] = [$oneElement->id, $oneElement->id.' - '.$oneElement->title];
        }

        echo json_encode($return);
        exit;
    }

    public function searchEventCategory()
    {
        $id = acym_getVar('int', 'id');
        if (!empty($id)) {
            $subject = acym_loadResult('SELECT `name` FROM #__eb_categories WHERE `id` = '.intval($id));
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
            'SELECT DISTINCT `id`, `name` AS `title` 
            FROM `#__eb_categories` 
            WHERE `name` LIKE '.acym_escapeDB('%'.$search.'%').' 
            ORDER BY `title` ASC',
            'id'
        );

        foreach ($elements as $oneElement) {
            $return[] = [$oneElement->id, $oneElement->id.' - '.$oneElement->title];
        }
        echo json_encode($return);
        exit;
    }

    public function onAcymProcessCondition_ebregistration(&$query, $options, $num, &$conditionNotValid)
    {
        $this->processConditionFilter_ebregistration($query, $options, $num);
        $affectedRows = $query->count();
        if (empty($affectedRows)) $conditionNotValid++;
    }

    private function processConditionFilter_ebregistration(&$query, $options, $num)
    {
        $join = '`#__eb_registrants` AS eventbooking'.$num.' ON (
                    eventbooking'.$num.'.email = user.email 
                    OR (
                        eventbooking'.$num.'.user_id != 0 
                        AND eventbooking'.$num.'.user_id = user.cms_id
                    )
                )';

        if (!empty($options['event'])) {
            $join .= ' AND eventbooking'.$num.'.event_id = '.intval($options['event']);
        } elseif (!empty($options['category'])) {
            $join .= ' AND eventbooking'.$num.'.event_id IN (SELECT DISTINCT eventbookingCat'.$num.'.event_id FROM #__eb_event_categories AS eventbookingCat'.$num.' WHERE eventbookingCat'.$num.'.category_id = '.intval(
                    $options['category']
                ).')';
        }

        if (isset($options['status']) && $options['status'] != -1) $join .= ' AND eventbooking'.$num.'.published = '.intval($options['status']);

        if (!empty($options['datemin'])) {
            $options['datemin'] = acym_replaceDate($options['datemin']);
            if (!is_numeric($options['datemin'])) $options['datemin'] = strtotime($options['datemin']);
            if (!empty($options['datemin'])) {
                $join .= ' AND eventbooking'.$num.'.register_date > '.acym_escapeDB(date('Y-m-d H:i:s', $options['datemin']));
            }
        }

        if (!empty($options['datemax'])) {
            $options['datemax'] = acym_replaceDate($options['datemax']);
            if (!is_numeric($options['datemax'])) $options['datemax'] = strtotime($options['datemax']);
            if (!empty($options['datemax'])) {
                $join .= ' AND eventbooking'.$num.'.register_date < '.acym_escapeDB(date('Y-m-d H:i:s', $options['datemax']));
            }
        }

        if (empty($options['in']) || $options['in'] === 'in') {
            $query->join['ebregistration'.$num] = $join;
        } else {
            $query->leftjoin['ebregistration'.$num] = $join;
            $query->where[] = 'eventbooking'.$num.'.event_id IS NULL';
        }
    }

    public function onAcymDeclareSummary_conditions(&$automationCondition)
    {
        $this->summaryConditionFilters($automationCondition);
    }

    private function summaryConditionFilters(&$automationCondition)
    {
        $finalText = '';
        if (!empty($automationCondition['ebregistration'])) {
            acym_loadLanguageFile('com_eventbooking', JPATH_SITE);
            acym_loadLanguageFile('com_eventbooking', JPATH_ADMINISTRATOR);
            acym_loadLanguageFile('com_eventbookingcommon', JPATH_ADMINISTRATOR);

            if (!empty($automationCondition['ebregistration']['event'])) {
                $event = acym_loadResult('SELECT `title` FROM #__eb_events WHERE `id` = '.intval($automationCondition['ebregistration']['event']));
                $category = '';
            } elseif (!empty($automationCondition['ebregistration']['category'])) {
                $category = acym_loadResult('SELECT `name` FROM #__eb_categories WHERE `id` = '.intval($automationCondition['ebregistration']['category']));
                $event = '';
            } else {
                $event = acym_translation('ACYM_ANY_EVENT');
                $category = acym_translation('ACYM_ANY_CATEGORY');
            }

            $status = [
                '-1' => 'ACYM_ANY',
                '0' => 'EB_PENDING',
                '1' => 'EB_PAID',
                '2' => 'EB_CANCELLED',
                '3' => 'EB_WAITING_LIST',
                '4' => 'EB_WAITING_LIST_CANCELLED',
            ];

            $status = acym_translation($status[$automationCondition['ebregistration']['status']]);

            if (empty($automationCondition['ebregistration']['in']) || $automationCondition['ebregistration']['in'] === 'in') {
                if (empty($event)) {
                    $finalText = acym_translationSprintf('ACYM_IS_REGISTERED', $status, $category);
                } else {
                    $finalText = acym_translationSprintf('ACYM_IS_REGISTERED', $status, $event);
                }
            } else {
                if (empty($event)) {
                    $finalText = acym_translationSprintf('ACYM_NOT_REGISTERED', $status, $category);
                } else {
                    $finalText = acym_translationSprintf('ACYM_NOT_REGISTERED', $status, $event);
                }
            }

            $dates = [];
            if (!empty($automationCondition['ebregistration']['datemin'])) {
                $dates[] = acym_translation('ACYM_AFTER').' '.acym_replaceDate($automationCondition['ebregistration']['datemin'], true);
            }

            if (!empty($automationCondition['ebregistration']['datemax'])) {
                $dates[] = acym_translation('ACYM_BEFORE').' '.acym_replaceDate($automationCondition['ebregistration']['datemax'], true);
            }

            if (!empty($dates)) {
                $finalText .= ' '.implode(' '.acym_translation('ACYM_AND').' ', $dates);
            }

            $automationCondition = $finalText;
        }
    }
}
