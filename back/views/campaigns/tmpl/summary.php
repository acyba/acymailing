<?php
$isSent = !empty($data['campaignInformation']->sent) && !empty($data['campaignInformation']->active);
$campaignController = acym_isAdmin() ? 'campaigns' : 'frontcampaigns';
?>
<form id="acym_form" action="<?php echo acym_completeLink(acym_getVar('cmd', 'ctrl')); ?>" method="post" name="acyForm" class="acym__form__campaign__edit" data-abide novalidate>
	<div class="grid-x">
		<div class="cell medium-auto"></div>
		<div class="cell <?php echo $data['containerClass']; ?> acym__content">

            <?php
            $workflow = acym_get('helper.workflow');
            echo $workflow->display($this->steps, $this->step);
            ?>

			<div id="acym__campaign__summary" class="grid-x grid-margin-y">
				<div class="cell grid-x acym__campaign__summary__section margin-right-2">
					<h5 class="cell shrink margin-right-2">
						<b><?php echo acym_translation('ACYM_EMAIL'); ?></b>
					</h5>
					<div class="cell auto acym__campaign__summary__modify">
						<a href="<?php echo acym_completeLink($campaignController.'&task=edit&step=editEmail&edition=1&id='.intval($data['campaignInformation']->id)); ?>"><i class="acymicon-pencil"></i><span> <?php echo acym_translation('ACYM_EDIT'); ?></span></a>
					</div>
					<div class="cell grid-x">
						<p class="cell medium-6 acym__campaign__summary__email__information">
                            <?php echo acym_translation('ACYM_FROM_NAME'); ?>: <span class="acym__color__blue"><?php echo acym_escape($data['mailInformation']->from_name); ?></span>
						</p>
						<p class="cell medium-6 acym__campaign__summary__email__information">
                            <?php echo acym_translation('ACYM_FROM_EMAIL'); ?>: <span class="acym__color__blue"><?php echo acym_escape($data['mailInformation']->from_email); ?></span>
						</p>
						<p class="cell medium-6 acym__campaign__summary__email__information">
                            <?php echo acym_translation('ACYM_REPLYTO_NAME'); ?>: <span class="acym__color__blue"><?php echo acym_escape($data['mailInformation']->reply_to_name); ?></span>
						</p>
						<p class="cell medium-6 acym__campaign__summary__email__information">
                            <?php echo acym_translation('ACYM_REPLYTO_EMAIL'); ?>: <span class="acym__color__blue"><?php echo acym_escape($data['mailInformation']->reply_to_email); ?></span>
						</p>
						<p class="cell medium-6 margin-top-1 acym__campaign__summary__email__information">
                            <?php echo acym_translation('ACYM_EMAIL_SUBJECT'); ?>: <span class="acym__color__blue acym__campaign__summary__email__information-subject"><?php echo acym_escape($data['mailInformation']->subject); ?></span>
						</p>
					</div>
					<!-- We add the email content in a hidden div to load it into the iframe preview -->
                    <?php
                    if ($data['multilingual']) {
                        include acym_getView('campaigns', 'summary_languages', true);
                    }
                    ?>
					<input type="hidden" class="acym__hidden__mail__content" value="<?php echo acym_escape(acym_absoluteURL($data['mailInformation']->body)); ?>">
					<div style="display: none" class="acym__hidden__mail__stylesheet"><?php echo $data['mailInformation']->stylesheet; ?></div>
					<div class="cell grid-x">
						<div id="acym__wysid__email__preview" class="acym__email__preview grid-x cell"></div>
					</div>
				</div>
                <?php if (!empty($data['mailInformation']->attachments)) { ?>
					<div class="cell grid-x acym__campaign__summary__section">
						<h5 class="cell shrink margin-right-2">
							<b><?php echo acym_translation('ACYM_ATTACHMENTS'); ?></b>
						</h5>
						<div class="cell auto acym__campaign__summary__modify">
							<a href="<?php echo acym_completeLink($campaignController.'&task=edit&step=editEmail&edition=1&id='.intval($data['campaignInformation']->id)); ?>"><i class="acymicon-pencil"></i><span> <?php echo acym_translation('ACYM_EDIT'); ?></span></a>
						</div>
                        <?php foreach (json_decode($data['mailInformation']->attachments) as $key => $oneAttachment) {
                            $onlyFilename = explode("/", $oneAttachment->filename);

                            $onlyFilename = end($onlyFilename);

                            if (strlen($onlyFilename) > 40) {
                                $onlyFilename = substr($onlyFilename, 0, 15)."...".substr($onlyFilename, strlen($onlyFilename) - 15);
                            }
                            echo acym_tooltip('<div class="acym__row__no-listing cell" data-toggle="path_attachment_'.$key.'">'.$onlyFilename.'</div>', $oneAttachment->filename, 'cell');
                        } ?>
					</div>
                <?php } ?>
				<div class="cell grid-x acym__campaign__summary__section">
					<h5 class="cell shrink margin-right-2">
						<b><?php echo acym_translation('ACYM_RECIPIENTS'); ?></b>
					</h5>
					<div class="cell auto acym__campaign__summary__modify">
						<a href="<?php echo acym_completeLink($campaignController.'&task=edit&step=recipients&edition=1&id='.intval($data['campaignInformation']->id)); ?>"><i class="acymicon-pencil"></i><span> <?php echo acym_translation('ACYM_EDIT'); ?></span></a>
					</div>
                    <?php foreach ($data['listsReceiver'] as $oneList) {
                        echo '<div class="cell grid-x acym__row__no-listing">
							<span class="cell medium-6"><i class="acymicon-circle acym__campaign__summary__recipients__list__color" style="color: '.$oneList->color.'"></i> <b>'.$oneList->name.'</b></span> <span class="cell medium-6"><b>'.$oneList->subscribers.'</b> '.strtolower(acym_translation('ACYM_SUBSCRIBERS')).'</span>
						</div>';
                    } ?>
					<p class="cell">
                        <?php
                        echo acym_translation_sprintf(
                            $isSent ? 'ACYM_CAMPAIGN_HAS_BEEN_SENT_TO_A_TOTAL_OF' : 'ACYM_CAMPAIGN_WILL_BE_SENT_TO_A_TOTAL_OF',
                            acym_tooltip('<b>'.$data['nbSubscribers'].'</b>', acym_translation('ACYM_SUMMARY_NUMBER_RECEIVERS_EXPLICATION'))
                        );
                        ?>
					</p>
				</div>
				<div class="cell grid-x acym__campaign__summary__section">
					<h5 class="cell shrink margin-right-2">
						<b><?php echo acym_translation('ACYM_SEND_SETTINGS'); ?></b>
					</h5>
					<div class="cell auto acym__campaign__summary__modify">
						<a href="<?php echo acym_completeLink($campaignController.'&task=edit&step=sendSettings&edition=1&id='.intval($data['campaignInformation']->id)); ?>"><i class="acymicon-pencil"></i><span> <?php echo acym_translation('ACYM_EDIT'); ?></span></a>
					</div>
					<div class="cell grid-x grid-margin-x">
						<p class="cell large-2 medium-3"><b><?php echo acym_translation('ACYM_SENDING_TYPE'); ?>:</b></p>
						<p class="cell auto">
                            <?php
                            $sendingTimeText = '';

                            if ($data['automatic']['isAuto']) {
                                $sendingTimeText = $data['automatic']['text'];
                            } elseif (!$isSent && $data['campaignClass']::SENDING_TYPE_SCHEDULED !== $data['campaignInformation']->sending_type) {
                                $sendingTimeText .= acym_translation('ACYM_THIS_CAMPAIGN_WILL_BE_SENT').' '.strtolower(acym_translation('ACYM_NOW'));
                            } else {
                                if ($isSent) {
                                    $text = 'ACYM_THIS_CAMPAIGN_HAS_BEEN_SENT_ON_AT';
                                } else {
                                    $text = 'ACYM_THIS_CAMPAIGN_WILL_BE_SENT_ON_AT';
                                }

                                $sendingTimeText .= acym_translation_sprintf(
                                    $text,
                                    acym_date($data['campaignInformation']->sending_date, 'F j, Y'),
                                    acym_date($data['campaignInformation']->sending_date, 'H:i')
                                );
                            }

                            echo $sendingTimeText;
                            ?>
						</p>
					</div>
					<div class="cell grid-x grid-margin-x">
						<p class="cell large-2 medium-3"><b><?php echo acym_translation('ACYM_TRACKING'); ?>:</b></p>
						<p class="cell auto"><?php echo acym_translation($data['campaignInformation']->tracking ? 'ACYM_THIS_CAMPAIGN_BEING_TRACKED' : 'ACYM_THIS_CAMPAIGN_NOT_BEING_TRACKED') ?></p>
					</div>
				</div>
				<div class="cell grid-x acym__campaign__summary__bottom-controls acym__campaign__summary__section">
                    <?php
                    if (!empty($data['campaignInformation']->sent) && !empty($data['campaignInformation']->active)) {
                        ?>
						<div id="acym__campaign__summary__resendoptions" class="cell padding-1 margin-bottom-1 acym__zone__warning">
                            <?php
                            echo acym_translation_sprintf('ACYM_ALREADY_SENT');
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
						</div>
                        <?php
                    }
                    ?>
					<div class="cell medium-shrink medium-margin-bottom-0 margin-bottom-1 text-left">
                        <?php echo acym_backToListing($campaignController); ?>
					</div>
					<div class="cell medium-auto grid-x text-right">
						<div class="cell auto hide-for-small-only"></div>

                        <?php
                        // The campaign is in the queue and has been paused
                        if (empty($data['campaignInformation']->active) && !$data['campaignInformation']->isAuto && $data['campaignInformation']->sent) {
                            ?>

							<button type="button" class="cell button primary margin-bottom-0 acy_button_submit medium-shrink" data-task="unpause_campaign">
                                <?php echo acym_translation('ACYM_UNPAUSE_CAMPAIGN'); ?>
							</button>

                            <?php
                        } elseif ($data['campaignInformation']->active && $data['campaignInformation']->isAuto) {
                            ?>

							<button type="button" class="cell button primary margin-bottom-0 acy_button_submit medium-shrink" data-task="toggleActivateColumnCampaign">
                                <?php echo acym_translation('ACYM_DEACTIVATE_CAMPAIGN'); ?>
							</button>
                            <?php
                        } else {
                            // The campaign isn't already sent
                            if (empty($data['campaignInformation']->sent) && (empty($data['campaignInformation']->active) && $data['campaignInformation']->draft)) {
                                ?>

								<button type="submit" class="cell button button-secondary medium-margin-bottom-0 margin-right-1 acy_button_submit medium-shrink" data-task="saveAsDraftCampaign">
                                    <?php echo acym_translation('ACYM_SAVE_AS_DRAFT'); ?>
								</button>

                                <?php
                            }

                            if ($data['campaignClass']::SENDING_TYPE_NOW == $data['campaignInformation']->sending_type) {
                                $task = 'addQueue';
                                $buttonText = 'ACYM_SEND_CAMPAIGN';
                                if (!acym_level(1) || $this->config->get('cron_last', 0) < (time() - 43200)) $buttonText = 'ACYM_ADD_TO_QUEUE';
                            } elseif (!$data['campaignInformation']->isAuto) {
                                $task = 'confirmCampaign';
                                $buttonText = 'ACYM_CONFIRM_CAMPAIGN';
                            } else {
                                $task = 'activeAutoCampaign';
                                $buttonText = 'ACYM_ACTIVE_CAMPAIGN';
                            }

                            $buttonClass = '';
                            if ($data['nbSubscribers'] <= 0) $buttonClass = ' disabled-button';

                            $button = '<button type="button" class="cell button primary margin-bottom-0 acy_button_submit medium-shrink'.$buttonClass.'" data-task="'.acym_escape($task).'">';
                            $button .= acym_translation($buttonText);
                            $button .= '</button>';

                            if ($data['nbSubscribers'] > 0) {
                                echo $button;
                            } else {
                                echo acym_tooltip($button, acym_translation('ACYM_ADD_RECIPIENTS_TO_SEND_THIS_CAMPAIGN'));
                            }
                        }
                        ?>
					</div>
				</div>
			</div>
		</div>
		<div class="cell medium-auto"></div>
	</div>
	<input type="hidden" value="<?php echo intval($data['campaignInformation']->id); ?>" name="id" />
	<input type="hidden" value="<?php echo acym_escape($data['campaignInformation']->sending_date); ?>" name="sending_date" />
    <?php acym_formOptions(true, 'edit', 'summary'); ?>
</form>
