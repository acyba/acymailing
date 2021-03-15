<?php if (!empty($data['user-information']->id)) { ?>
	<div class="cell grid-x acym__users__display__history acym__content">
		<div class="cell grid-x acym__users__history__toggle">
			<button type="button"
					class="cell small-6 acym__users__history__toggle-button acym__users__history__toggle-button-selected"
					data-acym-toggle-history="mail"><?php echo acym_translation('ACYM_EMAIL_HISTORY'); ?></button>
			<button type="button" class="cell small-6 acym__users__history__toggle-button" data-acym-toggle-history="user"><?php echo acym_translation(
                    'ACYM_USER_HISTORY'
                ); ?></button>
		</div>
		<div class="cell grid-x align-middle" data-acym-type="mail">
            <?php if (empty($data['userMailHistory'])) {
                echo '<h2 class="cell acym__title__primary__color text-center">'.acym_translation('ACYM_YOU_DIDNT_SENT_EMAIL_SUBSCRIBER').'</h2>';
            } else { ?>
				<div class="grid-x cell grid-margin-x acym__listing__header acym__listing__header__user_history text-center">
					<div class="medium-4 hide-for-small-only cell acym__listing__header__title">
                        <?php echo acym_translation('ACYM_EMAIL_SUBJECT'); ?>
					</div>
					<div class="medium-2 hide-for-small-only cell acym__listing__header__title">
                        <?php echo acym_translation('ACYM_SEND_DATE'); ?>
					</div>
					<div class="medium-1 hide-for-small-only cell acym__listing__header__title">
                        <?php echo acym_translation('ACYM_OPEN'); ?>
					</div>
					<div class="medium-2 hide-for-small-only cell acym__listing__header__title">
                        <?php echo acym_translation('ACYM_OPEN_DATE'); ?>
					</div>
					<div class="medium-1 hide-for-small-only cell acym__listing__header__title">
                        <?php echo acym_translation('ACYM_CLICK'); ?>
					</div>
					<div class="medium-1 hide-for-small-only cell acym__listing__header__title">
                        <?php echo acym_translation('ACYM_BOUNCES'); ?>
					</div>
				</div>
				<div class="acym__users__display__history__listing grid-x cell">
                    <?php foreach ($data['userMailHistory'] as $oneMailHistory) { ?>
						<div class="grid-x cell text-center acym__listing__row grid-margin-x">
							<div class="medium-4 cell acym__users__email__history__subject">
                                <?php echo $oneMailHistory->subject; ?>
							</div>
							<div class="medium-2 cell">
                                <?php echo empty($oneMailHistory->send_date) || '0000-00-00 00:00:00' == $oneMailHistory->send_date
                                    ? '-'
                                    : acym_tooltip(
                                        acym_date(acym_getTime($oneMailHistory->send_date), 'd F H:i'),
                                        acym_date(acym_getTime($oneMailHistory->send_date), 'd F Y H:i:s')
                                    ); ?>
							</div>
							<div class="medium-1 cell text-center">
                                <?php echo $oneMailHistory->open; ?>
							</div>
							<div class="medium-2 cell text-center">
                                <?php echo empty($oneMailHistory->open_date)
                                    ? '-'
                                    : acym_tooltip(
                                        acym_date(acym_getTime($oneMailHistory->open_date), 'd F H:i'),
                                        acym_date(acym_getTime($oneMailHistory->open_date), 'd F Y H:i:s')
                                    ); ?>
							</div>
							<div class="medium-1 cell text-center">
                                <?php echo $oneMailHistory->click; ?>
							</div>
							<div class="medium-1 cell text-center">
                                <?php echo empty($oneMailHistory->bounce_rule) ? '-' : $oneMailHistory->bounce_rule; ?>
							</div>
						</div>
                    <?php } ?>
				</div>
            <?php } ?>
		</div>
		<div class="cell grid-x align-middle" data-acym-type="user">
            <?php if (empty($data['userHistory'])) {
                echo '<h2 class="cell acym__title__primary__color text-center">'.acym_translation('ACYM_USER_HISTORY_EMPTY').'</h2>';
            } else { ?>
				<div class="grid-x cell text-center grid-margin-x acym__listing__header acym__listing__header__user_history">
					<div class="medium-2 hide-for-small-only cell acym__listing__header__title">
                        <?php echo acym_translation('ACYM_DATE'); ?>
					</div>
					<div class="medium-2 hide-for-small-only cell acym__listing__header__title">
                        <?php echo acym_translation('ACYM_IP'); ?>
					</div>
					<div class="medium-2 hide-for-small-only cell acym__listing__header__title">
                        <?php echo acym_translation('ACYM_ACTIONS'); ?>
					</div>
					<div class="medium-3 hide-for-small-only cell acym__listing__header__title">
                        <?php echo acym_translation('ACYM_DETAILS'); ?>
					</div>
					<div class="medium-3 hide-for-small-only cell acym__listing__header__title">
                        <?php echo acym_translation('ACYM_SOURCE'); ?>
					</div>
				</div>
				<div class="acym__users__display__history__listing grid-x cell">
                    <?php
                    foreach ($data['userHistory'] as $key => $oneHistory) { ?>
						<div class="grid-x cell text-center acym__listing__row grid-margin-x">
							<div class="cell small-12 medium-2">
                                <?php echo acym_date($oneHistory->date, 'Y-m-d H:i'); ?>
							</div>
							<div class="cell small-6 medium-2">
                                <?php echo acym_escape($oneHistory->ip); ?>
							</div>
							<div class="cell small-6 medium-2">
                                <?php echo acym_translation('ACYM_ACTION_'.strtoupper(acym_escape($oneHistory->action))); ?>
							</div>
							<div class="cell small-6 medium-3">
                                <?php if (!empty($oneHistory->data)) echo $oneHistory->data; ?>
							</div>
							<div class="cell small-6 medium-3">
                                <?php if (!empty($oneHistory->source)) echo $oneHistory->source; ?>
							</div>
						</div>
                    <?php } ?>
				</div>
            <?php } ?>
		</div>
	</div>
<?php }
