<div class="cell grid-x align-center">
	<h2 class="cell acym__title text-center"><?php echo acym_translation('ACYM_DISCOVER_ALL_FEATURES'); ?></h2>
</div>
<div class="cell grid-x grid-margin-x align-center">
	<div class="cell grid-x grid-margin-x grid-margin-y xxlarge-6 small-10 padding-1 acym__walkthrough__card acym__walkthrough__card__left">
		<div class="cell grid-x medium-6 acym__gopro__text">
			<div class="cell">
				<h3 class=" acym__title acym__title__secondary"><?php echo acym_translation('ACYM_AUTO_SEND_PROCESS'); ?></h3>
				<p><?php echo acym_translation('ACYM_GOPRO_AUTO_SEND'); ?></p>
                <?php
                if (acym_level(ACYM_ESSENTIAL)) {
                    $btnText = acym_translation('ACYM_SEE_DOCUMENTATION');
                    $btnUrl = 'https://docs.acymailing.com/setup/configuration/queue-process';
                    $btnClass = 'button-secondary';
                } else {
                    $btnText = acym_translation('ACYM_GOPRO');
                    $btnUrl = 'https://www.acymailing.com/pricing';
                    $btnClass = 'button-gopro';
                }
                ?>
				<a class="button <?php echo $btnClass; ?> margin-top-1" href="<?php echo $btnUrl; ?>" target="_blank"><?php echo $btnText; ?></a>
			</div>
		</div>
		<div class="cell grid-x medium-6 align-center acym__gopro__doublepic align-middle">
			<img class="cell acym__gopro__images acym__gopro__images_vertical"
				 src="<?php echo ACYM_IMAGES.'upgrade/tablet_smartphone.png'; ?>"
				 alt="Screenshots of AcyMailing stats on tablet and smartphone">
			<img class="cell acym__gopro__images acym__gopro__transat"
				 src="<?php echo ACYM_IMAGES.'upgrade/illustration_transat.png'; ?>"
				 alt="Screenshots of AcyMailing stats on tablet and smartphone">
		</div>
	</div>
	<div class="cell grid-x grid-margin-x grid-margin-y xxlarge-4 small-10 padding-1 acym__walkthrough__card">
		<div class="cell grid-x align-center align-middle">
			<img class="cell acym__gopro__images"
				 src="<?php echo ACYM_IMAGES.'upgrade/subscription_forms.png'; ?>"
				 alt="Screenshot of AcyMailing subscription form">
		</div>
		<div class="cell grid-x acym__gopro__text">
			<h3 class="cell acym__title acym__title__secondary"><?php echo acym_translation('ACYM_SUBSCRIPTION_FORMS'); ?></h3>
			<p><?php echo acym_translation('ACYM_GOPRO_SUBSCRIPTION_FORMS'); ?></p>
            <?php
            if (acym_level(ACYM_ENTERPRISE)) {
                $btnText = acym_translation('ACYM_SEE_DOCUMENTATION');
                $btnUrl = 'https://docs.acymailing.com/main-pages/subscription-forms';
                $btnClass = 'button-secondary';
            } else {
                $btnText = acym_translation('ACYM_GOPRO');
                $btnUrl = 'https://www.acymailing.com/pricing';
                $btnClass = 'button-gopro';
            }
            ?>
			<div class="cell">
				<a class="button <?php echo $btnClass; ?> margin-top-1" href="<?php echo $btnUrl; ?>" target="_blank"><?php echo $btnText; ?></a>
			</div>
		</div>
	</div>
</div>

