<?php
$classesClassicEmails = 'acym__selection__card cell medium-4 text-center';
$classesOneTimeEmails = 'acym__selection__card acym__selection__select-card cell medium-4 text-center';
?>
<form id="acym_form" action="<?php echo acym_completeLink(acym_getVar('cmd', 'ctrl')); ?>" method="post" name="acyForm"
    <?php echo !empty($data['menuClass']) ? 'class="'.acym_escape($data['menuClass']).'"' : ''; ?> >
	<div id="acym__email__new" class="cell grid-x grid-margin-y align-center acym__content acym__selection">
		<h1 class="margin-top-1 margin-bottom-2 acym__title"><?php echo acym_translation('ACYM_WHICH_KIND_OF_MAIL_CREATE'); ?></h1>
		<div class="cell grid-x grid-margin-x align-center margin-y">
            <?php
            $classes = $classesClassicEmails;
            if ($data['selectedType'] === 'campaign') {
                $classes .= ' acym__selection__card-selected';
            }
            ?>
			<div class="<?php echo $classes; ?>" acym-data-link="<?php echo $data['campaign_link']; ?>">
				<i class="acymicon-bullhorn acym__selection__card__icon"></i>
				<h1 class="acym__selection__card__title"><?php echo acym_translation('ACYM_CLASSIC_CAMPAIGN'); ?></h1>
				<p class="acym__selection__card__description"><?php echo acym_translation('ACYM_CLASSIC_CAMPAIGN_DESC'); ?></p>
			</div>
            <?php
            $classes = $classesOneTimeEmails;
            if ($data['selectedType'] === 'welcome') {
                $classes .= ' acym__selection__card-selected';
            }
            ?>
			<div class="<?php echo $classes; ?>" acym-data-link="<?php echo $data['welcome_email_link']; ?>">
				<i class="acymicon-handshake-o acym__selection__card__icon"></i>
				<h1 class="acym__selection__card__title"><?php echo acym_translation('ACYM_WELCOME_EMAIL'); ?></h1>
				<p class="acym__selection__card__description"><?php echo acym_translation('ACYM_WELCOME_EMAIL_DESC'); ?></p>
                <?php echo acym_select(
                    $data['lists'],
                    'welcome_list_id',
                    null,
                    ['class' => 'acym__email__new__card__select acym__select']
                ); ?>
			</div>
            <?php
            $classes = $classesOneTimeEmails;
            if ($data['selectedType'] === 'unsubscribe') {
                $classes .= ' acym__selection__card-selected';
            }
            ?>
			<div class="<?php echo $classes; ?>" acym-data-link="<?php echo $data['unsubscribe_email_link']; ?>">
				<i class="acymicon-hand-paper-o acym__selection__card__icon"></i>
				<h1 class="acym__selection__card__title"><?php echo acym_translation('ACYM_UNSUBSCRIBE_EMAIL'); ?></h1>
				<p class="acym__selection__card__description"><?php echo acym_translation('ACYM_UNSUBSCRIBE_EMAIL_DESC'); ?></p>
                <?php echo acym_select(
                    $data['lists'],
                    'unsubscribe_list_id',
                    null,
                    ['class' => 'acym__email__new__card__select acym__select']
                ); ?>
			</div>
		</div>
		<div class="cell grid-x grid-margin-x align-center">
            <?php
            $classes = $classesClassicEmails;
            if (!acym_level(ACYM_ESSENTIAL)) {
                $classes .= ' acym__selection__card__disabled';
            } elseif ($data['selectedType'] === 'scheduled') {
                $classes .= ' acym__selection__card-selected';
            }
            ?>
			<div class="<?php echo $classes; ?>" acym-data-link="<?php echo $data['campaign_scheduled_link']; ?>">
				<i class="acymicon-access_time acym__selection__card__icon"></i>
				<h1 class="acym__selection__card__title"><?php echo acym_translation('ACYM_SCHEDULED_CAMPAIGN'); ?></h1>
				<p class="acym__selection__card__description"><?php echo acym_translation('ACYM_SCHEDULED_CAMPAIGN_DESC'); ?></p>
                <?php if (!acym_level(ACYM_ESSENTIAL)) { ?>
					<div class="acym__selection__card__disabled__container cell">
                        <?php echo acym_translation('ACYM_ONLY_AVAILABLE_ESSENTIAL_VERSION'); ?>
					</div>
                <?php } ?>
			</div>
		</div>
		<button type="button"
				class="cell shrink button"
				id="acym__selection__button-select"
            <?php echo empty($data['selectedType']) ? 'disabled' : ''; ?>>
            <?php echo acym_translation('ACYM_CREATE'); ?>
		</button>
	</div>
    <?php acym_formOptions(); ?>
</form>
