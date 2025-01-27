<form id="acym_form" action="<?php echo acym_completeLink('dashboard'); ?>" method="post" name="acyForm" data-abide novalidate>
	<div id="acym__walkthrough">
		<div class="acym__walkthrough cell grid-x" id="acym__walkthrough__license">
			<div class="acym__content cell text-center grid-x acym__walkthrough__content align-center">
                <?php
                $data['stepsType']->display(
                    [
                        'currentStep' => 2,
                        'totalSteps' => 3,
                    ]
                );
                ?>

				<h1 class="cell acym__title text-center"><?php echo acym_translation('ACYM_CONNECT_YOUR_ACYMAILING_LICENSE'); ?></h1>
				<div class="cell">
					<p>
                        <?php
                        if (acym_level(ACYM_ESSENTIAL)) {
                            echo acym_translationSprintf('ACYM_ENTER_LICENSE_KEY_TO_ACTIVATE_ACYM_X_FEATURES', $data['level']);
                        } else {
                            echo acym_translation('ACYM_STARTER_LICENSE_KEY');
                        }
                        ?>
					</p>
					<p> <?php echo '('.acym_externalLink('ACYM_FIND_YOUR_LICENSE', ACYM_ACYMAILING_WEBSITE.'account/license/').')' ?></p>
				</div>

				<div class="cell grid-x align-center margin-top-3 margin-bottom-3">
					<div class="cell large-10 xlarge-8 xxlarge-6 grid-x grid-margin-y text-left">
						<div class="cell grid-x grid-margin-x">
							<label class="cell large-3" for="acym__walkthrough__license__key">
                                <?php echo acym_translation('ACYM_YOUR_LICENSE_KEY').acym_info('ACYM_LICENSE_DESC'); ?>
							</label>
							<div class="cell large-9 grid-x grid-margin-x">
								<input class="cell small-6 margin-left-0"
									   type="text"
									   name="config[license_key]"
									   id="acym__walkthrough__license__key"
									   value="">
								<div class="cell small-6 grid-x grid-margin-x">
									<button type="button"
											id="acym__walk_through_license__button__license"
											class="cell small-8 button margin-bottom-0">
                                        <?php echo acym_translation('ACYM_ATTACH_MY_LICENSE'); ?>
									</button>
									<i class="cell small-4 acymicon-circle-o-notch acymicon-spin is-hidden"
									   id="acym__walkthrough__license__spinner__attach"></i>
								</div>
							</div>
						</div>

						<div class="cell grid-x grid-margin-x">
							<label class="cell large-3">
                                <?php echo acym_translation('ACYM_STATUS_ACTIVATION'); ?>
							</label>
							<div class="cell large-9 acym__color__red" id="acym__walk_through_license__licenseStatus">
                                <?php echo acym_translation('ACYM_NOT_ENABLED_YET'); ?>
							</div>
						</div>

                        <?php if (acym_level(ACYM_ESSENTIAL)) { ?>
							<div class="cell grid-x grid-margin-x">
								<label class="cell large-3">
                                    <?php echo acym_translation('ACYM_AUTOMATED_TASKS').acym_info('ACYM_AUTOMATED_TASKS_DESC'); ?>
								</label>
								<div class="cell large-9 grid-x grid-margin-x">
									<div class="cell small-6 margin-left-0 acym__color__red" id="acym__walk_through_license__cron_label">
                                        <?php echo acym_translation('ACYM_DEACTIVATED'); ?>
									</div>
									<div class="cell shrink grid-x grid-margin-x">
                                        <?php
                                        echo acym_tooltip(
                                            [
                                                'hoveredText' => '<a type="button" 
															id="acym__walkthrough__license__button__cron" 
															class="grid-x align-center acym_vcenter button" 
															disabled>'.acym_translation('ACYM_ACTIVATE_IT').'</a>',
                                                'textShownInTooltip' => acym_translation('ACYM_ACTIVATE_IT_CRON_DESC'),
                                                'classContainer' => 'cell small-8',
                                                'classText' => 'acym__tooltip_button__cron',
                                            ]
                                        );
                                        ?>
										<i class="cell small-4 acymicon-circle-o-notch acymicon-spin is-hidden"
										   id="acym__walkthrough__license__spinner__cron"></i>
									</div>
								</div>
							</div>
                        <?php } ?>
					</div>
				</div>

				<button type="submit" class="button"><?php echo acym_translation('ACYM_CONTINUE'); ?></button>
			</div>
		</div>
	</div>
    <?php acym_formOptions(true, 'saveStepLicense', '', 'dashboard'); ?>
</form>
