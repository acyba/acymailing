<form id="acym_form" action="<?php echo acym_completeLink('dashboard'); ?>" method="post" name="acyForm" data-abide novalidate>
	<div id="acym__walkthrough">
		<div class="acym__walkthrough cell grid-x" id="acym__walkthrough__final">
			<div class="acym__content cell text-center grid-x acym__walkthrough__content align-center">
                <?php
                $data['stepsType']->display(
                    [
                        'currentStep' => 3,
                        'totalSteps' => 3,
                    ]
                );
                ?>

				<h1 class="cell acym__title text-center"><?php echo acym_translation('ACYM_YOUR_SENDING_METHOD'); ?></h1>

				<div class="cell grid-x align-center margin-top-3 margin-bottom-3 text-left">
					<div class="cell xxlarge-7 large-10 grid-x">
						<h5 class="cell margin-bottom-1"><?php echo acym_translation('ACYM_WITHOUT_SENDING_SERVICE'); ?></h5>
						<div class="cell margin-bottom-1"><?php echo acym_translation('ACYM_DEFAULT_SENDING_SERVICE'); ?></div>
						<div class="cell"><?php echo acym_translation('ACYM_CHANGE_SENDING_SERVICE'); ?></div>

						<div class="cell ">
							<ul class="margin-top-1 text-left">
								<li>
                                    <?php echo acym_translation('ACYM_OUR_SENDING_SERVICE'); ?>
									<a target="_blank" href="<?php echo $data['pricingPage']; ?>" id="acym__pricing__page"><?php echo $data['pricingPage']; ?></a>
								</li>
								<li><?php echo acym_translation('ACYM_AN_EXTERNAL_SMTP'); ?></li>
								<li><?php echo acym_translation('ACYM_OTHER_INTEGRATIONS'); ?></li>
							</ul>
						</div>
					</div>
				</div>

				<div class="cell grid-x grid-margin-x align-center">
					<button type="button" data-task="startUsing" class="cell shrink button acy_button_submit">
                        <?php echo acym_translation('ACYM_START_USING'); ?>
					</button>
					<button type="button" data-task="tryEditor" class="cell shrink button button-secondary acy_button_submit">
                        <?php echo acym_translation('ACYM_TEST_THE_EDITOR'); ?>
					</button>
				</div>
			</div>
		</div>
	</div>
    <?php acym_formOptions(true, '', '', 'dashboard'); ?>
</form>
