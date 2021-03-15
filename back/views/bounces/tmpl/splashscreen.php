<form id="acym_form" action="<?php echo acym_completeLink(acym_getVar('cmd', 'ctrl')); ?>" method="post" name="acyForm">
	<div id="acym__bounces__splashscreen" class="acym__content cell grid-x">
		<div class="cell grid-x acym__splashscreen__bloc margin-top-2">
			<div class="cell large-2 hide-for-medium-only hide-for-small-only"></div>
			<div class="cell large-8 grid-x text-center">
				<h3 class="cell acym__title text-center"><i class="acymicon-transfer acym__color__green margin-right-1"></i><?php echo acym_translation(
                        'ACYM_WHAT_IS_BOUNCE_HANDLING'
                    ); ?></h3>
				<p class="cell acym__splashscreen__desc"><?php echo acym_translation('ACYM_WHAT_IS_BOUNCE_HANDLING_TEXT'); ?></p>
			</div>
			<div class="cell large-2 hide-for-medium-only hide-for-small-only"></div>
		</div>

		<hr class="cell medium-5 margin-vertical-2">

		<div class="cell grid-x acym__splashscreen__bloc">
			<div class="cell large-2 hide-for-medium-only hide-for-small-only"></div>
			<div class="cell large-8 grid-x text-center">
				<h3 class="cell acym__title text-center"><i class="acymicon-bell acym__color__orange margin-right-1"></i><?php echo acym_translation(
                        'ACYM_WHAT_ARE_THE_RISKS_BOUNCES'
                    ); ?></h3>
				<p class="cell acym__splashscreen__desc"><?php echo acym_translation('ACYM_WHAT_ARE_THE_RISKS_BOUNCES_TEXT'); ?></p>
			</div>
			<div class="cell large-2 hide-for-medium-only hide-for-small-only"></div>
		</div>

		<hr class="cell medium-5 margin-vertical-2">

		<div class="cell grid-x acym__splashscreen__bloc">
			<div class="cell large-2 hide-for-medium-only hide-for-small-only"></div>
			<div class="cell large-8 grid-x text-center">
				<h3 class="cell acym__title text-center"><i class="acymicon-aid-kit acym__color__light-blue margin-right-1"></i><?php echo acym_translation(
                        'ACYM_HOW_ACYMAILING_CAN_HELP'
                    ); ?></h3>
				<p class="cell acym__splashscreen__desc"><?php echo acym_translation('ACYM_HOW_ACYMAILING_CAN_HELP_TEXT'); ?></p>
			</div>
			<div class="cell large-2 hide-for-medium-only hide-for-small-only"></div>
		</div>

		<div class="cell grid-x margin-top-3">
			<div class="cell medium-auto hide-for-small-only"></div>
            <?php if ($data['isEnterprise']) { ?>
				<button data-task="passSplash" class="cell medium-shrink button primary acy_button_submit" type="button"><?php echo acym_translation('ACYM_LETS_GO'); ?></button>
            <?php } else { ?>
				<div class="cell medium-shrink">
                    <?php acym_upgradeTo('enterprise', 'bounces'); ?>
				</div>
            <?php } ?>
			<div class="cell medium-auto hide-for-small-only"></div>
		</div>
	</div>
    <?php acym_formOptions(); ?>
</form>
