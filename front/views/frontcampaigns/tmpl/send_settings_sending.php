<h5 class="cell margin-top-1 acym__title acym__title__secondary"><?php echo acym_translation('ACYM_WHEN_EMAIL_WILL_BE_SENT'); ?></h5>
<div class="cell grid-x align-center margin-top-1">
	<div class="cell grid-x medium-11 acym__campaign__sendsettings__send-type grid-margin-x">
        <?php if (!empty($data['currentCampaign']->sent) && empty($data['currentCampaign']->active)) { ?>
			<div class="acym__hide__div"></div>
			<h3 class="acym__title__primary__color acym__middle_absolute__text text-center"><?php echo acym_translation('ACYM_CAMPAIGN_ALREADY_QUEUED'); ?></h3>
        <?php } ?>
		<div class="cell grid-x grid-margin-x margin-bottom-2">
			<div class="cell auto grid-x align-center">
                <?php
                $class = $data['currentCampaign']->send_now ? 'button-radio-selected' : 'button-radio-unselected';
                $class .= $data['currentCampaign']->draft ? '' : ' disabled';
                ?>
				<button type="button"
						class="cell medium-6 small-12 button-radio acym__campaign__sendsettings__buttons-type <?php echo $class; ?>"
						acym-button-radio-group="sendingType"
						id="acym__campaign__sendsettings__now"
						data-sending-type="<?php echo $data['campaignClass']::SENDING_TYPE_NOW; ?>"><?php echo acym_translation('ACYM_NOW'); ?></button>
			</div>
            <?php
            $tooltip = acym_level(ACYM_ESSENTIAL) ? '' : 'data-acym-tooltip="'.acym_translationSprintf('ACYM_USE_THIS_FEATURE', 'AcyMailing Essential').'"';
            $class = $data['currentCampaign']->send_scheduled ? 'button-radio-selected' : 'button-radio-unselected';
            $class .= $data['currentCampaign']->draft ? '' : ' disabled';
            ?>
			<div class="cell auto grid-x align-center">
				<button type="button" <?php echo $tooltip; ?>
						class="cell medium-6 small-12 button-radio acym__campaign__sendsettings__buttons-type <?php echo $class; ?>"
						acym-button-radio-group="sendingType"
						id="acym__campaign__sendsettings__scheduled"
						data-sending-type="<?php echo $data['campaignClass']::SENDING_TYPE_SCHEDULED; ?>"><?php echo acym_translation('ACYM_SCHEDULED'); ?></button>
			</div>
		</div>
	</div>
</div>

<h5 class="cell margin-top-1 margin-bottom-1 acym__title acym__title__secondary"><?php echo acym_translation('ACYM_ADDITIONAL_SETTINGS'); ?></h5>
<div class="cell grid-x margin-top-1 margin-y">
	<div class="cell medium-11 grid-margin-x grid-x acym__campaign__sendsettings__params"
		 data-show="acym__campaign__sendsettings__now" <?php echo $data['currentCampaign']->send_now ? '' : 'style="display: none"'; ?>>
		<p class="cell"><?php echo acym_translation('ACYM_SENT_AS_SOON_CAMPAIGN_SAVE'); ?></p>
	</div>
	<div class="cell grid-x acym__campaign__sendsettings__params"
		 data-show="acym__campaign__sendsettings__scheduled" <?php echo $data['currentCampaign']->send_scheduled ? '' : 'style="display: none"'; ?>>
		<div class="cell grid-x acym__campaign__sendsettings__display-send-type-scheduled">
			<p id="acym__campaign__sendsettings__scheduled__send-date__label" class="cell shrink"><?php echo acym_translation('ACYM_CAMPAIGN_WILL_BE_SENT'); ?></p>
			<label class="cell shrink" for="acym__campaign__sendsettings__send">
                <?php
                $value = empty($data['currentCampaign']->sending_date) ? '' : acym_date($data['currentCampaign']->sending_date, 'Y-m-d H:i');
                $required = $data['currentCampaign']->send_scheduled ? 'required="required"' : '';
                echo acym_tooltip(
                    '<input 
                    	class="text-center acy_date_picker" 
                    	data-acym-translate="0" 
                    	type="text" 
                    	name="sendingDate" 
                    	id="acym__campaign__sendsettings__send-type-scheduled__date" 
                    	value="'.acym_escape($value).'" 
                    	readonly
                    	'.$required.'>',
                    acym_translation('ACYM_CLICK_TO_EDIT')
                );
                ?>
			</label>
		</div>
	</div>
    <?php if (!empty($data['langChoice'])) { ?>
		<div class="cell grid-x">
			<label class="cell medium-7 large-4">
                <?php
                echo acym_translation('ACYM_EMAIL_LANGUAGE');
                echo acym_info('ACYM_EMAIL_LANGUAGE_DESC');
                ?>
			</label>
			<div class="cell medium-5 large-3">
                <?php echo $data['langChoice']; ?>
			</div>
		</div>
    <?php } ?>
	<div class="cell grid-x medium-10 large-7 xlarge-5">
        <?php
        $label = acym_translation('ACYM_TRACK_THIS_CAMPAIGN');
        $label .= acym_info('ACYM_TRACK_THIS_CAMPAIGN_DESC');
        echo acym_switch(
            'senderInformation[tracking]',
            isset($data['currentCampaign']->tracking) ? $data['currentCampaign']->tracking : 1,
            $label,
            []
        ); ?>
	</div>
</div>
