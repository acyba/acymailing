<?php

namespace AcyMailing\Helpers;

use AcyMailing\Core\AcymObject;

class ToolbarHelper extends AcymObject
{
    private array $searchBarInformation = [];
    private array $actionButtons = [];
    private array $filteringOptions = [];

    public function addSearchBar(string $search, string $name, string $placeholder = 'ACYM_SEARCH', bool $showClearBtn = true): void
    {
        $this->searchBarInformation = [
            'search' => $search,
            'name' => $name,
            'placeholder' => $placeholder,
            'showClearBtn' => $showClearBtn,
        ];
    }

    public function addButton(string $textContent, array $attributes, string $icon = '', bool $isPrimary = false): void
    {
        $this->actionButtons[] = [
            'type' => 'button',
            'icon' => $icon,
            'content' => acym_translation($textContent),
            'attributes' => $attributes,
            'isPrimary' => $isPrimary,
        ];
    }

    public function addModalButton(array $options): void
    {
        $options['type'] = 'modal';
        $this->actionButtons[] = $options;
    }

    public function addFilterByTag(array &$data, string $name, string $class): void
    {
        $allTags = new \stdClass();
        $allTags->name = acym_translation('ACYM_ALL_TAGS');
        $allTags->value = '';
        array_unshift($data['allTags'], $allTags);
        $this->addOptionSelect(
            acym_translation('ACYM_TAG'),
            acym_select(
                $data['allTags'],
                $name,
                $data['tag'],
                [
                    'class' => $class,
                ],
                'value',
                'name'
            )
        );
    }

    public function addOptionSelect(string $title, string $select): void
    {
        $this->filteringOptions[] = [
            'title' => $title,
            'select' => $select,
        ];
    }

    public function displayToolbar(array $data): void
    {
        $data['toolbarHelper'] = $this;
        $data['searchBarInformation'] = $this->searchBarInformation;
        $data['actionButtons'] = $this->actionButtons;
        $data['filteringOptions'] = $this->filteringOptions;
        include acym_getPartial('toolbar', 'toolbar');
    }

    public function displaySearchBar(): void
    {
        acym_filterSearch(
            $this->searchBarInformation['search'],
            $this->searchBarInformation['name'],
            $this->searchBarInformation['placeholder'],
            $this->searchBarInformation['showClearBtn'],
            'acym__toolbar__search-field margin-bottom-0'
        );
    }

    public function displayActionButtons(): void
    {
        foreach ($this->actionButtons as $data) {
            if ($data['type'] === 'button') {
                include acym_getPartial('toolbar', 'button_main');
            } else {
                acym_modal(
                    $data['button'],
                    $data['modalContent'] ?? '',
                    $data['id'] ?? null,
                    $data['attributesModal'] ?? [],
                    $data['attributesButton'] ?? [],
                    $data['isButton'] ?? true,
                    $data['isLarge'] ?? true,
                    $data['classesModal'] ?? '',
                );
            }
        }
    }
}
