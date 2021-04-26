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
        echo $workflow->displayTabs($this->tabs, 'globalStats');
        ?>
		<div id="acym_stats_global">
            <?php
            include acym_getView('stats', 'global_stats_data', true); ?>
		</div>
	</div>
    <?php acym_formOptions(); ?>
</form>
