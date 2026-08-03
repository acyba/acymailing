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

        $this->identifier = (string)acym_rand(1000, 9000);
    }

    public function startTab(string $title, bool $selected = false, bool $clickable = true): string
    {
        if ($this->opened) {
            $this->endTab();
        }

        $this->opened = true;
        $this->identifier = preg_replace('#[^a-z0-9]#is', '_', strtolower($title));

        $this->titles[] = [
            'title' => $title,
            'clickable' => $clickable,
            'selected' => $selected,
            'identifier' => $this->identifier,
            'tabNumber' => $this->tabNumber,
        ];

        ob_start();

        return $this->identifier;
    }

    public function endTab(): void
    {
        if (!$this->opened) {
            return;
        }

        $this->opened = false;
        $this->content[] = '<div class="tabs-panel" id="tab_'.acym_escape($this->identifier.'_'.$this->tabNumber).'">'.ob_get_clean().'</div>';
        $this->tabNumber++;
    }

    public function addCallbackElement(callable $callable, string $identifier = ''): void
    {
        $this->inBarElements[] = [
            'callable' => $callable,
            'identifier' => $identifier,
        ];
    }

    public function display(string $tabId): void
    {
        if ($this->opened) {
            $this->endTab();
        }

        echo '<ul class="tabs" data-tabs id="'.acym_escape($tabId).'">';
        foreach ($this->titles as $title) {
            echo '<li class="tabs-title '.acym_escape($title['clickable'] ? '' : 'tabs-title-empty').'">
                    <a class="acym_tab acym__color__medium-gray" 
                    '.($title['clickable'] ? '' : 'data-empty="true"').' 
                    '.($title['selected'] ? 'data-selected="true"' : '').' 
                        href="#" 
                        data-tab-identifier="'.acym_escape($title['identifier']).'" 
                        data-tabs-target="tab_'.acym_escape($title['identifier'].'_'.$title['tabNumber']).'">'.acym_escapeHtml($title['title']).'</a>
                </li>';
        }

        if (!empty($this->inBarElements)) {
            echo '<div class="acym__tabs__inbar">';
            foreach ($this->inBarElements as $oneElement) {
                echo '<div class="acym__tabs__inbar__element" ';
                if (!empty($oneElement['identifier'])) {
                    echo 'acym-data-identifier="'.acym_escape($oneElement['identifier']).'" style="display: none;"';
                }
                echo '>';
                $oneElement['callable']();
                echo '</div>';
            }
            echo '</div>';
        }

        echo '</ul>';

        echo '<div class="tabs-content margin-bottom-1" data-tabs-content="'.acym_escape($tabId).'">';
        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Content escaped from the add-on/plugin side.
        echo implode('', $this->content);
        echo '</div>';
    }
}
