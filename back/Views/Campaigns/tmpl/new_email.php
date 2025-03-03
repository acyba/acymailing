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
        'data-link' => $data['campaign_scheduled_link'],
        'type' => 'scheduled',
        'icon' => 'access-time',
        'title' => 'ACYM_SCHEDULED_CAMPAIGN',
        'promotion-text' => 'ACYM_PROMOTE_SCHEDULED_CAMPAIGN',
        'desc' => 'ACYM_SCHEDULED_CAMPAIGN_DESC',
        'level' => ACYM_ESSENTIAL,
        'error_message' => 'ACYM_ONLY_AVAILABLE_ESSENTIAL_VERSION',
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
        'data-link' => $data['campaign_auto_link'],
        'type' => 'automatic_campaign',
        'icon' => 'autorenew',
        'title' => 'ACYM_AUTOMATIC_CAMPAIGN',
        'promotion-text' => 'ACYM_PROMOTE_AUTO_CAMPAIGN',
        'desc' => 'ACYM_AUTOMATIC_CAMPAIGN_DESC',
        'level' => ACYM_ENTERPRISE,
        'error_message' => 'ACYM_ONLY_AVAILABLE_ENTERPRISE_VERSION',
    ],
    [
        'data-link' => $data['followup_link'],
        'type' => 'followup',
        'icon' => 'email',
        'title' => 'ACYM_FOLLOW_UP',
        'promotion-text' => 'ACYM_PROMOTE_FOLLOWUP_CAMPAIGN',
        'desc' => 'ACYM_FOLLOW_UP_DESC',
        'level' => ACYM_ENTERPRISE,
        'error_message' => 'ACYM_ONLY_AVAILABLE_ENTERPRISE_VERSION',
    ],
];

