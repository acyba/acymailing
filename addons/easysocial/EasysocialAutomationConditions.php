<?php

use AcyMailing\Types\OperatorInType;
use AcyMailing\Types\OperatorType;

trait EasysocialAutomationConditions
{
    public function onAcymDeclareConditions(array &$conditions): void
    {
        acym_loadLanguageFile('com_easysocial', JPATH_SITE);
        $operatorIn = new OperatorInType();
        $operator = new OperatorType();

        // Groups filter
        $conditions['user']['easysocialgroups'] = new stdClass();
        $conditions['user']['easysocialgroups']->name = acym_translationSprintf('ACYM_COMBINED_TRANSLATIONS', 'EasySocial', acym_translation('ACYM_GROUP'));
        $conditions['user']['easysocialgroups']->option = '<div class="cell grid-x grid-margin-x">';

        $conditions['user']['easysocialgroups']->option .= '<div class="intext_select_automation cell">';
        $conditions['user']['easysocialgroups']->option .= $operatorIn->display('acym_condition[conditions][__numor__][__numand__][easysocialgroups][in]');
        $conditions['user']['easysocialgroups']->option .= '</div>';

        $conditions['user']['easysocialgroups']->option .= '<div class="intext_select_automation cell">';
        $conditions['user']['easysocialgroups']->option .= acym_select(
            [],
            'acym_condition[conditions][__numor__][__numand__][easysocialgroups][group]',
            null,
            [
                'class' => 'acym__select acym_select2_ajax',
                'data-placeholder' => acym_translation('ACYM_ANY_GROUP'),
                'data-params' => [
                    'plugin' => __CLASS__,
                    'trigger' => 'searchGroup',
                ],
            ]
        );
        $conditions['user']['easysocialgroups']->option .= '</div>';

        $conditions['user']['easysocialgroups']->option .= '</div>';

        $conditions['user']['easysocialgroups']->option .= '<div class="cell grid-x grid-margin-x">';
        $conditions['user']['easysocialgroups']->option .= acym_dateField('acym_condition[conditions][__numor__][__numand__][easysocialgroups][datemin]', '', 'cell shrink');
        $conditions['user']['easysocialgroups']->option .= '<span class="acym__title acym__title__secondary acym_vcenter margin-bottom-0 cell shrink"><</span>';
        $conditions['user']['easysocialgroups']->option .= '<span class="acym_vcenter">'.acym_translation('ACYM_SUBSCRIPTION_DATE').'</span>';
        $conditions['user']['easysocialgroups']->option .= '<span class="acym__title acym__title__secondary acym_vcenter margin-bottom-0 cell shrink"><</span>';
        $conditions['user']['easysocialgroups']->option .= acym_dateField('acym_condition[conditions][__numor__][__numand__][easysocialgroups][datemax]', '', 'cell shrink');
        $conditions['user']['easysocialgroups']->option .= '</div>';


        // Profile type filter
        $conditions['user']['easysocialprofiles'] = new stdClass();
        $conditions['user']['easysocialprofiles']->name = acym_translationSprintf('ACYM_COMBINED_TRANSLATIONS', 'EasySocial', acym_translation('ACYM_MENU_PROFILE'));
        $conditions['user']['easysocialprofiles']->option = '<div class="cell grid-x grid-margin-x">';

        $conditions['user']['easysocialprofiles']->option .= '<div class="intext_select_automation cell">';
        $conditions['user']['easysocialprofiles']->option .= $operatorIn->display('acym_condition[conditions][__numor__][__numand__][easysocialprofiles][in]');
        $conditions['user']['easysocialprofiles']->option .= '</div>';

        $conditions['user']['easysocialprofiles']->option .= '<div class="intext_select_automation cell">';
        $conditions['user']['easysocialprofiles']->option .= acym_select(
            [],
            'acym_condition[conditions][__numor__][__numand__][easysocialprofiles][profile]',
            null,
            [
                'class' => 'acym__select acym_select2_ajax',
                'data-placeholder' => acym_translation('ACYM_ANY_PROFILE'),
                'data-params' => [
                    'plugin' => __CLASS__,
                    'trigger' => 'searchProfile',
                ],
            ]
        );
        $conditions['user']['easysocialprofiles']->option .= '</div>';

        $conditions['user']['easysocialprofiles']->option .= '</div>';

        $conditions['user']['easysocialprofiles']->option .= '<div class="cell grid-x grid-margin-x">';
        $conditions['user']['easysocialprofiles']->option .= acym_dateField('acym_condition[conditions][__numor__][__numand__][easysocialprofiles][datemin]', '', 'cell shrink');
        $conditions['user']['easysocialprofiles']->option .= '<span class="acym__title acym__title__secondary acym_vcenter margin-bottom-0 cell shrink"><</span>';
        $conditions['user']['easysocialprofiles']->option .= '<span class="acym_vcenter">'.acym_translation('ACYM_SUBSCRIPTION_DATE').'</span>';
        $conditions['user']['easysocialprofiles']->option .= '<span class="acym__title acym__title__secondary acym_vcenter margin-bottom-0 cell shrink"><</span>';
        $conditions['user']['easysocialprofiles']->option .= acym_dateField('acym_condition[conditions][__numor__][__numand__][easysocialprofiles][datemax]', '', 'cell shrink');
        $conditions['user']['easysocialprofiles']->option .= '</div>';


        // Badge filter
        $conditions['user']['easysocialbadge'] = new stdClass();
        $conditions['user']['easysocialbadge']->name = acym_translationSprintf('ACYM_COMBINED_TRANSLATIONS', 'EasySocial', acym_translation('COM_EASYSOCIAL_BADGES'));
        $conditions['user']['easysocialbadge']->option = '<div class="cell grid-x grid-margin-x">';

        $conditions['user']['easysocialbadge']->option .= '<div class="intext_select_automation cell">';
        $conditions['user']['easysocialbadge']->option .= $operatorIn->display('acym_condition[conditions][__numor__][__numand__][easysocialbadge][in]');
        $conditions['user']['easysocialbadge']->option .= '</div>';

        $conditions['user']['easysocialbadge']->option .= '<div class="intext_select_automation cell">';
        $allBadges = acym_loadObjectList('SELECT id, title FROM #__social_badges');
        foreach ($allBadges as $i => $oneBadge) {
            $allBadges[$i]->title = acym_translation($oneBadge->title);
        }
        usort($allBadges, function ($a, $b) {
            return strtolower($a->title) > strtolower($b->title) ? 1 : -1;
        });
        $conditions['user']['easysocialbadge']->option .= acym_select(
            $allBadges,
            'acym_condition[conditions][__numor__][__numand__][easysocialbadge][badge]',
            null,
            [
                'class' => 'acym__select',
                'data-placeholder' => acym_translation('ACYM_ANY_BADGE'),
            ],
            'id',
            'title'
        );
        $conditions['user']['easysocialbadge']->option .= '</div>';

        $conditions['user']['easysocialbadge']->option .= '</div>';

        $conditions['user']['easysocialbadge']->option .= '<div class="cell grid-x grid-margin-x">';
        $conditions['user']['easysocialbadge']->option .= acym_dateField('acym_condition[conditions][__numor__][__numand__][easysocialbadge][datemin]', '', 'cell shrink');
        $conditions['user']['easysocialbadge']->option .= '<span class="acym__title acym__title__secondary acym_vcenter margin-bottom-0 cell shrink"><</span>';
        $conditions['user']['easysocialbadge']->option .= '<span class="acym_vcenter">'.acym_translation('ACYM_DATE_CREATED').'</span>';
        $conditions['user']['easysocialbadge']->option .= '<span class="acym__title acym__title__secondary acym_vcenter margin-bottom-0 cell shrink"><</span>';
        $conditions['user']['easysocialbadge']->option .= acym_dateField('acym_condition[conditions][__numor__][__numand__][easysocialbadge][datemax]', '', 'cell shrink');
        $conditions['user']['easysocialbadge']->option .= '</div>';


        // Field filter
        $conditions['user']['easysocialfield'] = new stdClass();
        $conditions['user']['easysocialfield']->name = acym_translationSprintf('ACYM_COMBINED_TRANSLATIONS', 'EasySocial', acym_translation('ACYM_FIELD'));
        $conditions['user']['easysocialfield']->option = '<div class="cell grid-x grid-margin-x">';

        // profile
        $conditions['user']['easysocialfield']->option .= '<div class="intext_select_automation cell">';
        $conditions['user']['easysocialfield']->option .= acym_select(
            [],
            'acym_condition[conditions][__numor__][__numand__][easysocialfield][profile]',
            null,
            [
                'class' => 'acym__select acym_select2_ajax',
                'data-placeholder' => acym_translation('COM_EASYSOCIAL_PROFILE'),
                'data-params' => [
                    'plugin' => __CLASS__,
                    'trigger' => 'searchProfile',
                ],
                'acym-automation-reload' => [
                    'plugin' => __CLASS__,
                    'trigger' => 'searchFields',
                    'change' => '#easysocialfield_tochange___numor_____numand__',
                    'name' => 'acym_condition[conditions][__numor__][__numand__][easysocialfield][field]',
                    'paramFields' => [
                        'profile' => 'acym_condition[conditions][__numor__][__numand__][easysocialfield][profile]',
                    ],
                ],
            ]
        );
        $conditions['user']['easysocialfield']->option .= '</div>';

        // field
        $conditions['user']['easysocialfield']->option .= '<div class="intext_select_automation cell" id="easysocialfield_tochange___numor_____numand__">';
        $conditions['user']['easysocialfield']->option .= '<input type="text" name="acym_condition[conditions][__numor__][__numand__][easysocialfield][field]" disabled="disabled"/>';
        $conditions['user']['easysocialfield']->option .= '</div>';

        $conditions['user']['easysocialfield']->option .= '<div class="intext_select_automation cell">';
        $conditions['user']['easysocialfield']->option .= $operator->display(
            'acym_condition[conditions][__numor__][__numand__][easysocialfield][operator]',
            '',
            'acym__automation__conditions__operator__dropdown'
        );
        $conditions['user']['easysocialfield']->option .= '</div>';
        $conditions['user']['easysocialfield']->option .= '<input 
            class="acym__automation__one-field intext_input_automation cell acym__automation__condition__regular-field" 
            type="text" 
            name="acym_condition[conditions][__numor__][__numand__][easysocialfield][value]">';

        $conditions['user']['easysocialfield']->option .= '</div>';


        // Attending event filter
        $conditions['user']['easysocialevent'] = new stdClass();
        $conditions['user']['easysocialevent']->name = acym_translationSprintf('ACYM_COMBINED_TRANSLATIONS', 'EasySocial', acym_translation('COM_ES_EVENTS'));
        $conditions['user']['easysocialevent']->option = '<div class="cell grid-x grid-margin-x">';

        $conditions['user']['easysocialevent']->option .= '<div class="intext_select_automation cell">';
        $conditions['user']['easysocialevent']->option .= acym_select(
            [],
            'acym_condition[conditions][__numor__][__numand__][easysocialevent][event]',
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
        $conditions['user']['easysocialevent']->option .= '</div>';

        $conditions['user']['easysocialevent']->option .= '<div class="intext_select_automation cell">';
        $allCats = acym_loadObjectList('SELECT id, title FROM #__social_clusters_categories WHERE type LIKE "event" ORDER BY title');
        $cats = [acym_selectOption(0, acym_translation('ACYM_ANY_CATEGORY'))];
        foreach ($allCats as $oneCat) {
            $cats[] = acym_selectOption($oneCat->id, acym_translation($oneCat->title));
        }
        $conditions['user']['easysocialevent']->option .= acym_select(
            $cats,
            'acym_condition[conditions][__numor__][__numand__][easysocialevent][category]',
            null,
            ['class' => 'acym__select']
        );
        $conditions['user']['easysocialevent']->option .= '</div>';

        $conditions['user']['easysocialevent']->option .= '<div class="intext_select_automation cell">';
        $state = [
            '0' => acym_translation('ACYM_ANY_STATUS'),
            '1' => acym_translation('COM_EASYSOCIAL_EVENTS_GUEST_GOING'),
            '3' => acym_translation('COM_EASYSOCIAL_EVENTS_GUEST_MAYBE'),
            '4' => acym_translation('COM_EASYSOCIAL_EVENTS_GUEST_NOTGOING'),
        ];
        $conditions['user']['easysocialevent']->option .= acym_select(
            $state,
            'acym_condition[conditions][__numor__][__numand__][easysocialevent][status]',
            null,
            ['class' => 'acym__select']
        );
        $conditions['user']['easysocialevent']->option .= '</div>';

        $conditions['user']['easysocialevent']->option .= '<div class="cell grid-x grid-margin-x margin-top-1 margin-left-0">';
        $conditions['user']['easysocialevent']->option .= acym_dateField('acym_condition[conditions][__numor__][__numand__][easysocialevent][datemin]', '', 'cell shrink');
        $conditions['user']['easysocialevent']->option .= '<span class="acym__title acym__title__secondary acym_vcenter margin-bottom-0 cell shrink"><</span>';
        $conditions['user']['easysocialevent']->option .= '<span class="acym_vcenter">'.acym_translation('ACYM_DATE_CREATED').'</span>';
        $conditions['user']['easysocialevent']->option .= '<span class="acym__title acym__title__secondary acym_vcenter margin-bottom-0 cell shrink"><</span>';
        $conditions['user']['easysocialevent']->option .= acym_dateField('acym_condition[conditions][__numor__][__numand__][easysocialevent][datemax]', '', 'cell shrink');
        $conditions['user']['easysocialevent']->option .= '</div>';

        $conditions['user']['easysocialevent']->option .= '</div>';
    }

    public function onAcymDeclareConditionsScenario(array &$conditions): void
    {
        $this->onAcymDeclareConditions($conditions);
    }

    public function onAcymProcessCondition_easysocialgroups(&$query, $options, $num, &$conditionNotValid)
    {
        $this->processConditionFilter_easysocialgroups($query, $options, $num);
        $affectedRows = $query->count();
        if (empty($affectedRows)) $conditionNotValid++;
    }

    private function processConditionFilter_easysocialgroups(&$query, $options, $num)
    {
        $groupsTable = 'easysocialgroups'.$num;
        $membersTable = 'easysocialmembers'.$num;
        $query->leftjoin[$membersTable] = '#__social_clusters_nodes AS '.$membersTable.' ON '.$membersTable.'.uid = user.cms_id AND '.$membersTable.'.type = "user"';

        $query->leftjoin[$groupsTable] = '#__social_clusters AS '.$groupsTable.' ON '.$groupsTable.'.id = '.$membersTable.'.cluster_id';
        $query->leftjoin[$groupsTable] .= ' AND '.$groupsTable.'.cluster_type = "group"';
        $query->leftjoin[$groupsTable] .= ' AND '.$groupsTable.'.state = 1';

        if (!empty($options['group'])) {
            $query->leftjoin[$membersTable] .= ' AND '.$membersTable.'.cluster_id = '.intval($options['group']);
        }

        if (!empty($options['datemin'])) {
            $options['datemin'] = acym_replaceDate($options['datemin']);
            if (!is_numeric($options['datemin'])) $options['datemin'] = strtotime($options['datemin']);
            if (!empty($options['datemin'])) {
                $query->leftjoin[$groupsTable] .= ' AND '.$groupsTable.'.created > '.acym_escapeDB(acym_date($options['datemin'], 'Y-m-d H:i:s', false));
            }
        }

        if (!empty($options['datemax'])) {
            $options['datemax'] = acym_replaceDate($options['datemax']);
            if (!is_numeric($options['datemax'])) $options['datemax'] = strtotime($options['datemax']);
            if (!empty($options['datemax'])) {
                $query->leftjoin[$groupsTable] .= ' AND '.$groupsTable.'.created < '.acym_escapeDB(acym_date($options['datemax'], 'Y-m-d H:i:s', false));
            }
        }

        if (empty($options['in']) || $options['in'] === 'in') {
            $query->where[] = $groupsTable.'.id IS NOT NULL';
        } else {
            $query->where[] = $groupsTable.'.id IS NULL';
        }
    }

    public function onAcymProcessCondition_easysocialprofiles(&$query, $options, $num, &$conditionNotValid)
    {
        $this->processConditionFilter_easysocialprofiles($query, $options, $num);
        $affectedRows = $query->count();
        if (empty($affectedRows)) $conditionNotValid++;
    }

    private function processConditionFilter_easysocialprofiles(&$query, $options, $num)
    {
        $profilesTable = 'easysocialprofiles'.$num;
        $join = '#__social_profiles_maps AS '.$profilesTable.' ON '.$profilesTable.'.user_id = user.cms_id AND '.$profilesTable.'.state = 1';

        if (!empty($options['profile'])) {
            $join .= ' AND '.$profilesTable.'.profile_id = '.intval($options['profile']);
        }

        if (!empty($options['datemin'])) {
            $options['datemin'] = acym_replaceDate($options['datemin']);
            if (!is_numeric($options['datemin'])) $options['datemin'] = strtotime($options['datemin']);
            if (!empty($options['datemin'])) {
                $join .= ' AND '.$profilesTable.'.created > '.acym_escapeDB(acym_date($options['datemin'], 'Y-m-d H:i:s', false));
            }
        }

        if (!empty($options['datemax'])) {
            $options['datemax'] = acym_replaceDate($options['datemax']);
            if (!is_numeric($options['datemax'])) $options['datemax'] = strtotime($options['datemax']);
            if (!empty($options['datemax'])) {
                $join .= ' AND '.$profilesTable.'.created < '.acym_escapeDB(acym_date($options['datemax'], 'Y-m-d H:i:s', false));
            }
        }

        if (empty($options['in']) || $options['in'] === 'in') {
            $query->join[] = $join;
        } else {
            $query->leftjoin[] = $join;
            $query->where[] = $profilesTable.'.id IS NULL';
        }
    }

    public function onAcymProcessCondition_easysocialbadge(&$query, $options, $num, &$conditionNotValid)
    {
        $this->processConditionFilter_easysocialbadge($query, $options, $num);
        $affectedRows = $query->count();
        if (empty($affectedRows)) $conditionNotValid++;
    }

    private function processConditionFilter_easysocialbadge(&$query, $options, $num)
    {
        $badgeTable = 'easysocialbadge'.$num;
        $join = '#__social_badges_maps AS '.$badgeTable.' ON '.$badgeTable.'.user_id = user.cms_id';

        if (!empty($options['badge'])) {
            $join .= ' AND '.$badgeTable.'.badge_id = '.intval($options['badge']);
        }

        if (!empty($options['datemin'])) {
            $options['datemin'] = acym_replaceDate($options['datemin']);
            if (!is_numeric($options['datemin'])) $options['datemin'] = strtotime($options['datemin']);
            if (!empty($options['datemin'])) {
                $join .= ' AND '.$badgeTable.'.created > '.acym_escapeDB(acym_date($options['datemin'], 'Y-m-d H:i:s', false));
            }
        }

        if (!empty($options['datemax'])) {
            $options['datemax'] = acym_replaceDate($options['datemax']);
            if (!is_numeric($options['datemax'])) $options['datemax'] = strtotime($options['datemax']);
            if (!empty($options['datemax'])) {
                $join .= ' AND '.$badgeTable.'.created < '.acym_escapeDB(acym_date($options['datemax'], 'Y-m-d H:i:s', false));
            }
        }

        if (empty($options['in']) || $options['in'] === 'in') {
            $query->join[] = $join;
        } else {
            $query->leftjoin[] = $join;
            $query->where[] = $badgeTable.'.id IS NULL';
        }
    }

    public function onAcymProcessCondition_easysocialfield(&$query, $options, $num, &$conditionNotValid)
    {
        $this->processConditionFilter_easysocialfield($query, $options, $num);
        $affectedRows = $query->count();
        if (empty($affectedRows)) $conditionNotValid++;
    }

    private function processConditionFilter_easysocialfield(&$query, $options, $num)
    {
        $fieldTable = 'easysocialfield'.$num;
        $query->join[$fieldTable] = '#__social_fields_data AS '.$fieldTable.' ON user.cms_id = '.$fieldTable.'.uid';
        $query->where[] = $fieldTable.'.field_id = '.intval($options['field']);
        $query->where[] = $query->convertQuery($fieldTable, 'raw', $options['operator'], $options['value']);
    }

    public function onAcymProcessCondition_easysocialevent(&$query, $options, $num, &$conditionNotValid)
    {
        $this->processConditionFilter_easysocialevent($query, $options, $num);
        $affectedRows = $query->count();
        if (empty($affectedRows)) $conditionNotValid++;
    }

    private function processConditionFilter_easysocialevent(&$query, $options, $num)
    {
        $eventTable = 'easysocialcat'.$num;
        $attendeeTable = 'easysocialevent'.$num;
        $query->join[$attendeeTable] = '#__social_clusters_nodes AS '.$attendeeTable.' ON '.$attendeeTable.'.uid = user.cms_id AND '.$attendeeTable.'.type = "user"';
        $query->join[$eventTable] = '#__social_clusters AS '.$eventTable.' ON '.$eventTable.'.id = '.$attendeeTable.'.cluster_id';

        $query->where[] = $eventTable.'.cluster_type = "event"';
        $query->where[] = $eventTable.'.state = 1';

        if (!empty($options['status'])) {
            $query->join[$attendeeTable] .= ' AND '.$attendeeTable.'.state = '.intval($options['status']);
        }

        if (!empty($options['event'])) {
            $query->where[] = $attendeeTable.'.cluster_id = '.intval($options['event']);
        } elseif (!empty($options['category'])) {
            $query->where[] = $eventTable.'.category_id = '.intval($options['category']);
        }

        if (!empty($options['datemin'])) {
            $options['datemin'] = acym_replaceDate($options['datemin']);
            if (!is_numeric($options['datemin'])) $options['datemin'] = strtotime($options['datemin']);
            if (!empty($options['datemin'])) {
                $query->where[] = $attendeeTable.'.created > '.acym_escapeDB(acym_date($options['datemin'], 'Y-m-d H:i:s', false));
            }
        }

        if (!empty($options['datemax'])) {
            $options['datemax'] = acym_replaceDate($options['datemax']);
            if (!is_numeric($options['datemax'])) $options['datemax'] = strtotime($options['datemax']);
            if (!empty($options['datemax'])) {
                $query->where[] = $attendeeTable.'.created < '.acym_escapeDB(acym_date($options['datemax'], 'Y-m-d H:i:s', false));
            }
        }
    }

    public function onAcymDeclareSummary_conditions(&$automationCondition)
    {
        $this->summaryConditionFilters($automationCondition);
    }

    private function summaryConditionFilters(&$automationCondition)
    {
        acym_loadLanguageFile('com_easysocial', JPATH_SITE);

        if (!empty($automationCondition['easysocialgroups'])) {
            if (empty($automationCondition['easysocialgroups']['group'])) {
                $group = acym_translation('ACYM_ANY_GROUP');
            } else {
                $group = acym_loadResult('SELECT `title` FROM #__social_clusters WHERE `id` = '.intval($automationCondition['easysocialgroups']['group']));
            }

            $inOperator = acym_translation($automationCondition['easysocialgroups']['in'] === 'in' ? 'ACYM_IN' : 'ACYM_NOT_IN');
            $finalText = acym_translationSprintf('ACYM_FILTER_ACY_GROUP_SUMMARY', $inOperator, $group);

            $dates = [];
            if (!empty($automationCondition['easysocialgroups']['datemin'])) {
                $dates[] = acym_translation('ACYM_AFTER').' '.acym_replaceDate($automationCondition['easysocialgroups']['datemin'], true);
            }

            if (!empty($automationCondition['easysocialgroups']['datemax'])) {
                $dates[] = acym_translation('ACYM_BEFORE').' '.acym_replaceDate($automationCondition['easysocialgroups']['datemax'], true);
            }

            if (!empty($dates)) {
                $finalText .= ' '.implode(' '.acym_translation('ACYM_AND').' ', $dates);
            }

            $automationCondition = $finalText;
        }

        if (!empty($automationCondition['easysocialprofiles'])) {
            if (empty($automationCondition['easysocialprofiles']['profile'])) {
                $profile = acym_translation('ACYM_ANY_PROFILE');
            } else {
                $profile = acym_loadResult('SELECT `title` FROM #__social_profiles WHERE `id` = '.intval($automationCondition['easysocialprofiles']['profile']));
            }

            $inOperator = acym_translation($automationCondition['easysocialprofiles']['in'] === 'in' ? 'ACYM_IN' : 'ACYM_NOT_IN');
            $finalText = acym_translationSprintf('ACYM_FILTER_IN_PROFILE_SUMMARY', $inOperator, $profile);

            $dates = [];
            if (!empty($automationCondition['easysocialprofiles']['datemin'])) {
                $dates[] = acym_translation('ACYM_AFTER').' '.acym_replaceDate($automationCondition['easysocialprofiles']['datemin'], true);
            }

            if (!empty($automationCondition['easysocialprofiles']['datemax'])) {
                $dates[] = acym_translation('ACYM_BEFORE').' '.acym_replaceDate($automationCondition['easysocialprofiles']['datemax'], true);
            }

            if (!empty($dates)) {
                $finalText .= ' '.implode(' '.acym_translation('ACYM_AND').' ', $dates);
            }

            $automationCondition = $finalText;
        }

        if (!empty($automationCondition['easysocialbadge'])) {
            if (empty($automationCondition['easysocialbadge']['badge'])) {
                $badge = 'ACYM_ANY_BADGE';
            } else {
                $badge = acym_loadResult('SELECT `title` FROM #__social_badges WHERE `id` = '.intval($automationCondition['easysocialbadge']['badge']));
            }

            $inOperator = acym_translation($automationCondition['easysocialbadge']['in'] === 'in' ? 'ACYM_IN' : 'ACYM_NOT_IN');
            $finalText = acym_translationSprintf('ACYM_FILTER_IN_PROFILE_SUMMARY', $inOperator, acym_translation($badge));

            $dates = [];
            if (!empty($automationCondition['easysocialbadge']['datemin'])) {
                $dates[] = acym_translation('ACYM_AFTER').' '.acym_replaceDate($automationCondition['easysocialbadge']['datemin'], true);
            }

            if (!empty($automationCondition['easysocialbadge']['datemax'])) {
                $dates[] = acym_translation('ACYM_BEFORE').' '.acym_replaceDate($automationCondition['easysocialbadge']['datemax'], true);
            }

            if (!empty($dates)) {
                $finalText .= ' '.implode(' '.acym_translation('ACYM_AND').' ', $dates);
            }

            $automationCondition = $finalText;
        }

        if (!empty($automationCondition['easysocialfield'])) {
            $field = acym_loadResult('SELECT title FROM #__social_fields WHERE id = '.intval($automationCondition['easysocialfield']['field']));

            $automationCondition = acym_translationSprintf(
                'ACYM_FILTER_ACY_FIELD_SUMMARY',
                acym_translation(empty($field) ? 'ACYM_FIELD' : $field),
                $automationCondition['easysocialfield']['operator'],
                $automationCondition['easysocialfield']['value']
            );
        }

        if (!empty($automationCondition['easysocialevent'])) {
            if (empty($automationCondition['easysocialevent']['event'])) {
                if (empty($automationCondition['easysocialevent']['category'])) {
                    $category = acym_translation('ACYM_ANY_CATEGORY');
                } else {
                    $category = acym_loadResult('SELECT `title` FROM #__social_clusters_categories WHERE `id` = '.intval($automationCondition['easysocialevent']['category']));
                }
                $finalText = acym_translationSprintf('ACYM_EVENT_FILTER_CATEGORY_SUMMARY', $category);
            } else {
                $event = acym_loadResult('SELECT `title` FROM #__social_clusters WHERE `id` = '.intval($automationCondition['easysocialevent']['event']));
                $finalText = acym_translationSprintf('ACYM_EVENT_FILTER_SUMMARY', $event);
            }

            $dates = [];
            if (!empty($automationCondition['easysocialevent']['datemin'])) {
                $dates[] = acym_translation('ACYM_AFTER').' '.acym_replaceDate($automationCondition['easysocialevent']['datemin'], true);
            }

            if (!empty($automationCondition['easysocialevent']['datemax'])) {
                $dates[] = acym_translation('ACYM_BEFORE').' '.acym_replaceDate($automationCondition['easysocialevent']['datemax'], true);
            }

            if (!empty($dates)) {
                $finalText .= ' '.implode(' '.acym_translation('ACYM_AND').' ', $dates);
            }

            $automationCondition = $finalText;
        }
    }

    /**
     * Function called with ajax to search in groups
     */
    public function searchGroup()
    {
        $ids = $this->getIdsSelectAjax();

        if (!empty($ids)) {
            $elements = acym_loadObjectList('SELECT `title` AS name, `id` FROM #__social_clusters WHERE cluster_type = "group" AND `id` IN ("'.implode('","', $ids).'")');

            $value = [];
            if (!empty($elements)) {
                foreach ($elements as $element) {
                    $value[] = [
                        'text' => $element->name,
                        'value' => $element->id,
                    ];
                }
            }
            echo json_encode($value);
            exit;
        }

        $return = [];
        $search = acym_getVar('string', 'search', '');
        $elements = acym_loadObjectList(
            'SELECT `id`, `title` FROM `#__social_clusters` WHERE cluster_type = "group" AND `title` LIKE '.acym_escapeDB('%'.$search.'%').' ORDER BY `title`'
        );

        foreach ($elements as $oneElement) {
            $return[] = [$oneElement->id, $oneElement->title];
        }

        echo json_encode($return);
        exit;
    }

    /**
     * Function called with ajax to search in profiles
     */
    public function searchProfile()
    {
        $ids = $this->getIdsSelectAjax();

        if (!empty($ids)) {
            $elements = acym_loadObjectList('SELECT `title` AS name, `id` FROM #__social_profiles WHERE `id` IN ("'.implode('","', $ids).'")');

            $value = [];
            if (!empty($elements)) {
                foreach ($elements as $element) {
                    $value[] = [
                        'text' => $element->name,
                        'value' => $element->id,
                    ];
                }
            }
            echo json_encode($value);
            exit;
        }

        $return = [];
        $search = acym_getVar('string', 'search', '');
        $elements = acym_loadObjectList(
            'SELECT `id`, `title` FROM `#__social_profiles` WHERE `title` LIKE '.acym_escapeDB('%'.$search.'%').' ORDER BY `title`'
        );

        foreach ($elements as $oneElement) {
            $return[] = [$oneElement->id, $oneElement->title];
        }

        echo json_encode($return);
        exit;
    }

    public function searchFields()
    {
        acym_loadLanguageFile('com_easysocial', JPATH_SITE);
        acym_loadLanguageFile('com_easysocial', JPATH_ADMINISTRATOR);

        $id = acym_getVar('int', 'profile', 0);
        if (empty($id)) exit;

        $elements = acym_loadObjectList(
            'SELECT field.id, field.title 
			FROM #__social_fields AS field 
			JOIN #__social_fields_steps AS fieldStep ON field.step_id = fieldStep.id 
			JOIN #__social_workflows_maps AS workflowMap ON workflowMap.workflow_id = fieldStep.workflow_id 
			WHERE fieldStep.type = "profiles" 
				AND workflowMap.uid = '.intval($id).' 
				AND field.unique_key NOT LIKE "'.implode(
                '%" AND field.unique_key NOT LIKE "',
                ['JOOMLA_', 'HEADER', 'SEPARATOR', 'TERMS', 'COVER', 'AVATAR', 'HTML', 'TEXT-', 'FILE', 'CURRENCY']
            ).'%"'
        );

        $options = [];
        $options[0] = acym_translation('ACYM_SELECT_FIELD');
        foreach ($elements as $oneElement) {
            $options[$oneElement->id] = acym_translation($oneElement->title);
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
     * Function called with ajax to search in profiles
     */
    public function searchEvent()
    {
        $ids = $this->getIdsSelectAjax();

        if (!empty($ids)) {
            $elements = acym_loadObjectList(
                'SELECT clusters.`title` AS name, clusters.`id` 
                FROM #__social_clusters AS clusters 
                WHERE clusters.`id` IN ("'.implode('","', $ids).'") 
                    AND clusters.cluster_type = "event" 
                    AND clusters.state = 1 
                ORDER BY clusters.title'
            );

            $value = [];
            if (!empty($elements)) {
                foreach ($elements as $element) {
                    $value[] = [
                        'text' => acym_translation($element->name),
                        'value' => $element->id,
                    ];
                }
            }
            echo json_encode($value);
            exit;
        }

        $return = [];
        $search = acym_getVar('string', 'search', '');
        $elements = acym_loadObjectList(
            'SELECT clusters.`title`, clusters.`id` 
            FROM #__social_clusters AS clusters 
            JOIN #__social_events_meta AS meta ON clusters.id = meta.cluster_id
            WHERE clusters.cluster_type = "event" 
                AND clusters.state = 1 
                AND clusters.`title` LIKE '.acym_escapeDB('%'.$search.'%').' 
                AND meta.start > '.acym_escapeDB(date('Y-m-d H:i:s', time() - 5184000)).' 
            ORDER BY clusters.title'
        );

        foreach ($elements as $oneElement) {
            $return[] = [$oneElement->id, acym_translation($oneElement->title)];
        }

        echo json_encode($return);
        exit;
    }
}
