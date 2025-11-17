<?php

namespace AcyMailing\Controllers;

use AcyMailing\Classes\ListClass;
use AcyMailing\Classes\UserClass;
use AcyMailing\Core\AcymController;

class ToggleController extends AcymController
{
    protected array $toggleableColumns = [];
    protected array $icons = [];
    protected array $tooltips = [];
    private array $deletableRows = [];

    public function __construct()
    {
        parent::__construct();

        $this->defaulttask = 'toggle';

        $this->defineToggles();

        // Avoid caching issue in Internet Explorer
        acym_noCache();
    }

    protected function defineToggles(): void
    {
        // $this->toggleableColumns[TABLE NAME WITHOUT PREFIX] = array(COLUMN NAME => PRIMARY KEY);
        $this->toggleableColumns['automation'] = ['active' => 'id'];
        $this->toggleableColumns['field'] = [
            'active' => 'id',
            'required' => 'id',
            'backend_edition' => 'id',
            'backend_listing' => 'id',
            'frontend_edition' => 'id',
            'frontend_listing' => 'id',
        ];
        $this->toggleableColumns['list'] = ['active' => 'id', 'visible' => 'id'];
        $this->toggleableColumns['rule'] = ['active' => 'id'];
        $this->toggleableColumns['user'] = ['active' => 'id', 'confirmed' => 'id'];
        $this->toggleableColumns['form'] = ['active' => 'id'];
        $this->toggleableColumns['segment'] = ['active' => 'id'];
        $this->toggleableColumns['campaign'] = ['visible' => 'id'];
        $this->toggleableColumns['followup'] = ['active' => 'id'];
        $this->toggleableColumns['mail_override'] = ['active' => 'id'];
        $this->toggleableColumns['mailbox_action'] = ['active' => 'id'];
        $this->toggleableColumns['scenario'] = ['active' => 'id'];

        // $this->icons[TABLE NAME WITHOUT PREFIX][COLUMN NAME][VALUE] = ICON CLASS;
        $this->icons['automation']['active'][1] = 'acymicon-check-circle acym__color__green';
        $this->icons['automation']['active'][0] = 'acymicon-times-circle acym__color__red';
        $this->icons['field']['active'][1] = 'acymicon-check-circle acym__color__green';
        $this->icons['field']['active'][0] = 'acymicon-times-circle acym__color__red';
        $this->icons['field']['required'][1] = 'acymicon-check-circle acym__color__green';
        $this->icons['field']['required'][0] = 'acymicon-times-circle acym__color__red';
        $this->icons['field']['backend_edition'][1] = 'acymicon-check-circle acym__color__green';
        $this->icons['field']['backend_edition'][0] = 'acymicon-times-circle acym__color__red';
        $this->icons['field']['backend_listing'][1] = 'acymicon-check-circle acym__color__green';
        $this->icons['field']['backend_listing'][0] = 'acymicon-times-circle acym__color__red';
        $this->icons['field']['frontend_edition'][1] = 'acymicon-check-circle acym__color__green';
        $this->icons['field']['frontend_edition'][0] = 'acymicon-times-circle acym__color__red';
        $this->icons['field']['frontend_listing'][1] = 'acymicon-check-circle acym__color__green';
        $this->icons['field']['frontend_listing'][0] = 'acymicon-times-circle acym__color__red';
        $this->icons['list']['active'][1] = 'acymicon-check-circle acym__color__green';
        $this->icons['list']['active'][0] = 'acymicon-times-circle acym__color__red';
        $this->icons['list']['visible'][1] = 'acymicon-eye';
        $this->icons['list']['visible'][0] = 'acymicon-eye-slash acym__color__dark-gray';
        $this->icons['rule']['active'][0] = 'acymicon-times-circle acym__color__red';
        $this->icons['rule']['active'][1] = 'acymicon-check-circle acym__color__green';
        $this->icons['user']['active'][1] = 'acymicon-check-circle acym__color__green';
        $this->icons['user']['active'][0] = 'acymicon-times-circle acym__color__red';
        $this->icons['user']['confirmed'][1] = 'acymicon-check-circle acym__color__green';
        $this->icons['user']['confirmed'][0] = 'acymicon-times-circle acym__color__red';
        $this->icons['form']['active'][1] = 'acymicon-check-circle acym__color__green';
        $this->icons['form']['active'][0] = 'acymicon-times-circle acym__color__red';
        $this->icons['segment']['active'][1] = 'acymicon-check-circle acym__color__green';
        $this->icons['segment']['active'][0] = 'acymicon-times-circle acym__color__red';
        $this->icons['campaign']['visible'][1] = 'acymicon-eye';
        $this->icons['campaign']['visible'][0] = 'acymicon-eye-slash acym__color__dark-gray';
        $this->icons['followup']['active'][1] = 'acymicon-check-circle acym__color__green';
        $this->icons['followup']['active'][0] = 'acymicon-times-circle acym__color__red';
        $this->icons['mail_override']['active'][1] = 'acymicon-check-circle acym__color__green';
        $this->icons['mail_override']['active'][0] = 'acymicon-times-circle acym__color__red';
        $this->icons['mailbox_action']['active'][1] = 'acymicon-check-circle acym__color__green';
        $this->icons['mailbox_action']['active'][0] = 'acymicon-times-circle acym__color__red';
        $this->icons['scenario']['active'][1] = 'acymicon-check-circle acym__color__green';
        $this->icons['scenario']['active'][0] = 'acymicon-times-circle acym__color__red';

        $this->tooltips['user']['active'][1] = 'ACYM_ACTIVATED';
        $this->tooltips['user']['active'][0] = 'ACYM_DEACTIVATED';
        $this->tooltips['user']['confirmed'][1] = 'ACYM_CONFIRMED';
        $this->tooltips['user']['confirmed'][0] = 'ACYM_NOT_CONFIRMED';
        $this->tooltips['list']['active'][1] = 'ACYM_ACTIVATED';
        $this->tooltips['list']['active'][0] = 'ACYM_DEACTIVATED';
        $this->tooltips['list']['visible'][1] = 'ACYM_VISIBLE';
        $this->tooltips['list']['visible'][0] = 'ACYM_INVISIBLE';
        $this->tooltips['campaign']['visible'][0] = 'ACYM_INVISIBLE';
        $this->tooltips['campaign']['visible'][1] = 'ACYM_VISIBLE';
        $this->tooltips['mail_override']['active'][0] = 'ACYM_INACTIVE';
        $this->tooltips['mail_override']['active'][1] = 'ACYM_ACTIVE';

        // $this->deletableRows[] = TABLE NAME WITHOUT PREFIX;
        $this->deletableRows[] = 'mail';
        $this->deletableRows[] = 'queue';
    }

