<form id="acym_form"
	  action="<?php echo acym_completeLink(acym_getVar('cmd', 'ctrl').'&task='.acym_getVar('string', 'task').'&id='.acym_getVar('string', 'id')); ?>"
	  method="post"
	  name="acyForm"
	  class="acym__form__campaign__edit">
	<input type="hidden" name="id" value="<?php echo acym_escape($data['id']); ?>">
	<div class="cell grid-x">
		<div class="cell medium-auto"></div>
		<div id="acym__campaigns__tests" class="cell xxlarge-9 grid-x acym__content">
            <?php
            $this->addSegmentStep($data['displaySegmentTab']);
            $workflow = $data['workflowHelper'];
            echo $workflow->display($this->steps, $this->step);
            ?>
			<div class="cell grid-x grid-margin-x" id="campaigns_tests_step">
				<div id="spam_test_zone" class="cell large-5">
					<h6 class="acym__title acym__title__secondary"><?php echo acym_translation('ACYM_SAFE_CHECK').acym_info('ACYM_INTRO_SAFE_CHECK'); ?></h6>
					<p class="margin-bottom-1"><?php echo acym_translation('ACYM_SAFE_CHECK_DESC'); ?></p>
					<div class="grid-x align-center">
						<div class="cell">
                            <?php
                            ?>
						</div>
                        <?php
                        $icon = empty($data['upgrade']) ? '<i></i>' : acym_tooltip('<i class="acymicon-question-circle-o"></i>', acym_translation('ACYM_NEED_PRO_VERSION'));
                        $iconSpamTest = acym_level(ACYM_ENTERPRISE) ? '<i></i>' : acym_tooltip('<i class="acymicon-question-circle-o"></i>', acym_translation('ACYM_NEED_ENTERPRISE_VERSION'));
                        ?>
						<div class="cell grid-x <?php echo !empty($data['upgrade']) ? 'acym__campaigns__tests__starter' : 'is-hidden'; ?>" id="safe_check_results">
							<div class="cell grid-x acym_vcenter" id="check_words">
								<div class="cell small-10"><?php echo acym_translation('ACYM_TESTS_SAFE_CONTENT'); ?></div>
								<div class="cell small-2 text-center acym_icon_container"><?php echo $icon; ?></div>
							</div>
							<div class="cell acym_check_results"></div>

							<div class="cell grid-x acym_vcenter" id="check_links">
								<div class="cell small-10"><?php echo acym_translation('ACYM_TESTS_LINKS'); ?></div>
								<div class="cell small-2 text-center acym_icon_container"><?php echo $icon; ?></div>
							</div>
							<div class="cell acym_check_results"></div>

                            <?php
                            $spamtestRow = '<div class="cell grid-x acym_vcenter" id="check_spam" data-iframe="spamtestpopup" data-iframe-class="acym__iframe_spamtest">
													<div class="cell small-10">'.acym_translation('ACYM_TESTS_SPAM').'</div>
													<div class="cell small-2 text-center acym_icon_container">'.$iconSpamTest.'</div>
												</div>';

                            echo acym_modal(
                                $spamtestRow,
                                '',
                                'spamtestpopup',
                                'data-reveal-larger',
                                '',
                                false
                            );
                            ?>
							<div class="cell acym_check_results"></div>
							<div class="cell text-center is-hidden" id="acym_spam_test_details">
								<button type="button" class="button button-secondary"><?php echo acym_translation('ACYM_DETAILS'); ?></button>
							</div>
						</div>
                        <?php
                        $blocDisplay = '';
                        $getProBtn = acym_buttonGetProVersion();
                        if (acym_level(ACYM_ESSENTIAL)) {
                            $blocDisplay = 'style="display:none;"';
                            $getProBtn = acym_buttonGetProVersion('cell shrink', 'ACYM_GET_ENTERPRISE_VERSION');
                        }
                        echo '<div class="cell grid-x grid-margin-x margin-top-2 align-center acym__campaigns__test__pro" '.$blocDisplay.'>';
                        echo '<a href="'.ACYM_DOCUMENTATION.'main-pages/campaigns/tests" target="_blank" class="button button-secondary cell shrink">'.acym_translation(
                                'ACYM_SEE_MORE'
                            ).'</a>';
                        echo $getProBtn;
                        echo '</div>';
                        ?>
					</div>
				</div>
				<div class="cell large-1 margin-top-2 acym_zone_separator"></div>
				<div id="send_test_zone" class="cell large-6">
					<h6 class="acym__title acym__title__secondary"><?php echo acym_translation('ACYM_SEND_TEST_TO'); ?></h6>
                    <?php

                    echo acym_selectMultiple(
                        $data['test_emails'],
                        'test_emails',
                        $data['test_emails'],
                        [
                            'class' => 'acym__multiselect__email',
                            'placeholder' => acym_translation('ACYM_TEST_ADDRESS'),
                        ]
                    );

                    ?>
					<label class="margin-top-1">
                        <?php echo acym_translation('ACYM_TEST_NOTE'); ?>
						<textarea
								id="acym__wysid__send__test__note"
								name="test_note"
								type="text"
								placeholder="<?php echo acym_translation('ACYM_TEST_NOTE_PLACEHOLDER', true); ?>"></textarea>
					</label>
					<div class="grid-x">
						<button id="acym__campaign__send-test" type="button" class="button button-secondary margin-top-1">
                            <?php echo acym_translation('ACYM_SEND_TEST'); ?>
						</button>
						<div class="cell shrink margin-top-1 margin-left-1" id="acym__campaigns__send-test__spinner" style="display: none">
							<i class="acymicon-circle-o-notch acymicon-spin"></i>
						</div>
					</div>
				</div>
			</div>

			<div class="cell grid-x text-center acym__campaign__recipients__save-button cell">
				<div class="cell medium-shrink medium-margin-bottom-0 margin-bottom-1 text-left">
                    <?php echo acym_backToListing(); ?>
				</div>
				<div class="cell medium-auto grid-x text-right">
					<div class="cell medium-auto"></div>
					<button data-task="save"
							data-step="listing"
							type="submit"
							class="cell button-secondary medium-shrink button medium-margin-bottom-0 margin-right-1 acy_button_submit">
                        <?php echo acym_translation('ACYM_SAVE_EXIT'); ?>
					</button>
					<button data-task="save" data-step="summary" type="submit" class="cell medium-shrink button margin-bottom-0 acy_button_submit">
                        <?php echo acym_translation('ACYM_SAVE_CONTINUE'); ?><i class="acymicon-chevron-right"></i>
					</button>
				</div>
			</div>
		</div>
		<div class="medium-auto cell"></div>
	</div>
    <?php acym_formOptions(true, 'edit', 'tests'); ?>
</form>
