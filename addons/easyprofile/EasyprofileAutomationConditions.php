<?php

use AcyMailing\Types\OperatorType;

trait EasyprofileAutomationConditions
{
    public function onAcymDeclareConditions(&$conditions)
    {
        $fields = [];

        foreach ($this->epfields as $field) {
            if ($field->type == '' || in_array($field->alias, $this->bannedFields)) continue;
            $text = ucfirst(strtolower($field->title));
            $fields[] = acym_selectOption($field->alias, $text);
        }
        $conditions['user']['epfield'] = new stdClass();
        $conditions['user']['epfield']->name = 'Easy Profile - '.acym_translation('ACYM_FIELDS');

        $operator = new OperatorType();

        $conditions['user']['epfield']->option = '<div class="intext_select_automation cell">';
        $conditions['user']['epfield']->option .= acym_select(
            $fields,
            'acym_condition[conditions][__numor__][__numand__][epfield][field]',
            null,
            ['class' => 'acym__select']
        );
        $conditions['user']['epfield']->option .= '</div>';
        $conditions['user']['epfield']->option .= '<div class="intext_select_automation cell">';
        $conditions['user']['epfield']->option .= $operator->display('acym_condition[conditions][__numor__][__numand__][epfield][operator]');
        $conditions['user']['epfield']->option .= '</div>';
        $conditions['user']['epfield']->option .= '<input class="intext_input_automation cell" type="text" name="acym_condition[conditions][__numor__][__numand__][epfield][value]">';
    }

    public function onAcymDeclareConditionsScenario(&$conditions)
    {
        $this->onAcymDeclareConditions($conditions);
    }

    public function onAcymProcessCondition_epfield(&$query, $options, $num, &$conditionNotValid)
    {
        $this->processConditionFilter_epfield($query, $options, $num);
        $affectedRows = $query->count();
        if (empty($affectedRows)) $conditionNotValid++;
    }

    public function processConditionFilter_epfield(&$query, $options, $num)
    {
        $fieldName = $options['field'];
        if (empty($fieldName)) return;

        $tableName = '';
        foreach ($this->epfields as $field) {
            if ($field->alias == $fieldName) {
                $tableName = $field->table;
                break;
            }
        }

        $query->join['epfield'.$num] = $tableName.' AS epfield'.$num.' ON epfield'.$num.'.id = user.cms_id';

        $query->where[] = $query->convertQuery('epfield'.$num, $options['field'], $options['operator'], $options['value']);
    }

    public function onAcymDeclareSummary_conditions(&$automationCondition)
    {
        $this->summaryConditionFilters($automationCondition);
    }

    public function summaryConditionFilters(&$automationCondition)
    {
        if (!empty($automationCondition['epfield'])) {
            $automationCondition = acym_translationSprintf(
                'ACYM_CONDITION_X_FIELD_SUMMARY',
                $this->pluginDescription->name,
                $automationCondition['epfield']['field'],
                $automationCondition['epfield']['operator'],
                $automationCondition['epfield']['value']
            );
        }
    }
}
