<form id="acym_form" action="<?php echo acym_completeLink(acym_getVar('cmd', 'ctrl')); ?>" method="post" name="acyForm">
	<div id="acym__fields__splashscreen" class="acym__content cell grid-x">
		<div class="cell text-center" id="acym__fields__splashscreen__image">
			<img src="<?php echo ACYM_MEDIA_URL.'/images/custom-fields.png'; ?>">
		</div>
		<div class="cell grid-x acym__splashscreen__bloc margin-top-2">
			<div class="cell large-2 hide-for-medium-only hide-for-small-only"></div>
			<div class="cell large-8 grid-x text-center">
				<h3 class="cell acym__title text-center"><?php echo acym_translation('ACYM_WHAT_ARE_CUSTOM_FIELDS'); ?></h3>
				<p class="cell acym__splashscreen__desc"><?php echo acym_translation('ACYM_WHAT_ARE_CUSTOM_FIELDS_DESC'); ?></p>
			</div>
			<div class="cell large-2 hide-for-medium-only hide-for-small-only"></div>
		</div>

		<hr class="cell medium-5 margin-vertical-2">

		<div class="cell grid-x acym__splashscreen__bloc">
			<div class="cell large-2 hide-for-medium-only hide-for-small-only"></div>
			<div class="cell large-8 grid-x text-center">
				<h3 class="cell acym__title text-center"><?php echo acym_translation('ACYM_WHY_USING_CUSTOM_FIELDS'); ?></h3>
				<p class="cell acym__splashscreen__desc"><?php echo acym_translation('ACYM_WHY_USING_CUSTOM_FIELDS_DESC'); ?></p>
			</div>
			<div class="cell large-2 hide-for-medium-only hide-for-small-only"></div>
		</div>

		<div class="cell grid-x margin-top-3">
			<div class="cell medium-auto hide-for-small-only"></div>

			<div class="cell medium-shrink">
                <?php acym_upgradeTo('enterprise', 'custom_fields'); ?>
			</div>
			<div class="cell medium-auto hide-for-small-only"></div>
		</div>
	</div>
    <?php acym_formOptions(); ?>
</form>
