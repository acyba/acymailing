<form id="acym_form" action="<?php echo acym_completeLink(acym_getVar('cmd', 'ctrl')); ?>" method="post" name="acyForm" class="cell grid-x align-center" data-abide novalidate>
	<div id="acym__followup__condition" class="cell grid-x margin-y align-center acym__content margin-top-2 large-9">
		<input type="hidden" name="id" value="<?php echo empty($data['followup']->id) ? '' : $data['followup']->id; ?>">
		<input type="hidden" name="trigger" value="<?php echo empty($data['followup']->trigger) ? $data['trigger'] : ''; ?>">
		<div class="cell grid-x">
            <?php
            $workflow = $data['workflowHelper'];
            if (empty($data['followup']->id)) $data['workflowHelper']->disabledAfter = 'followupCondition';
            echo $workflow->display($this->followupSteps, 'followupCondition', true, false, empty($data['followup']->trigger) ? '&trigger='.$data['trigger'] : '');
            ?>
		</div>
		<p class="cell acym__followup__condition__desc padding-left-1"><?php echo acym_translation('ACYM_FOLLOW_UP_CONDITION_DESC_1'); ?></p>
		<p class="cell padding-left-1"><?php echo acym_translation('ACYM_FOLLOW_UP_CONDITION_DESC_2'); ?></p>
		<h5 class="cell padding-left-1 acym__title acym__title__secondary"><?php echo acym_translation('ACYM_SEND_FOLLOW_UP_EMAIL_IF'); ?></h5>
		<div class="cell grid-x padding-left-1">
            <?php
            if (!empty($data['followup']->condition) && empty($data['additionalCondition']) && !in_array($data['trigger'], ['user_subscribe', 'user_creation'])) {
                echo '<p class="cell acym__color__orange padding-left-1"><b>'.acym_translation('ACYM_MISSING_ADDON').'</b></p>';
            }
            if (!empty($data['additionalCondition']) || $data['trigger'] == 'user_subscribe') {
                echo '<h5 class="cell acym__title__primary__color padding-left-1">'.acym_translation(
                        'ACYM_SPECIFIC_CONDITIONS_TRIGGER'
                    ).'</h5>';
            }
            foreach ($data['additionalCondition'] as $condition) {
                ?>
				<span class="cell grid-x acym_vcenter margin-top-1 padding-left-2"><?php echo $condition; ?></span>
                <?php
            }

            ?>
            <?php if ($data['trigger'] == 'user_subscribe') { ?>
				<span class="cell grid-x acym_vcenter margin-bottom-1 margin-top-1 padding-left-2"><?php echo acym_translationSprintf(
                        $data['lists_subscribe_translation'],
                        $data['select_status_lists'],
                        $data['lists_multiselect']
                    ); ?></span>
            <?php } ?>
			<h5 class="cell acym__title__primary__color margin-top-2 padding-left-1"><?php echo acym_translation('ACYM_CLASSIC_CONDITIONS'); ?></h5>
            <?php if ($data['trigger'] != 'user_subscribe') { ?>
				<span class="cell grid-x acym_vcenter margin-bottom-1 margin-top-1 padding-left-2"><?php echo acym_translationSprintf(
                        $data['lists_subscribe_translation'],
                        $data['select_status_lists'],
                        $data['lists_multiselect']
                    ); ?></span>
            <?php } ?>
			<span class="cell grid-x acym_vcenter padding-left-2"><?php echo acym_translationSprintf(
                    'ACYM_FOLLOW_UP_CONDITION_USER_SEGMENT',
                    $data['select_status_segments'],
                    $data['segments_multiselect']
                ); ?></span>
		</div>
		<div class="cell grid-x">
			<div class="cell medium-shrink medium-margin-bottom-0 margin-bottom-1 text-left">
                <?php echo acym_backToListing(); ?>
			</div>
			<div class="cell auto align-right grid-margin-x grid-x">
				<button type="button" class="cell shrink acy_button_submit button button-secondary" data-task="save" data-step="listing"><?php echo acym_translation(
                        'ACYM_SAVE_EXIT'
                    ); ?></button>
				<button type="button" class="cell shrink acy_button_submit button" data-task="save" data-step="followupEmail"><?php echo acym_translation('ACYM_SAVE_CONTINUE'); ?>
					<i class="acymicon-chevron-right"></i></button>
			</div>
		</div>
	</div>
    <?php acym_formOptions(true, 'edit', 'followupCondition'); ?>
</form>
