<form id="acym_form" action="<?php echo acym_completeLink('dashboard'); ?>" method="post" name="acyForm" data-abide novalidate>
	<div id="acym__walkthrough">
		<div class="acym__walkthrough cell grid-x" id="acym__walkthrough__acymailer">
			<div class="acym__content cell grid-x acym__walkthrough__content align-center">
                <?php
                $data['stepsType']->display(
                    [
                        'currentStep' => 3,
                        'totalSteps' => 3,
                    ]
                );
                ?>

				<h1 class="cell acym__title text-center"><?php echo acym_translation('ACYM_YOUR_SENDING_METHOD'); ?></h1>

				<div class="cell grid-x grid-margin-x align-center margin-top-3">
					<div id="acym__walkthrough__acymailer__domain__container" class="cell xlarge-10 large-12 grid-x grid-margin-y margin-bottom-3">
						<div class="cell grid-x large-6 align-left">
							<h5 class="cell margin-bottom-2"><?php echo acym_translation('ACYM_LICENSE_WITH_SENDING_SERVICE'); ?></h5>

							<div class="cell margin-bottom-1"><?php echo acym_translation('ACYM_LETS_CONFIGURE'); ?></div>
							<div class="cell"><?php echo acym_translation('ACYM_WALK_ACYMAILER_1'); ?></div>
							<div class="cell"><?php echo acym_translationSprintf('ACYM_WALK_ACYMAILER_2', $data['suggestedDomain']); ?></div>
							<div class="cell">
                                <?php echo acym_translation('ACYM_WALK_ACYMAILER_3'); ?>
								<a target="_blank"
								   id="acym__cname__documentation"
								   href="<?php echo ACYM_DOCUMENTATION; ?>external-sending-method/acymailing-sending-service#how-to-add-the-dns-entries-on-my-server">
                                    <?php echo acym_translation('ACYM_STEP_BY_STEP_GUIDE'); ?>
								</a>
							</div>
						</div>
						<div class="cell grid-x grid-margin-x align-center align-middle large-6">
							<div class="cell grid-x grid-margin-x grid-margin-y align-center">
								<div class="cell small-7 medium-8 large-9 xlarge-7">
									<input id="acymailer_domain"
										   class="cell medium-6 large-4 xlarge-3"
										   type="text"
										   autocomplete="off"
										   value="">
									<span id="acymailer_domain_error" class="medium-6 large-4 xlarge-3"></span>
                                    <?php if (!empty($data['suggestedDomain'])) { ?>
										<span id="acym__acymailer__unverifiedDomains">
											<span class="acym__acymailer__oneSuggestion"><?php echo acym_escape($data['suggestedDomain']); ?></span>
										</span>
                                    <?php } ?>
								</div>
								<div id="acym__configuration__sending__method_addDomain_submit" class="cell grid-x shrink acym_vcenter">
									<button type="button"
											id="acym__walkthrough__acymailer__add_domain"
											class="cell shrink button button-secondary">
                                        <?php echo acym_translation('ACYM_ADD_MY_DOMAIN'); ?>
									</button>
								</div>
								<div class="cell grid-x align-center margin-top-1">
									<i class="acym_vcenter acymicon-circle-o-notch acymicon-spin is-hidden"
									   id="acym__walkthrough__acymailer__domain__spinner"></i>
									<div class="cell shrink grid-x acym_vcenter is-hidden" id="acym__walkthrough__acymailer__add__error">
										<i class="acymicon-close acym__color__red cell shrink"></i>
										<span class="cell shrink" id="acym__walkthrough__acymailer__add__error__message"></span>
									</div>
								</div>
							</div>
						</div>
					</div>

					<div id="acym__walkthrough__acymailer__cname__container" class="cell xxlarge-8 large-10 grid-x margin-bottom-3 text-center is-hidden">
						<div class="cell"><?php echo acym_translation('ACYM_ADD_CNAME'); ?></div>
						<div class="cell">
                            <?php echo acym_translation('ACYM_WALK_ACYMAILER_3'); ?>
							<a target="_blank"
							   id="acym__cname__documentation"
							   href="<?php echo ACYM_DOCUMENTATION; ?>external-sending-method/acymailing-sending-service#how-to-add-the-dns-entries-on-my-server">
                                <?php echo acym_translation('ACYM_STEP_BY_STEP_GUIDE'); ?>
							</a>
						</div>
						<div class="cell margin-top-1"><?php echo acym_translation('ACYM_CHECK_STATUS_ANYTIME'); ?></div>

						<div class="cell grid-x acym__listing margin-top-3" id="acym__walkthrough__acymailer__domain__cname">
							<div class="cell grid-x grid-margin-x margin-left-0 acym__listing__header text-left">
								<div class="cell small-6 acym__listing__header__title">
                                    <?php echo acym_translation('ACYM_NAME'); ?>
								</div>
								<div class="cell small-6 acym__listing__header__title">
                                    <?php echo acym_translation('ACYM_VALUE'); ?>
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>
			<div class="cell acym__walkthrough_footer grid-x algin-center text-center">
				<div id="acym__walkthrough_footer__domain__container" class="cell grid-x grid-margin-x margin-top-3 margin-bottom-2 text-center">
					<h5 class="cell margin-bottom-1"><?php echo acym_translation('ACYM_DONT_WANT_WAIT'); ?></h5>
					<div class="cell"><?php echo acym_translation('ACYM_WALK_ACYMAILER_LATER'); ?></div>
					<div class="cell"><?php echo acym_translation('ACYM_WALK_ACYMAILER_OTHER'); ?></div>
				</div>
				<div id="acym__walkthrough_footer__cname__container" class="cell grid-x grid-margin-x margin-top-3 margin-bottom-2 text-center align-center is-hidden">
					<div class="cell xlarge-6"><?php echo acym_translation('ACYM_WALK_FOOTER_SWITCH'); ?></div>
					<div class="cell"></div>
					<div class="cell xlarge-6"><?php echo acym_translation('ACYM_WALK_FOOTER_DNS'); ?></div>
					<div class="cell margin-top-1"><?php echo acym_translation('ACYM_WALK_FOOTER_USE'); ?></div>
				</div>
				<div class="cell grid-x grid-margin-x margin-bottom-2 align-center">
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
