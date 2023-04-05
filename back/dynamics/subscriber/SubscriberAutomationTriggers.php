<?php

use AcyMailing\Helpers\ExportHelper;
use AcyMailing\Classes\UserClass;
use AcyMailing\Classes\FieldClass;
use AcyMailing\Classes\AutomationClass;

trait SubscriberAutomationTriggers
{
    private $triggers = [
        'user_creation' => 'ACYM_ON_USER_CREATION',
        'user_modification' => 'ACYM_ON_USER_MODIFICATION',
        'user_click' => 'ACYM_WHEN_USER_CLICKS_MAIL',
        'user_open' => 'ACYM_WHEN_USER_OPEN_MAIL',
        'user_confirmation' => 'ACYM_WHEN_USER_CONFIRMS_SUBSCRIPTION',
    ];

    private $triggerMail = ['user_click', 'user_open'];

    public function onAcymDeclareTriggers(&$triggers, &$defaultValues)
    {
        foreach ($this->triggers as $key => $name) {
            $triggers['user'][$key] = new stdClass();
            $triggers['user'][$key]->name = '<div class="cell shrink">'.acym_translation($name).'</div>';
            $triggers['user'][$key]->option = '<input type="hidden" name="[triggers][user]['.$key.'][]" value="">';

            if (in_array($key, $this->triggerMail)) {
                $ajaxParams = json_encode(['plugin' => 'plgAcymStatistics', 'trigger' => 'searchMail',]);
                $mailIdAttributes = [
                    'data-class' => 'acym_select2_ajax',
                    'data-placeholder' => acym_translation('ACYM_ANY_EMAIL', true),
                    'data-params' => $ajaxParams,
                ];
                if (!empty($defaultValues['mail_'.$key])) $mailIdAttributes['data-selected'] = $defaultValues['mail_'.$key];

                $triggers['user'][$key]->option .= '<div class="cell shrink">'.acym_select(
                        [],
                        '[triggers][user][mail_'.$key.']',
                        null,
                        $mailIdAttributes
                    ).'</div>';
            }
        }
    }

    public function onAcymExecuteTrigger(&$step, &$execute, &$data)
    {
        if (empty($data['userId'])) return;

        $triggers = $step->triggers;

        foreach ($this->triggers as $identifier => $name) {
            if (empty($triggers[$identifier])) continue;

            if (!empty($triggers['mail_'.$identifier]) && in_array($identifier, $this->triggerMail)) {
                if (empty($data['mailId']) || $data['mailId'] != $triggers['mail_'.$identifier]) continue;
            }

            $execute = true;
            break;
        }
    }

    public function onAcymDeclareSummary_triggers(&$automation)
    {
        if (!empty($automation->triggers['user_open'])) $automation->triggers['user_open'] = acym_translation('ACYM_WHEN_USER_OPEN_MAIL');
        if (!empty($automation->triggers['user_click'])) $automation->triggers['user_click'] = acym_translation('ACYM_WHEN_USER_CLICKS_MAIL');
        if (!empty($automation->triggers['user_modification'])) $automation->triggers['user_modification'] = acym_translation('ACYM_ON_USER_MODIFICATION');
        if (!empty($automation->triggers['user_creation'])) $automation->triggers['user_creation'] = acym_translation('ACYM_ON_USER_CREATION');
        if (!empty($automation->triggers['user_confirmation'])) $automation->triggers['user_confirmation'] = acym_translation('ACYM_WHEN_USER_CONFIRMS_SUBSCRIPTION');
    }

    public function onAcymAfterUserModify(&$user, &$oldUser)
    {
        if (empty($user)) return;

        $automationClass = new AutomationClass();
        $automationClass->trigger('user_modification', ['userId' => $user->id]);

        if (empty($oldUser)) return;

        $exportChanges = $this->config->get('export_data_changes', 0);
        if (!$exportChanges) return;

        $fieldsToExport = $this->config->get('export_data_changes_fields', []);
        if (empty($fieldsToExport)) return;

        $userClass = new UserClass();
        $newUser = $userClass->getOneByIdWithCustomFields($user->id);
        if (empty($newUser)) return;

        $fieldsToExport = explode(',', $fieldsToExport);
        $fieldClass = new FieldClass();
        $fields = $fieldClass->getByIds($fieldsToExport);

        $fieldsName = [];
        foreach ($fields as $field) {
            if ($field->name == 'ACYM_NAME') {
                $name = 'name';
            } elseif ($field->name == 'ACYM_EMAIL') {
                $name = 'email';
            } elseif ($field->name == 'ACYM_LANGUAGE') {
                $name = 'language';
            } else {
                $name = $field->name;
            }
            $fieldsName[] = $name;
        }

        if (empty($fieldsName)) return;

        $exportHelper = new ExportHelper();

        foreach ($newUser as $column => $value) {
            if (!isset($oldUser[$column])) $oldUser[$column] = '';
            if (!isset($newUser[$column])) $newUser[$column] = '';

            if ($oldUser[$column] == $newUser[$column]) continue;

            $exportHelper->exportChanges($newUser, $fieldsName, $column, $newUser[$column], $oldUser[$column]);
        }
    }

    public function onAcymAfterUserConfirm(&$user)
    {
        $automationClass = new AutomationClass();
        $automationClass->trigger('user_confirmation', ['userId' => $user->id]);
    }
}