<div class="cell grid-x grid-margin-x align-center">
	<div class="cell grid-x grid-margin-x grid-margin-y xxlarge-4 small-10 padding-1 acym__walkthrough__card acym__walkthrough__card__left">
		<div class="cell grid-x align-center">
			<img class="cell acym__gopro__images"
				 src="<?php echo ACYM_IMAGES.'upgrade/advanced_statistics.png'; ?>"
				 alt="Screenshot of some detailed statistic charts">
		</div>
		<div class="cell grid-x acym__gopro__text">
			<h3 class="cell acym__title acym__title__secondary"><?php echo acym_translation('ACYM_ADVANCED_STATISTICS'); ?></h3>
			<p><?php echo acym_translation('ACYM_GOPRO_ADVANCED_STATISTICS'); ?></p>
            <?php
            if (acym_level(ACYM_ENTERPRISE)) {
                $btnText = acym_translation('ACYM_SEE_DOCUMENTATION');
                $btnUrl = 'https://docs.acymailing.com/main-pages/statistics';
                $btnClass = 'button-secondary';
            } else {
                $btnText = acym_translation('ACYM_GOPRO');
                $btnUrl = 'https://www.acymailing.com/pricing';
                $btnClass = 'button-gopro';
            }
            ?>
			<a class="button <?php echo $btnClass; ?> margin-top-1" href="<?php echo $btnUrl; ?>" target="_blank"><?php echo $btnText; ?></a>
		</div>
	</div>
	<div class="cell grid-x grid-margin-x grid-margin-y xxlarge-6 small-10 padding-1 acym__walkthrough__card">
		<div class="cell grid-x medium-6 acym__gopro__text">
			<h3 class="cell acym__title acym__title__secondary"><?php echo acym_translation('ACYM_CUSTOM_FIELDS'); ?></h3>
			<p><?php echo acym_translation('ACYM_GOPRO_CUSTOM_FIELDS'); ?></p>
            <?php
            if (acym_level(ACYM_ENTERPRISE)) {
                $btnText = acym_translation('ACYM_SEE_DOCUMENTATION');
                $btnUrl = 'https://docs.acymailing.com/main-pages/custom-fields';
                $btnClass = 'button-secondary';
            } else {
                $btnText = acym_translation('ACYM_GOPRO');
                $btnUrl = 'https://www.acymailing.com/pricing';
                $btnClass = 'button-gopro';
            }
            ?>
			<a class="button <?php echo $btnClass; ?> margin-top-1" href="<?php echo $btnUrl; ?>" target="_blank"><?php echo $btnText; ?></a>
		</div>
		<div class="cell grid-x medium-6 align-center align-middle">
			<img class="cell acym__gopro__images"
				 src="<?php echo ACYM_IMAGES.'upgrade/custom-fields.png'; ?>"
				 alt="Image of a fictional profile with user specific information">
		</div>
	</div>
</div>
<div class="cell grid-x grid-margin-x align-center">
	<div class="cell grid-x grid-margin-x grid-margin-y xxlarge-6 small-10 padding-1 acym__walkthrough__card acym__walkthrough__card__left">
		<div class="cell grid-x medium-6 acym__gopro__text">
			<h3 class="cell acym__title acym__title__secondary"><?php echo acym_translation('ACYM_GOPRO_NEWSLETER_OF_YOUR_CHOICE'); ?></h3>
			<p><?php echo acym_translation('ACYM_GOPRO_NEWSLETER_OF_YOUR_CHOICE_DESC'); ?></p>
            <?php
            if (acym_level(ACYM_ENTERPRISE)) {
                $btnText = acym_translation('ACYM_SEE_DOCUMENTATION');
                $btnUrl = 'https://docs.acymailing.com/main-pages/campaigns';
                $btnClass = 'button-secondary';
            } else {
                $btnText = acym_translation('ACYM_GOPRO');
                $btnUrl = 'https://www.acymailing.com/pricing';
                $btnClass = 'button-gopro';
            }
            ?>
			<a class="button <?php echo $btnClass; ?> margin-top-1" href="<?php echo $btnUrl; ?>" target="_blank"><?php echo $btnText; ?></a>
		</div>
		<div class="cell grid-x medium-6 align-center align-middle">
			<img class="cell acym__gopro__image__bigger"
				 src="<?php echo ACYM_IMAGES.'upgrade/create_your_newsletter.png'; ?>"
				 alt="Screenshot of email creation type choice">
		</div>
	</div>
	<div class="cell grid-x grid-margin-x grid-margin-y xxlarge-4 small-10 padding-1 acym__walkthrough__card">
		<div class="cell grid-x align-center acym__gopro__override">
			<img class="cell acym__gopro__images"
				 src="<?php echo ACYM_IMAGES.'upgrade/overrides.png'; ?>"
				 alt="Fictional welcome email">
		</div>
		<div class="cell grid-x acym__gopro__text">
			<h3 class="cell acym__title acym__title__secondary"><?php echo acym_translation('ACYM_EMAILS_OVERRIDE'); ?></h3>
			<p><?php echo acym_translationSprintf(
                    'ACYM_GOPRO_EMAILS_OVERRIDE',
                    ACYM_CMS_TITLE,
                    ACYM_CMS === 'wordpress' ? 'WooCommerce' : 'HikaShop'
                ); ?></p>
            <?php
            if (acym_level(ACYM_ENTERPRISE)) {
                $btnText = acym_translation('ACYM_SEE_DOCUMENTATION');
                $btnUrl = 'https://docs.acymailing.com/main-pages/email-overrides';
                $btnClass = 'button-secondary';
            } else {
                $btnText = acym_translation('ACYM_GOPRO');
                $btnUrl = 'https://www.acymailing.com/pricing';
                $btnClass = 'button-gopro';
            }
            ?>
			<div class="cell">
				<a class="button <?php echo $btnClass; ?> margin-top-1" href="<?php echo $btnUrl; ?>" target="_blank"><?php echo $btnText; ?></a>
			</div>
		</div>
	</div>
</div>
