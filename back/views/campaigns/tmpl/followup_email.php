<form id="acym_form" action="<?php echo acym_completeLink(acym_getVar('cmd', 'ctrl')); ?>" method="post" name="acyForm" class="cell grid-x align-center" data-abide novalidate>
	<div id="acym__followup__email" class="cell grid-x margin-y align-center margin-top-2 large-9">
		<input type="hidden" name="id" value="<?php echo empty($data['followup']->id) ? '' : $data['followup']->id; ?>">
		<div class="cell grid-x acym__content">
			<div class="cell grid-x">
                <?php
                $workflow = $data['workflowHelper'];
                echo $workflow->display($this->followupSteps, 'followupEmail');
                ?>
			</div>
			<div class="cell grid-x grid-margin-x" id="acym__followup__email__information">
				<div class="cell grid-x margin-y medium-6">
					<div class="cell grid-x acym_vcenter">
						<label for="acym__followup__email__name" class="cell large-4 medium-5"><?php echo acym_translation('ACYM_NAME'); ?></label>
						<input required
							   type="text"
							   id="acym__followup__email__name"
							   class="cell medium-6"
							   name="followup[name]"
							   value="<?php echo empty($data['followup']->name) ? '' : acym_escape($data['followup']->name); ?>">
					</div>
					<div class="cell grid-x acym_vcenter">
						<label for="acym__followup__email__name" class="cell large-4 medium-5">
                            <?php echo acym_translation('ACYM_DISPLAY_NAME').acym_info('ACYM_DISPLAY_NAME_DESC'); ?>
						</label>
						<input required
							   type="text"
							   id="acym__followup__email__name"
							   class="cell medium-6"
							   name="followup[display_name]"
							   value="<?php echo empty($data['followup']->display_name) ? '' : acym_escape($data['followup']->display_name); ?>">
					</div>
				</div>
				<div class="cell grid-x margin-y medium-6">
					<div class="cell grid-x acym_vcenter align-center">
                        <?php echo acym_switch(
                            'followup[active]',
                            empty($data['followup']->active) ? 0 : $data['followup']->active,
                            acym_translation('ACYM_ACTIVE'),
                            [],
                            'large-3 medium-5 small-9',
                            'shrink'
                        ); ?>
					</div>
					<div class="cell grid-x acym_vcenter align-center">
                        <?php
                        $sendOnceText = acym_translation('ACYM_SEND_ONCE').acym_info('ACYM_SEND_ONCE_DESC');
                        echo acym_switch(
                            'followup[send_once]',
                            empty($data['followup']->send_once) ? 0 : $data['followup']->send_once,
                            $sendOnceText,
                            [],
                            'large-3 medium-5 small-9',
                            'shrink'
                        ); ?>
					</div>
				</div>
			</div>
		</div>
		<div class="cell grid-x acym__content">
			<div class="cell grid-x align-center margin-top-2" id="acym__followup__email__emails">
                <?php if (empty($data['followup']->mails)) { ?>
					<input type="hidden" name="linkNewEmail" value="<?php echo $data['linkNewEmail']; ?>">
					<button class="cell shrink button acy_button_submit" data-task="createNewFollowupMail" data-ctrl="campaigns">
						<i class="acymicon-add"></i><?php echo acym_translation('ACYM_CREATE_YOUR_FIRST_FOLLOW_UP_EMAIL'); ?></button>
                <?php } else { ?>
					<input type="hidden" name="action_mail_id" value="">
					<div class="grid-x large-8 medium-10 acym__listing acym__followup__email__listing">
						<div class="grid-x cell acym__listing__header">
							<div class="grid-x medium-auto small-11 cell acym__listing__header__title__container">
								<div class="auto cell acym__listing__header__title">
                                    <?php echo acym_translation('ACYM_EMAIL_SUBJECT'); ?>
								</div>
								<div class="auto cell acym__listing__header__title text-center">
                                    <?php echo acym_translation('ACYM_DELAY'); ?>
								</div>
								<div class="auto cell acym__listing__header__title text-center">
                                    <?php echo acym_translation('ACYM_ACTIONS'); ?>
								</div>
							</div>
						</div>
                        <?php foreach ($data['followup']->mails as $mail) { ?>
							<div class="grid-x cell acym__listing__row">
								<div class="grid-x medium-auto small-11 cell acym__listing__title__container">
									<div class="auto cell acym__listing__title">
                                        <?php echo $mail->subject; ?>
									</div>
									<div class="auto cell grid-x align-center">
                                        <?php echo $mail->delay_display; ?>
									</div>
									<div class="auto cell grid-x align-center acym__followup__email__listing__action">
										<a href="<?php echo $mail->edit_link; ?>" class="cell shrink acym__color__font"><i class="acymicon-pencil"></i></a>
										<i class="cell shrink acymicon-content_copy margin-left-1 margin-right-1 acym__followup__email__listing__action-duplicate"
										   acym-data-id="<?php echo $mail->id; ?>"></i>
										<i class="cell shrink acymicon-trash-o acym__followup__email__listing__action-delete" acym-data-id="<?php echo $mail->id; ?>"></i>
									</div>
								</div>
							</div>
                        <?php } ?>
					</div>
					<div class="cell grid-x align-center margin-top-1 acym__followup__email__listing__add-button">
						<input type="hidden" name="linkNewEmail" value="<?php echo $data['linkNewEmail']; ?>">
						<button class="cell shrink button acy_button_submit" data-task="createNewFollowupMail" data-ctrl="campaigns">
							<i class="acymicon-add"></i><?php echo acym_translation('ACYM_ADD_AN_EMAIL'); ?></button>
					</div>
                <?php } ?>
			</div>
			<div class="cell grid-x">
				<div class="cell medium-shrink medium-margin-bottom-0 margin-bottom-1 text-left">
                    <?php echo acym_backToListing(); ?>
				</div>
				<div class="cell medium-auto grid-x text-right">
					<div class="cell medium-auto"></div>
					<button data-task="save" data-step="listing" type="submit" class="cell button-secondary medium-shrink button margin-bottom-1 medium-margin-bottom-0 margin-right-1 acy_button_submit">
						<?php echo acym_translation('ACYM_SAVE_EXIT'); ?>
					</button>
					<button data-task="save" data-step="followupSummary" type="submit" class="cell medium-shrink button margin-bottom-0 acy_button_submit">
						<?php echo acym_translation('ACYM_SAVE_CONTINUE'); ?><i class="acymicon-chevron-right"></i>
					</button>
				</div>
			</div>
		</div>
	</div>
    <?php acym_formOptions(true, 'edit', 'followupEmail'); ?>
</form>
