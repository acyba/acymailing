<form id="acym_form" action="<?php echo acym_completeLink(acym_getVar('cmd', 'ctrl')); ?>" method="post" name="acyForm">
	<div id="acym__followup__trigger"
		 class="cell grid-x grid-margin-y align-center acym__content margin-top-2 acym__selection <?php echo !empty($data['followup']->id) ? 'acym__selection_disabled' : ''; ?>">
		<div class="cell grid-x">
            <?php
            $workflow = $data['workflowHelper'];
            if (empty($data['followup']->id)) $data['workflowHelper']->disabledAfter = 'followupTrigger';
            echo $workflow->display($this->followupSteps, 'followupTrigger');
            ?>
		</div>
		<h1 class="margin-top-1 margin-bottom-2 acym__title">
            <?php echo acym_translation('ACYM_WHAT_TRIGGERS_FOLLOW_UP_SHOULD_START'); ?>
		</h1>
		<div class="cell grid-x grid-margin-x align-center margin-y">
            <?php
            $blocks = [];
            $oneSelected = false;
            acym_trigger('getFollowupTriggerBlock', [&$blocks]);
            foreach ($blocks as $block) {
                if (!acym_level($block['level'])) continue;
                $selected = '';
                if (!empty($data['followup']->trigger) && $data['followup']->trigger == $block['alias']) {
                    $selected = 'acym__selection__card-selected';
                    $oneSelected = true;
                }
                ?>
				<div class="acym__selection__card acym__selection__select-card cell xxlarge-2 xlarge-3 medium-4 text-center <?php echo $selected; ?>"
					 acym-data-link="<?php echo $block['link']; ?>">
					<i class="<?php echo $block['icon']; ?> acym__selection__card__icon"></i>
					<h1 class="acym__selection__card__title"><?php echo $block['name']; ?></h1>
					<p class="acym__selection__card__description"><?php echo $block['description']; ?></p>
				</div>
            <?php } ?>
			<div class="acym__selection__card acym__selection__card__disabled cell xxlarge-2 xlarge-3 medium-4 text-center">
				<i class="acymicon-idea acym__selection__card__icon"></i>
				<h1 class="acym__selection__card__title"><?php echo acym_translation('ACYM_HAVE_SUGGESTION'); ?></h1>
				<p class="acym__selection__card__description"><?php echo acym_translation('ACYM_HAVE_SUGGESTION_DESC'); ?></p>
				<a href="<?php echo ACYM_ACYMAILLING_WEBSITE; ?>contact/" target="_blank" class="button button-secondary"><?php echo acym_translation('ACYM_SUGGEST_IDEA'); ?></a>
			</div>
		</div>
        <?php
        if (!empty($data['followup']->trigger) && !$oneSelected) {
            echo '<div class="cell grid-x align-center margin-y acym__color__orange"><b>'.acym_translation('ACYM_MISSING_ADDON').'</b></div>';
        }
        ?>
		<button type="button" class="cell shrink button" id="acym__selection__button-select" disabled><?php echo acym_translation('ACYM_CREATE'); ?></button>
	</div>
    <?php acym_formOptions(); ?>
</form>
