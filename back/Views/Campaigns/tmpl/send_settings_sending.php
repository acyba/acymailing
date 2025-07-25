<h5 class="cell margin-top-1 acym__title acym__title__secondary">
    <?php echo acym_translation('ACYM_WHEN_EMAIL_WILL_BE_SENT').acym_info('ACYM_PRESELECT_DESC'); ?>
</h5>
<div class="cell grid-x align-center">
	<div class="cell grid-x medium-11 acym__campaign__sendsettings__send-type grid-margin-x">
        <?php
        if (empty($data['currentCampaign']->send_specific)) {
            if (!empty($data['currentCampaign']->sent) && empty($data['currentCampaign']->active)) { ?>
				<div class="acym__hide__div"></div>
				<h3 class="acym__title__primary__color acym__middle_absolute__text text-center"><?php echo acym_translation('ACYM_CAMPAIGN_ALREADY_QUEUED'); ?></h3>
            <?php } ?>
			<div class="cell grid-x grid-margin-x margin-bottom-1">
				<div class="cell auto grid-x align-center">
                    <?php
                    $class = $data['currentCampaign']->send_now ? 'button-radio-selected' : 'button-radio-unselected';
                    $class .= $data['currentCampaign']->draft ? '' : ' disabled';
                    ?>
					<button type="button"
							class="cell medium-9 small-12 button-radio acym__campaign__sendsettings__buttons-type <?php echo $class; ?>"
							acym-button-radio-group="sendingType"
							id="acym__campaign__sendsettings__now"
							data-sending-type="<?php echo $data['campaignClass']::SENDING_TYPE_NOW; ?>"><?php echo acym_translation('ACYM_NOW'); ?></button>
				</div>
                <?php
                $tooltip = acym_level(ACYM_ESSENTIAL) ? '' : 'data-acym-tooltip="'.acym_translationSprintf('ACYM_USE_THIS_FEATURE', 'AcyMailing Essential').'"';
                $class = $data['currentCampaign']->send_scheduled ? 'button-radio-selected' : 'button-radio-unselected';
                $class .= !acym_level(ACYM_ESSENTIAL) || !$data['currentCampaign']->draft ? ' disabled' : '';
                ?>
				<div class="cell auto grid-x align-center">
					<button type="button" <?php echo $tooltip; ?>
							class="cell medium-9 small-12 button-radio acym__campaign__sendsettings__buttons-type <?php echo $class; ?>"
							acym-button-radio-group="sendingType"
							id="acym__campaign__sendsettings__scheduled"
							data-sending-type="<?php echo $data['campaignClass']::SENDING_TYPE_SCHEDULED; ?>"><?php echo acym_translation('ACYM_SCHEDULED'); ?></button>
				</div>
                <?php
                $tooltip = acym_level(ACYM_ENTERPRISE) ? '' : 'data-acym-tooltip="'.acym_translationSprintf('ACYM_USE_THIS_FEATURE', 'AcyMailing Enterprise').'"';
                $class = $data['currentCampaign']->send_auto ? 'button-radio-selected' : 'button-radio-unselected';
                $class .= !acym_level(ACYM_ENTERPRISE) || !$data['currentCampaign']->draft || isset($data['currentCampaign']->sending_params['abtest']) ? ' disabled' : '';
                ?>
				<div class="cell auto grid-x align-center">
					<button type="button" <?php echo $tooltip; ?>
							class="cell medium-9 small-12 button-radio acym__campaign__sendsettings__buttons-type <?php echo $class; ?>"
							acym-button-radio-group="sendingType"
							id="acym__campaign__sendsettings__auto"
							data-sending-type="<?php echo $data['campaignClass']::SENDING_TYPE_AUTO; ?>"><?php echo acym_translation('ACYM_AUTO'); ?></button>
				</div>
			</div>
        <?php } else { ?>
			<div class="cell grid-x margin-bottom-1">
                <?php echo $data['currentCampaign']->send_specific[0]['whenSettings']; ?>
			</div>
        <?php } ?>
	</div>
</div>

<h5 class="cell margin-top-1 margin-bottom-1 acym__title acym__title__secondary">
    <?php echo acym_translation('ACYM_ADDITIONAL_SETTINGS'); ?>
