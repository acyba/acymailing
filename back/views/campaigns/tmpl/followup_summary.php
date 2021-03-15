<form id="acym_form" action="<?php echo acym_completeLink(acym_getVar('cmd', 'ctrl')); ?>" method="post" name="acyForm" class="cell grid-x align-center">
	<div id="acym__followup__summary" class="cell grid-x margin-y acym__content margin-top-2 large-9">
		<input type="hidden" name="action_mail_id" value="">
		<input type="hidden" name="id" value="<?php echo empty($data['followup']->id) ? '' : $data['followup']->id; ?>">
		<input type="hidden" name="trigger" value="<?php echo !empty($data['trigger']) ? $data['trigger'] : ''; ?>">
		<div class="cell grid-x">
            <?php echo $data['workflowHelper']->display($this->followupSteps, 'followupSummary'); ?>
		</div>

		<div class="cell grid-x acym__followup__summary__section margin-right-2">
			<h5 class="cell shrink margin-right-2 acym__title acym__title__secondary">
				<b><?php echo acym_translation('ACYM_TRIGGER'); ?></b>
			</h5>
            <?php
            $blocks = [];
            acym_trigger('getFollowupTriggerBlock', [&$blocks]);
            $triggerAdded = false;
            foreach ($blocks as $block) {
                if ($data['followup']->trigger !== $block['alias']) continue;
                $triggerAdded = true;
                ?>
				<div class="cell grid-x">
					<div class="cell small-5 medium-4 large-2 acym__summary__card text-center">
						<i class="<?php echo $block['icon']; ?>"></i>
						<p><?php echo $block['name']; ?></p>
					</div>
				</div>
                <?php
                break;
            }
            if (!empty($data['followup']->trigger) && !$triggerAdded) {
                ?>
				<div class="cell grid-x">
					<div class="cell small-8 large-6 acym__summary__card text-center">
						<p class="acym__color__orange"><?php echo acym_translation('ACYM_MISSING_ADDON'); ?></p>
					</div>
				</div>
                <?php
            }
            ?>
		</div>

		<div class="cell grid-x acym__followup__summary__section margin-right-2">
			<h5 class="cell shrink margin-right-2 acym__title acym__title__secondary">
				<b><?php echo acym_translation('ACYM_CONDITIONS'); ?></b>
			</h5>
			<div class="cell auto acym__followup__summary__modify">
				<a href="<?php echo acym_completeLink('campaigns&task=edit&step=followupCondition&id='.intval($data['followup']->id)); ?>">
					<i class="acymicon-pencil"></i><span> <?php echo acym_translation('ACYM_EDIT'); ?></span>
				</a>
			</div>
			<div class="cell grid-x" id="acym__followup__summary__conditions">
                <?php
                if (empty($data['condition'])) {
                    echo acym_translation('ACYM_NO_CONDITION');
                } else {
                    echo '<ul class="acym__ul">';
                    foreach ($data['condition'] as $oneCondition) {
                        echo '<li class="cell">'.ucfirst($oneCondition).'</li>';
                    }
                    echo '</ul>';
                }
                ?>
			</div>
		</div>

		<div class="cell grid-x acym__followup__summary__section margin-right-2 margin-bottom-2">
			<h5 class="cell shrink margin-right-2 acym__title acym__title__secondary">
				<b><?php echo acym_translation('ACYM_EMAILS'); ?></b>
			</h5>
			<div class="cell auto acym__followup__summary__modify">
				<a href="<?php echo acym_completeLink('campaigns&task=edit&step=followupEmail&id='.intval($data['followup']->id)); ?>">
					<i class="acymicon-pencil"></i><span> <?php echo acym_translation('ACYM_EDIT'); ?></span>
				</a>
			</div>
			<div class="cell grid-x acym__listing" id="acym__followup__summary__conditions">
                <?php
                if (empty($data['followup']->mails)) {
                    echo acym_translation('ACYM_NO_EMAIL_FOR_FOLLOWUP');
                } else {
                    ?>
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
                                    <?php
                                    echo $mail->delay_display;
                                    echo acym_info('ACYM_DELAY_SUMMARY');
                                    ?>
								</div>
								<div class="auto cell grid-x align-center acym__followup__summary__listing__action">
									<a href="<?php echo $mail->edit_link; ?>" class="cell shrink acym__color__font"><i class="acymicon-pencil"></i></a>
									<i class="cell shrink acymicon-trash-o acym__followup__summary__listing__action-delete margin-left-1"
									   acym-data-id="<?php echo intval($mail->id); ?>"></i>
								</div>
							</div>
						</div>
                        <?php
                    }
                }
                ?>
			</div>
		</div>

		<div class="cell grid-x text-center acym__followup__summary__save-button">
			<div class="cell medium-shrink medium-margin-bottom-0 margin-bottom-1 text-left">
                <?php echo acym_backToListing(); ?>
			</div>
			<div class="cell medium-auto grid-x text-right">
				<div class="cell medium-auto"></div>
				<button data-task="followupDraft"
						type="submit"
						class="cell button-secondary medium-shrink button margin-bottom-1 medium-margin-bottom-0 margin-right-1 acy_button_submit">
                    <?php echo acym_translation('ACYM_SAVE_AS_DRAFT'); ?>
				</button>
				<button data-task="followupActivate" type="submit" class="cell medium-shrink button margin-bottom-0 acy_button_submit">
                    <?php echo acym_translation('ACYM_ACTIVATE'); ?>
				</button>
			</div>
		</div>
	</div>
    <?php acym_formOptions(true, 'edit', 'followupSummary'); ?>
</form>
