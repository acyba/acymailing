<?php

namespace AcyMailing\Helpers;

use AcyMailing\Libraries\acymObject;

class TabHelper extends acymObject
{
    var $titles = [];
    var $content = [];
    var $tabNumber = 0;
    var $opened = false;
    var $identifier;
    var $inBarElements = [];

    public function __construct()
    {
        parent::__construct();
        $this->identifier = rand(1000, 9000);
    }

    public function startTab($title, $selected = false, $attributes = '', $clickable = true)
    {
        if ($this->opened) $this->endTab();
        $this->opened = true;

        $attributes .= $clickable ? '' : 'data-empty="true"';
        $attributes .= $selected ? 'data-selected="true"' : '';
        $classLi = $clickable ? '' : 'tabs-title-empty';

        $this->identifier = preg_replace('#[^a-z0-9]#is', '_', strtolower($title));

        $this->titles[] = '<li class="tabs-title '.$classLi.'"><a class="acym_tab acym__color__medium-gray" '.$attributes.' href="#" data-tab-identifier="'.$this->identifier.'" data-tabs-target="tab_'.$this->identifier.'_'.$this->tabNumber.'">'.$title.'</a></li>';

        ob_start();

        return $this->identifier;
    }

    public function endTab()
    {
        if (!$this->opened) return;

        $this->opened = false;
        $this->content[] = '<div class="tabs-panel" id="tab_'.$this->identifier.'_'.$this->tabNumber.'">'.ob_get_clean().'</div>';
        $this->tabNumber++;
    }

    public function addElementInBar($element, $identifier = '')
    {
        $this->inBarElements[] = [
            'element' => $element,
            'identifier' => $identifier,
        ];
    }

    public function display($id)
    {
        if ($this->opened) {
            $this->endTab();
        }

        $tabSystem = '<ul class="tabs" data-tabs id="'.acym_escape($id).'">';
        $tabSystem .= implode('', $this->titles);

        if (!empty($this->inBarElements)) {
            $tabSystem .= '<div class="acym__tabs__inbar">';
            foreach ($this->inBarElements as $oneElement) {
                $displayOptions = '';
                if (!empty($oneElement['identifier'])) {
                    $displayOptions = 'acym-data-identifier="'.acym_escape($oneElement['identifier']).'" style="display: none;"';
                }
                $tabSystem .= '<div class="acym__tabs__inbar__element" '.$displayOptions.'>'.$oneElement['element'].'</div>';
            }
            $tabSystem .= '</div>';
        }

        $tabSystem .= '</ul>';

        $tabSystem .= '<div class="tabs-content margin-bottom-1" data-tabs-content="'.acym_escape($id).'">';
        $tabSystem .= implode('', $this->content);
        $tabSystem .= '</div>';

        echo $tabSystem;
    }
}
