<?php

use AcyMailing\Helpers\ExportHelper;
use AcyMailing\Libraries\acymPlugin;
use AcyMailing\Classes\UserClass;
use AcyMailing\Classes\FieldClass;
use AcyMailing\Classes\AutomationClass;

require_once __DIR__.DIRECTORY_SEPARATOR.'SubscriberAutomationTriggers.php';
require_once __DIR__.DIRECTORY_SEPARATOR.'SubscriberAutomationConditions.php';
require_once __DIR__.DIRECTORY_SEPARATOR.'SubscriberAutomationFilters.php';
require_once __DIR__.DIRECTORY_SEPARATOR.'SubscriberAutomationActions.php';
require_once __DIR__.DIRECTORY_SEPARATOR.'SubscriberFollowup.php';
require_once __DIR__.DIRECTORY_SEPARATOR.'SubscriberInsertion.php';

class plgAcymSubscriber extends acymPlugin
{
    use SubscriberAutomationTriggers;
    use SubscriberAutomationConditions;
    use SubscriberAutomationFilters;
    use SubscriberAutomationActions;
    use SubscriberFollowup;
    use SubscriberInsertion;

    /**
     * Array of fields loaded to have the right option value
     */
    var $fields = [];

    public function __construct()
    {
        parent::__construct();
        $this->pluginDescription->name = acym_translation('ACYM_SUBSCRIBER');
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

        if (empty($fieldsToExport)) return;

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

    public function onAcymToggleUserConfirmed($userId, $newValue)
    {
        if ($newValue == 1) {
            $userClass = new UserClass();
            $userClass->confirm($userId);
        }
    }

    public function onAcymDeclareDataSourcesBirthdayTrigger(&$dataSources)
    {
        $data = [
            'source_name' => 'AcyMailing',
            'fields' => [],
            'no_fields_error_message' => 'ACYM_NO_FIELDS_BIRTHDAY_TRIGGER',
        ];

        $fieldClass = new FieldClass();

        $fieldsData = $fieldClass->getMatchingElements(['types' => ['date']]);
        $fields = $fieldsData['elements'];

        foreach ($fields as $oneField) {
            $data['fields'][] = [
                'name' => $oneField->name,
                'id' => $oneField->id,
                'format' => 'Y-m-d',
                'query' => 'SELECT user_id, value AS date FROM #__acym_user_has_field WHERE field_id = '.intval($oneField->id),
            ];
        }

        $dataSources['acymailing'] = $data;
    }

    public function onBeforeSaveConfigFields(&$newConfig)
    {
        $fieldToExportOnChange = $this->config->get('export_data_changes_fields', []);
        if (empty($fieldToExportOnChange)) return;

        if (!is_array($fieldToExportOnChange)) $fieldToExportOnChange = explode(',', $fieldToExportOnChange);

        if (empty($newConfig['export_data_changes_fields'])) $newConfig['export_data_changes_fields'] = [];

        if ($fieldToExportOnChange == $newConfig['export_data_changes_fields']) return;

        $exportHelper = new ExportHelper();
        $fileExportPath = $exportHelper->getExportChangesFilePath();

        if (!file_exists($fileExportPath)) return;

        $newFilename = $exportHelper->generateExportChangesFilePathConfigChanges();
        @rename($fileExportPath, $newFilename);
    }
}
