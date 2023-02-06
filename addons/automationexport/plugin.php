<?php

use AcyMailing\Libraries\acymPlugin;
use AcyMailing\Classes\FieldClass;
use AcyMailing\Helpers\EncodingHelper;
use AcyMailing\Helpers\ExportHelper;

class plgAcymAutomationexport extends acymPlugin
{
    public function onAcymDeclareActions(&$actions)
    {
        $actions['export'] = new stdClass();
        $actions['export']->name = acym_translation('ACYM_EXPORT_SUBSCRIBERS');
        $actions['export']->option = '<div class="cell grid-x margin-bottom-1">';

        $fields = acym_getColumns('user');
        $fieldClass = new FieldClass();
        $customFields = $fieldClass->getAll();
        $fields = array_merge($fields, $customFields);

        $defaultFields = explode(',', $this->config->get('export_fields', 'name,email'));
        $coreFields = [1, 2, $fieldClass->getLanguageFieldId()];

        foreach ($fields as $field) {
            if (is_object($field)) {
                $type = 'custom';
                $fieldName = $field->name;
                $fieldValue = $field->id;
                if ($field->type === 'file' || in_array($field->id, $coreFields)) {
                    continue;
                }
            } else {
                $type = 'core';
                $fieldName = $field;
                $fieldValue = $fieldName;
                if (in_array($fieldName, ['id', 'automation'])) {
                    continue;
                }
            }

            $checked = in_array($fieldValue, $defaultFields) ? 'checked="checked"' : '';

            $actions['export']->option .= '<div class="cell large-6 xlarge-3">';
            $actions['export']->option .= '<input '.$checked.' id="checkbox__and___'.$fieldName.'" type="checkbox" name="acym_action[actions][__and__][export]['.$type.']['.$fieldValue.']" value="'.acym_escape(
                    $fieldName
                ).'">';
            $actions['export']->option .= '<label for="checkbox__and___'.$fieldName.'">'.$fieldName.'</label>';
            $actions['export']->option .= '</div>';
        }

        $checked = in_array('subscribe_date', $defaultFields) ? 'checked="checked"' : '';
        $actions['export']->option .= '<div class="cell large-6 xlarge-3">';
        $actions['export']->option .= '<input '.$checked.' id="checkbox__and___subscribe_date" type="checkbox" name="acym_action[actions][__and__][export][special][subscribe_date]" value="subscribe_date">';
        $actions['export']->option .= '<label for="checkbox__and___subscribe_date">'.acym_translation('ACYM_SUBSCRIPTION_DATE').'</label>';
        $actions['export']->option .= '</div>';

        $actions['export']->option .= '</div>';

        $actions['export']->option .= '<div class="cell medium-6 xlarge-3 grid-x margin-bottom-1">';
        $actions['export']->option .= '<div class="cell">'.acym_translation('ACYM_SEPARATOR').'</div>';
        $actions['export']->option .= '<div class="cell">'.acym_select(
                [
                    'semicol' => acym_translation('ACYM_SEMICOLON'),
                    'comma' => acym_translation('ACYM_COMMA'),
                ],
                'acym_action[actions][__and__][export][separator]',
                $this->config->get('export_separator', 'comma'),
                ['class' => 'acym__select']
            ).'</div>';
        $actions['export']->option .= '</div>';

        $actions['export']->option .= '<div class="cell medium-6 xlarge-3 grid-x margin-bottom-1">';
        $actions['export']->option .= '<div class="cell">'.acym_translation('ACYM_ENCODING').'</div>';
        $encodingHelper = new EncodingHelper();
        $actions['export']->option .= '<div class="cell">'.$encodingHelper->charsetField(
                'acym_action[actions][__and__][export][charset]',
                $this->config->get('export_charset', 'UTF-8'),
                ['class' => 'acym__select']
            ).'</div>';
        $actions['export']->option .= '</div>';

        $actions['export']->option .= '<div class="cell xlarge-6 xxlarge-5 grid-x">';
        $actions['export']->option .= '<label class="cell acym_vcenter margin-left-0" for="action__and__exportpath">'.acym_translation('ACYM_REPORT_SAVE_TO_DESC').'</label>';
        $actions['export']->option .= '<input class="cell" type="text" name="acym_action[actions][__and__][export][path]" value="'.ACYM_LOGS_FOLDER.'export_%Y_%m_%d.csv" id="action__and__exportpath"/>';
        $actions['export']->option .= '</div>';
    }

    public function onAcymProcessAction_export(&$cquery, $action)
    {
        // Get the file name
        $pathtolog = ACYM_ROOT.ACYM_LOGS_FOLDER.'export_'.date('Y-m-d').'.csv';
        if (!empty($action['path']) && false === strpos($action['path'], '..')) {
            $pathtolog = ACYM_ROOT.strftime($action['path']);
        }

        if (empty($action['core']) && empty($action['custom'])) {
            return '['.acym_translation('ACYM_EXPORT_SUBSCRIBERS').'] '.acym_translation('ACYM_EXPORT_SELECT_FIELD');
        }

        if (empty($action['core'])) {
            $action['core'] = [];
        }
        if (empty($action['custom'])) {
            $action['custom'] = [];
        }
        if (empty($action['special'])) {
            $action['special'] = [];
        }

        acym_increasePerf();
        $select = ['user.`id`'];
        foreach ($action['core'] as $oneField) {
            $select[] = 'user.`'.acym_secureDBColumn($oneField).'`';
        }
        $query = $cquery->getQuery($select);

        $exportHelper = new ExportHelper();
        $realSeparators = ['comma' => ',', 'semicol' => ';'];
        $error = $exportHelper->exportCSV($query, $action['core'], $action['custom'], $action['special'], $realSeparators[$action['separator']], $action['charset'], $pathtolog);

        if (empty($error)) {
            return acym_translationSprintf('ACYM_USERS_EXPORTED', $pathtolog);
        } else {
            return '['.acym_translation('ACYM_EXPORT_SUBSCRIBERS').'] '.$error;
        }
    }

    public function onAcymDeclareSummary_actions(&$automation)
    {
        if (!empty($automation['export'])) {
            $automation = acym_translation('ACYM_ALL_SUBSCRIBER_WILL_BE_EXPORTED');
        }
    }
}
