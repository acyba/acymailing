<form id="acym_form" action="<?php echo acym_completeLink(acym_getVar('cmd', 'ctrl')); ?>" method="post" name="acyForm">
	<div id="acym__email__new" class="cell grid-x grid-margin-y align-center acym__content">
		<h1 class="margin-top-1 margin-bottom-2 acym__email__new__title"><?php echo acym_translation('ACYM_WHICH_KIND_OF_MAIL_CREATE'); ?></h1>
		<div class="cell grid-x grid-margin-x align-center">
			<div class="acym__email__new__card cell medium-4 text-center" acym-data-link="<?php echo $data['campaign_link']; ?>">
				<i class="acymicon-bullhorn acym__email__new__card__icon"></i>
				<h1 class="acym__email__new__card__title"><?php echo acym_translation('ACYM_CLASSIC_CAMPAIGN'); ?></h1>
				<p class="acym__email__new__card__description"><?php echo acym_translation('ACYM_CLASSIC_CAMPAIGN_DESC'); ?></p>
			</div>
			<div class="acym__email__new__card cell medium-4 text-center acym__email__new__card-list" acym-data-link="<?php echo $data['welcome_email_link'];; ?>">
				<i class="acymicon-handshake-o acym__email__new__card__icon"></i>
				<h1 class="acym__email__new__card__title"><?php echo acym_translation('ACYM_WELCOME_EMAIL'); ?></h1>
				<p class="acym__email__new__card__description"><?php echo acym_translation('ACYM_WELCOME_EMAIL_DESC'); ?></p>
                <?php echo acym_select(
                    $data['lists'],
                    'welcome_list_id',
                    null,
                    ['class' => 'acym__email__new__card__select']
                ) ?>
			</div>
			<div class="acym__email__new__card cell medium-4 text-center acym__email__new__card-list" acym-data-link="<?php echo $data['unsubscribe_email_link'];; ?>">
				<i class="acymicon-hand-paper-o acym__email__new__card__icon"></i>
				<h1 class="acym__email__new__card__title"><?php echo acym_translation('ACYM_UNSUBSCRIBE_EMAIL'); ?></h1>
				<p class="acym__email__new__card__description"><?php echo acym_translation('ACYM_UNSUBSCRIBE_EMAIL_DESC'); ?></p>
                <?php echo acym_select(
                    $data['lists'],
                    'welcome_list_id',
                    null,
                    ['class' => 'acym__email__new__card__select']
                ) ?>
			</div>
		</div>
		<div class="cell grid-x grid-margin-x align-center">
			<div class="acym__email__new__card <?php echo !acym_level(1) ? 'acym__email__new__card__disabled' : ''; ?> cell medium-4 text-center" acym-data-link="<?php echo $data['campaign_scheduled_link'];; ?>">
				<i class="acymicon-access_time acym__email__new__card__icon"></i>
				<h1 class="acym__email__new__card__title"><?php echo acym_translation('ACYM_SCHEDULED_CAMPAIGN'); ?></h1>
				<p class="acym__email__new__card__description"><?php echo acym_translation('ACYM_SCHEDULED_CAMPAIGN_DESC'); ?></p>
                <?php if (!acym_level(1)) echo '<div class="acym__email__new__card__disabled__container_test cell">'.acym_translation('ACYM_ONLY_AVAILABLE_ESSENTIAL_VERSION').'</div>'; ?>
			</div>
		</div>
		<button type="button" class="cell shrink button" disabled id="acym__email__new__button-create"><?php echo acym_translation('ACYM_CREATE'); ?></button>
	</div>
    <?php acym_formOptions(); ?>
</form>