</h5>
<div class="cell grid-x margin-left-1">
	<div class="cell medium-11 grid-margin-x grid-x acym__campaign__sendsettings__params margin-left-3"
		 data-show="acym__campaign__sendsettings__now" <?php echo $data['currentCampaign']->send_now ? '' : 'style="display: none"'; ?>>
		<label><?php echo acym_translation('ACYM_SENT_AS_SOON_CAMPAIGN_SAVE'); ?></label>
	</div>
	<div class="cell grid-x acym__campaign__sendsettings__params margin-left-3"
		 data-show="acym__campaign__sendsettings__scheduled" <?php echo $data['currentCampaign']->send_scheduled ? '' : 'style="display: none"'; ?>>
		<div class="cell grid-x acym__campaign__sendsettings__display-send-type-scheduled">
			<label id="acym__campaign__sendsettings__scheduled__send-date__label" class="cell shrink">
                <?php echo acym_translation('ACYM_CAMPAIGN_WILL_BE_SENT'); ?>
			</label>
			<label class="cell shrink" for="acym__campaign__sendsettings__send">
                <?php
                $value = empty($data['currentCampaign']->sending_date) ? '' : acym_date($data['currentCampaign']->sending_date, 'Y-m-d H:i');
                $required = $data['currentCampaign']->send_scheduled ? 'required="required"' : '';
                echo acym_tooltip(
                    [
                        'hoveredText' => '<input 
							class="text-center acy_date_picker" 
							data-acym-translate="0" 
							type="text" 
							name="sendingDate" 
							id="acym__campaign__sendsettings__send-type-scheduled__date" 
							value="'.acym_escape($value).'" 
							readonly
							'.$required.'>',
                        'textShownInTooltip' => acym_translation('ACYM_CLICK_TO_EDIT'),
                    ]
                );
                ?>
			</label>
		</div>
	</div>
	<div class="cell grid-x align-center margin-left-3 acym__campaign__sendsettings__params"
		 data-show="acym__campaign__sendsettings__auto" <?php echo $data['currentCampaign']->send_auto ? '' : 'style="display: none"'; ?>>
		<div class="cell grid-x align-center">
			<div class="cell grid-x acym_vcenter">
				<label class="cell"><?php echo acym_translation('ACYM_THIS_WILL_GENERATE_CAMPAIGN_AUTOMATICALLY'); ?></label>
				<div class="cell grid-x margin-y">
					<div class="cell medium-5 small-10">
                        <?php
                        echo acym_select(
                            $data['triggers_select'],
                            'acym_triggers',
                            empty($data['currentCampaign']->sending_params['trigger_type']) ? null : $data['currentCampaign']->sending_params['trigger_type'],
                            ['class' => 'acym__select']
                        );
                        ?>
					</div>
					<div class="cell medium-5 hide-for-small-only"></div>
					<div class="cell grid-x medium-5 small-10">
                        <?php
                        foreach ($data['triggers_display'] as $key => $display) {
                            echo '<div class="acym__campaign__sendsettings__params__one cell grid-x" data-trigger-show="'.$key.'" style="display: none">';
                            echo str_replace('[triggers][classic]['.$key.']', $key, $display);
                            echo '</div>';
                        }
                        ?>
					</div>
				</div>
			</div>
			<div class="cell grid-x margin-top-1">
				<label class="cell">
                    <?php
                    echo acym_translation('ACYM_START_DATE');
                    echo acym_info('ACYM_START_DATE_AUTO_CAMPAIGN_DESC');
                    ?>
				</label>
				<div class="cell medium-5 small-10">
                    <?php
                    $startDateValue = '';
                    if (!empty($data['currentCampaign']->sending_params['start_date'])) {
                        $startDateValue = acym_date($data['currentCampaign']->sending_params['start_date'], 'Y-m-d H:i');
                    }
                    ?>
					<input type="text"
						   name="start_date"
						   class="acy_date_picker"
						   data-acym-translate="0"
						   readonly
						   value="<?php echo acym_escape($startDateValue); ?>">
				</div>
			</div>
			<div class="cell grid-x margin-top-1">
                <?php
                echo acym_switch(
                    'need_confirm',
                    $data['currentCampaign']->sending_params['need_confirm_to_send'] ?? 1,
                    acym_translation('ACYM_CONFIRM_AUTOCAMPAIGN'),
                    [],
                    'shrink',
                    'shrink',
                    '',
                    'acym__campaign__sendsettings__notification'
                );
                ?>
			</div>
			<div class="cell grid-x margin-top-1" id="acym__campaign__sendsettings__notification">
				<label class="cell">
                    <?php
                    echo acym_translation('ACYM_NOTIFICATION_AUTO_CAMPAIGN_GENERATED');
                    ?>
				</label>
				<div class="cell medium-5 small-104">
                    <?php
                    if (empty($data['currentCampaign']->sending_params['admin_notification_emails'])) {
                        $selectedValues = [acym_currentUserEmail() => acym_currentUserEmail()];
                    } else {
                        $selectedValues = [];
                        foreach ($data['currentCampaign']->sending_params['admin_notification_emails'] as $value) {
                            if (acym_isValidEmail($value)) {
                                $selectedValues[$value] = $value;
                            }
                        }
                    }

                    echo acym_selectMultiple(
                        $selectedValues,
                        'sending_params[admin_notification_emails]',
                        $selectedValues,
                        [
                            'class' => 'acym__multiselect__email',
                        ]
                    );
                    ?>
				</div>
			</div>
		</div>
	</div>
    <?php if (!empty($data['langChoice'])) { ?>
		<div class="cell grid-x margin-top-1 margin-left-3">
			<label class="cell">
                <?php
                echo acym_translation('ACYM_EMAIL_LANGUAGE');
                echo acym_info('ACYM_EMAIL_LANGUAGE_DESC');
                ?>
			</label>
			<div class="cell medium-5 small-10">
                <?php echo $data['langChoice']; ?>
			</div>
		</div>
    <?php }
    if (!empty($data['currentCampaign']->send_specific) && !empty($data['currentCampaign']->send_specific[0]['additionnalSettings'])) {
        echo $data['currentCampaign']->send_specific[0]['additionnalSettings'];
    } ?>
	<div class="cell grid-x medium-10 large-7 xlarge-5 margin-left-3 margin-top-1">
        <?php
        $label = acym_translation('ACYM_TRACK_THIS_CAMPAIGN');
        $label .= acym_info('ACYM_TRACK_THIS_CAMPAIGN_DESC');
        $isTracked = $data['currentCampaign']->tracking ?? 1;
        echo acym_switch(
            'senderInformation[tracking]',
            $isTracked,
            $label
        ); ?>
	</div>
    <?php
    $trackingMode = $this->config->get('trackingsystem', 'acymailing');
    if (stripos($trackingMode, 'google') !== false) {
        ?>
		<div class="cell grid-x align-center" id="utm_settings" <?php echo empty($isTracked) ? 'style="display:none;"' : ''; ?>>
			<h6 class="cell acym__title acym__title__secondary acym__title__secondary__utm">
                <?php echo acym_translation('ACYM_CUSTOM_UTM'); ?>
			</h6>
			<div class="cell grid-x medium-11 grid-margin-x margin-y">
				<div class="cell medium-5">
					<label for="utm_campaign">utm_campaign</label>
					<input name="sending_params[utm_campaign]" id="utm_campaign"
						   type="text"
						   maxlength="100"
						   class="cell"
						   value="<?php echo empty($data['currentCampaign']->sending_params['utm_campaign'])
                               ? ''
                               : acym_escape(
                                   $data['currentCampaign']->sending_params['utm_campaign']
                               ); ?>"
						   placeholder="<?php echo acym_escape($data['currentCampaign']->subject); ?>">
				</div>
				<div class="cell medium-1"></div>
				<div class="cell medium-5">
					<label for="utm_source">utm_source</label>
					<input name="sending_params[utm_source]" id="utm_source"
						   type="text"
						   value="<?php echo empty($data['currentCampaign']->sending_params['utm_source'])
                               ? ''
                               : acym_escape(
                                   $data['currentCampaign']->sending_params['utm_source']
                               ); ?>"
						   placeholder="newsletter_<?php echo $data['currentCampaign']->id; ?>">
				</div>
				<div class="cell medium-5">
					<label for="utm_medium">utm_medium</label>
					<input name="sending_params[utm_medium]" id="utm_medium"
						   type="text"
						   value="<?php echo empty($data['currentCampaign']->sending_params['utm_medium'])
                               ? ''
                               : acym_escape(
                                   $data['currentCampaign']->sending_params['utm_medium']
                               ); ?>"
						   placeholder="email">
				</div>
			</div>
		</div>
    <?php } ?>
    <?php if ($this->config->get('mailer_method') === 'postmark') { ?>
		<div class="cell grid-x">
			<div class="cell grid-x medium-10 large-7 xlarge-5 margin-left-3 margin-top-1">
				<label class="cell medium-6">
                    <?php echo acym_translation('ACYM_POSTMARK_STREAM_ID') ?>
					<input type="text"
						   class="cell auto"
						   name="sending_params[message_stream_id]"
						   value="<?php echo empty($data['currentCampaign']->sending_params['message_stream_id']) ? ''
                               : $data['currentCampaign']->sending_params['message_stream_id']; ?>">
				</label>
			</div>
		</div>
    <?php } ?>
</div>
