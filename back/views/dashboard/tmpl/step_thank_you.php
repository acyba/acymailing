<form id="acym_form" action="<?php echo acym_completeLink('dashboard'); ?>" method="post" name="acyForm" data-abide novalidate>
	<div id="acym__walkthrough">
		<div class="acym__walkthrough cell grid-x" id="acym__walkthrough__thank_you">
			<div class="acym__content cell grid-x acym__walkthrough__content acym__walkthrough__main align-center">
				<h1 class="cell acym__title acym__main_title text-center">
                    <?php echo acym_translationSprintf('ACYM_WALKTHROUGH_LICENCE_TITLE', $data['level']); ?>
				</h1>
				<div class="cell text-center"><?php echo acym_translation('ACYM_ONBOARDING_FEW_STEPS_AWAY'); ?></div>

				<button type="submit" class="button margin-top-1"><?php echo acym_translation('ACYM_START_SETUP'); ?></button>
			</div>
			<div class="cell grid-x algin-center acym__walkthrough__discover">
				<div class="cell grid-x grid-margin-x grid-margin-y margin-top-1 margin-bottom-2">
                    <?php include acym_getView('gopro', 'content_new'); ?>

					<div class="cell medium-auto"></div>
					<div class="cell xxlarge-8 grid-x grid-margin-x">
                        <?php include acym_getView('gopro', 'footer'); ?>
					</div>
					<div class="cell medium-auto"></div>
				</div>
			</div>
			<div class="acym__content cell grid-x acym__walkthrough__footer align-center">
				<button type="submit" class="button margin-top-2"><?php echo acym_translation('ACYM_START_SETUP'); ?></button>
			</div>
		</div>
	</div>
    <?php acym_formOptions(true, 'saveStepThankYou', '', 'dashboard'); ?>
</form>
