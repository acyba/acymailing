<form id="acym_form" action="<?php echo acym_completeLink(acym_getVar('cmd', 'ctrl')); ?>" method="post" name="acyForm">
	<div id="acym__subform__new" class="cell grid-x margin-y align-center acym__content margin-top-2 acym__selection">
		<h1 class="margin-top-1 margin-bottom-2 acym__title"><?php echo acym_translation('ACYM_WHICH_KIND_OF_SUB_FORM_CREATE'); ?></h1>
		<div class="cell grid-x grid-margin-x align-center">
			<div class="acym__selection__card cell large-2 medium-4 text-center" acym-data-link="<?php echo $data['widget_link']; ?>">
				<i class="acymicon-puzzle-piece acym__selection__card__icon"></i>
				<h1 class="acym__selection__card__title"><?php echo ACYM_CMS === 'joomla' ? acym_translation('ACYM_MODULE') : acym_translation('ACYM_WIDGET'); ?></h1>
				<p class="acym__selection__card__description"><?php echo ACYM_CMS === 'joomla'
                        ? acym_translation('ACYM_MODULE_SUBFORM_DESC')
                        : acym_translation(
                            'ACYM_WIDGET_SUBFORM_DESC'
                        ); ?></p>
			</div>
            <?php if (ACYM_CMS === 'wordpress') { ?>
				<div class="acym__selection__card acym__selection__select-card cell large-2 medium-4 text-center" acym-data-link="<?php echo $data['shortcode_link']; ?>">
					<i class="acymicon-chevrons acym__selection__card__icon"></i>
					<h1 class="acym__selection__card__title"><?php echo acym_translation('ACYM_SHORTCODE'); ?></h1>
					<p class="acym__selection__card__description"><?php echo acym_translation('ACYM_SHORTCODE_SUBFORM_DESC'); ?></p>
				</div>
            <?php } ?>
			<div class="acym__selection__card <?php echo !acym_level(ACYM_ENTERPRISE) ? 'acym__selection__card__disabled' : ''; ?> cell large-2 medium-4 text-center"
				 acym-data-link="<?php echo $data['popup_link']; ?>">
				<i class="acymicon-window acym__selection__card__icon"></i>
				<h1 class="acym__selection__card__title"><?php echo acym_translation('ACYM_POPUP'); ?></h1>
				<p class="acym__selection__card__description"><?php echo acym_translation('ACYM_POPUP_SUBFORM_DESC'); ?></p>
                <?php if (!acym_level(ACYM_ENTERPRISE)) echo '<div class="acym__selection__card__disabled__container cell">'.acym_translation(
                        'ACYM_ONLY_AVAILABLE_ENTERPRISE_VERSION'
                    ).'</div>'; ?>
			</div>
		</div>
		<div class="cell grid-x grid-margin-x align-center">
			<div class="acym__selection__card <?php echo !acym_level(ACYM_ENTERPRISE) ? 'acym__selection__card__disabled' : ''; ?> cell large-2 medium-4 text-center"
				 acym-data-link="<?php echo $data['header_link']; ?>">
				<i class="acymicon-vertical_align_top acym__selection__card__icon"></i>
				<h1 class="acym__selection__card__title"><?php echo acym_translation('ACYM_HEADER'); ?></h1>
				<p class="acym__selection__card__description"><?php echo acym_translation('ACYM_HEADER_SUBFORM_DESC'); ?></p>
                <?php if (!acym_level(ACYM_ENTERPRISE)) echo '<div class="acym__selection__card__disabled__container cell">'.acym_translation(
                        'ACYM_ONLY_AVAILABLE_ENTERPRISE_VERSION'
                    ).'</div>'; ?>
			</div>
			<div class="acym__selection__card <?php echo !acym_level(ACYM_ENTERPRISE) ? 'acym__selection__card__disabled' : ''; ?> cell large-2 medium-4 text-center"
				 acym-data-link="<?php echo $data['footer_link']; ?>">
				<i class="acymicon-vertical_align_bottom acym__selection__card__icon"></i>
				<h1 class="acym__selection__card__title"><?php echo acym_translation('ACYM_FOOTER'); ?></h1>
				<p class="acym__selection__card__description"><?php echo acym_translation('ACYM_FOOTER_SUBFORM_DESC'); ?></p>
                <?php if (!acym_level(ACYM_ENTERPRISE)) echo '<div class="acym__selection__card__disabled__container cell">'.acym_translation(
                        'ACYM_ONLY_AVAILABLE_ENTERPRISE_VERSION'
                    ).'</div>'; ?>
			</div>
		</div>
		<button type="button" class="cell shrink button" id="acym__selection__button-select" disabled><?php echo acym_translation('ACYM_CREATE'); ?></button>
	</div>
    <?php acym_formOptions(); ?>
</form>
