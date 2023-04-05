<?php

namespace AcyMailing\Types;

use AcyMailing\Classes\ListClass;
use AcyMailing\Libraries\acymObject;

class FailactionType extends acymObject
{
    public $values = [];
    public $lists = [];

    public function __construct()
    {
        parent::__construct();

        $this->values[] = acym_selectOption('noaction', 'ACYM_DO_NOTHING');
        $this->values[] = acym_selectOption('remove', 'ACYM_REMOVE_SUB');
        $this->values[] = acym_selectOption('unsub', 'ACYM_UNSUB_USER');
        $this->values[] = acym_selectOption('sub', 'ACYM_SUBSCRIBE_USER');
        $this->values[] = acym_selectOption('block', 'ACYM_BLOCK_USER');
        $this->values[] = acym_selectOption('delete', 'ACYM_DELETE_USER');

        $listClass = new ListClass();
        $lists = $listClass->getAll('name');
        foreach ($lists as $oneList) {
            $this->lists[] = acym_selectOption($oneList->id, $oneList->name);
        }

        $js = 'function updateSubAction(num){
                    window.document.getElementById("bounce_action_lists_"+num).style.display = window.document.getElementById("bounce_action_"+num).value == "sub" ? "" : "none";
                }';
        acym_addScript(true, $js);
    }

    public function display($num, $value)
    {
        $js = 'jQuery(document).ready(function($){ updateSubAction("'.$num.'"); });';
        acym_addScript(true, $js);

        $return = acym_select(
            $this->values,
            'config[bounce_action_'.$num.']',
            $value,
            'class="intext_select" style="width: 200px;" onchange="updateSubAction(\''.$num.'\');"',
            'value',
            'text',
            'bounce_action_'.$num
        );

        //We add a span with an ID around the dropdown as we can't hide/display the dropdown itself any more due to bootstrap integration
        $return .= '<span id="bounce_action_lists_'.$num.'" style="display:none">';

        $return .= acym_select(
            $this->lists,
            'config[bounce_action_lists_'.$num.']',
            $this->config->get('bounce_action_lists_'.$num),
            'class="intext_select" style="width: 200px;margin-left: 5px;"',
            'value',
            'text',
            str_replace(['[', ']'], ['_', ''], 'config[bounce_action_lists_'.$num.']')
        );

        $return .= '</span>';

        return $return;
    }
}
