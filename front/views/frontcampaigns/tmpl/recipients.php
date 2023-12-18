<form id="acym_form"
	  action="<?php echo acym_completeLink(acym_getVar('cmd', 'ctrl')); ?>"
	  method="post"
	  name="acyForm"
	  class="acym__form__campaign__edit <?php echo !empty($data['menuClass']) ? acym_escape($data['menuClass']) : ''; ?>">
	<input type="hidden"
		   value="<?php echo !empty($data['campaignInformation']) ? acym_escape($data['campaignInformation']) : ''; ?>"
		   name="campaignId"
		   id="acym__campaign__recipients__form__campaign">
	<input type="hidden"
		   value="<?php echo !empty($data['showSelected']) ? $data['showSelected'] : ''; ?>"
		   name="showSelected"
		   id="acym__campaign__recipients__show-all-or-selected">
	<div id="acym__campaigns__recipients" class="grid-x">
		<div class="cell <?php echo $data['containerClass']; ?> float-center grid-x acym__content">
            <?php
            $this->addSegmentStep($data['displaySegmentTab']);
            $workflow = $data['workflowHelper'];
            echo $workflow->display($this->steps, $this->step, true, false, '', 'campaignId');

            ?>
			<div class="acym__campaigns__recipients__modal">
                <?php if (!empty($data['currentCampaign']->sent) && empty($data['currentCampaign']->active)) { ?>
					<div class="acym__hide__div"></div>
					<h3 class="acym__title__primary__color acym__middle_absolute__text text-center"><?php echo acym_translation('ACYM_CAMPAIGN_ALREADY_QUEUED'); ?></h3>
                <?php }
                $entityHelper = $data['entitySelectHelper'];
                echo $entityHelper->entitySelect(
                    'list',
                    ['join' => 'join_mail-'.$data['currentCampaign']->mail_id],
                    $entityHelper->getColumnsForList('maillist.mail_id')
                );
                ?>
				<div class="cell grid-x acym__campaign__recipients__total-recipients acym__content acym_vcenter">
					<p class="cell shrink"><?php echo acym_translation('ACYM_CAMPAIGN_SENT_TO'); ?>&nbsp;</p>
					<div class="cell auto acym__campaign__recipients__number-display grid-x align-left acym_vcenter">
                        <?php echo acym_loaderLogo(); ?>
						<div class="cell shrink">
							<span class="acym__campaign__recipients__number-recipients">0</span>&nbsp;<span id="acym__campaign__recipients__span"><?php echo acym_strtolower(
                                    acym_translation('ACYM_RECIPIENTS')
                                ); ?></span></div>
					</div>
				</div>
			</div>
			<div class="cell grid-x text-center acym__campaign__recipients__save-button cell">
				<div class="cell medium-shrink medium-margin-bottom-0 margin-bottom-1 text-left">
                    <?php echo acym_backToListing(); ?>
				</div>
				<div class="cell medium-auto grid-x text-right">
					<div class="cell medium-auto"></div>
                    <?php if (empty($data['campaignInformation'])) { ?>
						<button data-task="save" data-step="sendSettings" type="submit" class="cell medium-shrink button margin-bottom-0 acy_button_submit">
                            <?php echo acym_translation('ACYM_SAVE_CONTINUE'); ?><i class="acymicon-chevron-right"></i>
						</button>
                    <?php } else { ?>
						<button data-task="save"
								data-step="listing"
								type="submit"
								class="cell button-secondary medium-shrink button medium-margin-bottom-0 margin-right-1 acy_button_submit">
                            <?php echo acym_translation('ACYM_SAVE_EXIT'); ?>
						</button>
						<button data-task="save"
								data-step="sendSettings"
								type="submit"
								class="cell medium-shrink button margin-bottom-0 acy_button_submit"
								id="acym__campaign__recipients__save-continue">
                            <?php echo acym_translation('ACYM_SAVE_CONTINUE'); ?><i class="acymicon-chevron-right"></i>
						</button>
                    <?php } ?>
				</div>
			</div>
		</div>
	</div>
    <?php acym_formOptions(true, 'edit', 'recipients'); ?>
</form>
