<?php

use AcyMailing\Types\OperatorType;
use AcyMailing\Types\OperatorinType;

trait UserAutomationFilters
{
    public function onAcymDeclareFilters(&$filters)
    {
        $allGroups = acym_getGroups();
        $groups = [];
        foreach ($allGroups as $group) {
            $groups[$group->id] = $group->text;
        }
        $operatorIn = new OperatorinType();

        $filters['acy_group'] = new stdClass();
        $filters['acy_group']->name = acym_translation('ACYM_GROUP');
        $filters['acy_group']->option = '<div class="intext_select_automation cell">';
        $filters['acy_group']->option .= $operatorIn->display('acym_action[filters][__numor__][__numand__][acy_group][in]');
        $filters['acy_group']->option .= '</div>';
        $filters['acy_group']->option .= '<div class="intext_select_automation cell">';
        $filters['acy_group']->option .= acym_select(
            $groups,
            'acym_action[filters][__numor__][__numand__][acy_group][group]',
            null,
            ['class' => 'acym__select']
        );
        $filters['acy_group']->option .= '</div>';

        if (ACYM_CMS == 'joomla') {
            $filters['acy_group']->option .= '<div class="cell grid-x medium-3">';
            $filters['acy_group']->option .= acym_switch('acym_action[filters][__numor__][__numand__][acy_group][subgroup]', 1, acym_translation('ACYM_INCLUDE_SUB_GROUPS'));
            $filters['acy_group']->option .= '</div>';
        }


        $cmsFields = [];
        foreach (acym_getColumns('users', false) as $key => $column) {
            $cmsFields[$column] = $column;
        }

        // Handle custom fields
        if (ACYM_CMS == 'joomla' && ACYM_J37) {
            $query = 'SELECT id, title 
						FROM #__fields 
						WHERE context = "com_users.user"
							AND state = 1
							AND type IN ("calendar", "checkboxes", "color", "integer", "list", "radio", "sql", "text", "url")
						ORDER BY title ASC';
            $customFields = acym_loadObjectList($query);
            foreach ($customFields as $oneCF) {
                $cmsFields['cf_'.$oneCF->id] = $oneCF->title;
            }
        }
        $excluded = ['password', 'params', 'activation', 'lastResetTime', 'resetCount', 'optKey', 'otep', 'requireReset', 'user_pass', 'user_activation_key'];
        foreach ($excluded as $oneExcluded) {
            unset($cmsFields[$oneExcluded]);
        }

        $operator = new OperatorType();

        $filters['acy_cmsfield'] = new stdClass();
        $filters['acy_cmsfield']->name = acym_translation('ACYM_ACCOUNT_USER_FIELD');
        $filters['acy_cmsfield']->option = '<div class="intext_select_automation cell">';
        $filters['acy_cmsfield']->option .= acym_select(
            $cmsFields,
            'acym_action[filters][__numor__][__numand__][acy_cmsfield][field]',
            null,
            ['class' => 'acym__select']
        );
        $filters['acy_cmsfield']->option .= '</div>';
        $filters['acy_cmsfield']->option .= '<div class="intext_select_automation cell">';
        $filters['acy_cmsfield']->option .= $operator->display('acym_action[filters][__numor__][__numand__][acy_cmsfield][operator]');
        $filters['acy_cmsfield']->option .= '</div>';
        $filters['acy_cmsfield']->option .= '<input class="intext_input_automation cell" type="text" name="acym_action[filters][__numor__][__numand__][acy_cmsfield][value]">';
    }

    public function onAcymProcessFilter_acy_group(&$query, $options, $num)
    {
        $this->_processAcyGroup($query, $options, $num);
    }

    public function onAcymProcessFilterCount_acy_group(&$query, $options, $num)
    {
        $this->onAcymProcessFilter_acy_group($query, $options, $num);

        return acym_translationSprintf('ACYM_SELECTED_USERS', $query->count());
    }

    public function onAcymProcessFilter_acy_cmsfield(&$query, $options, $num)
    {
        $this->_processAcyCMSField($query, $options, $num);
    }

    public function onAcymProcessFilterCount_acy_cmsfield(&$query, $options, $num)
    {
        $this->onAcymProcessFilter_acy_cmsfield($query, $options, $num);

        return acym_translationSprintf('ACYM_SELECTED_USERS', $query->count());
    }

    public function onAcymDeclareSummary_filters(&$automation)
    {
        $this->_summaryGroup($automation);

        if (!empty($automation['acy_cmsfield'])) {
            $automation = acym_translationSprintf(
                'ACYM_FILTER_ACY_CMS_FIELD_SUMMARY',
                $automation['acy_cmsfield']['field'],
                $automation['acy_cmsfield']['operator'],
                $automation['acy_cmsfield']['value']
            );
        }
    }
}
