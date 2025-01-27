<?php $pricingPage = ACYM_ACYMAILING_WEBSITE.'pricing?utm_source=acymailing_plugin&utm_medium=override&utm_campaign=upgrade_from_plugin';
$cms = (ACYM_CMS === 'joomla') ? 'HikaShop' : 'WooCommerce'; ?>
<div class="acym__splashscreen__white__container cell grid-x padding-2 align-center">
	<div class="cell large-8 grid-x align-center acym__splashscreen__container">
		<div class="cell large-6 padding-3 align-center">
			<h2 class="acym__title acym__title__secondary acym__splashscreen__subtitle margin-bottom-1">
                <?php echo acym_translation('ACYM_MAKE_EMAILS_EFFICIENT'); ?>
			</h2>
			<h3 class="acym__title acym__splashscreen__title">
                <?php echo acym_translation('ACYM_USE_FOR_AUTO_EMAIL'); ?>
			</h3>
			<p class="acym__splashscreen__desc margin-bottom-1">
                <?php echo acym_translationSprintf('ACYM_WHAT_IS_OVERRIDE_EMAIL_1_X', $cms); ?>
			</p>
			<p class="acym__splashscreen__desc margin-bottom-2">
                <?php echo acym_translationSprintf('ACYM_WHAT_IS_OVERRIDE_EMAIL_2_X', $cms); ?>
			</p>
			<a class="cell medium-6 large-shrink button acym__button__upgrade" target="_blank" href="<?php echo $pricingPage; ?>">
                <?php echo acym_translation('ACYM_GOPRO'); ?>
			</a>
		</div>
		<div class="cell large-6 align-center acym__splashscreen__image">
			<img src="<?php echo ACYM_IMAGES.'upgrade/override.png'; ?>" alt="Email override image">
		</div>
	</div>
</div>

<div class="acym__splashscreen__blue__container cell grid-x padding-2 align-center">
	<div class="cell large-8 grid-x align-center acym__splashscreen__container">
		<div class="cell large-6 align-center acym__splashscreen__image">
			<img src="<?php echo ACYM_IMAGES.'upgrade/mobile_stats.png'; ?>" alt="Mobile Stats Image">
		</div>
		<div class="cell large-6 padding-3 align-center">
			<h2 class="acym__title acym__title__secondary acym__splashscreen__subtitle margin-bottom-1">
                <?php echo acym_translation('ACYM_WHY_OVERRIDE_EMAIL'); ?>
			</h2>
			<p class="acym__splashscreen__desc margin-bottom-1">
                <?php echo acym_translation('ACYM_BECAUSE_OVERRIDE_EMAIL_1'); ?>
			</p>
			<p class="acym__splashscreen__desc margin-bottom-1">
                <?php echo acym_translation('ACYM_BECAUSE_OVERRIDE_EMAIL_2'); ?>
			</p>
			<p class="acym__splashscreen__desc margin-bottom-1">
                <?php echo acym_translation('ACYM_OVERRIDE_TRY_IT'); ?>
			</p>
			<a class="cell medium-6 large-shrink button acym__button__upgrade margin-top-2" target="_blank" href="<?php echo $pricingPage; ?>">
                <?php echo acym_translation('ACYM_REFOUND_GUARANTEE'); ?>
			</a>
		</div>
	</div>
</div>
