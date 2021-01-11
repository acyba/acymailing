<div class="cell grid-x acym__content" id="acym__forms__menu">
	<div class="cell grid-x acym__forms__menu__switch__container">
		<div class="cell small-6 acym__forms__menu__switch acym_vcenter align-center"
			 @click="changeMenuActive('settings')"
			 :class="{'acym__forms__menu__switch__active': isMenuSettingsActive}"><i class="acymicon-cog"></i></div>
		<div class="cell small-6 acym__forms__menu__switch acym_vcenter align-center"
			 @click="changeMenuActive('style')"
			 :class="{'acym__forms__menu__switch__active': !isMenuSettingsActive}"><i class="acymicon-edit"></i></div>
	</div>
	<div class="cell grid-x acym__forms__menu__container" v-if="isMenuSettingsActive">
        <?php foreach ($data['menu_render_settings'] as $options) {
            if (empty($options)) continue;
            ?>
			<h3 class="cell acym__forms__menu__title acym__title acym__title__tertiary"><?php echo $options['title']; ?></h3>
            <?php foreach ($options['render'] as $html) { ?>
				<div class="cell grid-x acym__forms__menu__options grid-margin-x acym_vcenter"><?php echo $html; ?></div>
            <?php } ?>
        <?php } ?>
	</div>
	<div class="cell grid-x acym__forms__menu__container" v-if="!isMenuSettingsActive">
        <?php foreach ($data['menu_render_style'] as $options) {
            if (empty($options)) continue;
            ?>
			<h3 class="cell acym__forms__menu__title acym__title acym__title__tertiary"><?php echo $options['title']; ?></h3>
            <?php foreach ($options['render'] as $html) { ?>
				<div class="cell grid-x acym__forms__menu__options grid-margin-x acym_vcenter"><?php echo $html; ?></div>
            <?php } ?>
        <?php } ?>
	</div>
</div>
