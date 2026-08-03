<?php
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- View file, its variables are local to the include scope, not true globals.
// context verification
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
        if ($value === 'button-left') {
            echo '<span 
            			class="cell shrink acym__forms__menu__position__button acym_vcenter" 
            			:class="{\'position_selected\': '.acym_escape($vModel).' === \''.acym_escape($value).'\'}" 
            			@click="selectPosition(\''.acym_escape($value).'\')"
				  >
				      <i class="acymicon-crop-16-9"></i><i class="acymicon-menu"></i>
				</span>';
        } elseif ($value === 'button-right') {
            echo '<span 
            			class="cell shrink acym__forms__menu__position__button acym_vcenter" 
            			:class="{\'position_selected\': '.acym_escape($vModel).' === \''.acym_escape($value).'\'}" 
            			@click="selectPosition(\''.acym_escape($value).'\')"
				  >
				      <i class="acymicon-menu"></i><i class="acymicon-crop-16-9"></i>
				</span>';
        } elseif ($value === 'image-top') {
            echo '<span 
            			class="cell shrink acym__forms__menu__position__button acym_vcenter grid-x text-center" 
            			:class="{\'position_selected\': '.acym_escape($vModel).' === \''.acym_escape($value).'\'}" 
            			@click="selectPosition(\''.acym_escape($value).'\')"
				  >
				      <i class="acymicon-insert-photo cell"></i><i class="acymicon-menu cell"></i>
				</span>';
        } elseif ($value === 'image-bottom') {
            echo '<span 
            			class="cell shrink acym__forms__menu__position__button acym_vcenter grid-x text-center" 
            			:class="{\'position_selected\': '.acym_escape($vModel).' === \''.acym_escape($value).'\'}" 
            			@click="selectPosition(\''.acym_escape($value).'\')"
				  >
				      <i class="acymicon-menu cell"></i><i class="acymicon-insert-photo cell"></i>
				</span>';
        } elseif ($value === 'image-right') {
            echo '<span 
            			class="cell shrink acym__forms__menu__position__button acym_vcenter" 
            			:class="{\'position_selected\': '.acym_escape($vModel).' === \''.acym_escape($value).'\'}" 
            			@click="selectPosition(\''.acym_escape($value).'\')"
				  >
				      <i class="acymicon-menu"></i><i class="acymicon-insert-photo"></i>
				</span>';
        } elseif ($value === 'image-left') {
            echo '<span 
            			class="cell shrink acym__forms__menu__position__button acym_vcenter" 
            			:class="{\'position_selected\': '.acym_escape($vModel).' === \''.acym_escape($value).'\'}" 
            			@click="selectPosition(\''.acym_escape($value).'\')"
				  >
				      <i class="acymicon-insert-photo"></i><i class="acymicon-menu"></i>
				</span>';
        }
    }
    ?>
</div>
