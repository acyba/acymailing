<?php

namespace AcyMailing\Types;

use AcyMailing\Libraries\acymObject;

class AclType extends acymObject
{
    private $groups;
    private $choices = [];

    public function __construct()
    {
        parent::__construct();

        $this->groups = acym_getGroups();

        $allGroups = new \stdClass();
        $allGroups->value = 'all';
        $allGroups->text = acym_translation('ACYM_ALL');

        $seprator = new \stdClass();
        $seprator->value = '';
        $seprator->text = '-----------------------------';
        $seprator->disable = true;
        array_unshift($this->groups, $seprator);
        array_unshift($this->groups, $allGroups);

        unset($this->groups[ACYM_ADMIN_GROUP]);

        $this->choices[] = acym_selectOption('all', acym_translation('ACYM_ALL'));
        $this->choices[] = acym_selectOption('custom', acym_translation('ACYM_CUSTOM'));
    }

    public function display($page)
    {
        $name = 'acl_'.$page;

        $selected = explode(',', $this->config->get($name, 'all'));

        return acym_selectMultiple($this->groups, 'config['.$name.']', $selected, ['class' => 'acym__select']);
    }
}
