<?php $pricingPage = ACYM_ACYCHECKER_WEBSITE.'pricing?utm_source=acymailing_plugin&utm_medium=splash_acychecker&utm_campaign=acychecker_more_info';
$moneyBackPage = ACYM_ACYCHECKER_WEBSITE.'license-agreement/#Money-Back-Policy?utm_source=acymailing_plugin&utm_medium=splash_acychecker&utm_campaign=acychecker_more_info'; ?>
<div id="acym__override__splashscreen" class="acym__splashscreen__white__container cell grid-x padding-2 align-center">
	<div class="cell large-8 grid-x align-center acym__splashscreen__container">
		<div class="cell large-6 padding-3 align-center">
			<h2 class="acym__title acym__title__secondary acym__splashscreen__subtitle margin-bottom-1">
                <?php echo acym_translation('ACYM_USE_ACYCHECKER_TO_CLEAN'); ?>
			</h2>
			<h3 class="acym__title acym__splashscreen__title">
                <?php echo acym_translation('ACYM_MAXIMISE_DELIVRABILITY'); ?>
			</h3>
			<p class="acym__splashscreen__desc margin-bottom-1">
                <?php echo acym_translation('ACYM_ACYCHECKER_TEXT_1'); ?>
			</p>
			<p class="acym__splashscreen__desc margin-bottom-2">
                <?php echo acym_translation('ACYM_ACYCHECKER_TEXT_2'); ?>
			</p>
			<a class="cell medium-6 large-shrink button acym__button__upgrade" target="_blank" href="<?php echo $pricingPage; ?>">
                <?php echo acym_translation('ACYM_TRY_FOR_FREE_NOW'); ?>
			</a>
		</div>
		<div class="cell large-6 align-center acym__splashscreen__image">
			<img src="<?php echo ACYM_IMAGES.'upgrade/acychecker.png'; ?>" alt="AcyChecker Image">
		</div>
	</div>
</div>
<div id="acym__override__splashscreen" class="acym__splashscreen__blue__container cell grid-x padding-2 align-center">
	<div class="cell large-8 grid-x align-center acym__splashscreen__container">
		<div class="cell large-6 align-center acym__splashscreen__image">
			<img src="<?php echo ACYM_IMAGES.'upgrade/detailed_stats.png'; ?>" alt="Detailed Stats Image">
		</div>
		<div class="cell large-6 padding-3 align-center">
			<h2 class="acym__title acym__title__secondary acym__splashscreen__subtitle margin-bottom-1">
                <?php echo acym_translation('ACYM_REALISE_IMPROVEMENTS'); ?>
			</h2>
			<p class="acym__splashscreen__desc margin-bottom-1">
                <?php echo acym_translation('ACYM_ACYCHECKER_TEXT_3'); ?>
			</p>
			<ul class="acym__ul acym__splashscreen__ul acym margin-bottom-2">
				<li class="acym__splashscreen__desc">
                    <?php echo acym_translation('ACYM_ACYCHECKER_LISTING_TEXT_1'); ?>
				</li>
				<li class="acym__splashscreen__desc">
                    <?php echo acym_translation('ACYM_ACYCHECKER_LISTING_TEXT_2'); ?>
				</li>
				<li class="acym__splashscreen__desc">
                    <?php echo acym_translation('ACYM_ACYCHECKER_LISTING_TEXT_3'); ?>
				</li>
				<li class="acym__splashscreen__desc">
                    <?php echo acym_translation('ACYM_ACYCHECKER_LISTING_TEXT_4'); ?>
				</li>
				<li class="acym__splashscreen__desc">
                    <?php echo acym_translation('ACYM_ACYCHECKER_LISTING_TEXT_5'); ?>
				</li>
			</ul>
			<a class="cell medium-6 large-shrink button acym__button__upgrade" target="_blank" href="<?php echo $moneyBackPage; ?>">
                <?php echo acym_translation('ACYM_REFOUND_GUARANTEE'); ?>
			</a>
		</div>
	</div>
</div>
