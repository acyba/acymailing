<?php

use AcyMailing\Classes\UserClass;
use AcyMailing\Classes\FieldClass;
use AcyMailing\Helpers\AutomationHelper;
use AcyMailing\Types\OperatorType;

trait SubscriberAutomationConditions
{
    private function onAcymDeclareSummary_conditionsFilters(&$automation, $key)
    {
        if (!empty($automation['acy_field'])) {
            $usersColumns = acym_getColumns('user');

            if (!in_array($automation['acy_field']['field'], $usersColumns)) {
                $fieldClass = new FieldClass();
                $field = $fieldClass->getOneById($automation['acy_field']['field']);
                $automation['acy_field']['field'] = $field->name;
            }

            $automation = acym_translationSprintf(
                $key,
                $automation['acy_field']['field'],
                $automation['acy_field']['operator'],
                $automation['acy_field']['value']
            );
        } elseif (!empty($automation['random'])) {
            $automation = acym_translationSprintf(
                'ACYM_RANDOMLY_SELECT_X_SUBSCRIBERS',
                $automation['random']['number']
            );
        }
    }

    public function onAcymDeclareConditions(&$conditions)
    {
        $userClass = new UserClass();
        $fieldClass = new FieldClass();
        $fields = $userClass->getAllColumnsUserAndCustomField();
        unset($fields['automation']);

        $customFields = $fieldClass->getAllFieldsForUser();
        $customFieldValues = [];
        foreach ($customFields as $field) {
            if (in_array($field->type, ['single_dropdown', 'radio', 'checkbox', 'multiple_dropdown']) && !empty($field->value)) {
                $values = [];
                $field->value = json_decode($field->value, true);
                foreach ($field->value as $value) {
                    $valueTmp = new stdClass();
                    $valueTmp->text = $value['title'];
                    $valueTmp->value = $value['value'];
                    if ($value['disabled'] == 'y') $valueTmp->disable = true;
                    $values[$value['value']] = $valueTmp;
                }
                $customFieldValues[$field->id] = '<div class="acym__automation__one-field intext_select_automation cell" style="display: none">';
                $customFieldValues[$field->id] .= acym_select(
                    $values,
                    '[conditions][__numor__][__numand__][acy_field][value]',
                    null,
                    [
                        'class' => 'acym__select acym__automation__conditions__fields__select',
                        'data-condition-field' => intval($field->id),
                    ]
                );
                $customFieldValues[$field->id] .= '</div>';
            } elseif ('date' == $field->type) {
                $field->option = json_decode($field->option, true);
                $customFieldValues[$field->id] = acym_tooltip(
                    [
                        'hoveredText' => '<input class="acym__automation__one-field acym__automation__conditions__fields__select intext_input_automation cell" type="text" name="[conditions][__numor__][__numand__][acy_field][value]" style="display: none" data-condition-field="'.intval(
                                $field->id
                            ).'">',
                        'textShownInTooltip' => acym_translationSprintf('ACYM_DATE_AUTOMATION_INPUT', $field->option['format']),
                        'classContainer' => 'intext_select_automation cell',
                    ]
                );
            }
        }
        $operator = new OperatorType();

        $conditions['user']['acy_field'] = new stdClass();
        $conditions['user']['acy_field']->name = acym_translation('ACYM_ACYMAILING_FIELD');
        ob_start();
        include acym_getPartial('conditions', 'acy_field');
        $conditions['user']['acy_field']->option = ob_get_clean();
    }

    public function onAcymDeclareConditionsScenario(&$conditions)
    {
        $this->onAcymDeclareConditions($conditions);
    }

    public function onAcymProcessCondition_acy_field(&$query, &$options, $num, &$conditionNotValid)
    {
        $affectedRows = $this->_processAcyField($query, $options, $num);
        if (empty($affectedRows)) $conditionNotValid++;
    }

    public function onAcymDeclareSummary_conditions(&$automation)
    {
        $this->onAcymDeclareSummary_conditionsFilters($automation, 'ACYM_CONDITION_ACY_FIELD_SUMMARY');
    }

    private function _processAcyField(&$query, &$options, $num)
    {
        $usersColumns = acym_getColumns('user');

        if (!in_array($options['field'], $usersColumns)) {
            $fieldClass = new FieldClass();
            $field = $fieldClass->getOneById($options['field']);

            $fieldType = empty($field->type) ? '' : $field->type;

            $query->leftjoin['userfield'.$num] = ' #__acym_user_has_field as userfield'.$num.' ON userfield'.$num.'.user_id = user.id AND userfield'.$num.'.field_id = '.intval(
                    $options['field']
                );
            $query->where[] = $query->convertQuery(
                'userfield'.$num,
                'value',
                $options['operator'],
                $options['value'],
                'phone' === $fieldType ? AutomationHelper::TYPE_PHONE : ''
            );
        } else {
            if (in_array($options['field'], ['creation_date', 'confirmation_date', 'last_sent_date', 'last_open_date', 'last_click_date'])) {
                $options['value'] = acym_replaceDate($options['value']);
                if (!is_numeric($options['value'])) {
                    $options['value'] = strtotime($options['value']);
                }
                $options['value'] = acym_date($options['value'], 'Y-m-d H:i:s');
            }
            $query->where[] = $query->convertQuery('user', $options['field'], $options['operator'], $options['value']);
        }

        return $query->count();
    }
}
