<?php

namespace AcyMailing\Helpers;

use AcyMailing\Libraries\acymObject;

class ToolbarHelper extends acymObject
{
    var $leftPart = '';
    var $rightPart = '';
    var $nonPermanentPart = '';
    var $moreOptionsPart = [];

    public function addButton($textContent, $attributes, $icon = '', $isPrimary = false)
    {
        $data = [];
        $data['icon'] = $icon;
        $data['content'] = acym_translation($textContent);
        $data['attributes'] = $attributes;
        $data['isPrimary'] = $isPrimary;

        ob_start();
        include acym_getPartial('toolbar', 'button_main');
        $this->rightPart .= ob_get_clean();
    }

    public function addOtherContent($content, $side = 'right')
    {
        if ($side === 'right' || $side === 'left') {
            $this->{$side.'Part'} .= $content;
        }
    }

    public function addSearchBar($search, $name, $placeholder = 'ACYM_SEARCH', $showClearBtn = true)
    {
        $this->leftPart .= acym_filterSearch($search, $name, $placeholder, $showClearBtn, 'acym__toolbar__search-field margin-bottom-0');
    }

    public function addFilterByTag(&$data, $name, $class)
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

    public function addOption($content)
    {
        array_push($this->moreOptionsPart, $content);
    }

    public function addOptionSelect($title, $select)
    {
        $content = '<div class="cell grid-x shrink acym__toolbar__filters__select"><label class="cell">'.$title.'</label>'.$select.'</div>';
        array_push($this->moreOptionsPart, $content);
    }

    public function displayToolbar($data)
    {
        $data['leftPart'] = $this->leftPart;
        $data['rightPart'] = $this->rightPart;
        $data['nonPermanentPart'] = $this->nonPermanentPart;
        $data['moreOptionsPart'] = $this->moreOptionsPart;
        include acym_getPartial('toolbar', 'toolbar');
    }
}
