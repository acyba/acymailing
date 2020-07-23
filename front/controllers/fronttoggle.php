<?php

include ACYM_CONTROLLER.'toggle.php';

class FronttoggleController extends ToggleController
{
    public function __construct()
    {
        $this->authorizedFrontTasks = ['toggle'];
        parent::__construct();
    }

    protected function defineToggles()
    {
        // $this->toggleableColumns[TABLE NAME WITHOUT PREFIX] = [COLUMN NAME => PRIMARY KEY];
        $this->toggleableColumns['list'] = ['active' => 'id', 'visible' => 'id'];
        $this->toggleableColumns['user'] = ['active' => 'id', 'confirmed' => 'id'];

        // $this->icons[TABLE NAME WITHOUT PREFIX][COLUMN NAME][VALUE] = ICON CLASS;
        $this->icons['list']['active'][1] = 'acymicon-check-circle acym__color__green';
        $this->icons['list']['active'][0] = 'acymicon-times-circle acym__color__red';
        $this->icons['list']['visible'][1] = 'acymicon-eye';
        $this->icons['list']['visible'][0] = 'acymicon-eye-slash acym__color__dark-gray';
        $this->icons['user']['active'][1] = 'acymicon-check-circle acym__color__green';
        $this->icons['user']['active'][0] = 'acymicon-times-circle acym__color__red';
        $this->icons['user']['confirmed'][1] = 'acymicon-check-circle acym__color__green';
        $this->icons['user']['confirmed'][0] = 'acymicon-times-circle acym__color__red';

        $this->tooltips['user']['active'][1] = 'ACYM_ACTIVATED';
        $this->tooltips['user']['active'][0] = 'ACYM_DEACTIVATED';
        $this->tooltips['user']['confirmed'][1] = 'ACYM_CONFIRMED';
        $this->tooltips['user']['confirmed'][0] = 'ACYM_NOT_CONFIRMED';
        $this->tooltips['list']['active'][1] = 'ACYM_ACTIVATED';
        $this->tooltips['list']['active'][0] = 'ACYM_DEACTIVATED';
        $this->tooltips['list']['visible'][1] = 'ACYM_VISIBLE';
        $this->tooltips['list']['visible'][0] = 'ACYM_INVISIBLE';
    }

    protected function listGlobal($id, $table, $field, $newValue)
    {
        $listClass = acym_get('class.list');
        $lists = $listClass->getManageableLists();

        if (!in_array($id, $lists)) exit;

        $this->doToggle($id, $table, $field, $newValue);
    }

    protected function userGlobal($id, $table, $field, $newValue)
    {
        $arrayVersion = [$id];
        $userClass = acym_get('class.user');
        $userClass->onlyManageableUsers($arrayVersion);

        if (empty($arrayVersion)) exit;

        $this->doToggle($id, $table, $field, $newValue);
    }
}