$classesOneTimeEmails = 'acym__campaign__selection__card acym__selection__select-card cell xxlarge-2 large-3 medium-4 text-center';
?>
<form id="acym_form" action="<?php echo acym_completeLink(acym_getVar('cmd', 'ctrl')); ?>" method="post" name="acyForm">
	<div id="acym__email__new" class="cell grid-x grid-margin-y align-center acym__content margin-top-2 acym__selection">
		<div class="cell shrink medium-12">
			<h1 class="margin-top-1 margin-bottom-2 acym__title acym__campaign__selection__title text-center">
                <?php echo acym_translation('ACYM_WHICH_KIND_OF_MAIL_CREATE'); ?>
			</h1>
		</div>
		<div class="cell shrink medium-4 margin-bottom-3">
			<ul class="acym__selection__page__tabs" id="workflow">
                <?php if ($data['isNewslettersTab']) { ?>
					<li class="step current_step" data-tab="acym__campaign__selection__newsletters">
						<a>
                            <?php echo acym_translation('ACYM_NEWSLETTERS'); ?>
						</a>
					</li>
					<li class="step " data-tab="acym__campaign__selection__onetime">
						<a>
                            <?php echo acym_translation('ACYM_ONE_TIME_EMAIL'); ?>
						</a>
					</li>
                <?php } else { ?>
					<li class="step" data-tab="acym__campaign__selection__newsletters">
						<a>
                            <?php echo acym_translation('ACYM_NEWSLETTERS'); ?>
						</a>
					</li>
					<li class="step current_step" data-tab="acym__campaign__selection__onetime">
						<a>
                            <?php echo acym_translation('ACYM_ONE_TIME_EMAIL'); ?>
						</a>
					</li>
                <?php } ?>
			</ul>
		</div>
		<!-- Newsletters -->
		<div id="acym__campaign__selection__newsletters"
			 class="cell grid-x grid-margin-x align-center margin-y"
			 style="<?php echo $data['isNewslettersTab'] ? '' : 'display: none;'; ?>">
            <?php
            foreach ($newsletters as $oneNewsletterType) {
                $classes = '';
                if (!acym_level($oneNewsletterType['level'])) {
                    $classes = 'acym__selection__card__promotion ';
                }
                $classes .= 'acym__campaign__selection__card cell xxlarge-2 large-3 medium-4 text-center';
                if ($oneNewsletterType['type'] === $data['selectedType']) {
                    $classes .= ' acym__campaign__selection__card-selected ';
                } else {
                    $classes .= '';
                }
                if ($oneNewsletterType['type'] === 'scheduled') {
                    $classes .= ' scheduled ';
                } ?>
				<div class="<?php echo $classes; ?> acym__campaign__selection__card-container" data-email-type="<?php echo acym_escape($oneNewsletterType['type']); ?>">
                    <?php if (!acym_level($oneNewsletterType['level'])) { ?>
						<div class="acym__selection__card__lock">
							<i class="acymicon-lock"></i>
						</div>
                    <?php } ?>
					<i class="acymicon-<?php echo $oneNewsletterType['icon']; ?> acym__selection__card__icon"></i>
					<h1 class="acym__selection__card__title"><?php echo acym_translation($oneNewsletterType['title']); ?></h1>
                    <?php if (!acym_level($oneNewsletterType['level'])) { ?>
						<p class="acym__selection__card__promotion__text"><?php if (!empty($oneNewsletterType['promotion-text'])) {
                                echo acym_translation(
                                    $oneNewsletterType['promotion-text']
                                );
                            } ?></p>
                    <?php } else { ?>
						<p class="acym__selection__card__promotion__text"><?php echo ' '; ?></p>
                    <?php } ?>
					<p class="acym__selection__card__description"><?php echo acym_translation($oneNewsletterType['desc']); ?></p>
					<button type="button"
							class="cell shrink button acym__campaign__selection__button-select button-primary <?php echo acym_level($oneNewsletterType['level']) ? ''
                                : 'acym__promotion__disabled__button'; ?>"
							acym-data-link="<?php echo acym_level($oneNewsletterType['level']) ? $oneNewsletterType['data-link'] : ''; ?>">
                        <?php echo acym_translation('ACYM_CREATE'); ?>
					</button>
				</div>
                <?php
            }
            ?>
		</div>
		<!-- One time email -->
        <?php if (acym_isAllowed('mails')) { ?>
			<div id="acym__campaign__selection__onetime"
				 class="cell grid-x grid-margin-x align-center margin-y"
				 style="<?php echo $data['isNewslettersTab'] ? 'display: none;' : ''; ?>">
                <?php
                $classes = $classesOneTimeEmails;
                if ($data['selectedType'] === 'welcome') {
                    $classes .= ' acym__campaign__selection__card-selected ';
                } else {
                    $classes .= ' ';
                }
                ?>
				<div class="<?php echo $classes; ?> acym__campaign__selection__card-container">
					<i class="acymicon-handshake-o acym__selection__card__icon"></i>
					<h1 class="acym__selection__card__title"><?php echo acym_translation('ACYM_WELCOME_EMAIL'); ?></h1>
					<p class="acym__selection__card__description"><?php echo acym_translation('ACYM_WELCOME_EMAIL_DESC'); ?></p>
                    <?php echo acym_select(
                        $data['lists'],
                        'welcome_list_id',
                        null,
                        ['class' => 'acym__email__new__card__select acym__select']
                    ); ?>
					<button type="button"
							class="cell shrink button acym__campaign__selection__button-select button-primary"
							acym-data-link="<?php echo $data['welcome_email_link']; ?>" <?php echo empty($data['selectedType']); ?>>
                        <?php echo acym_translation('ACYM_CREATE'); ?>
					</button>
				</div>
                <?php
                $classes = $classesOneTimeEmails;
                if ($data['selectedType'] === 'unsubscribe') {
                    $classes .= ' acym__campaign__selection__card-selected ';
                } else {
                    $classes .= ' ';
                }
                ?>
				<div class="<?php echo $classes; ?> acym__campaign__selection__card-container">
					<i class="acymicon-hand-paper-o acym__selection__card__icon"></i>
					<h1 class="acym__selection__card__title"><?php echo acym_translation('ACYM_UNSUBSCRIBE_EMAIL'); ?></h1>
					<p class="acym__selection__card__description"><?php echo acym_translation('ACYM_UNSUBSCRIBE_EMAIL_DESC'); ?></p>
                    <?php echo acym_select(
                        $data['lists'],
                        'unsubscribe_list_id',
                        null,
                        ['class' => 'acym__email__new__card__select acym__select']
                    ); ?>
					<button type="button"
							class="cell shrink button acym__campaign__selection__button-select button-primary"
							acym-data-link="<?php echo $data['unsubscribe_email_link']; ?>" <?php echo empty($data['selectedType']); ?>>
                        <?php echo acym_translation('ACYM_CREATE'); ?>
					</button>
				</div>
                <?php
                foreach ($data['extraBlocks'] as $oneExtra) {
                    if (!acym_level($oneExtra['level'])) {
                        continue;
                    }

                    $classes = $classesOneTimeEmails;
                    if (!empty($oneExtra['email_type']) && $data['selectedType'] === $oneExtra['email_type']) {
                        $classes .= ' acym__campaign__selection__card-selected ';
                    } else {
                        $classes .= ' ';
                    }
                    ?>
					<div class="<?php echo $classes; ?> acym__campaign__selection__card-container">
						<i class="<?php echo $oneExtra['icon']; ?> acym__selection__card__icon"></i>
						<h1 class="acym__selection__card__title"><?php echo $oneExtra['name']; ?></h1>
						<p class="acym__selection__card__description"><?php echo $oneExtra['description']; ?></p>
						<button type="button"
								class="cell shrink button acym__campaign__selection__button-select button-primary"
								acym-data-link="<?php echo $oneExtra['link']; ?>" <?php echo empty($data['selectedType']); ?>>
                            <?php echo acym_translation('ACYM_CREATE'); ?>
						</button>
					</div>
                <?php } ?>
			</div>
        <?php } ?>
        <?php
        if (!acym_level(ACYM_ENTERPRISE)) {
            $pricingPage = ACYM_ACYMAILING_WEBSITE.'pricing?utm_source=acymailing_plugin&utm_medium=new_email&utm_campaign=upgrade_from_plugin';
            include acym_getPartial('modal', 'promotion_popup');
        }
        acym_formOptions();
        ?>
</form>

