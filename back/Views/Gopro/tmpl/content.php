<div class="cell medium-auto"></div>
<div class="cell grid-x large-12 acym__gopro__text align-center">
	<h2 class="acym__title acym__title__secondary acym__titletext-center">
        <?php echo acym_translation('ACYM_DISCOVER_ALL_FEATURES'); ?>
	</h2>
</div>

<div class="grid-x align-center acym__gopro__content">
	<!-- Automatic send process-->
	<div class="cell medium-6 grid-x align-center acym__gopro__white__container">
		<div class="cell acym__gopro__text">
			<h2 class="acym__title"><?php echo acym_translation('ACYM_AUTO_SEND_PROCESS'); ?></h2>
			<p><?php echo acym_translation('ACYM_GOPRO_AUTO_SEND'); ?></p>
			<a class="cell large-shrink button acym__button__upgrade margin-top-1" target="_blank" href="<?php echo $pricingPage; ?>">
                <?php echo acym_translation('ACYM_GOPRO'); ?>
			</a>
		</div>
		<div class="cell align-center acym__gopro__images_container">
			<img class="acym__gopro__images" src="<?php echo ACYM_IMAGES.'upgrade/tablet_smartphone.png'; ?>" alt="Screenshots of AcyMailing stats on tablet and smartphone">
			<img class="acym__gopro__images acym__gopro__transat"
				 src="<?php echo ACYM_IMAGES.'upgrade/illustration_transat.png'; ?>"
				 alt="Screenshots of AcyMailing stats on tablet and smartphone">
		</div>
	</div>

	<!-- Subscription form -->
	<div class="cell medium-4 grid-y align-center acym__gopro__white__container">
		<div class="cell margin-bottom-1">
			<img class="cell acym__gopro__images" src="<?php echo ACYM_IMAGES.'upgrade/subscription_forms.png'; ?>" alt="Screenshot of AcyMailing subscription form">
		</div>
		<div class="cell acym__gopro__text">
			<h2 class="acym__title"><?php echo acym_translation('ACYM_SUBSCRIPTION_FORMS'); ?></h2>
			<p><?php echo acym_translation('ACYM_GOPRO_SUBSCRIPTION_FORMS'); ?></p>
			<a class="cell large-shrink button acym__button__upgrade margin-top-1" target="_blank" href="<?php echo $pricingPage; ?>">
                <?php echo acym_translation('ACYM_GOPRO'); ?>
			</a>
		</div>
	</div>
</div>

<div class="grid-x align-center acym__gopro__content">
	<!-- Advanced statistics-->
	<div class="cell medium-4 grid-y align-center acym__gopro__white__container">
		<div class="cell margin-bottom-1">
			<img class="cell acym__gopro__images" src="<?php echo ACYM_IMAGES.'upgrade/advanced_statistics.png'; ?>" alt="Screenshot of some detailed statistic charts">
		</div>
		<div class="cell acym__gopro__text">
			<h2 class="acym__title"><?php echo acym_translation('ACYM_ADVANCED_STATISTICS'); ?></h2>
			<p><?php echo acym_translation('ACYM_GOPRO_ADVANCED_STATISTICS'); ?></p>
			<a class="cell large-shrink button acym__button__upgrade margin-top-1" target="_blank" href="<?php echo $pricingPage; ?>">
                <?php echo acym_translation('ACYM_GOPRO'); ?>
			</a>
		</div>
	</div>

	<!-- Custom fields-->
	<div class="cell medium-6 grid-x align-center acym__gopro__white__container">
		<div class="cell acym__gopro__text">
			<h2 class="acym__title"><?php echo acym_translation('ACYM_CUSTOM_FIELDS'); ?></h2>
			<p><?php echo acym_translation('ACYM_GOPRO_CUSTOM_FIELDS'); ?></p>
			<a class="cell medium-6 large-shrink button acym__button__upgrade margin-top-1" target="_blank" href="<?php echo $pricingPage; ?>">
                <?php echo acym_translation('ACYM_GOPRO'); ?>
			</a>
		</div>
		<div class="cell align-center acym__gopro__images_container">
			<img class="cell acym__gopro__images" src="<?php echo ACYM_IMAGES.'upgrade/custom-fields.png'; ?>" alt="Image of a fictional profile with user specific information">
		</div>
	</div>
</div>

<div class="grid-x align-center acym__gopro__content">
	<!-- Campaigns -->
	<div class="cell medium-6 grid-x align-center acym__gopro__white__container">
		<div class="cell acym__gopro__text">
			<h2 class="acym__title"><?php echo acym_translation('ACYM_GOPRO_NEWSLETER_OF_YOUR_CHOICE'); ?></h2>
			<p><?php echo acym_translation('ACYM_GOPRO_NEWSLETER_OF_YOUR_CHOICE_DESC'); ?></p>
			<a class="cell medium-6 large-shrink button acym__button__upgrade margin-top-1" target="_blank" href="<?php echo $pricingPage; ?>">
                <?php echo acym_translation('ACYM_GOPRO'); ?>
			</a>
		</div>
		<div class="cell align-center acym__gopro__images_container">
			<img class="cell acym__gopro__image__bigger" src="<?php echo ACYM_IMAGES.'upgrade/create_your_newsletter.png'; ?>" alt="Screenshot of email creation type choice">
		</div>
	</div>

	<!-- Override -->
	<div class="cell medium-4 grid-y align-center acym__gopro__white__container">
		<div class="cell margin-bottom-1">
			<img class="cell acym__gopro__images" src="<?php echo ACYM_IMAGES.'upgrade/overrides.png'; ?>" alt="Fictional welcome email">
		</div>
		<div class="cell acym__gopro__text">
			<h2 class="acym__title"><?php echo acym_translation('ACYM_EMAILS_OVERRIDE'); ?></h2>
			<p><?php echo acym_translationSprintf('ACYM_GOPRO_EMAILS_OVERRIDE', ACYM_CMS_TITLE, ACYM_CMS === 'wordpress' ? 'WooCommerce' : 'HikaShop'); ?></p>
			<a class="cell large-shrink button acym__button__upgrade margin-top-1" target="_blank" href="<?php echo $pricingPage; ?>">
                <?php echo acym_translation('ACYM_GOPRO'); ?>
			</a>
		</div>
	</div>
</div>
