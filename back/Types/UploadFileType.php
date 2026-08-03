<?php

namespace AcyMailing\Types;

use AcyMailing\Core\AcymObject;

class UploadFileType extends AcymObject
{
    public function display(string $map, int $num): void
    {
        echo '<input type="hidden" name="'.acym_escape($map).'[]" id="'.acym_escape($map.$num).'" />';

        $ctrlFile = acym_isAdmin() ? 'file' : 'frontfile';
        acym_modal(
            acym_translation('ACYM_SELECT'),
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

        echo '<span id="'.acym_escape($map.$num).'selection" class="acy_selected_attachment cell medium-shrink"></span>';
    }
}
