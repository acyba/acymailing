<form id="acym_form" action="<?php echo acym_completeLink(acym_getVar('cmd', 'ctrl')); ?>" method="post" name="acyForm">
	<div class="acym__content acym__content__tab" id="acym_stats">
		<div class="cell grid-x acym_vcenter" id="acym_stats__select">
			<div class="cell grid-x acym_vcenter">
				<h2 class="cell medium-5 text-right acym_stats__title__choose"><?php echo acym_translation('ACYM_SELECT_AN_EMAIL'); ?></h2>
				<div class="cell large-2 medium-4 margin-left-1"><?php echo $data['mail_filter']; ?></div>
				<button class="cell margin-left-1 shrink acy_button_submit button" data-task="listing"><?php echo acym_translation('ACYM_VALIDATE'); ?></button>
			</div>
            <?php if (!empty($data['emailTranslationsFilters']) && !empty($data['emailTranslations'])) { ?>
				<div class="cell grid-x margin-top-1 acym_vcenter acym__stats__select__language__container">
					<h2 class="cell medium-5 text-right acym_stats__title__choose-smaller"><?php echo acym_translation('ACYM_SPECIFY_LANGUAGE'); ?></h2>
					<div class="cell large-2 medium-4 margin-left-1"><?php echo $data['emailTranslationsFilters']; ?></div>
				</div>
            <?php } ?>
		</div>
        <?php
        $workflow = $data['workflowHelper'];
        $this->isMailSelected($data['selectedMailid'], empty($data['no_click_map']));
        echo $workflow->displayTabs($this->tabs, 'statsByList');
        ?>

		<div class="acym__content acym__stats">
            <?php
            if (count(array_unique($data['selectedMailid'])) != 1) {
                echo '<h1 class="acym__listing__empty__title text-center cell">'.acym_translation('ACYM_FEATURE_WORKS_WHEN_ONLY_ONE_CAMPAIGN_SELECTED').'</h1>';
            } elseif (empty($data['listsStats'])) {
                echo '<h1 class="acym__listing__empty__title text-center cell">'.acym_translation('ACYM_NO_LIST_WITH_THIS_CAMPAIGN').'</h1>';
            } else {
                ?>
				<div class="cell grid-x acym__content align-center">
					<h2 class="cell acym__title acym__title__secondary"><?php echo acym_translation('ACYM_EMAIL_STATISTICS'); ?></h2>
					<div class="grid-x">
						<div class="cell small-12 medium-6">
                            <?php echo acym_pieChart('', $data['emailsSent'], '', acym_translation('ACYM_EMAILS_SENT'), true, true); ?>
						</div>
						<div class="cell small-12 medium-6">
                            <?php echo acym_barChart('', $data['emailsOpen'], '', acym_translation('ACYM_EMAILS_OPENED')); ?>
						</div>
					</div>
					<div class="grid-x padding-top-3">
						<div class="cell small-12 medium-4">
                            <?php echo acym_barChart('', $data['click'], '', acym_translation('ACYM_CLICKED_ON_LINK').' (%)'); ?>
						</div>
						<div class="cell small-12 medium-4">
                            <?php echo acym_barChart('', $data['bounces'], '', acym_translation('ACYM_BOUNCES').' (%)'); ?>
						</div>
						<div class="cell small-12 medium-4">
                            <?php echo acym_barChart('', $data['unsubscribed'], '', acym_translation('ACYM_ACTION_UNSUBSCRIBED').' (%)'); ?>
						</div>
					</div>
				</div>
            <?php } ?>
		</div>
	</div>
    <?php acym_formOptions(); ?>
</form>

