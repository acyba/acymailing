<?php

namespace AcyMailing\Helpers;

use AcyMailing\Libraries\acymObject;

class FormPositionHelper extends acymObject
{
    public function displayPositionButtons($positions, $vModel)
    {
        $html = '<div class="cell grid-x auto"><input type="hidden" v-model="'.$vModel.'" name="">';
        foreach ($positions as $value) {
            $class = ':class="{\'position_selected\': '.$vModel.' === \''.$value.'\'}"';
            $click = '@click="selectPosition(\''.$value.'\')"';
            if ($value == 'button-left') {
                $html .= '<span class="cell shrink acym__forms__menu__position__button acym_vcenter" '.$class.' '.$click.'><i class="acymicon-crop_16_9"></i><i class="acymicon-menu"></i></span>';
            } elseif ($value == 'button-right') {
                $html .= '<span class="cell shrink acym__forms__menu__position__button acym_vcenter" '.$class.' '.$click.'><i class="acymicon-menu"></i><i class="acymicon-crop_16_9"></i></span>';
            } elseif ($value == 'image-top') {
                $html .= '<span class="cell shrink acym__forms__menu__position__button acym_vcenter grid-x text-center" '.$class.' '.$click.'><i class="acymicon-insert_photo cell"></i><i class="acymicon-menu cell"></i></span>';
            } elseif ($value == 'image-bottom') {
                $html .= '<span class="cell shrink acym__forms__menu__position__button acym_vcenter grid-x text-center" '.$class.' '.$click.'><i class="acymicon-menu cell"></i><i class="acymicon-insert_photo cell"></i></span>';
            } elseif ($value == 'image-right') {
                $html .= '<span class="cell shrink acym__forms__menu__position__button acym_vcenter" '.$class.' '.$click.'><i class="acymicon-menu"></i><i class="acymicon-insert_photo"></i></span>';
            } elseif ($value == 'image-left') {
                $html .= '<span class="cell shrink acym__forms__menu__position__button acym_vcenter" '.$class.' '.$click.'><i class="acymicon-insert_photo"></i><i class="acymicon-menu"></i></span>';
            }
        }
        $html .= '</div>';

        return $html;
    }
}
