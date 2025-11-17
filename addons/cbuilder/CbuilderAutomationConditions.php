<?php

use AcyMailing\Types\OperatorType;

trait CbuilderAutomationConditions
{
    public function onAcymDeclareConditions(array &$conditions): void
    {
        // Load CB language
        $languages = [];
        $langPath = JPATH_SITE.DS.'components'.DS.'com_comprofiler'.DS.'plugin'.DS.'language'.DS.'default_language'.DS;
        if (file_exists($langPath.'language.php')) {
            if (!defined('CBLIB')) include_once JPATH_SITE.DS.'libraries/CBLib/CB/Application/CBApplication.php';
            $languages = include_once $langPath.'language.php';
        } elseif (file_exists($langPath.'default_language.php')) {
            include_once $langPath.'default_language.php';
        }

        // Load the fields
        $fieldTitles = acym_loadObjectList('SELECT `name`, `title` FROM #__comprofiler_fields WHERE `table` LIKE "#__comprofiler"', 'name');
        $fields = acym_getColumns('comprofiler', false);

        $cbfields = [];
        foreach ($fields as $alias) {
            $text = $alias;

            if (!empty($fieldTitles[$alias])) {
                if (empty($languages[$fieldTitles[$alias]->title])) {
                    if (defined($fieldTitles[$alias]->title)) {
                        $text = constant($fieldTitles[$alias]->title);
                    } else {
                        $text = $fieldTitles[$alias]->title;
                    }
                } else {
                    $text = $languages[$fieldTitles[$alias]->title];
                }
            }

            $cbfields[] = acym_selectOption($alias, $text);
        }

        usort($cbfields, [$this, 'sortFields']);

        $operator = new OperatorType();

        $conditions['user']['cbfield'] = new stdClass();
        $conditions['user']['cbfield']->name = acym_translationSprintf('ACYM_COMBINED_TRANSLATIONS', 'Community Builder', acym_translation('ACYM_FIELDS'));
        $conditions['user']['cbfield']->option = '<div class="intext_select_automation cell">';
        $conditions['user']['cbfield']->option .= acym_select(
            $cbfields,
            'acym_condition[conditions][__numor__][__numand__][cbfield][field]',
            null,
            ['class' => 'acym__select']
        );
        $conditions['user']['cbfield']->option .= '</div>';
        $conditions['user']['cbfield']->option .= '<div class="intext_select_automation cell">';
        $conditions['user']['cbfield']->option .= $operator->display('acym_condition[conditions][__numor__][__numand__][cbfield][operator]');
        $conditions['user']['cbfield']->option .= '</div>';
        $conditions['user']['cbfield']->option .= '<input class="intext_input_automation cell" type="text" name="acym_condition[conditions][__numor__][__numand__][cbfield][value]">';
    }

    public function onAcymDeclareConditionsScenario(array &$conditions): void
    {
        $this->onAcymDeclareConditions($conditions);
    }

    public function sortFields($a, $b)
    {
        return strcmp($a->text, $b->text);
    }

    public function onAcymProcessCondition_cbfield(&$query, $options, $num, &$conditionNotValid)
    {
        $this->processConditionFilter_cbfield($query, $options, $num);
        $affectedRows = $query->count();
        if (empty($affectedRows)) $conditionNotValid++;
    }

    public function processConditionFilter_cbfield(&$query, $options, $num)
    {
        if (empty($options['field'])) return;

        $query->leftjoin['cbfield'.$num] = '#__comprofiler AS cbfield'.$num.' ON cbfield'.$num.'.id = user.cms_id';
        $query->where[] = $query->convertQuery('cbfield'.$num, $options['field'], $options['operator'], $options['value']);
    }

    public function onAcymDeclareSummary_conditions(&$automationCondition)
    {
        $this->summaryConditionFilters($automationCondition);
    }

    public function summaryConditionFilters(&$automationCondition)
    {
        if (!empty($automationCondition['cbfield'])) {
            $automationCondition = acym_translationSprintf(
                'ACYM_CONDITION_X_FIELD_SUMMARY',
                $this->pluginDescription->name,
                $automationCondition['cbfield']['field'],
                $automationCondition['cbfield']['operator'],
                $automationCondition['cbfield']['value']
            );
        }
    }
}
