<form id="acym_form" action="<?php echo acym_completeLink(acym_getVar('cmd', 'ctrl')); ?>" method="post" name="acyForm">
    <?php $pricingPage = ACYM_ACYMAILING_WEBSITE.'pricing?utm_source=acymailing_plugin&utm_medium=custom_field&utm_campaign=upgrade_from_plugin'; ?>

	<div class="acym__splashscreen__white__container cell grid-x padding-2 align-center">
		<div class="cell large-8 grid-x align-center acym__splashscreen__container">
			<div class="cell large-6 padding-3 align-center">
				<h2 class="acym__title acym__title__secondary acym__splashscreen__subtitle margin-bottom-1">
                    <?php echo acym_translation('ACYM_ULTRA_CUSTOMIZATION'); ?>
				</h2>
				<h3 class="acym__title acym__splashscreen__title">
                    <?php echo acym_translation('ACYM_CUSTOM_FIELDS'); ?>
				</h3>
				<p class="acym__splashscreen__desc">
                    <?php echo acym_translation('ACYM_CUSTOM_FIELDS_UPGRADE_TEXT_1'); ?>
				</p>
				<p class="acym__splashscreen__desc margin-bottom-2">
                    <?php echo acym_translation('ACYM_CUSTOM_FIELDS_UPGRADE_TEXT_2'); ?>
				</p>
				<p class="acym__splashscreen__desc">
                    <?php echo acym_translation('ACYM_CUSTOM_FIELDS_UPGRADE_TEXT_3'); ?>
				</p>
				<p class="acym__splashscreen__desc margin-bottom-2">
                    <?php echo acym_translation('ACYM_CUSTOM_FIELDS_UPGRADE_TEXT_4'); ?>
				</p>
				<a class="cell medium-6 large-shrink button acym__button__upgrade" target="_blank" href="<?php echo $pricingPage; ?>">
                    <?php echo acym_translation('ACYM_UPGRADE_NOW_SIMPLE'); ?>
				</a>
			</div>
			<div class="cell large-6 align-center acym__splashscreen__image">
				<img src="<?php echo ACYM_IMAGES.'upgrade/custom-fields.png'; ?>" alt="<?php echo acym_translation('ACYM_CUSTOM_FIELDS_IMAGE'); ?>">
			</div>
		</div>
	</div>

	<div class="acym__splashscreen__blue__container cell grid-x padding-2 align-center">
		<div class="cell large-8 grid-x align-center acym__splashscreen__container">
			<div class="cell large-6 align-center acym__splashscreen__image">
				<img src="<?php echo ACYM_IMAGES.'upgrade/override.png'; ?>" alt="Detailed Stats Image">
			</div>
			<div class="cell large-6 padding-3 align-center">
				<h2 class="acym__title acym__title__secondary acym__splashscreen__subtitle margin-bottom-1">
                    <?php echo acym_translation('ACYM_USE_CUSTOM_FIELD'); ?>
				</h2>
				<p class="acym__splashscreen__desc margin-bottom-1">
                    <?php echo acym_translation('ACYM_USE_CUSTOM_FIELD_FOR_1'); ?>
				</p>
				<p class="acym__splashscreen__desc margin-bottom-1">
                    <?php echo acym_translation('ACYM_USE_CUSTOM_FIELD_FOR_2'); ?>
				</p>
				<p class="acym__splashscreen__desc margin-bottom-1">
                    <?php echo acym_translation('ACYM_USE_CUSTOM_FIELD_FOR_3'); ?>
				</p>
				<a class="cell medium-6 large-shrink button acym__button__upgrade margin-top-2" target="_blank" href="<?php echo $pricingPage; ?>">
                    <?php echo acym_translation('ACYM_UPGRADE_NOW_SIMPLE'); ?>
				</a>
			</div>
		</div>
	</div>

    <?php acym_formOptions(); ?>
</form>
