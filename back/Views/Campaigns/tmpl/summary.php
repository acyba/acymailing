<?php
$isAbTest = $data['abtest'];
$isSent = !empty($data['campaignInformation']->sent) && !empty($data['campaignInformation']->active);
$campaignController = acym_isAdmin() ? 'campaigns' : 'frontcampaigns';
?>
<form id="acym_form"
	  action="<?php echo acym_completeLink(acym_getVar('cmd', 'ctrl')); ?>"
	  method="post"
	  name="acyForm"
	  class="acym__form__campaign__edit <?php echo !empty($data['menuClass']) ? acym_escape($data['menuClass']) : ''; ?>"
	  data-abide
	  novalidate>
	<div class="grid-x">
		<div class="cell medium-auto"></div>
		<div class="cell <?php echo $data['containerClass']; ?> acym__content">

            <?php
            $this->addSegmentStep($data['displaySegmentTab']);
            $workflow = $data['workflowHelper'];
            echo $workflow->display($this->steps, $this->step, true, false, '', 'campaignId');
            ?>

			<div id="acym__campaign__summary" class="grid-x grid-margin-y">
				<div class="cell grid-x acym__campaign__summary__section margin-right-2">
					<h5 class="cell shrink margin-next-1 acym__title acym__title__secondary">
						<b><?php echo acym_translation('ACYM_EMAIL'); ?></b>
					</h5>
					<div class="cell auto acym__campaign__summary__modify">
						<a href="<?php echo acym_completeLink(
                            $campaignController.'&task=edit&step=editEmail&edition=1&campaignId='.intval($data['campaignInformation']->id)
                        ); ?>"><i class="acymicon-pencil"></i><span> <?php echo acym_translation('ACYM_EDIT'); ?></span></a>
					</div>
					<div class="cell grid-x">
						<p class="cell medium-6 acym__campaign__summary__email__information">
                            <?php echo acym_translation('ACYM_FROM_NAME'); ?>: <span class="acym__color__blue">
								<?php echo acym_escape($data['mailInformation']->from_name); ?>
							</span>
						</p>
						<p class="cell medium-6 acym__campaign__summary__email__information">
                            <?php echo acym_translation('ACYM_FROM_EMAIL'); ?>: <span class="acym__color__blue">
								<?php echo acym_escape($data['mailInformation']->from_email); ?>
							</span>
						</p>
						<p class="cell medium-6 acym__campaign__summary__email__information">
                            <?php echo acym_translation('ACYM_REPLYTO_NAME'); ?>: <span class="acym__color__blue">
								<?php echo acym_escape($data['mailInformation']->reply_to_name); ?>
							</span>
						</p>
						<p class="cell medium-6 acym__campaign__summary__email__information">
                            <?php echo acym_translation('ACYM_REPLYTO_EMAIL'); ?>: <span class="acym__color__blue">
								<?php echo acym_escape($data['mailInformation']->reply_to_email); ?>
							</span>
						</p>
						<p class="cell medium-6 margin-bottom-1 margin-top-1 acym__campaign__summary__email__information">
                            <?php echo acym_translation('ACYM_EMAIL_SUBJECT'); ?>:
							<span class="acym__color__blue acym__campaign__summary__email__information-subject">
								<?php echo acym_escape($data['mailInformation']->subject); ?>
							</span>
						</p>
                        <?php if (!empty($data['isArchiveCached'])) { ?>
							<div class="cell grid-x grid-margin-x" id="acym__campaigns__summary__archive__refresh">
								<button
										id="acym__campaigns__summary__refresh__archive"
										class="button-secondary button margin-top-0 margin-bottom-2 margin-right-0 cell medium-shrink">
                                    <?php echo acym_translation('ACYM_REFRESH_ARCHIVE'); ?>
								</button>
                                <?php echo acym_info(['textShownInTooltip' => 'ACYM_REFRESH_ARCHIVE_DESC']); ?>
								<div id="acym__campaigns__summary__refresh__archive__message"></div>
							</div>
                        <?php } ?>
					</div>
					<!-- We add the email content in a hidden div to load it into the iframe preview -->
                    <?php
                    if ($data['multilingual']) {
                        include acym_getView('campaigns', 'summary_languages', true);
                    }
                    if (!empty($data['abtest'])) {
                        include acym_getView('campaigns', 'summary_abtest', true);
                    }
                    ?>
					<input type="hidden" class="acym__hidden__mail__content" value="<?php echo acym_escape(acym_absoluteURL($data['mailInformation']->body)); ?>">
					<input type="hidden" class="acym__hidden__mail__stylesheet" value="<?php echo acym_escape($data['mailInformation']->stylesheet); ?>">
					<div class="cell grid-x">
						<div id="acym__wysid__email__preview" class="acym__email__preview grid-x cell"></div>
					</div>
				</div>
                <?php if (!empty($data['mailInformation']->attachments)) { ?>
					<div class="cell grid-x acym__campaign__summary__section">
						<h5 class="cell shrink margin-next-1 acym__title acym__title__secondary">
							<b><?php echo acym_translation('ACYM_ATTACHMENTS'); ?></b>
						</h5>
						<div class="cell auto acym__campaign__summary__modify">
							<a href="<?php echo acym_completeLink(
                                $campaignController.'&task=edit&step=editEmail&edition=1&campaignId='.intval($data['campaignInformation']->id)
                            ); ?>"><i
										class="acymicon-pencil"></i><span> <?php echo acym_translation('ACYM_EDIT'); ?></span></a>
						</div>
                        <?php
                        $attachments = json_decode($data['mailInformation']->attachments, true);
                        foreach ($attachments as $key => $oneAttachment) {
                            $onlyFilename = explode('/', $oneAttachment['filename']);

                            $onlyFilename = end($onlyFilename);

                            echo acym_tooltip(
                                [
                                    'hoveredText' => '<div class="cell" data-toggle="path_attachment_'.$key.'">'.$onlyFilename.'</div>',
                                    'textShownInTooltip' => $oneAttachment['filename'],
                                    'classContainer' => 'cell',
                                ]
                            );
                        }
                        ?>
					</div>
                <?php } ?>
				<div class="cell grid-x acym__campaign__summary__section">
					<h5 class="cell shrink margin-next-1 acym__title acym__title__secondary">
						<b>
                            <?php
                            echo acym_translation('ACYM_RECIPIENTS');
                            echo ' ('.$data['nbSubscribers'].' '.acym_translation('ACYM_SUBSCRIBERS').acym_info(['textShownInTooltip' => 'ACYM_SUMMARY_NUMBER_RECEIVERS_DESC']).')';
                            ?>
						</b>
					</h5>
					<div class="cell auto acym__campaign__summary__modify">
						<a href="<?php echo acym_completeLink(
                            $campaignController.'&task=edit&step=recipients&edition=1&campaignId='.intval($data['campaignInformation']->id)
                        ); ?>"><i class="acymicon-pencil"></i><span> <?php echo acym_translation('ACYM_EDIT'); ?></span></a>
					</div>
					<div class="cell acym__listing">
                        <?php foreach ($data['listsReceiver'] as $oneList) {
                            $subscribers = acym_modalInclude(
                                '<span class="text-underline cursor-pointer">
											<b>'.$oneList->subscribers.'
											</b> '.acym_strtolower(acym_translation('ACYM_SUBSCRIBERS')).'
											</span>',
                                acym_getPartial('modal', 'users'),
                                'acym__campaign__summary__users_summary__'.$oneList->list_id,
                                [
                                    'ctrl' => acym_isAdmin() ? 'lists' : 'frontlists',
                                    'task' => 'usersSummary',
                                    'list_id' => $oneList->list_id,
                                ],
                                'acym__modal__users__summary__container'
                            );
                            echo '<div class="cell grid-x acym__listing__row">
							<span class="cell small-6">
							<i class="acymicon-circle acym__campaign__summary__recipients__list__color margin-right-1" 
							style="color: '.$oneList->color.'">
							</i>
							<b>'.$oneList->name.'</b>
							</span>
							<span class="cell small-6">'.$subscribers.'</span>
						</div>';
                        } ?>
					</div>
                    <?php if (!empty($data['segment'])) { ?>
						<div class="cell grid-x acym__campaign__summary__section margin-top-1">
							<h5 class="cell shrink margin-next-1 acym__title acym__title__secondary"><?php
                                if (!empty($data['campaignInformation']->sending_params['segment']['invert'])) {
                                    $segmentInformation = $data['campaignInformation']->sending_params['segment']['invert'] === 'exclude'
                                        ? ' ('.acym_translation('ACYM_EXCLUDE').')'
                                        : ' ('.acym_translation('ACYM_INCLUDE').')';
                                } else {
                                    $segmentInformation = ' ('.acym_translation('ACYM_INCLUDE').')';
                                }
                                echo acym_translation('ACYM_SEGMENT').$segmentInformation;
                                ?></h5>
							<div class="cell auto acym__campaign__summary__modify">
								<a href="<?php echo acym_completeLink(
                                    $campaignController.'&task=edit&step=segment&edition=1&campaignId='.intval($data['campaignInformation']->id)
                                ); ?>"><i class="acymicon-pencil"></i><span> <?php echo acym_translation('ACYM_EDIT'); ?></span></a>
							</div>
							<div class="cell grid-x acym__campaign__summary__segment">
								<span class="cell medium-6"><b><?php echo $data['segment']['name']; ?></b></span>
								<span class="cell medium-6"><b><?php echo $data['segment']['count'].'</b> '.strtolower(acym_translation('ACYM_SUBSCRIBERS')); ?></span>
							</div>
						</div>
                    <?php } ?>
				</div>
                <?php if (!empty($data['campaignInformation']->sending_params['abtest'])) {
                    $numberOfUsersToSend = round($data['campaignInformation']->sending_params['abtest']['repartition'] * $data['nbSubscribersAbTest'] / 100);
                    ?>
					<div class="cell grid-x acym__campaign__summary__section">
						<h5 class="cell shrink margin-next-1 acym__title acym__title__secondary">
							<b><?php
                                echo acym_translation('ACYM_AB_TEST'); ?></b>
						</h5>
						<div class="cell auto acym__campaign__summary__modify">
							<a href="<?php echo acym_completeLink(
                                $campaignController.'&task=edit&step=sendSettings&edition=1&campaignId='.intval($data['campaignInformation']->id)
                            ); ?>"><i class="acymicon-pencil"></i><span> <?php echo acym_translation('ACYM_EDIT'); ?></span></a>
						</div>
						<div class="cell acym__listing">
							<div class="cell acym__listing__row">
                                <?php echo acym_translationSprintf(
                                    'ACYM_VERSION_WILL_BE_SENT_TO_X_SUBSCRIBERS',
                                    acym_translationSprintf('ACYM_VERSION_NAME', 'A'),
                                    round($numberOfUsersToSend / 2)
                                ); ?>
							</div>
							<div class="cell acym__listing__row">
                                <?php echo acym_translationSprintf(
                                    'ACYM_VERSION_WILL_BE_SENT_TO_X_SUBSCRIBERS',
                                    acym_translationSprintf('ACYM_VERSION_NAME', 'B'),
                                    $numberOfUsersToSend - round($numberOfUsersToSend / 2)
                                ); ?>
							</div>
						</div>
                        <?php if (!empty($data['segment'])) { ?>
							<div class="cell grid-x acym__campaign__summary__section margin-top-1">
								<h5 class="cell shrink margin-next-1 acym__title acym__title__secondary"><?php
                                    if (!empty($data['campaignInformation']->sending_params['segment']['invert'])) {
                                        $segmentInformation = $data['campaignInformation']->sending_params['segment']['invert'] === 'exclude'
                                            ? ' ('.acym_translation('ACYM_EXCLUDE').')'
                                            : ' ('.acym_translation('ACYM_INCLUDE').')';
                                    } else {
                                        $segmentInformation = ' ('.acym_translation('ACYM_INCLUDE').')';
                                    }
                                    echo acym_translation('ACYM_SEGMENT').$segmentInformation;
                                    ?></h5>
								<div class="cell auto acym__campaign__summary__modify">
									<a href="<?php echo acym_completeLink(
                                        $campaignController.'&task=edit&step=segment&edition=1&campaignId='.intval($data['campaignInformation']->id)
                                    ); ?>"><i class="acymicon-pencil"></i><span> <?php echo acym_translation('ACYM_EDIT'); ?></span></a>
								</div>
								<div class="cell grid-x acym__campaign__summary__segment">
									<span class="cell medium-6"><b><?php echo $data['segment']['name']; ?></b></span>
									<span class="cell medium-6"><b><?php echo $data['segment']['count'].'</b> '.strtolower(acym_translation('ACYM_SUBSCRIBERS')); ?></span>
								</div>
							</div>
                        <?php } ?>
					</div>
                <?php } ?>
				<div class="cell grid-x acym__campaign__summary__section">
					<h5 class="cell shrink margin-next-1 acym__title acym__title__secondary">
						<b><?php echo acym_translation('ACYM_SEND_SETTINGS'); ?></b>
					</h5>
					<div class="cell auto acym__campaign__summary__modify">
						<a href="<?php echo acym_completeLink(
                            $campaignController.'&task=edit&step=sendSettings&edition=1&campaignId='.intval($data['campaignInformation']->id)
                        ); ?>"><i
									class="acymicon-pencil"></i><span> <?php echo acym_translation('ACYM_EDIT'); ?></span></a>
					</div>
					<div class="cell grid-x grid-margin-x">
						<p class="cell large-2 medium-3"><b><?php echo acym_translation('ACYM_SENDING_TYPE'); ?>:</b></p>
						<p class="cell auto">
                            <?php
                            $sendingTimeText = '';

                            if ($data['automatic']['isAuto']) {
                                $sendingTimeText = $data['automatic']['text'];
                            } elseif ($data['campaignClass']::SENDING_TYPE_SCHEDULED === $data['campaignInformation']->sending_type) {
                                if (empty($data['campaignInformation']->sending_date)) {
                                    $sendingTimeText = acym_translation('ACYM_PLEASE_SET_SEND_DATE');
                                } else {
                                    if ($isSent) {
                                        $text = 'ACYM_THIS_CAMPAIGN_HAS_BEEN_SENT_ON_AT';
                                    } else {
                                        $text = 'ACYM_THIS_CAMPAIGN_WILL_BE_SENT_ON_AT';
                                    }

                                    $sendingTimeText .= acym_translationSprintf(
                                        $text,
                                        acym_date($data['campaignInformation']->sending_date, 'F j, Y'),
                                        acym_date($data['campaignInformation']->sending_date, 'H:i')
                                    );
                                }
                            } elseif (!$isSent) {
                                $sendingTimeText .= acym_translation('ACYM_THIS_CAMPAIGN_WILL_BE_SENT').' '.acym_strtolower(acym_translation('ACYM_NOW'));
                            } else {
                                $sendingTimeText .= acym_translationSprintf(
                                    'ACYM_THIS_CAMPAIGN_HAS_BEEN_SENT_ON_AT',
                                    acym_date($data['campaignInformation']->sending_date, 'F j, Y'),
                                    acym_date($data['campaignInformation']->sending_date, 'H:i')
                                );
                            }

                            echo $sendingTimeText;
                            ?>
						</p>
					</div>
                    <?php if ($data['automatic']['isAuto'] && !empty($data['automatic']['startDate'])) { ?>
						<div class="cell grid-x grid-margin-x">
							<p class="cell large-2 medium-3"><b><?php echo acym_translation('ACYM_START_DATE'); ?>:</b></p>
							<p class="cell auto"><?php echo acym_date($data['automatic']['startDate'], acym_getDateTimeFormat()); ?></p>
						</div>
                    <?php } ?>
					<div class="cell grid-x grid-margin-x">
						<p class="cell large-2 medium-3"><b><?php echo acym_translation('ACYM_TRACKING'); ?>:</b></p>
						<p class="cell auto"><?php echo acym_translation(
                                $data['campaignInformation']->tracking ? 'ACYM_THIS_CAMPAIGN_BEING_TRACKED' : 'ACYM_THIS_CAMPAIGN_NOT_BEING_TRACKED'
                            ); ?></p>
					</div>
				</div>
				<div class="cell grid-x acym__campaign__summary__bottom-controls acym__campaign__summary__section">
                    <?php
                    if (!$isAbTest && !empty($data['campaignInformation']->sent) && !empty($data['campaignInformation']->active)) {
                        ?>
						<div id="acym__campaign__summary__resendoptions" class="cell padding-1 margin-bottom-1 acym__zone__warning">
                            <?php
                            echo acym_translationSprintf('ACYM_ALREADY_SENT');
                            echo acym_radio(
                                [
                                    'new' => acym_translation('ACYM_YES'),
                                    'all' => acym_translation('ACYM_ALREADY_SENT_ALL'),
                                ],
                                'resend_target',
                                null,
                                [],
                                ['required' => true],
                                !acym_isAdmin()
                            );
                            ?>
							<div class="cell">
								<span id="resend_receivers_new" style="display: none;">
									<?php echo acym_translationSprintf('ACYM_X_RECIPIENTS', $data['receiversNew']); ?>
								</span>
								<span id="resend_receivers_all" style="display: none;">
									<?php echo acym_translationSprintf('ACYM_X_RECIPIENTS', $data['nbSubscribers']); ?>
								</span>
							</div>
						</div>
                        <?php
                    }
                    ?>
					<div class="cell medium-shrink medium-margin-bottom-0 margin-bottom-1 text-left">
                        <?php
                        echo acym_backToListing(
                            in_array($data['campaignInformation']->sending_type, ['birthday', 'woocommerce_cart'])
                                ? $campaignController.'&task=specificListing&type='.$data['campaignInformation']->sending_type : $campaignController
                        );
                        ?>
					</div>
					<div class="cell medium-auto grid-x text-right">
						<div class="cell auto hide-for-small-only"></div>

                        <?php
                        // The campaign is in the queue and has been paused
                        if (empty($data['campaignInformation']->active) && !$data['campaignInformation']->isAuto && $data['campaignInformation']->sent) {
                            ?>

							<button type="button" class="cell button primary acy_button_submit medium-shrink" data-task="unpause_campaign">
                                <?php echo acym_translation('ACYM_UNPAUSE_CAMPAIGN'); ?>
							</button>

                            <?php
                        } elseif ($data['campaignInformation']->active && $data['campaignInformation']->isAuto) {
                            ?>

							<button type="button" class="cell button primary acy_button_submit medium-shrink" data-task="toggleActivateColumnCampaign">
                                <?php echo acym_translation('ACYM_DEACTIVATE_CAMPAIGN'); ?>
							</button>
                            <?php
                        } else {
                            // The campaign isn't already sent
                            if (empty($data['campaignInformation']->sent) && (empty($data['campaignInformation']->active) && $data['campaignInformation']->draft)) {
                                ?>

								<button type="submit"
										class="cell button button-secondary margin-bottom-1 margin-next-1 acy_button_submit medium-shrink"
										data-task="saveAsDraftCampaign">
                                    <?php echo acym_translation('ACYM_SAVE_AS_DRAFT'); ?>
								</button>

                                <?php
                            }

                            if ($data['campaignClass']::SENDING_TYPE_NOW == $data['campaignInformation']->sending_type) {
                                $task = 'addQueue';
                                $buttonText = 'ACYM_SEND_CAMPAIGN';
                                if (!acym_level(ACYM_ESSENTIAL) || $this->config->get('cron_last', 0) < (time() - 43200)) $buttonText = 'ACYM_ADD_TO_QUEUE';
                            } elseif (!$data['campaignInformation']->isAuto) {
                                $task = 'confirmCampaign';
                                $buttonText = 'ACYM_CONFIRM_CAMPAIGN';
                            } else {
                                $task = 'activeAutoCampaign';
                                $buttonText = 'ACYM_ACTIVE_CAMPAIGN';
                            }


                            if ($isAbTest && empty($data['campaignInformation']->sending_params['abtest']['B'])) {
                                $data['notAllowedSendingError'] = acym_translation('ACYM_VERSION_MISSING_AB_TEST');
                            }

                            if (isset($numberOfUsersToSend) && $numberOfUsersToSend < 2) {
                                $data['notAllowedSendingError'] = acym_translation('ACYM_NOT_ENOUGH_USERS_AB_TEST');
                            }

                            if ($isAbTest && $isSent) {
                                $data['notAllowedSendingError'] = acym_translation('ACYM_CANNOT_SEND_AB_TEST_AGAIN');
                            }

                            if ($data['nbSubscribers'] <= 0) {
                                $data['notAllowedSendingError'] = acym_translation('ACYM_ADD_RECIPIENTS_TO_SEND_THIS_CAMPAIGN');
                            }

                            $buttonClass = empty($data['notAllowedSendingError']) ? '' : ' disabled';

                            if ($this->config->get('mailer_method') === 'acymailer' && $this->config->get('acymailer_popup', '0') === '0') {
                                ob_start();
                                require acym_getView('mails', 'acymailer_popup', true);
                                $popupData = ob_get_clean();
                                $button = acym_modal(
                                    acym_translation($buttonText),
                                    $popupData,
                                    'acym__acymailer__popup',
                                    [],
                                    ['class' => 'cell button medium-shrink'.$buttonClass]
                                );
                            } else {
                                $button = '<button type="button" class="cell button acy_button_submit medium-shrink'.$buttonClass.'" data-task="'.acym_escape($task).'">';
                                $button .= acym_translation($buttonText);
                                $button .= '</button>';
                            }

                            if (!empty($data['notAllowedSendingError'])) {
                                echo acym_tooltip(
                                    [
                                        'hoveredText' => $button,
                                        'textShownInTooltip' => $data['notAllowedSendingError'],
                                        'classContainer' => 'cell medium-shrink',
                                    ]
                                );
                            } else {
                                echo $button;
                            }
                        }
                        ?>
					</div>
				</div>
			</div>
		</div>
		<div class="cell medium-auto"></div>
	</div>
	<input type="hidden" value="<?php echo intval($data['campaignInformation']->id); ?>" name="campaignId" />
	<input type="hidden" value="<?php echo acym_escape($data['campaignInformation']->sending_date); ?>" name="sending_date" />
    <?php acym_formOptions(true, 'edit', 'summary'); ?>
</form>
