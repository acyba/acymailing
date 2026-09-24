<?php
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- View file, its variables are local to the include scope, not true globals.
// context verification


use AcyMailing\Classes\FormClass;

$formClass = new FormClass();
?>
<div class="cell grid-x acym__content" id="acym__forms__menu">
	<div class="cell grid-x acym__forms__menu__switch__container">
		<div class="cell small-6 acym__forms__menu__switch acym_vcenter align-center"
		     @click="changeMenuActive('settings')"
		     :class="{'acym__forms__menu__switch__active': isMenuSettingsActive}"><i class="acymicon-cog"></i></div>
		<div class="cell small-6 acym__forms__menu__switch acym_vcenter align-center"
		     @click="changeMenuActive('style')"
		     :class="{'acym__forms__menu__switch__active': !isMenuSettingsActive}"><i class="acymicon-edit"></i></div>
	</div>
	<div class="cell grid-x acym__forms__menu__container" v-show="isMenuSettingsActive">
		<?php $formClass->prepareMenuHtml($data['form'], 'options'); ?>
	</div>
	<div class="cell grid-x acym__forms__menu__container" v-show="!isMenuSettingsActive">
		<?php $formClass->prepareMenuHtml($data['form'], 'styles'); ?>
	</div>
</div>
