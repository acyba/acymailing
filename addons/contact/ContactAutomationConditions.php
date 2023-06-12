<?php

use AcyMailing\Types\OperatorinType;

trait ContactAutomationConditions
{
    public function onAcymDeclareConditions(&$conditions)
    {
        $categories = acym_loadObjectList('SELECT id AS value, title AS text FROM #__categories WHERE extension = "com_contact" ORDER BY lft ASC');
        if (empty($categories)) return;

        acym_loadLanguageFile('com_contact', JPATH_ADMINISTRATOR);

        $conditions['user']['contact'] = new stdClass();
        $conditions['user']['contact']->name = acym_translationSprintf(
            'ACYM_COMBINED_TRANSLATIONS',
            acym_translation('COM_CONTACT'),
            acym_translation('COM_CONTACT_SUBMENU_CATEGORIES')
        );

        $operatorIn = new OperatorinType();
        $conditions['user']['contact']->option = '<div class="intext_select_automation cell">';
        $conditions['user']['contact']->option .= $operatorIn->display('acym_condition[conditions][__numor__][__numand__][contact][type]');
        $conditions['user']['contact']->option .= '</div>';

        $conditions['user']['contact']->option .= '<div class="intext_select_automation cell">';
        $conditions['user']['contact']->option .= acym_select(
            $categories,
            'acym_condition[conditions][__numor__][__numand__][contact][category]',
            null,
            ['class' => 'acym__select']
        );
        $conditions['user']['contact']->option .= '</div>';
    }

    public function onAcymProcessCondition_contact(&$query, $options, $num, &$conditionNotValid)
    {
        $this->processConditionFilter_contact($query, $options, $num);
        $affectedRows = $query->count();
        if (empty($affectedRows)) $conditionNotValid++;
    }

    private function processConditionFilter_contact(&$query, $options, $num)
    {
        $query->leftjoin['contact_'.$num] = '`#__contact_details` AS contact'.$num.' ON contact'.$num.'.`catid` = '.intval($options['category']).' AND (
            contact'.$num.'.`email_to` = user.`email` OR 
            (
                contact'.$num.'.`user_id` != 0 AND 
                contact'.$num.'.`user_id` = user.`cms_id`
            )
        )';

        $operator = strtolower($options['type']) === 'in' ? 'IS NOT NULL' : 'IS NULL';
        $query->where[] = 'contact'.$num.'.`email_to` '.$operator;
    }

    public function onAcymDeclareSummary_conditions(&$automationCondition)
    {
        $this->summaryConditionFilters($automationCondition);
    }

    private function summaryConditionFilters(&$automation)
    {
        if (!empty($automation['contact'])) {

            $category = acym_loadResult('SELECT title FROM #__categories WHERE id = '.intval($automation['contact']['category']));
            $category = empty($category) ? '' : acym_translation($category);

            $finalText = acym_translationSprintf(
                'ACYM_SUMMARY_IN_CATEGORY',
                acym_translation($automation['contact']['type'] == 'in' ? 'ACYM_IN' : 'ACYM_NOT_IN'),
                $category
            );

            $automation = $finalText;
        }
    }
}
