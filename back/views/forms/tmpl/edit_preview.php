<div class="cell grid-x acym__content" id="acym__forms__preview">
	<div class="cell acym_vcenter" id="acym__forms__preview__bar">
		<span class="acym__forms__preview__bar__button acym__forms__preview__button-red"></span>
		<span class="acym__forms__preview__bar__button acym__forms__preview__button-orange"></span>
		<span class="acym__forms__preview__bar__button acym__forms__preview__button-green"></span>
		<span class="acym__forms__preview__bar__search"><?php echo ACYM_LIVE; ?></span>
	</div>
	<iframe src="<?php echo ACYM_LIVE.'?acym_preview=1'; ?>" id="acym__forms__preview__content" class="cell"></iframe>
	<div id="acym__forms__preview__content__loader" class="grid-x cell align-center acym_vcenter" v-if="loading"><?php echo acym_loaderLogo(); ?>'</div>
</div>
