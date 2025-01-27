<div class="acym__content acym_area padding-vertical-1 padding-horizontal-2">
	<div class="acym__title acym__title__secondary"><?php echo acym_translation('ACYM_CONFIGURATION_QUEUE'); ?></div>
	<div class="grid-x grid-margin-x margin-y">
		<div class="cell medium-3"><?php echo acym_translation('ACYM_CONFIGURATION_QUEUE_PROCESSING'); ?></div>
		<div class="cell medium-9">
            <?php
            $disabledOptions = [];
            if (!acym_level(ACYM_ESSENTIAL)) {
                $disabledOptions = [
                    'auto' => ['tooltipTxt' => acym_translation('ACYM_PRO_ONLY'), 'disabledClass' => 'acym__disabled'],
                    'automan' => ['tooltipTxt' => acym_translation('ACYM_PRO_ONLY'), 'disabledClass' => 'acym__disabled'],
                ];
            }
            echo acym_radio(
                [
                    'auto' => acym_translation('ACYM_CONFIGURATION_QUEUE_AUTOMATIC'),
                    'automan' => acym_translation('ACYM_CONFIGURATION_QUEUE_AUTOMAN'),
                    'manual' => acym_translation('ACYM_CONFIGURATION_QUEUE_MANUAL'),
                ],
                'config[queue_type]',
                $this->config->get('queue_type', 'automan'),
                [
                    'related' => [
                        'auto' => 'automatic_only',
                        'automan' => 'automatic_manual',
                        'manual' => 'manual_only',
                    ],
                ],
                [],
                false,
                $disabledOptions
            );
            ?>
		</div>
		<div class="cell medium-3 automatic_only automatic_manual">
            <?php
            echo acym_translation('ACYM_AUTO_SEND_PROCESS');
            echo acym_info('ACYM_AUTO_SEND_PROCESS_DESC');
            ?>
		</div>
		<div class="cell medium-9 grid-x automatic_only automatic_manual">
			<div class="cell large-9 grid-x margin-y">
				<div class="cell">
                    <?php
                    $cronFrequency = $this->config->get('cron_frequency');
                    $valueBatch = acym_level(ACYM_ENTERPRISE) ? $this->config->get('queue_batch_auto', 1) : 1;
                    if (!function_exists('curl_multi_exec') && (intval($cronFrequency) < 900 || intval($valueBatch) > 1)) {
                        acym_display(acym_translation('ACYM_MULTI_CURL_DISABLED'), 'error', false);
                    } elseif (empty($cronFrequency) && !empty($this->config->get('active_cron', 0))) {
                        acym_display(acym_translation('ACYM_EMPTY_FREQUENCY'), 'error', false);
                    }

                    $disabledBatch = acym_level(ACYM_ENTERPRISE) ? '' : 'disabled';
                    $delayTypeAuto = $data['typeDelay'];
                    $delayHtml = '<span data-acym-tooltip="'.acym_translation('ACYM_CRON_TRIGGERED_DESC').'">'.$delayTypeAuto->display(
                            'config[cron_frequency]',
                            $cronFrequency,
                            2,
                            '',
                            '',
                            'auto_sending_input'
                        ).'</span>';
                    echo acym_translationSprintf(
                        'ACYM_SEND_X_BATCH_OF_X_EMAILS_EVERY_Y',
                        '<input '.$disabledBatch.' class="intext_input auto_sending_input" type="number" min="1" name="config[queue_batch_auto]" value="'.$valueBatch.'" />',
                        '<input class="intext_input auto_sending_input" type="number" min="1" name="config[queue_nbmail_auto]" value="'.intval(
                            $this->config->get('queue_nbmail_auto')
                        ).'" />',
                        $delayHtml
                    );
                    echo '<span id="automatic_sending_speed_too_many_batches">';
                    echo acym_info('ACYM_TOO_MANY_BATCHES', '', '', '', true);
                    echo '</span>';
                    ?>
				</div>
				<div class="cell">
                    <?php
                    echo acym_translationSprintf(
                        'ACYM_WAIT_X_SECONDS_BETWEEN_MAILS',
                        '<input class="intext_input auto_sending_input" type="number" min="0" name="config[email_frequency]" value="'.$this->config->get(
                            'email_frequency',
                            0
                        ).'" />'
                    );
                    ?>
				</div>
			</div>
		</div>
		<div class="cell medium-3 automatic_only automatic_manual"></div>
		<div class="cell medium-9 automatic_only automatic_manual margin-bottom-2">
            <?php echo acym_translationSprintf('ACYM_SEND_X_EMAILS_PER_HOUR', '<span id="automatic_sending_speed_preview">280</span>'); ?>
			<span id="automatic_sending_speed_too_much">
				<?php echo acym_info(acym_translation('ACYM_ONE_SECOND_PER_EMAIL_WARNING')); ?>
			</span>
			<span id="automatic_sending_speed_no_wait">
				<?php echo acym_info(acym_translation('ACYM_SEND_FASTER_DECREASE_WAIT')); ?>
			</span>
		</div>
		<div class="cell medium-3 automatic_only automatic_manual"><?php echo acym_translation('ACYM_SEND_RESTRICTIONS'); ?></div>
		<div class="cell medium-9 grid-x margin-y automatic_only automatic_manual acym_auto_send_time">
			<div class="cell">
                <?php
                $hoursFrom = acym_select(
                    $data['listHours'],
                    'config[queue_send_from_hour]',
                    $this->config->get('queue_send_from_hour', '00'),
                    ['class' => 'intext_select']
                );
                $minutesFrom = acym_select(
                    $data['listAllMinutes'],
                    'config[queue_send_from_minute]',
                    $this->config->get('queue_send_from_minute', '00'),
                    ['class' => 'intext_select']
                );
                $hoursTo = acym_select(
                    $data['listHours'],
                    'config[queue_send_to_hour]',
                    $this->config->get('queue_send_to_hour', '23'),
                    ['class' => 'intext_select']
                );
                $minutesTo = acym_select(
                    $data['listAllMinutes'],
                    'config[queue_send_to_minute]',
                    $this->config->get('queue_send_to_minute', '59'),
                    ['class' => 'intext_select']
                );
                echo acym_translationSprintf('ACYM_SEND_FROM_TO', $hoursFrom, $minutesFrom, $hoursTo, $minutesTo);
                ?>
			</div>
			<div class="cell grid-x">
				<div class="cell shrink margin-right-1">
                    <?php echo acym_translation('ACYM_DONT_SEND_WEEKEND'); ?>
				</div>
				<div class="cell shrink">
                    <?php echo acym_switch('config[queue_stop_weekend]', $this->config->get('queue_stop_weekend', 0)); ?>
				</div>
				<div class="cell auto"></div>
			</div>
		</div>
		<div class="cell medium-3 manual_only automatic_manual"><?php echo acym_translation('ACYM_MANUAL_SEND_PROCESS'); ?></div>
		<div class="cell medium-9 manual_only automatic_manual">
            <?php
            $delayTypeAuto = $data['typeDelay'];
            echo acym_translationSprintf(
                'ACYM_SEND_X_EMAILS_WAIT_Y_SECONDS',
                '<input class="intext_input" type="number" min="1" name="config[queue_nbmail]" value="'.intval($this->config->get('queue_nbmail')).'" />',
                '<input class="intext_input" type="number" min="0" name="config[queue_pause]" value="'.intval($this->config->get('queue_pause')).'" />'
            );
            ?>
		</div>
		<div class="cell medium-3"><?php echo '<span>'.acym_translation('ACYM_MAX_NB_TRY').'</span>'.acym_info('ACYM_MAX_NB_TRY_DESC'); ?></div>
		<div class="cell medium-9">
            <?php echo acym_translationSprintf(
                'ACYM_CONFIG_TRY',
                '<input class="intext_input" type="number" min="0" name="config[queue_try]" value="'.intval($this->config->get('queue_try')).'">'
            );

            $failaction = $data['failaction'];
            echo ' '.acym_translationSprintf('ACYM_CONFIG_TRY_ACTION', $failaction->display('maxtry', $this->config->get('bounce_action_maxtry'))); ?>
		</div>
		<div class="cell medium-3"><?php echo acym_translation('ACYM_NUMBER_OF_DAYS_TO_CLEAN_QUEUE').acym_info('ACYM_NUMBER_OF_DAYS_TO_CLEAN_QUEUE_DESC'); ?></div>
		<div class="cell medium-9 grid-x">
			<div class="cell medium-6 large-4 xlarge-3 xxlarge-2">
                <?php
                $queueDelete = $this->config->get('queue_delete_days', 0);
                if (!acym_level(ACYM_ESSENTIAL)) {
                    $inputContent = '<input type="number" class="intext_input" disabled min="0" name="config[queue_delete_days]" value="'.$queueDelete.'">';
                    $inputContent = acym_tooltip(
                        [
                            'hoveredText' => $inputContent,
                            'textShownInTooltip' => acym_translation('ACYM_PRO_ONLY'),
                        ]
                    );
                } else {
                    $inputContent = '<input type="number" class="intext_input" min="0" name="config[queue_delete_days]" value="'.$queueDelete.'">';
                }
                echo $inputContent;
                ?>
			</div>
		</div>
        <?php if (acym_level(ACYM_ENTERPRISE)) { ?>
			<div class="cell medium-3"><?php echo acym_translation('ACYM_SET_FOLLOWUP_PRIORITY').acym_info('ACYM_SET_FOLLOWUP_PRIORITY_DESC'); ?></div>
			<div class="cell medium-9 grid-x">
				<div class="cell medium-6 large-4 xlarge-3 xxlarge-2">
                    <?php echo acym_switch('config[followup_max_priority]', $this->config->get('followup_max_priority', 0)); ?>
				</div>
			</div>
        <?php } ?>
	</div>
</div>
<?php
if (!acym_level(ACYM_ESSENTIAL)) {
    echo '<div class="acym_area">
            <div class="acym__title acym__title__secondary">'.acym_translation('ACYM_CRON').'</div>';
    include acym_getView('configuration', 'upgrade_license');
    echo '</div>';
}
