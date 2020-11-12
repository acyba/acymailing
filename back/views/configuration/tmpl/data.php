<div class="acym__content acym_area padding-vertical-1 padding-horizontal-2 margin-bottom-2">
	<div class="acym_area_title"><?php echo acym_translation('ACYM_CONFIDENTIALITY'); ?></div>
	<div class="grid-x grid-margin-x">
        <?php echo acym_switch('config[gdpr_export]', $this->config->get('gdpr_export'), acym_translation('ACYM_GDPR_EXPORT_BUTTON'), [], 'xlarge-3 medium-5 small-9', "auto", "tiny", 'export_config'); ?>
	</div>
</div>

<div class="acym__content acym_area padding-vertical-1 padding-horizontal-2">
	<div class="acym_area_title"><?php echo acym_translation('ACYM_TRACKING'); ?></div>

	<div class="grid-x margin-bottom-1">
		<label class="cell xlarge-3 small-5" for="from_as_replyto">
            <?php echo acym_translation('ACYM_TRACKINGSYSTEM'); ?>
		</label>

		<div class="cell auto">
            <?php $trackingMode = $this->config->get('trackingsystem', 'acymailing'); ?>

			<input
					type="checkbox"
					name="config[trackingsystem][]"
					id="trackingsystem[0]"
					value="acymailing"
                <?php echo stripos($trackingMode, 'acymailing') !== false ? 'checked="checked"' : ''; ?>
			/>
			<label for="trackingsystem[0]">AcyMailing</label>

			<input
					type="checkbox"
					name="config[trackingsystem][]"
					id="trackingsystem[1]"
					value="google"
                <?php echo stripos($trackingMode, 'google') !== false ? 'checked="checked"' : ''; ?>
			/>
			<label for="trackingsystem[1]">Google Analytics</label>

			<input type="hidden" name="config[trackingsystem][]" value="1" />
		</div>
	</div>

	<div class="grid-x grid-margin-x">
        <?php
        echo acym_switch(
            'config[trackingsystemexternalwebsite]',
            $this->config->get('trackingsystemexternalwebsite'),
            acym_translation('ACYM_TRACKINGSYSTEM_EXTERNAL_LINKS'),
            [],
            'xlarge-3 medium-5 small-9',
            "auto",
            "tiny",
            'external_config'
        );
        ?>
	</div>
</div>
