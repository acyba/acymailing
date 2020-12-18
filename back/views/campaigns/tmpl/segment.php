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
            ?>
			<div class="cell grid-x acym__campaigns__segment__summary margin-bottom-1">
				<h4 class="cell"><?php echo acym_translation('ACYM_CHOSEN_LISTS').acym_info('ACYM_CHOSEN_LISTS_DESC'); ?></h4>
				<div class="cell grid-x padding-1">
                    <?php if (empty($data['campaignLists'])) {
                        echo '<p>'.acym_translation('ACYM_YOU_DID_NOT_SELECT_LISTS').'</p>';
                    } else { ?>
						<div class="cell grid-x grid-margin-x">
							<p class="cell large-3 medium-4"><?php echo acym_translation('ACYM_PREVIOUSLY_SELECTED_LISTS'); ?></p>
							<div class="cell shrink">
                                <?php
                                foreach ($data['campaignLists'] as $list) {
                                    echo acym_tooltip('<i class="acymicon-circle" style="color: '.$list->color.'"></i>', $list->name);
                                }
                                ?>
							</div>
						</div>
						<div class="cell grid-x grid-margin-x">
							<p class="cell large-3 medium-4"><?php echo acym_translation('ACYM_PREVIOUSLY_SELECTED_USERS'); ?></p>
							<p class="cell shrink"><b><?php echo $data['recipientsNumber']; ?></b></p>
						</div>
                    <?php } ?>
				</div>
			</div>
			<div class="cell grid-x acym__campaigns__segment__edit margin-top-1">
				<h4 class="cell"><?php echo acym_translation('ACYM_SEGMENT').acym_info('ACYM_SEGMENT_CAMPAIGN_DESC'); ?></h4>
				<div class="cell grid-x padding-1">
					<div class="cell grid-x acym_vcenter">
						<p class="cell shrink margin-right-1"><?php echo acym_translation('ACYM_SELECT_EXISTING_SEGMENT'); ?></p>
						<div class="cell large-2 medium-4"><?php echo acym_select(
                                $data['segments'],
                                'segment_selected',
                                empty($data['campaign']->sending_params['segment']['segment_id']) ? '' : $data['campaign']->sending_params['segment']['segment_id'],
                                'class="acym__select"'
                            ); ?></div>
					</div>
					<p class="cell acym__campaigns__segment__edit__or"><?php echo strtoupper(acym_translation('ACYM_OR')); ?></p>
					<div class="cell grid-x">
						<p class="cell"><?php echo acym_translation('ACYM_CREATE_NEW_SEGMENT_IN_CAMPAIGN'); ?></p>
						<div class="cell grid-x acym__content margin-bottom-1 acym__campaigns__segment__edit__filters">
							<div class="acym__campaigns__segment__edit__filters__save">
								<div class="acym__campaigns__segment__edit__filters__save-icon acym_vcenter grid-x align-right">
									<span id="acym__campaigns__segment__edit__filters__save-well" class="shrink margin-left-1 cell" style="display: none"></span>
                                    <?php echo acym_tooltip('<i class="acym__color__blue acymicon-floppy-o cell shrink"></i>', acym_translation('ACYM_SAVE_SEGMENT')); ?>
								</div>
								<div class="acym__campaigns__segment__edit__filters__save-action grid-x acym_vcenter align-right">
									<label for="acym__campaigns__segment__edit__filters__save-segment-name" class="cell shrink"><?php echo acym_translation('ACYM_SEGMENT_NAME'); ?>
										:</label>
									<input type="text" class="acym__light__input cell shrink" id="acym__campaigns__segment__edit__filters__save-segment-name" name="segment_name">
									<button type="button" class="button cell shrink acym__campaigns__segment__edit__filters__save-action-save"><?php echo acym_translation(
                                            'ACYM_SAVE'
                                        ); ?></button>
									<button type="button" class="button acym__button__cancel cell shrink"><?php echo acym_translation('ACYM_CANCEL'); ?></button>
								</div>
								<div class="grid-x acym__campaigns__segment__edit__filters__save-loading align-right">
									<i class="acymicon-circle-o-notch acymicon-spin"></i>
								</div>
							</div>
							<input type="hidden" value="<?php echo acym_escape($data['filter_option']); ?>" id="acym__segments__edit__info__options">
							<input type="hidden" id="acym__segments__filters__count__and" value="0">
							<input type="hidden" name="list_selected" value="<?php echo empty($data['campaignLists']) ? '' : json_encode(array_keys($data['campaignLists'])); ?>">
							<input type="hidden"
								   id="acym__segments__filters"
								   value="<?php echo acym_escape(
                                       empty($data['campaign']->sending_params['segment']['filters']) ? '' : $data['campaign']->sending_params['segment']['filters']
                                   ); ?>">
							<input type="hidden" name="saved_segment_id" value="">
                            <?php include acym_getView('segments', 'edit_filters'); ?>
						</div>
						<div class="cell grid-x acym__content acym_vcenter acym__campaigns__segment__edit__count_container">
							<p class="medium-6 cell"><?php echo acym_translation('ACYM_CAMPAIGN_SENT_TO'); ?></p>
							<p class="medium-6 cell acym_vcenter align-right"><span id="acym__campaigns__segment__edit-user-count"></span><?php echo acym_strtolower(
                                    acym_translation('ACYM_USERS')
                                ); ?></p>
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
