<?php

class ToggleController extends acymController
{
    var $toggleableColumns = [];
    var $icons = [];
    var $tooltips = [];
    var $deletableRows = [];

    public function __construct()
    {
        parent::__construct();

        $this->defaulttask = 'toggle';

        $this->defineToggles();

        // Avoid caching issue in Internet Explorer
        acym_noCache();
    }

    protected function defineToggles()
    {
        // $this->toggleableColumns[TABLE NAME WITHOUT PREFIX] = array(COLUMN NAME => PRIMARY KEY);
        $this->toggleableColumns['automation'] = ['active' => 'id'];
        $this->toggleableColumns['field'] = ['active' => 'id', 'required' => 'id', 'backend_edition' => 'id', 'backend_listing' => 'id', 'frontend_edition' => 'id', 'frontend_listing' => 'id'];
        $this->toggleableColumns['list'] = ['active' => 'id', 'visible' => 'id'];
        $this->toggleableColumns['rule'] = ['active' => 'id'];
        $this->toggleableColumns['user'] = ['active' => 'id', 'confirmed' => 'id'];
        $this->toggleableColumns['form'] = ['active' => 'id'];
        $this->toggleableColumns['campaign'] = ['visible' => 'id'];

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
        $this->icons['campaign']['visible'][1] = 'acymicon-eye';
        $this->icons['campaign']['visible'][0] = 'acymicon-eye-slash acym__color__dark-gray';

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

        // $this->deletableRows[] = TABLE NAME WITHOUT PREFIX;
        $this->deletableRows[] = 'mail';
        $this->deletableRows[] = 'queue';
    }

    public function toggle()
    {
        acym_checkToken();

        $table = acym_getVar('word', 'table', '');
        $field = acym_getVar('cmd', 'field', '');
        $id = acym_getVar('int', 'id', 0);
        $newValue = acym_getVar('int', 'value', 0);
        if (!empty($newValue)) $newValue = 1;

        if (empty($table) || empty($field) || empty($id) || empty($this->toggleableColumns[$table][$field])) exit;

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
            echo 'test';
            exit;
        }

        $result = [];
        $result['value'] = 1 - $newValue;
        $result['classes'] = 'acym_toggleable '.$this->icons[$table][$field][$newValue];

        if (!empty($this->tooltips[$table][$field][$newValue])) {
            $result['tooltip'] = ucfirst(acym_translation($this->tooltips[$table][$field][$newValue]));
        }

        echo json_encode($result);
        exit;
    }

    protected function doToggle($id, $table, $field, $newValue)
    {
        $updateQuery = 'UPDATE '.acym_secureDBColumn(ACYM_DBPREFIX.$table);
        $updateQuery .= ' SET `'.acym_secureDBColumn($field).'` = '.intval($newValue);
        $updateQuery .= ' WHERE `'.acym_secureDBColumn($this->toggleableColumns[$table][$field]).'` = '.intval($id);
        $updateQuery .= ' LIMIT 1';
        acym_query($updateQuery);
    }

    public function delete()
    {
        if (!acym_isAdmin()) exit;
        acym_checkToken();

        $table = acym_getVar('word', 'table', '');
        $id = acym_getVar('cmd', 'id', 0);
        $method = acym_getVar('word', 'method', 'delete');

        if (empty($table) || !in_array($table, $this->deletableRows) || empty($id)) {
            exit;
        }

        $elementClass = acym_get('class.'.$table);
        $elementClass->$method($id);

        exit;
    }

    public function getIntroJSConfig()
    {
        echo $this->config->get('introjs', '[]');
        exit;
    }

    public function toggleIntroJS()
    {
        $toggleElement = acym_getVar('string', 'where');
        $intro = json_decode($this->config->get('introjs', '[]'), true);
        $intro[$toggleElement] = 1;
        $newConfig = new stdClass();
        $newConfig->introjs = json_encode($intro);
        $this->config->save($newConfig);
        exit;
    }

    public function setDoNotRemindMe()
    {
        $newValue = acym_getVar('string', 'value');

        $return = [];
        $return['error'] = '';

        if (empty($newValue)) {
            $return['error'] = acym_translation('ACYM_ERROR_SAVING');
            echo json_encode($return);
            exit;
        }

        $newConfig = new stdClass();
        $newConfig->remindme = json_decode($this->config->get('remindme', '[]'));
        if (!in_array($newValue, $newConfig->remindme)) array_push($newConfig->remindme, $newValue);
        $newConfig->remindme = json_encode($newConfig->remindme);

        if ($this->config->save($newConfig)) {
            $return['message'] = acym_translation('ACYM_THANKS');
        } else {
            $return['error'] = acym_translation('ACYM_ERROR_SAVING');
        }

        echo json_encode($return);
        exit;
    }
}
