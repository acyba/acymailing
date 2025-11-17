<?php

namespace AcyMailing\Types;

use AcyMailing\Core\AcymObject;

class UploadFileType extends AcymObject
{
    public function display(string $map, int $num): string
    {
        $result = '<input type="hidden" name="'.acym_escape($map).'[]" id="'.acym_escape($map.$num).'" />';

        $buttonLoad = acym_translation('ACYM_SELECT');
        $ctrlFile = acym_isAdmin() ? 'file' : 'frontfile';
        $result .= acym_modal(
            $buttonLoad,
            '',
            'acym__campaign__email__'.$map.$num,
            [
                'width' => '800',
                'style' => 'width:800px;',
                'data-reveal-larger' => true,
            ],
            [
                'class' => 'button-secondary button acym__campaign__attach__button margin-top-0 margin-bottom-0 cell medium-shrink',
                'data-iframe' => acym_completeLink($ctrlFile.'&task=select&id='.$map.$num, true),
                'data-ajax' => 'false',
            ]
        );

        $result .= '<span id="'.acym_escape($map.$num).'selection" class="acy_selected_attachment cell medium-shrink"></span>';

        return $result;
    }
}
