<?php
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- View file, its variables are local to the include scope, not true globals.
// context verification
if (!empty($data['user-information']->id)) { ?>
	<div class="cell grid-x acym__users__display__history acym__content">
		<div class="cell grid-x acym__users__history__toggle">
			<button type="button"
			        class="cell small-6 acym__users__history__toggle-button acym__users__history__toggle-button-selected"
			        data-acym-toggle-history="mail"><?php echo acym_escapeHtml(acym_translation('ACYM_EMAIL_HISTORY')); ?></button>
			<button type="button" class="cell small-6 acym__users__history__toggle-button" data-acym-toggle-history="user">
                <?php echo acym_escapeHtml(acym_translation('ACYM_USER_HISTORY')); ?>
			</button>
		</div>
		<div class="cell grid-x align-middle" data-acym-type="mail">
            <?php if (empty($data['userMailHistory'])) {
                echo '<h2 class="cell acym__title__primary__color text-center">'.acym_escapeHtml(acym_translation('ACYM_YOU_DIDNT_SENT_EMAIL_SUBSCRIBER')).'</h2>';
            } else { ?>
				<div class="grid-x cell grid-margin-x acym__listing__header acym__listing__header__user_history text-center">
					<div class="medium-4 hide-for-small-only cell acym__listing__header__title">
                        <?php echo acym_escapeHtml(acym_translation('ACYM_EMAIL_SUBJECT')); ?>
					</div>
					<div class="medium-2 hide-for-small-only cell acym__listing__header__title">
                        <?php echo acym_escapeHtml(acym_translation('ACYM_SEND_DATE')); ?>
					</div>
					<div class="medium-1 hide-for-small-only cell acym__listing__header__title">
                        <?php echo acym_escapeHtml(acym_translation('ACYM_OPEN')); ?>
					</div>
					<div class="medium-2 hide-for-small-only cell acym__listing__header__title">
                        <?php echo acym_escapeHtml(acym_translation('ACYM_OPEN_DATE')); ?>
					</div>
					<div class="medium-1 hide-for-small-only cell acym__listing__header__title">
                        <?php echo acym_escapeHtml(acym_translation('ACYM_CLICK')); ?>
					</div>
					<div class="medium-2 hide-for-small-only cell acym__listing__header__title">
                        <?php echo acym_escapeHtml(acym_translation('ACYM_BOUNCES')); ?>
					</div>
				</div>
				<div class="acym__users__display__history__listing grid-x cell">
                    <?php foreach ($data['userMailHistory'] as $oneMailHistory) { ?>
						<div class="grid-x cell text-center acym__listing__row grid-margin-x">
							<div class="medium-4 cell acym__users__email__history__subject">
								<a href="<?php echo acym_escapeUrl(acym_frontendLink('archive&task=view&id='.$oneMailHistory->id.'&userid='.$data['user-information']->id.'-'.$data['user-information']->key.'&'.acym_noTemplate())); ?>"
								   target="_blank"><?php echo acym_escapeHtml($oneMailHistory->subject); ?></a>
							</div>
							<div class="medium-2 cell">
                                <?php
                                if (empty($oneMailHistory->send_date) || '0000-00-00 00:00:00' == $oneMailHistory->send_date) {
                                    echo '-';
                                } else {
                                    acym_tooltip(
                                        [
                                            'hoveredText' => acym_date(acym_getTimeFromUTCDate($oneMailHistory->send_date), 'd F H:i'),
                                            'textShownInTooltip' => acym_date(acym_getTimeFromUTCDate($oneMailHistory->send_date), acym_getDateTimeFormat()),
                                        ]
                                    );
                                }
                                ?>
							</div>
							<div class="medium-1 cell text-center">
                                <?php echo acym_escapeHtml($oneMailHistory->open ?? ''); ?>
							</div>
							<div class="medium-2 cell text-center">
                                <?php
                                if (empty($oneMailHistory->open_date)) {
                                    echo '-';
                                } else {
                                    acym_tooltip(
                                        [
                                            'hoveredText' => acym_date(acym_getTimeFromUTCDate($oneMailHistory->open_date), 'd F H:i'),
                                            'textShownInTooltip' => acym_date(acym_getTimeFromUTCDate($oneMailHistory->open_date), acym_getDateTimeFormat()),
                                        ]
                                    );
                                }
                                ?>
							</div>
							<div class="medium-1 cell text-center">
                                <?php echo acym_escapeHtml($oneMailHistory->click ?? ''); ?>
							</div>
							<div class="medium-2 cell text-center acym__listing__header__user_history__bounce">
                                <?php echo empty($oneMailHistory->bounce_rule) ? '-' : acym_escapeHtml($oneMailHistory->ruleName ?? ''); ?>
							</div>
						</div>
                    <?php } ?>
				</div>
            <?php } ?>
		</div>
		<div class="cell grid-x align-middle" data-acym-type="user">
            <?php if (empty($data['userHistory'])) {
                echo '<h2 class="cell acym__title__primary__color text-center">'.acym_escapeHtml(acym_translation('ACYM_USER_HISTORY_EMPTY')).'</h2>';
            } else { ?>
				<div class="grid-x cell text-center grid-margin-x acym__listing__header acym__listing__header__user_history">
					<div class="medium-2 hide-for-small-only cell acym__listing__header__title">
                        <?php echo acym_escapeHtml(acym_translation('ACYM_DATE')); ?>
					</div>
					<div class="medium-2 hide-for-small-only cell acym__listing__header__title">
                        <?php echo acym_escapeHtml(acym_translation('ACYM_IP')); ?>
					</div>
					<div class="medium-2 hide-for-small-only cell acym__listing__header__title">
                        <?php echo acym_escapeHtml(acym_translation('ACYM_ACTIONS')); ?>
					</div>
					<div class="medium-3 hide-for-small-only cell acym__listing__header__title">
                        <?php echo acym_escapeHtml(acym_translation('ACYM_DETAILS')); ?>
					</div>
					<div class="medium-3 hide-for-small-only cell acym__listing__header__title">
                        <?php echo acym_escapeHtml(acym_translation('ACYM_SOURCE')); ?>
					</div>
				</div>
				<div class="acym__users__display__history__listing grid-x cell">
                    <?php foreach ($data['userHistory'] as $key => $oneHistory) { ?>
						<div class="grid-x cell text-center acym__listing__row grid-margin-x">
							<div class="cell small-12 medium-2">
                                <?php echo acym_escapeHtml(acym_date($oneHistory->date, acym_getDateTimeFormat())); ?>
							</div>
							<div class="cell small-6 medium-2 acym__users__display__history__listing__ip">
                                <?php echo acym_escapeHtml($oneHistory->ip); ?>
							</div>
							<div class="cell small-6 medium-2">
                                <?php
                                $langKey = 'ACYM_ACTION_'.strtoupper($oneHistory->action);
                                $translation = acym_translation($langKey);
                                echo acym_escapeHtml($translation === $langKey ? $oneHistory->action : $translation);
                                if ($oneHistory->action === 'unsubscribed' && !empty($oneHistory->unsubscribe_reason)) {
                                    if (is_numeric($oneHistory->unsubscribe_reason)) {
                                        $index = $oneHistory->unsubscribe_reason - 1;
                                        $reason = $data['unsubReasons'][$index] ?? $oneHistory->unsubscribe_reason;
                                    } else {
                                        $reason = $oneHistory->unsubscribe_reason;
                                    }
                                    echo '<br />'.acym_escapeHtml($reason);
                                }
                                ?>
							</div>
							<div class="cell small-6 medium-3">
                                <?php
                                if (!empty($oneHistory->data)) {
                                    $historyData = explode("\n", $oneHistory->data);
                                    $details = '<div><h5>'.acym_escapeHtml(acym_translation('ACYM_DETAILS')).'</h5><br />';
                                    if (!empty($oneHistory->mail_id)) {
                                        $details .= '<b>'.acym_escapeHtml(acym_translation('ACYM_CAMPAIGN')).' : </b>';
                                        $details .= acym_escapeHtml($oneHistory->subject).' ( '.acym_escapeHtml(acym_translation('ACYM_ID').' : '.$oneHistory->mail_id).' )<br />';
                                    }

                                    foreach ($historyData as $value) {
                                        if (!strpos($value, '::')) {
                                            $details .= $value.'<br />';
                                            continue;
                                        }
                                        [$part1, $part2] = explode('::', $value);
                                        if (preg_match('#^[A-Z_]*$#', $part2)) {
                                            $part2 = acym_translation($part2);
                                        }
                                        $details .= '<b>'.acym_escapeHtml(acym_translation($part1)).' : </b>'.acym_escapeHtml($part2).'<br />';
                                    }

                                    if ($oneHistory->action === 'unsubscribed') {
                                        $details .= acym_escapeHtml(acym_translation('ACYM_UNSUBSCRIBE_REASON'));
                                        if (empty($oneHistory->unsubscribe_reason)) {
                                            $details .= ' '.acym_escapeHtml(acym_translation('ACYM_NO_REASON_SET_BY_USER'));
                                        } else {
                                            $reason = $data['historyClass']->getUnsubscribeReasonText($oneHistory->unsubscribe_reason);
                                            $details .= ' '.acym_escapeHtml($reason);
                                        }
                                    }

                                    $details .= '</div>';

                                    acym_modal(
                                        acym_translation('ACYM_VIEW_DETAILS'),
                                        $details,
                                        null,
                                        ['style' => 'word-break: break-word;'],
                                        ['class' => 'history_details'],
                                        true,
                                        false
                                    );
                                }
                                ?>
							</div>
							<div class="cell small-6 medium-3">
                                <?php
                                if (!empty($oneHistory->source)) {
                                    $source = explode("\n", $oneHistory->source);
                                    $details = '<div><h5>'.acym_escapeHtml(acym_translation('ACYM_SOURCE')).'</h5><br />';
                                    foreach ($source as $value) {
                                        if (!strpos($value, '::')) continue;
                                        [$part1, $part2] = explode('::', $value);
                                        $details .= '<b>'.acym_escapeHtml($part1).' : </b>'.acym_escapeHtml($part2).'<br />';
                                    }
                                    $details .= '</div>';

                                    acym_modal(
                                        acym_translation('ACYM_VIEW_SOURCE'),
                                        $details,
                                        null,
                                        ['style' => 'word-break: break-word;'],
                                        ['class' => 'history_details']
                                    );
                                }
                                ?>
							</div>
						</div>
                    <?php } ?>
				</div>
            <?php } ?>
		</div>
	</div>
<?php }
