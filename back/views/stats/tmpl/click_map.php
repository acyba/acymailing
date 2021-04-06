<form id="acym_form" action="<?php echo acym_completeLink(acym_getVar('cmd', 'ctrl')); ?>" method="post" name="acyForm">
	<div class="acym__content acym__content__tab" id="acym_stats">
		<div class="cell grid-x acym_vcenter" id="acym_stats__select">
			<div class="cell grid-x acym_vcenter">
				<h2 class="cell medium-6 text-right acym_stats__title__choose"><?php echo acym_translation('ACYM_SELECT_AN_EMAIL'); ?></h2>
				<div class="cell large-2 medium-4 margin-left-1"><?php echo $data['mail_filter']; ?></div>
			</div>
            <?php if (!empty($data['emailTranslationsFilters']) && !empty($data['emailTranslations'])) { ?>
				<div class="cell grid-x margin-top-1 acym_vcenter">
					<h2 class="cell medium-6 text-right acym_stats__title__choose-smaller"><?php echo acym_translation('ACYM_SPECIFY_LANGUAGE'); ?></h2>
					<div class="cell large-2 medium-4 margin-left-1"><?php echo $data['emailTranslationsFilters']; ?></div>
				</div>
            <?php } ?>
		</div>
        <?php
        $workflow = $data['workflowHelper'];
        $this->isMailSelected($data['selectedMailid'], empty($data['no_click_map']));
        echo $workflow->displayTabs($this->tabs, 'clickMap');
        ?>
		<div id="acym__stats__click-map" class="acym__content">
			<input type="hidden" id="acym__stats_click__map__all-links__click" value="<?php echo empty($data['url_click']) ? '' : acym_escape($data['url_click']); ?>">
			<input type="hidden" class="acym__hidden__mail__content" value="<?php echo acym_escape(acym_absoluteURL($data['mailInformation']->body)); ?>">
			<div style="display: none" class="acym__hidden__mail__stylesheet"><?php echo $data['mailInformation']->stylesheet; ?></div>
			<div class="cell grid-x">
				<div class="cell grid-x align-right">
					<button type="button" class="cell shrink button primary  acym__stats__export__click-map__charts"><?php echo acym_translation('ACYM_EXPORT'); ?></button>
				</div>
				<div id="acym__stats__add_style_export__click-map">
					<style>
						<?php
                        echo acym_fileGetContent($data['url_foundation_email']);
                        echo acym_fileGetContent($data['url_click_map_email']);
                        ?>
					</style>
				</div>
				<div id="acym__wysid__email__preview" class="acym__email__preview grid-x cell margin-top-1"></div>
			</div>
		</div>
	</div>
    <?php acym_formOptions(); ?>
</form>

