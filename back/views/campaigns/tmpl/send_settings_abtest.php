<?php $abTestParams = empty($data['currentCampaign']->sending_params['abtest']) ? [] : $data['currentCampaign']->sending_params['abtest']; ?>
<h5 class="cell margin-top-1 acym__title acym__title__secondary">
    <?php echo acym_translation('ACYM_AB_TEST_REPARTITION').acym_info('ACYM_AB_TEST_REPARTITION_DESC'); ?>
</h5>
<div class="cell grid-x align-center">
	<div class="cell grid-x medium-11 acym__campaign__sendsettings__send-type grid-margin-x margin-top-1">
		<span class="cell shrink"><?php echo acym_translation('ACYM_AB_TEST_CAMPAIGN_SENT_TO') ?></span>
		<span id="acym__campaign__sendsettings__abtest__number-subscribers" class="cell shrink" data-acym-subscribers="<?php echo acym_escape($data['nbSubscribers']); ?>"></span>
	</div>
	<div class="cell grid-x medium-11 acym__campaign__sendsettings__send-type grid-margin-x margin-top-1">
		<span class="cell small-1 acym_vcenter align-center">0%</span>
		<div class="slider cell small-10 margin-top-1 margin-bottom-1"
			 id="acym__campaign__sendsettings__send__abtest-slider"
			 data-slider
			 data-initial-start="<?php echo empty($abTestParams['repartition']) ? 15 : acym_escape($abTestParams['repartition']) ?>"
			 data-end="50">
			<span class="slider-handle" data-slider-handle role="slider" tabindex="1">
				<span class="slider-value">15%</span>
			</span>
			<span class="slider-fill" data-slider-fill></span>
			<input type="hidden" name="sending_params[abtest][repartition]" id="acym__campaign__sendsettings__send__abtest-value">
		</div>
		<span class="cell small-1 acym_vcenter align-center">50%</span>
	</div>
	<div class="cell grid-x medium-11 acym__campaign__sendsettings__send-type grid-margin-x margin-top-1 margin-bottom-1">
		<div class="cell">
            <?php
            $days = empty($abTestParams['after']['days']) ? 2 : acym_escape($abTestParams['after']['days']);
            ?>
			<p class="cell margin-bottom-1">
                <?php echo acym_translationSprintf(
                    'ACYM_AFTER_X_DAYS',
                    '<input name="sending_params[abtest][after][days]" type="number" min="1" class="intext_input" value="'.$days.'">'
                ); ?>
			</p>
            <?php
            $afterActions = [
                'open_rate' => acym_translation('ACYM_GENERATE_AND_SEND_VERSION_BEST_OPEN_RATE'),
                'click_rate' => acym_translation('ACYM_GENERATE_AND_SEND_VERSION_BEST_CLICK_RATE'),
                'click_open_rate' => acym_translation('ACYM_GENERATE_AND_SEND_VERSION_BEST_CLICK_OPEN_RATE'),
            ];
            echo acym_radio(
                $afterActions,
                'sending_params[abtest][after][action]',
                empty($abTestParams['after']['action']) ? 'open_rate' : $abTestParams['after']['action'],
                [],
                ['pluginMode' => true]
            );
            ?>
		</div>
	</div>
</div>
