<?php
$newsletters = [
    [
        'data-link' => $data['campaign_link'],
        'type' => 'campaign',
        'icon' => 'bullhorn',
        'title' => 'ACYM_CLASSIC_CAMPAIGN',
        'desc' => 'ACYM_CLASSIC_CAMPAIGN_DESC',
        'level' => ACYM_STARTER,
    ],
    [
        'data-link' => $data['campaign_test_link'],
        'type' => 'campaign_test',
        'icon' => 'flask',
        'title' => 'ACYM_AB_TEST_CAMPAIGN',
        'desc' => 'ACYM_AB_TEST_CAMPAIGN_DESC',
        'level' => ACYM_ENTERPRISE,
        'error_message' => 'ACYM_ONLY_AVAILABLE_ENTERPRISE_VERSION',
    ],
    [
        'data-link' => $data['campaign_scheduled_link'],
        'type' => 'scheduled',
        'icon' => 'access_time',
        'title' => 'ACYM_SCHEDULED_CAMPAIGN',
        'desc' => 'ACYM_SCHEDULED_CAMPAIGN_DESC',
        'level' => ACYM_ESSENTIAL,
        'error_message' => 'ACYM_ONLY_AVAILABLE_ESSENTIAL_VERSION',
    ],
    [
        'data-link' => $data['campaign_auto_link'],
        'type' => 'automatic_campaign',
        'icon' => 'autorenew',
        'title' => 'ACYM_AUTOMATIC_CAMPAIGN',
        'desc' => 'ACYM_AUTOMATIC_CAMPAIGN_DESC',
        'level' => ACYM_ENTERPRISE,
        'error_message' => 'ACYM_ONLY_AVAILABLE_ENTERPRISE_VERSION',
    ],
    [
        'data-link' => $data['followup_link'],
        'type' => 'followup',
        'icon' => 'email',
        'title' => 'ACYM_FOLLOW_UP',
        'desc' => 'ACYM_FOLLOW_UP_DESC',
        'level' => ACYM_ENTERPRISE,
        'error_message' => 'ACYM_ONLY_AVAILABLE_ENTERPRISE_VERSION',
    ],
];

$classesOneTimeEmails = 'acym__selection__card acym__selection__select-card cell xxlarge-2 large-3 medium-4 text-center';
?>
<form id="acym_form" action="<?php echo acym_completeLink(acym_getVar('cmd', 'ctrl')); ?>" method="post" name="acyForm">
	<div id="acym__email__new" class="cell grid-x grid-margin-y align-center acym__content margin-top-2 acym__selection">
		<h1 class="margin-top-1 margin-bottom-2 acym__title">
            <?php echo acym_translation('ACYM_WHICH_KIND_OF_MAIL_CREATE'); ?>
		</h1>
		<div class="cell grid-x grid-margin-x align-center margin-y">
			<h2 class="cell small-12 margin-bottom-1 text-center acym__title acym__title__secondary">
                <?php echo acym_translation('ACYM_NEWSLETTERS'); ?>
			</h2>
            <?php
            foreach ($newsletters as $oneNewsletterType) {
                $classes = 'acym__selection__card cell xxlarge-2 large-3 medium-4 text-center';
                if (!acym_level($oneNewsletterType['level'])) {
                    $classes .= ' acym__selection__card__disabled';
                } elseif ($oneNewsletterType['type'] === $data['selectedType']) {
                    $classes .= ' acym__selection__card-selected';
                }
                ?>
				<div class="<?php echo $classes; ?>" acym-data-link="<?php echo $oneNewsletterType['data-link']; ?>">
					<i class="acymicon-<?php echo $oneNewsletterType['icon']; ?> acym__selection__card__icon"></i>
					<h1 class="acym__selection__card__title"><?php echo acym_translation($oneNewsletterType['title']); ?></h1>
					<p class="acym__selection__card__description"><?php echo acym_translation($oneNewsletterType['desc']); ?></p>
                    <?php
                    if (!acym_level($oneNewsletterType['level'])) {
                        echo '<div class="acym__selection__card__disabled__container cell">'.acym_translation($oneNewsletterType['error_message']).'</div>';
                    }
                    ?>
				</div>
                <?php
            }
            ?>
		</div>
        <?php if (acym_isAllowed('mails')) { ?>
			<div class="cell grid-x grid-margin-x align-center margin-y">
				<h2 class="cell small-12 margin-bottom-1 text-center acym__title acym__title__secondary">
                    <?php echo acym_translation('ACYM_ONE_TIME_EMAIL'); ?>
				</h2>
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
                <?php
                $extraBlocks = [];
                acym_trigger('getNewEmailsTypeBlock', [&$extraBlocks]);
                foreach ($extraBlocks as $oneExtra) {
                    if (!acym_level($oneExtra['level'])) {
                        continue;
                    }

                    $classes = $classesOneTimeEmails;
                    if (!empty($oneExtra['email_type']) && $data['selectedType'] === $oneExtra['email_type']) {
                        $classes .= ' acym__selection__card-selected';
                    }
                    ?>
					<div class="<?php echo $classes; ?>" acym-data-link="<?php echo $oneExtra['link']; ?>">
						<i class="<?php echo $oneExtra['icon']; ?> acym__selection__card__icon"></i>
						<h1 class="acym__selection__card__title"><?php echo $oneExtra['name']; ?></h1>
						<p class="acym__selection__card__description"><?php echo $oneExtra['description']; ?></p>
					</div>
                <?php } ?>
			</div>
        <?php } ?>

		<button type="button" class="cell shrink button" id="acym__selection__button-select" <?php echo empty($data['selectedType']) ? 'disabled' : ''; ?>>
            <?php echo acym_translation('ACYM_CREATE'); ?>
		</button>
	</div>
    <?php acym_formOptions(); ?>
</form>
