<?php $class = (acym_getVar('string', 'page') === 'acymailing_gopro') ? 'medium-8' : 'medium-12'; ?>
<div class="cell grid-x align-center grid-margin-x grid-margin-y acym__gopro__footer align-center acym__gopro__blueblock margin-bottom-2 <?php echo $class; ?>">
	<div class="cell grid-x large-6">
		<div class="cell large-1 small-2 text-center acym__gopro__footer__i"><i class="acymicon-group"></i></div>
		<h3 class="cell large-11 small-10 acym__gopro__footer__title"><?php echo acym_translation('ACYM_SEGMENT'); ?></h3>
		<div class="cell large-1 small-2"></div>
		<p class="cell large-11 small-10"><?php echo acym_translation('ACYM_GOPRO_SEGMENT'); ?></p>
	</div>
	<div class="cell grid-x large-6">
		<div class="cell large-1 small-2 text-center acym__gopro__footer__i"><i class="acymicon-cart-arrow-down"></i></div>
		<h3 class="cell large-11 small-10 acym__gopro__footer__title">
            <?php echo acym_translationSprintf('ACYM_GOPRO_ECOMMERCE_ABANDONED_CART', ACYM_CMS === 'wordpress' ? 'WooCommerce' : 'HikaShop'); ?>
		</h3>
		<div class="cell large-1 small-2"></div>
		<p class="cell large-11 small-10"><?php echo acym_translation('ACYM_GOPRO_ECOMMERCE_ABANDONED_CART_DESC'); ?></p>
	</div>
</div>
