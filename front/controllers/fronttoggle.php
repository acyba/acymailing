<?php

namespace AcyMailing\FrontControllers;

use AcyMailing\Classes\ListClass;
use AcyMailing\Classes\UserClass;
use AcyMailing\Controllers\ToggleController;

class FronttoggleController extends ToggleController
{
    public function __construct()
    {
        parent::__construct();

        $this->allowedTasks = [
            'index.php?option=com_acym&view=frontlists&layout=listing' => [
                'toggle',
            ],
            'index.php?option=com_acym&view=frontusers&layout=listing' => [
                'toggle',
            ],
        ];
    }

    protected function defineToggles()
    {
        $currentMenu = acym_getMenu();
        if (empty($currentMenu)) {
            die('Menu not found');
        }

        if ($currentMenu->link === 'index.php?option=com_acym&view=frontlists&layout=listing') {
            $this->toggleableColumns['list'] = ['active' => 'id', 'visible' => 'id'];

            $this->icons['list']['active'][1] = 'acymicon-check-circle acym__color__green';
            $this->icons['list']['active'][0] = 'acymicon-times-circle acym__color__red';
            $this->icons['list']['visible'][1] = 'acymicon-eye';
            $this->icons['list']['visible'][0] = 'acymicon-eye-slash acym__color__dark-gray';

            $this->tooltips['list']['active'][1] = 'ACYM_ACTIVATED';
            $this->tooltips['list']['active'][0] = 'ACYM_DEACTIVATED';
            $this->tooltips['list']['visible'][1] = 'ACYM_VISIBLE';
            $this->tooltips['list']['visible'][0] = 'ACYM_INVISIBLE';
        } elseif ($currentMenu->link === 'index.php?option=com_acym&view=frontusers&layout=listing') {
            $this->toggleableColumns['user'] = ['active' => 'id', 'confirmed' => 'id'];

            $this->icons['user']['active'][1] = 'acymicon-check-circle acym__color__green';
            $this->icons['user']['active'][0] = 'acymicon-times-circle acym__color__red';
            $this->icons['user']['confirmed'][1] = 'acymicon-check-circle acym__color__green';
            $this->icons['user']['confirmed'][0] = 'acymicon-times-circle acym__color__red';

            $this->tooltips['user']['active'][1] = 'ACYM_ACTIVATED';
            $this->tooltips['user']['active'][0] = 'ACYM_DEACTIVATED';
            $this->tooltips['user']['confirmed'][1] = 'ACYM_CONFIRMED';
            $this->tooltips['user']['confirmed'][0] = 'ACYM_NOT_CONFIRMED';
        }
    }

    protected function listGlobal($id, $table, $field, $newValue)
    {
        $listClass = new ListClass();
        $lists = $listClass->getManageableLists();

        if (!in_array($id, $lists)) exit;

        $this->doToggle($id, $table, $field, $newValue);
    }

    protected function userGlobal($id, $table, $field, $newValue)
    {
        $arrayVersion = [$id];
        $userClass = new UserClass();
        $userClass->onlyManageableUsers($arrayVersion);

        if (empty($arrayVersion)) exit;

        $this->doToggle($id, $table, $field, $newValue);
    }
}
