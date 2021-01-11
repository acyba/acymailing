<?php

namespace AcyMailing\Types;

use AcyMailing\Libraries\acymObject;

class UploadfileType extends acymObject
{
    public function display($map, $value)
    {
        $result = '<input type="hidden" name="'.$map.'[]" id="'.$map.$value.'" />';

        $buttonLoad = acym_translation('ACYM_SELECT');
        $ctrlFile = acym_isAdmin() ? 'file' : 'frontfile';
        $result .= acym_modal(
            $buttonLoad,
            '',
            'acym__campaign__email__'.$map.$value,
            'width="800" style="width:800px;" data-reveal-larger',
            'class="button-secondary button acym__campaign__attach__button margin-top-0 margin-bottom-0 cell medium-shrink" data-iframe="'.acym_completeLink(
                $ctrlFile.'&task=select&id='.$map.$value,
                true
            ).'" data-ajax="false"'
        );

        $result .= '<span id="'.$map.$value.'selection" class="acy_selected_attachment cell medium-shrink"></span>';

        return $result;
    }
}
