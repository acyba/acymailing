<?php
if ($form->type === self::SUB_FORM_TYPE_POPUP) {
    $positions = ['image-top', 'image-bottom', 'image-right', 'image-left'];
} else {
    $positions = ['button-left', 'button-right'];
}
?>
<div class="cell grid-x auto">
	<input type="hidden" v-model="<?php echo acym_escape($vModel); ?>" name="<?php echo acym_escape($name); ?>">
    <?php
    foreach ($positions as $value) {
        $class = ':class="{\'position_selected\': '.$vModel.' === \''.$value.'\'}"';
        $click = '@click="selectPosition(\''.$value.'\')"';
        if ($value === 'button-left') {
            echo '<span class="cell shrink acym__forms__menu__position__button acym_vcenter" '.$class.' '.$click.'><i class="acymicon-crop_16_9"></i><i class="acymicon-menu"></i></span>';
        } elseif ($value === 'button-right') {
            echo '<span class="cell shrink acym__forms__menu__position__button acym_vcenter" '.$class.' '.$click.'><i class="acymicon-menu"></i><i class="acymicon-crop_16_9"></i></span>';
        } elseif ($value === 'image-top') {
            echo '<span class="cell shrink acym__forms__menu__position__button acym_vcenter grid-x text-center" '.$class.' '.$click.'><i class="acymicon-insert_photo cell"></i><i class="acymicon-menu cell"></i></span>';
        } elseif ($value === 'image-bottom') {
            echo '<span class="cell shrink acym__forms__menu__position__button acym_vcenter grid-x text-center" '.$class.' '.$click.'><i class="acymicon-menu cell"></i><i class="acymicon-insert_photo cell"></i></span>';
        } elseif ($value === 'image-right') {
            echo '<span class="cell shrink acym__forms__menu__position__button acym_vcenter" '.$class.' '.$click.'><i class="acymicon-menu"></i><i class="acymicon-insert_photo"></i></span>';
        } elseif ($value === 'image-left') {
            echo '<span class="cell shrink acym__forms__menu__position__button acym_vcenter" '.$class.' '.$click.'><i class="acymicon-insert_photo"></i><i class="acymicon-menu"></i></span>';
        }
    }
    ?>
</div>
