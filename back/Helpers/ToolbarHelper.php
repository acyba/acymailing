<?php

namespace AcyMailing\Helpers;

use AcyMailing\Core\AcymObject;

class ToolbarHelper extends AcymObject
{
    private string $leftPart = '';
    private string $rightPart = '';
    private array $moreOptionsPart = [];

    public function addButton(string $textContent, array $attributes, string $icon = '', bool $isPrimary = false): void
    {
        $data = [
            'icon' => $icon,
            'content' => acym_translation($textContent),
            'attributes' => $attributes,
            'isPrimary' => $isPrimary,
        ];

        ob_start();
        include acym_getPartial('toolbar', 'button_main');
        $this->rightPart .= ob_get_clean();
    }

    public function addOtherContent(string $content, string $side = 'right'): void
    {
        if (in_array($side, ['left', 'right'])) {
            $this->{$side.'Part'} .= $content;
        }
    }

    public function addSearchBar(string $search, string $name, string $placeholder = 'ACYM_SEARCH', bool $showClearBtn = true): void
    {
        $this->leftPart .= acym_filterSearch($search, $name, $placeholder, $showClearBtn, 'acym__toolbar__search-field margin-bottom-0');
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
                acym_escape($data['tag']),
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
        $this->moreOptionsPart[] = '<div class="cell grid-x shrink acym__toolbar__filters__select"><label class="cell">'.$title.'</label>'.$select.'</div>';
    }

    public function displayToolbar(array $data): void
    {
        $data['leftPart'] = $this->leftPart;
        $data['rightPart'] = $this->rightPart;
        $data['moreOptionsPart'] = $this->moreOptionsPart;
        include acym_getPartial('toolbar', 'toolbar');
    }
}
