<form id="acym_form" action="<?php echo acym_completeLink('dashboard'); ?>" method="post" name="acyForm" data-abide novalidate>
	<div id="acym__walkthrough">
		<div class="acym__walkthrough cell grid-x" id="acym__walkthrough__sender_information">
			<div class="acym__content cell text-center grid-x acym__walkthrough__content align-center">
                <?php
                $data['stepsType']->display(
                    [
                        'currentStep' => 1,
                        'totalSteps' => 3,
                    ]
                );
                ?>

				<h1 class="cell acym__title text-center"><?php echo acym_translation('ACYM_SET_SENDER_INFORMATION'); ?></h1>
				<div class="cell grid-x align-center">
					<div class="cell xxlarge-6 large-8 margin-top-2">
                        <?php echo acym_translation('ACYM_STEP_SENDER_EXPLANATION'); ?>
					</div>
				</div>

				<div class="cell grid-x align-center margin-top-3 margin-bottom-3">
					<div class="cell xxlarge-4 xlarge-5 large-6 medium-8 grid-x margin-y text-left">
						<div class="cell">
							<label>
                                <?php echo acym_translation('ACYM_FROM_NAME').acym_info('ACYM_FROM_NAME_INFO'); ?>
								<input type="text" name="from_name" value="<?php echo acym_escape($data['siteName']); ?>" required>
							</label>
						</div>
						<div class="cell">
							<label>
                                <?php echo acym_translation('ACYM_FROM_MAIL_ADDRESS').acym_info('ACYM_FROM_ADDRESS_INFO'); ?>
								<input type="email" name="from_email" value="<?php echo acym_escape($data['userEmail']); ?>" required>
							</label>
						</div>
					</div>
				</div>

				<div class="cell grid-x align-center">
					<button id="acym__walkthrough__sender_information__submit" type="button" class="cell shrink button">
                        <?php echo acym_translation('ACYM_CONTINUE'); ?>
					</button>
				</div>
			</div>
		</div>
	</div>
    <?php acym_formOptions(true, 'saveStepSenderInformation', '', 'dashboard'); ?>
</form>
