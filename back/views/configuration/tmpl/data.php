<div class="acym__content acym_area padding-vertical-1 padding-horizontal-2 margin-bottom-2">
	<div class="acym__title acym__title__secondary"><?php echo acym_translation('ACYM_CONFIDENTIALITY'); ?></div>
	<div class="grid-x grid-margin-x margin-y">
        <?php echo acym_switch(
            'config[gdpr_export]',
            $this->config->get('gdpr_export'),
            acym_translation('ACYM_GDPR_EXPORT_BUTTON'),
            [],
            'xlarge-3 medium-5 small-9',
            'auto',
            '',
            'export_config'
        ); ?>
	</div>
</div>

<div class="acym__content acym_area padding-horizontal-2">
	<div class="acym__title acym__title__secondary"><?php echo acym_translation('ACYM_TRACKING'); ?></div>

	<div class="grid-x margin-y">
		<label class="cell xlarge-3 small-5" for="from_as_replyto">
            <?php echo acym_translation('ACYM_TRACKINGSYSTEM'); ?>
		</label>

		<div class="cell xlarge-9 small-7 acym_vcenter">
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

		<div class="cell grid-x grid-margin-x">
            <?php
            echo acym_switch(
                'config[trackingsystemexternalwebsite]',
                $this->config->get('trackingsystemexternalwebsite'),
                acym_translation('ACYM_TRACKINGSYSTEM_EXTERNAL_LINKS'),
                [],
                'xlarge-3 medium-5 small-9',
                'auto',
                '',
                'external_config'
            );
            ?>
		</div>
	</div>
</div>
<div class="acym__content acym_area padding-vertical-1 padding-horizontal-2 margin-bottom-2 grid-margin-y">
	<div class="acym__title acym__title__secondary"><?php echo acym_translation('ACYM_DATA_MANAGEMENT'); ?></div>
	<div class="grid-x grid-margin-x margin-y">
        <?php
        echo acym_switch(
            'config[delete_stats_enabled]',
            $this->config->get('delete_stats_enabled', 0),
            acym_translation('ACYM_DELETE_DETAILED_STATS_AFTER'),
            [],
            'xlarge-3 medium-5 small-9',
            'auto',
            '',
            'delete_stats_enabled'
        ); ?>
		<div class="cell grid-x" id="delete_stats_enabled">
            <?php
            $delayTypeAuto = $data['typeDelay'];
            echo $delayTypeAuto->display('config[delete_stats]', $this->config->get('delete_stats', 31104000), 4);
            ?>
		</div>
	</div>
	<div class="grid-x grid-margin-x margin-y margin-top-1">
        <?php
        echo acym_switch(
            'config[delete_user_history_enabled]',
            $this->config->get('delete_user_history_enabled', 0),
            acym_translation('ACYM_DELETE_USER_HISTORY_AFTER'),
            [],
            'xlarge-3 medium-5 small-9',
            'auto',
            '',
            'delete_user_history_enabled'
        ); ?>
		<div class="cell grid-x" id="delete_user_history_enabled">
            <?php
            $delayTypeAuto = $data['typeDelay'];
            echo $delayTypeAuto->display('config[delete_user_history]', $this->config->get('delete_user_history', 0), 4);
            ?>
		</div>
	</div>
</div>
