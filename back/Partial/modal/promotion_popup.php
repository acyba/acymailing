<div id="promotionPopup" class="acym__promotion__popup__container" style="display: none">
	<div class="acym__promotion__popup__content text-left">
		<h2 class="acym__promotion__text acym__popup__content acym__enterprise__popup">
			<i class="acymicon-arrow-circle-o-up"></i>
            <?php echo acym_translation('ACYM_UPGRADE_TO_ENTERPRISE'); ?>
		</h2>
		<h2 class="acym__promotion__text acym__popup__content acym__essential__popup" style="display: none;">
			<i class="acymicon-arrow-circle-o-up"></i>
            <?php echo acym_translation('ACYM_UPGRADE_TO_ESSENTIAL'); ?>
		</h2>
		<h3 class="acym__promotion__price acym__popup__content acym__enterprise__popup cell text-left">
            <?php echo acym_translationSprintf('ACYM_ONLY_X_PER_MONTH', ACYM_ENTERPRISE_PRICE); ?>
		</h3>
		<h3 class="acym__promotion__price acym__popup__content cell text-left acym__essential__popup" style="display: none;">
            <?php echo acym_translationSprintf('ACYM_ONLY_X_PER_MONTH', ACYM_ESSENTIAL_PRICE); ?>
		</h3>
		<p class="acym__popup__content acym__promotion__text">
            <?php echo acym_translation('ACYM_DISCOVER_PAID_VERSION_BENEFITS'); ?>
		</p>

		<div class="acym__promotion__button__container">
			<a class="cell medium-6 large-shrink button popup-button acym__button__upgrade" target="_blank" href="<?php echo $pricingPage; ?>">
                <?php echo acym_translation('ACYM_UPGRADE_NOW_SIMPLE'); ?>
			</a>
			<a class="cell medium-6 large-shrink button popup-button button-secondary acym__promotion__popup__back">
                <?php echo acym_translation('ACYM_BACK'); ?>
			</a>
		</div>
	</div>
</div>
