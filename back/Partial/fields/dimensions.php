<?php
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- View file, its variables are local to the include scope, not true globals.
// context verification
$widthUnit = 'px';
$heightUnit = 'px';

if (!empty($option['units'][$form->type])) {
    $widthUnit = $option['units'][$form->type]['width'];
    $heightUnit = $option['units'][$form->type]['height'];
}
?>

<div class="cell grid-x acym_vcenter">
	<input type="number" class="cell medium-3" v-model="<?php echo acym_escape($vModel); ?>.width">
	<span class="cell shrink acym__forms__menu__options__style__size__default"><?php echo acym_escapeHtml($widthUnit); ?></span>
	<span class="cell shrink margin-1">x</span>
	<input type="number" class="cell medium-3" v-model="<?php echo acym_escape($vModel); ?>.height">
	<span class="cell shrink acym__forms__menu__options__style__size__default"><?php echo acym_escapeHtml($heightUnit); ?></span>
</div>
