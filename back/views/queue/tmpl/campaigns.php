<form id="acym_form" action="<?php echo acym_completeLink(acym_getVar('cmd', 'ctrl')); ?>" method="post" name="acyForm" data-abide novalidate>
    <?php
    $isEmpty = empty($data['allElements']) && empty($data['search']) && empty($data['tag']) && empty($data['status']);
    if (!$isEmpty) {
        $data['toolbar']->displayToolbar($data);
    }
    ?>
	<div id="acym__queue" class="acym__content">
        <?php
        $workflow = $data['workflowHelper'];
        echo $workflow->displayTabs($this->steps, 'campaigns');
        ?>

        <?php if ($isEmpty) { ?>
			<div class="grid-x text-center">
				<h1 class="acym__listing__empty__title cell"><?php echo acym_translation('ACYM_YOU_DONT_HAVE_ANY_CAMPAIGN_IN_QUEUE'); ?></h1>
				<h1 class="acym__listing__empty__subtitle cell"><?php echo acym_translation('ACYM_SEND_ONE_AND_SEE_HOW_AMAZING_QUEUE_IS'); ?></h1>
			</div>
        <?php } else { ?>
            <?php if (empty($data['allElements'])) { ?>
				<h1 class="cell acym__listing__empty__search__title text-center"><?php echo acym_translation('ACYM_NO_RESULTS_FOUND'); ?></h1>
            <?php } else { ?>
				<div class="grid-x">
					<div class="auto cell">
                        <?php
                        $sendingText = $this->config->get('cron_last', 0) < (time() - 43200) ? 'ACYM_QUEUE_READY' : 'ACYM_SENDING';

                        $options = [
                            '' => ['ACYM_ALL', $data['numberPerStatus']['all']],
                            'sending' => [$sendingText, $data['numberPerStatus']['sending']],
                            'paused' => ['ACYM_PAUSED', $data['numberPerStatus']['paused']],
                            'scheduled' => ['ACYM_SCHEDULED', $data['numberPerStatus']['scheduled']],
                            'automation' => ['ACYM_AUTOMATION', $data['numberPerStatus']['automation']],
                            'followup' => ['ACYM_FOLLOW_UP', $data['numberPerStatus']['followup']],
                        ];
                        echo acym_filterStatus($options, $data['status'], 'cqueue_status');
                        ?>
					</div>
				</div>
				<div class="grid-x acym__listing acym__listing__view__cqueue">
					<div class="cell grid-x acym__listing__header">
						<div class="acym__listing__header__title cell medium-auto hide-for-small-only">
                            <?php echo acym_translation('ACYM_MAILS'); ?>
						</div>
						<div class="acym__listing__header__title cell large-3 hide-for-medium-only hide-for-small-only text-center">
                            <?php echo acym_translation('ACYM_RECIPIENTS'); ?>
						</div>
						<div class="acym__listing__header__title cell medium-4 hide-for-small-only text-center">
                            <?php echo acym_translation('ACYM_STATUS'); ?>
						</div>
						<div class="cell medium-2 hide-for-small-only"></div>
					</div>
                    <?php foreach ($data['allElements'] as $row) {
                        ?>
						<div data-acy-elementid="<?php echo acym_escape($row->id); ?>" class="cell grid-x acym__listing__row">
							<div class="cell medium-auto acym_vcenter">
								<div class="acym__listing__title">
                                    <?php
                                    $afterName = $row->language;
                                    if (!empty($row->sending_params['abtest'])) {
                                        $afterName = $row->subject;
                                    } elseif (!empty($data['languages'][$row->language])) {
                                        $afterName = $data['languages'][$row->language]->name;
                                    }
                                    $afterName = empty($afterName) ? '' : ' - '.$afterName
                                    ?>
									<h6 class="acym__listing__title__primary acym_text_ellipsis"><?php echo $row->name.$afterName; ?></h6>
									<p class="acym__listing__title__secondary">
                                        <?php echo acym_date($row->sending_date, acym_getDateTimeFormat()); ?>
									</p>
								</div>
							</div>
							<div class="cell large-3 hide-for-medium-only hide-for-small-only text-center">
								<div class="queue_lists">
                                    <?php
                                    if (!$row->iscampaign) {
                                        echo $row->lists;
                                    } else {
                                        $i = 0;
                                        $class = 'acym_subscription acymicon-circle';
                                        foreach ($row->lists as $oneList) {
                                            if ($i == 6) {
                                                echo acym_tooltip(
                                                    '<i data-campaign="'.$row->id.'" class="acym_subscription acymicon-add"></i>',
                                                    acym_translation('ACYM_SHOW_ALL_LISTS')
                                                );
                                                $class .= ' is-hidden';
                                            }
                                            echo acym_tooltip('<i class="'.$class.'" style="color:'.$oneList->color.'"></i>', $oneList->name);
                                            $i++;
                                        }
                                    }
                                    ?>
								</div>
                                <?php
                                if (!empty($row->recipients)) {
                                    echo acym_translationSprintf('ACYM_X_RECIPIENTS', '<strong>'.number_format($row->recipients, 0, '.', ' ').'</strong>');
                                }
                                ?>
							</div>
							<div class="cell medium-4 small-9">
								<div class="acym_vcenter grid-x text-center">
                                    <?php
                                    // First determine the type (scheduled / paused / sending)
                                    if ($row->active == 0 && $row->iscampaign) {
                                        $text = acym_translation('ACYM_PAUSED');
                                        $class = 'acym_status_paused';
                                    } elseif (!$row->iscampaign || $row->sending_type === $data['campaignClass']::SENDING_TYPE_SCHEDULED && empty($row->nbqueued)) {
                                        $text = acym_translation('ACYM_SCHEDULED');
                                        $class = 'acym_status_scheduled';
                                    } else {
                                        if ($this->config->get('cron_last', 0) < (time() - 43200)) {
                                            $text = acym_translation('ACYM_QUEUE_READY');
                                            $class = 'acym_status_ready';
                                        } else {
                                            $text = acym_translation('ACYM_QUEUE_SENDING');
                                            $class = 'acym_status_sending';
                                        }
                                    }
                                    ?>

									<div class="cell">
										<div class="progress_bar <?php echo $class; ?>">
                                            <?php
                                            $percentageSent = 0;
                                            if (!empty($row->nbqueued) && $row->iscampaign) {
                                                if (!empty($row->recipients)) {
                                                    $percentageSent = 100 - ceil($row->nbqueued * 100 / $row->recipients);
                                                }
                                                echo '<div class="progress_bar_left" style="width: '.$percentageSent.'%;"></div>';
                                            }
                                            ?>
											<div class="progress_bar_text grid-x">
												<span class="cell auto acym_text_ellipsis"><?php echo $text; ?></span>
                                                <?php
                                                if (!empty($row->nbqueued) && $row->iscampaign) {
                                                    echo '<span class="cell" style="width: 40px;">'.$percentageSent.'%</span>';
                                                }
                                                ?>
											</div>
										</div>
									</div>

                                    <?php if (!empty($row->nbqueued) && $row->active == 1) { ?>
										<div class="cell acym_sendnow">

                                            <?php
                                            $sendID = 'send_campaign_'.$row->id;
                                            echo acym_modal(
                                                '<i class="acymicon-paper-plane" data-acy-elementid="'.$row->id.'"></i> '.acym_translation('ACYM_SEND_NOW'),
                                                '',
                                                null,
                                                'data-reveal-larger',
                                                'data-reload="true" data-ajax="true" data-iframe="&ctrl=queue&task=continuesend&id='.$row->id.'&totalsend='.$row->nbqueued.'"'
                                            );
                                            ?>
										</div>
                                    <?php } ?>
								</div>
							</div>
							<div class="cell medium-2 small-3">
								<div class="acym_vcenter">
                                    <?php

                                    // Now display the action buttons
                                    echo '<div class="acym_action_buttons">';

                                    $cancelText = 'ACYM_CANCEL_SCHEDULING';
                                    // Play/pause button
                                    if (!empty($row->nbqueued) && $row->iscampaign) {
                                        $class = $row->active == 0 ? 'acymicon-play_circle_filled' : 'acymicon-pause-circle';
                                        echo '<i campaignid="'.$row->campaign.'" class="'.$class.' acym__queue__play_pause__button"></i>';
                                        $cancelText = 'ACYM_CANCEL_CAMPAIGN';
                                    }

                                    // Delete button
                                    $deleteID = 'cancel_campaign_'.$row->id;
                                    echo acym_tooltip('<i class="acymicon-times-circle acym__queue__cancel__button" mailid="'.$row->id.'"></i>', acym_translation($cancelText));
                                    echo '</div>';
                                    ?>
								</div>
							</div>
						</div>
                    <?php } ?>
				</div>
                <?php echo $data['pagination']->display('cqueue'); ?>
            <?php } ?>
        <?php } ?>
        <?php acym_formOptions(); ?>
		<input type="hidden" name="acym__queue__cancel__mail_id">
		<input type="hidden" name="acym__queue__play_pause__campaign_id">
		<input type="hidden" name="acym__queue__play_pause__active__new_value">
	</div>

</form>
