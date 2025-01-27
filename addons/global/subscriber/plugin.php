<?php

use AcyMailing\Helpers\ExportHelper;
use AcyMailing\Core\AcymPlugin;
use AcyMailing\Classes\UserClass;
use AcyMailing\Classes\FieldClass;
use AcyMailing\Classes\AutomationClass;

require_once __DIR__.DIRECTORY_SEPARATOR.'SubscriberAutomationTriggers.php';
require_once __DIR__.DIRECTORY_SEPARATOR.'SubscriberAutomationConditions.php';
require_once __DIR__.DIRECTORY_SEPARATOR.'SubscriberAutomationFilters.php';
require_once __DIR__.DIRECTORY_SEPARATOR.'SubscriberAutomationActions.php';
require_once __DIR__.DIRECTORY_SEPARATOR.'SubscriberFollowup.php';
require_once __DIR__.DIRECTORY_SEPARATOR.'SubscriberInsertion.php';

class plgAcymSubscriber extends AcymPlugin
{
    use SubscriberAutomationTriggers;
    use SubscriberAutomationConditions;
    use SubscriberAutomationFilters;
    use SubscriberAutomationActions;
    use SubscriberFollowup;
    use SubscriberInsertion;

    private string $followTriggerName = 'user_creation';
    private array $triggerMail = ['user_click', 'user_open'];
    private array $triggers = [
        'user_creation' => 'ACYM_ON_USER_CREATION',
        'user_modification' => 'ACYM_ON_USER_MODIFICATION',
        'user_click' => 'ACYM_WHEN_USER_CLICKS_MAIL',
        'user_open' => 'ACYM_WHEN_USER_OPEN_MAIL',
        'user_confirmation' => 'ACYM_WHEN_USER_CONFIRMS_SUBSCRIPTION',
    ];

    public function __construct()
    {
        parent::__construct();
        $this->pluginDescription->name = acym_translation('ACYM_SUBSCRIBER');
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
