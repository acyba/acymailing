<?php

namespace AcyMailing\Helpers;

use AcyMailing\Core\AcymObject;

class TabHelper extends AcymObject
{
    private array $titles = [];
    private array $content = [];
    private array $inBarElements = [];
    private int $tabNumber = 0;
    private bool $opened = false;
    private string $identifier;

    public function __construct()
    {
        parent::__construct();

        $this->identifier = (string)rand(1000, 9000);
    }

    public function startTab(string $title, bool $selected = false, string $attributes = '', bool $clickable = true): string
    {
        if ($this->opened) {
            $this->endTab();
        }
        $this->opened = true;

        $attributes .= $clickable ? '' : 'data-empty="true"';
        $attributes .= $selected ? 'data-selected="true"' : '';
        $classLi = $clickable ? '' : 'tabs-title-empty';

        $this->identifier = preg_replace('#[^a-z0-9]#is', '_', strtolower((string)$title));

        $this->titles[] = '<li class="tabs-title '.$classLi.'"><a class="acym_tab acym__color__medium-gray" '.$attributes.' href="#" data-tab-identifier="'.$this->identifier.'" data-tabs-target="tab_'.$this->identifier.'_'.$this->tabNumber.'">'.$title.'</a></li>';

        ob_start();

        return $this->identifier;
    }

    public function endTab(): void
    {
        if (!$this->opened) {
            return;
        }

        $this->opened = false;
        $this->content[] = '<div class="tabs-panel" id="tab_'.$this->identifier.'_'.$this->tabNumber.'">'.ob_get_clean().'</div>';
        $this->tabNumber++;
    }

    public function addElementInBar(string $element, string $identifier = ''): void
    {
        $this->inBarElements[] = [
            'element' => $element,
            'identifier' => $identifier,
        ];
    }

    public function display(string $tabId): void
    {
        if ($this->opened) {
            $this->endTab();
        }

        $tabSystem = '<ul class="tabs" data-tabs id="'.acym_escape($tabId).'">';
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

        $tabSystem .= '<div class="tabs-content margin-bottom-1" data-tabs-content="'.acym_escape($tabId).'">';
        $tabSystem .= implode('', $this->content);
        $tabSystem .= '</div>';

        echo $tabSystem;
    }
}
