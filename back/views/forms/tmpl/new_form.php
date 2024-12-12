<form id="acym_form" action="<?php echo acym_completeLink(acym_getVar('cmd', 'ctrl')); ?>" method="post" name="acyForm">
	<div id="acym__subform__new" class="cell grid-x margin-y align-center acym__content margin-top-2 acym__selection">
        <?php if (!acym_level(ACYM_ENTERPRISE)) {
            $pricingPage = ACYM_ACYMAILING_WEBSITE.'pricing?utm_source=acymailing_plugin&utm_medium=new_form&utm_campaign=upgrade_from_plugin';
            include acym_getPartial('modal', 'promotion_popup');
            $class = !acym_level(ACYM_ENTERPRISE) ? 'acym__selection__card__promotion' : '';
        } ?>

		<h1 class="margin-top-1 margin-bottom-2 acym__title "><?php echo acym_translation('ACYM_WHICH_KIND_OF_SUB_FORM_CREATE'); ?></h1>
		<div class="cell grid-x grid-margin-x align-center margin-bottom-3 margin-top-3">
			<div class="acym__campaign__selection__card acym__selection__scroll cell large-2 medium-4 text-center margin-bottom-1"
				 acym-data-link="<?php echo $data['widget_link']; ?>">
				<i class="acymicon-puzzle-piece acym__selection__card__icon"></i>
				<h1 class="acym__selection__card__title"><?php echo ACYM_CMS === 'joomla' ? acym_translation('ACYM_MODULE') : acym_translation('ACYM_WIDGET'); ?></h1>
				<p class="acym__selection__card__description">
                    <?php echo acym_translation(ACYM_CMS === 'joomla' ? 'ACYM_MODULE_SUBFORM_DESCRIPTION' : 'ACYM_WIDGET_SUBFORM_DESCRIPTION'); ?>
				</p>
			</div>
            <?php if (ACYM_CMS === 'wordpress') { ?>
				<div class="acym__campaign__selection__card acym__selection__scroll acym__selection__select-card cell large-2 medium-4 text-center margin-bottom-1"
					 acym-data-link="<?php echo $data['shortcode_link']; ?>">
					<i class="acymicon-chevrons acym__selection__card__icon"></i>
					<h1 class="acym__selection__card__title"><?php echo acym_translation('ACYM_SHORTCODE'); ?></h1>
					<p class="acym__selection__card__description"><?php echo acym_translation('ACYM_SHORTCODE_SUBFORM_DESC'); ?></p>
				</div>
            <?php } ?>
			<div class="acym__campaign__selection__card acym__selection__scroll <?php echo $class; ?> cell large-2 medium-4 text-center margin-bottom-1"
				 acym-data-link="<?php echo $data['popup_link']; ?>">

                <?php if (!acym_level(ACYM_ENTERPRISE)) { ?>
					<div class="acym__selection__card__lock">
						<i class="acymicon-lock"></i>
					</div>
                <?php } ?>

				<i class="acymicon-window acym__selection__card__icon"></i>
				<h1 class="acym__selection__card__title"><?php echo acym_translation('ACYM_POPUP'); ?></h1>
                <?php if (!acym_level(ACYM_ENTERPRISE)) { ?>
					<p class="acym__selection__card__promotion__text"><?php echo acym_translationSprintf('ACYM_UP_TO_X_MORE_SUBSCRIBERS', '33%'); ?></p>
                <?php } ?>
				<p class="acym__selection__card__description"><?php echo acym_translation('ACYM_POPUP_SUBFORM_DESC'); ?></p>
			</div>
			<div class="acym__campaign__selection__card acym__selection__scroll <?php echo $class; ?> cell large-2 medium-4 text-center margin-bottom-1"
				 acym-data-link="<?php echo $data['header_link']; ?>">

                <?php if (!acym_level(ACYM_ENTERPRISE)) { ?>
					<div class="acym__selection__card__lock">
						<i class="acymicon-lock"></i>
					</div>
                <?php } ?>

				<i class="acymicon-vertical_align_top acym__selection__card__icon"></i>
				<h1 class="acym__selection__card__title"><?php echo acym_translation('ACYM_HEADER'); ?></h1>
                <?php if (!acym_level(ACYM_ENTERPRISE)) { ?>
					<p class="acym__selection__card__promotion__text"><?php echo acym_translationSprintf('ACYM_UP_TO_X_MORE_SUBSCRIBERS', '21%'); ?></p>
                <?php } ?>
				<p class="acym__selection__card__description"><?php echo acym_translation('ACYM_HEADER_SUBFORM_DESC'); ?></p>
			</div>
			<div class="acym__campaign__selection__card acym__selection__scroll <?php echo $class; ?> cell large-2 medium-4 text-center margin-bottom-1"
				 acym-data-link="<?php echo $data['footer_link']; ?>">

                <?php if (!acym_level(ACYM_ENTERPRISE)) { ?>
					<div class="acym__selection__card__lock">
						<i class="acymicon-lock"></i>
					</div>
                <?php } ?>

				<i class="acymicon-vertical_align_bottom acym__selection__card__icon"></i>
				<h1 class="acym__selection__card__title"><?php echo acym_translation('ACYM_FOOTER'); ?></h1>
                <?php if (!acym_level(ACYM_ENTERPRISE)) { ?>
					<p class="acym__selection__card__promotion__text"><?php echo acym_translationSprintf('ACYM_UP_TO_X_MORE_SUBSCRIBERS', '15%'); ?></p>
                <?php } ?>
				<p class="acym__selection__card__description"><?php echo acym_translation('ACYM_FOOTER_SUBFORM_DESC'); ?></p>
			</div>
		</div>
		<button type="button" class="cell shrink button" id="acym__selection__button-select" disabled><?php echo acym_translation('ACYM_CREATE'); ?></button>
	</div>
    <?php acym_formOptions(); ?>
</form>
