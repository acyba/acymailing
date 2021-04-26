<form id="acym_form"
	  action="<?php echo acym_completeLink(acym_getVar('cmd', 'ctrl').'&task='.acym_getVar('string', 'task').'&id='.acym_getVar('string', 'id')); ?>"
	  method="post"
	  name="acyForm"
	  class="acym__form__campaign__edit">
	<input type="hidden" value="<?php echo !empty($data['campaign']->id) ? $data['campaign']->id : ''; ?>" name="id" id="acym__campaign__recipients__form__campaign">
	<div id="acym__campaigns__segment" class="grid-x">
		<div class="cell <?php echo $data['containerClass']; ?> float-center grid-x acym__content">
            <?php
            $this->addSegmentStep($data['displaySegmentTab']);
            $workflow = $data['workflowHelper'];
            echo $workflow->display($this->steps, $this->step);

            if (!acym_level(ACYM_ENTERPRISE)) {
                include acym_getView('campaigns', 'segment_splashscreen');
            }
            ?>
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
					<button data-task="save"
							data-step="sendSettings"
							type="submit"
							class="cell medium-shrink button margin-bottom-0 acy_button_submit"
							id="acym__campaign__recipients__save-continue">
                        <?php echo acym_translation('ACYM_SAVE_CONTINUE'); ?><i class="acymicon-chevron-right"></i>
					</button>
				</div>
			</div>
		</div>
	</div>
    <?php acym_formOptions(true, 'edit', 'segment'); ?>
</form>
