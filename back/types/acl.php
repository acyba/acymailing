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
        unset($this->groups[ACYM_ADMIN_GROUP]);

        $this->choices[] = acym_selectOption('all', acym_translation('ACYM_ALL'));
        $this->choices[] = acym_selectOption('custom', acym_translation('ACYM_CUSTOM'));
    }

    public function display($page)
    {
        $name = 'acl_'.$page;
        $custom = $this->config->get($name, 'all') !== 'all';

        $result = acym_radio(
            $this->choices,
            'config['.$name.']',
            $custom ? 'custom' : 'all'
        );

        $containerClass = 'cell grid-x margin-top-1 margin-bottom-2';
        if (!$custom) $containerClass .= ' is-hidden';
        $result .= '<div id="'.$name.'_container" class="'.$containerClass.'">';
        foreach ($this->groups as $id => $oneGroup) {
            $nameForGroup = $name.'_'.$id;
            $result .= '<div class="cell small-6 large-4 xlarge-2 acym_vcenter">'.$oneGroup->text.'</div>';
            $result .= acym_boolean(
                'config['.$nameForGroup.']',
                $this->config->get($nameForGroup, '1') === '1',
                '',
                [
                    'containerClass' => 'cell small-6 large-8 xlarge-4',
                    'data-abide-ignore' => '',
                ]
            );
        }
        $result .= '</div>';

        return $result;
    }
}
