<?php $pricingPage = ACYM_ACYMAILING_WEBSITE.'pricing?utm_source=acymailing_plugin&utm_medium=licence&utm_campaign=upgrade_from_plugin'; ?>

<div class="acym__splashscreen__white__container cell grid-x padding-2 align-center">
	<div class="cell large-8 grid-x align-center acym__splashscreen__container">
		<div class="cell large-6 padding-3 align-center">
			<h2 class="acym__title acym__title__secondary acym__splashscreen__subtitle margin-bottom-1">
                <?php echo acym_translation('ACYM_MAKE_EMAILS_EFFICIENT'); ?>
			</h2>
			<h3 class="acym__title acym__splashscreen__title">
                <?php echo acym_translation('ACYM_LICENSE_AUTOMATIZE_SENDING'); ?>
			</h3>
			<p class="acym__splashscreen__desc">
                <?php echo acym_translation('ACYM_LICENSE_TEXT_1'); ?>
			</p>
			<p class="acym__splashscreen__desc margin-bottom-1">
                <?php echo acym_translation('ACYM_LICENSE_TEXT_3'); ?>
			</p>
			<ul class="acym__ul acym__splashscreen__ul acym margin-bottom-2">
				<li class="acym__splashscreen__desc">
                    <?php echo acym_translation('ACYM_LICENSE_LISTING_TEXT_1'); ?>
				</li>
				<li class="acym__splashscreen__desc">
                    <?php echo acym_translation('ACYM_LICENSE_LISTING_TEXT_2'); ?>
				</li>
				<li class="acym__splashscreen__desc">
                    <?php echo acym_translation('ACYM_LICENSE_LISTING_TEXT_3'); ?>
				</li>
				<li class="acym__splashscreen__desc">
                    <?php echo acym_translation('ACYM_LICENSE_LISTING_TEXT_4'); ?>
				</li>
				<li class="acym__splashscreen__desc">
                    <?php echo acym_translation('ACYM_LICENSE_LISTING_TEXT_5'); ?>
				</li>
				<li class="acym__splashscreen__desc">
                    <?php echo acym_translation('ACYM_LICENSE_LISTING_TEXT_6'); ?>
				</li>
			</ul>
			<a class="cell medium-6 large-shrink button acym__button__upgrade" target="_blank" href="<?php echo $pricingPage; ?>">
                <?php echo acym_translation('ACYM_GOPRO'); ?>
			</a>
		</div>
		<div class="cell large-6 padding-3 align-center acym__splashscreen__image">
			<img src="<?php echo ACYM_IMAGES.'upgrade/tablet_smartphone.png'; ?>" alt="AcyMailing stats on tablet and smartphone">
		</div>
	</div>
</div>

<div class="acym__splashscreen__blue__container cell grid-x padding-2 align-center">
	<div class="cell large-8 grid-x align-center acym__splashscreen__container">
		<div class="cell large-6 align-center acym__splashscreen__image">
			<img src="<?php echo ACYM_IMAGES.'upgrade/detailed_stats.png'; ?>" alt="Charts Stats Image">
		</div>
		<div class="cell large-6 padding-3 align-center">
			<h2 class="acym__title acym__title__secondary acym__splashscreen__subtitle margin-bottom-1">
                <?php echo acym_translation('ACYM_LICENSE_DOUBT_TO_TRY'); ?>
			</h2>
			<p class="acym__splashscreen__desc margin-bottom-1">
                <?php echo acym_translation('ACYM_LICENSE_TEXT_4'); ?>
			</p>
			<a class="cell medium-6 large-shrink button acym__button__upgrade margin-top-2" target="_blank" href="<?php echo $pricingPage; ?>">
                <?php echo acym_translation('ACYM_REFOUND_GUARANTEE'); ?>
			</a>
		</div>
	</div>
</div>