    public function toggle(): void
    {
        acym_checkToken();

        $table = acym_getVar('word', 'table', '');
        $field = acym_getVar('cmd', 'field', '');
        $id = acym_getVar('int', 'id', 0);
        $newValue = acym_getVar('int', 'value', 0);
        if (!empty($newValue)) {
            $newValue = 1;
        }

        if (empty($table) || empty($field) || empty($id) || empty($this->toggleableColumns[$table][$field])) {
            exit;
        }

        $preciseMethod = $table.ucfirst($field);
        $globalMethod = $table.'Global';
        if (method_exists($this, $preciseMethod)) {
            $this->$preciseMethod($id, $table, $field, $newValue);
        } elseif (method_exists($this, $globalMethod)) {
            $this->$globalMethod($id, $table, $field, $newValue);
        } else {
            $this->doToggle($id, $table, $field, $newValue);
        }

        acym_trigger('onAcymToggle'.ucfirst($table).ucfirst($field), [&$id, &$newValue]);

        // Return the replacement icon
        if (empty($this->icons[$table][$field][$newValue])) {
            acym_sendAjaxResponse(acym_translation('ACYM_UNAUTHORIZED_ACCESS'), [], false);
        }

        $data = [];
        $data['value'] = 1 - $newValue;
        $data['classes'] = 'acym_toggleable '.$this->icons[$table][$field][$newValue];

        if (!empty($this->tooltips[$table][$field][$newValue])) {
            $data['tooltip'] = ucfirst(acym_translation($this->tooltips[$table][$field][$newValue]));
        }

        acym_sendAjaxResponse('', $data);
    }

    protected function doToggle(int $id, string $table, string $field, int $newValue): void
    {
        $updateQuery = 'UPDATE '.acym_secureDBColumn(ACYM_DBPREFIX.$table);
        $updateQuery .= ' SET `'.acym_secureDBColumn($field).'` = '.$newValue;
        $updateQuery .= ' WHERE `'.acym_secureDBColumn($this->toggleableColumns[$table][$field]).'` = '.$id;
        $updateQuery .= ' LIMIT 1';
        acym_query($updateQuery);
    }

    public function delete(): void
    {
        if (!acym_isAdmin()) exit;
        acym_checkToken();

        $table = acym_getVar('word', 'table', '');
        $id = acym_getVar('int', 'id', 0);

        if (empty($table) || !in_array($table, $this->deletableRows) || empty($id)) {
            exit;
        }

        $method = $table === 'queue' ? 'deleteQueuedByUserIds' : 'delete';

        $namespaceClass = 'AcyMailing\\Classes\\'.ucfirst($table).'Class';
        $elementClass = new $namespaceClass();
        $elementClass->$method([$id]);

        exit;
    }

    public function setDoNotRemindMe(): void
    {
        $newValue = acym_getVar('string', 'value');

        if (empty($newValue)) {
            acym_sendAjaxResponse(acym_translation('ACYM_ERROR_SAVING'), [], false);
        }

        $remindMe = json_decode($this->config->get('remindme', '[]'));
        if (!in_array($newValue, $remindMe)) {
            $remindMe[] = $newValue;
        }

        if ($this->config->saveConfig(['remindme' => json_encode($remindMe)])) {
            acym_sendAjaxResponse(acym_translation('ACYM_THANKS'));
        } else {
            acym_sendAjaxResponse(acym_translation('ACYM_ERROR_SAVING'), [], false);
        }
    }

    public function subscribeOnClick(): void
    {
        $userIds = acym_getVar('array', 'userid', []);
        $listIds = acym_getVar('array', 'listid', []);

        $userClass = new UserClass();
        $userClass->onlyManageableUsers($userIds);

        $listClass = new ListClass();
        $listClass->onlyManageableLists($listIds);

        $userClass = new UserClass();
        $result = $userClass->subscribe($userIds, $listIds);

        if ($result) {
            acym_sendAjaxResponse();
        } else {
            acym_sendAjaxResponse('', [], false);
        }
    }

    public function unsubscribeOnClick(): void
    {
        $userIds = acym_getVar('array', 'userid', []);
        $listIds = acym_getVar('array', 'listid', []);

        $userClass = new UserClass();
        $userClass->onlyManageableUsers($userIds);

        $listClass = new ListClass();
        $listClass->onlyManageableLists($listIds);

        $userClass = new UserClass();
        $result = $userClass->unsubscribe($userIds, $listIds);

        if ($result) {
            acym_sendAjaxResponse();
        } else {
            acym_sendAjaxResponse('', [], false);
        }
    }
}
