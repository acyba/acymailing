<?php //__START__enterprise_
if (acym_level(2)) {
    ?>
	<div class="acym_area padding-vertical-1 padding-horizontal-2">
		<div class="acym_area_title"><?php echo acym_translation('ACYM_BOUNCE_HANDLING'); ?></div>

		<div class="grid-x">
			<label class="cell grid-x">
				<span class="cell medium-3"><?php echo acym_translation('ACYM_SMTP_SERVER'); ?></span>
				<input class="cell medium-4" type="text" name="config[bounce_server]" value="<?php echo acym_escape($this->config->get('bounce_server')); ?>">
			</label>
			<label class="cell grid-x">
				<span class="cell medium-3"><?php echo acym_translation('ACYM_SMTP_PORT'); ?></span>
				<input class="cell medium-2" type="text" name="config[bounce_port]" value="<?php echo acym_escape($this->config->get('bounce_port')); ?>">
			</label>
			<label class="cell grid-x">
				<span class="cell medium-3"><?php echo acym_translation('ACYM_CONNECTION_METHOD'); ?></span>
				<div class="cell medium-2">
                    <?php
                    $connectionMethods = [
                        "" => "---",
                        'imap' => 'IMAP',
                        'pop3' => 'POP3',
                        'pear' => 'POP3 ('.acym_translation('ACYM_WITHOUT_IMAP_EXT').')',
                        //'nntp' => 'NNTP',
                    ];

                    echo acym_select(
                        $connectionMethods,
                        'config[bounce_connection]',
                        $this->config->get('bounce_connection', 'imap'),
                        [
                            'class' => 'acym__select',
                            'acym-data-infinite' => '',
                        ],
                        '',
                        '',
                        'acym__config__bounce__connection'
                    );
                    ?>
				</div>
			</label>
			<label class="cell grid-x">
				<span class="cell medium-3"><?php echo acym_translation('ACYM_SMTP_SECURE'); ?></span>
				<div class="cell medium-2">
                    <?php
                    $secureMethods = [
                        "" => "---",
                        "ssl" => "SSL",
                        "tls" => "TLS",
                    ];

                    echo acym_select(
                        $secureMethods,
                        'config[bounce_secured]',
                        $this->config->get('bounce_secured', 'ssl'),
                        [
                            'class' => 'acym__select',
                            'acym-data-infinite' => '',
                        ],
                        "",
                        "",
                        'acym__config__bounce__secure_method'
                    );
                    ?>
				</div>
			</label>
			<div class="cell grid-x">
                <?php echo acym_switch('config[bounce_certif]', $this->config->get('bounce_certif', 1), acym_translation('ACYM_SELF_SIGNED_CERTIFICATE'), [], 'medium-3'); ?>
			</div>
			<label class="cell grid-x">
				<span class="cell medium-3"><?php echo acym_translation('ACYM_SMTP_USERNAME'); ?></span>
				<input class="cell medium-4" type="text" name="config[bounce_username]" value="<?php echo acym_escape($this->config->get('bounce_username')); ?>">
			</label>
			<label class="cell grid-x">
				<span class="cell medium-3"><?php echo acym_translation('ACYM_SMTP_PASSWORD'); ?></span>
				<input class="cell medium-4" type="text" name="config[bounce_password]" value="<?php echo str_repeat('*', strlen($this->config->get('bounce_password'))); ?>">
			</label>
			<label class="cell grid-x">
				<span class="cell medium-3"><?php echo acym_translation('ACYM_CONNECTION_TIMEOUT_SECOND'); ?></span>
				<input class="cell medium-2" type="text" name="config[bounce_timeout]" value="<?php echo acym_escape($this->config->get('bounce_timeout', 10)); ?>">
			</label>
			<label class="cell grid-x">
				<span class="cell medium-3"><?php echo acym_translation('ACYM_MAX_NUMBER_EMAILS'); ?></span>
				<input class="cell medium-2" type="text" name="config[bounce_max]" value="<?php echo acym_escape($this->config->get('bounce_max', 100)); ?>">
			</label>
			<div class="cell grid-x">
                <?php echo acym_switch('config[auto_bounce]', $this->config->get('auto_bounce'), acym_translation('ACYM_ENABLE_AUTO_BOUNCE'), [], 'medium-3'); ?>
			</div>
			<div class="cell grid-x grid-margin-x" id="acym__configuration__bounce__auto_bounce__configuration" <?php echo $this->config->get('auto_bounce') ? '' : "style='display: none'"; ?>>
				<div class="cell grid-x">
					<label class="cell medium-3" for="delayvalue3"><?php echo acym_translation('ACYM_FREQUENCY'); ?></label>
					<div class="cell medium-9">
                        <?php $delayTypeBounceAuto = $data['typeDelay'];
                        echo $delayTypeBounceAuto->display('config[auto_bounce_frequency]', $this->config->get('auto_bounce_frequency', 21600), 1);
                        ?>
					</div>
					<span class="cell medium-3"><?php echo acym_translation('ACYM_LAST_RUN'); ?></span>
					<span class="cell medium-9"><?php echo acym_date($this->config->get('auto_bounce_last'), 'Y-m-d H:i'); ?></span>
					<span class="cell medium-3"><?php echo acym_translation('ACYM_NEXT_RUN_TIME'); ?></span>
					<span class="cell medium-9"><?php echo acym_date($this->config->get('auto_bounce_next'), 'Y-m-d H:i'); ?></span>
					<span class="cell medium-3"><?php echo acym_translation('ACYM_REPORT'); ?></span>
					<span class="cell medium-9"><?php echo $this->config->get('auto_bounce_report'); ?></span>
				</div>
			</div>
		</div>
	</div>
    <?php
}
//__END__enterprise_
if (!acym_level(2)) {
    $data['version'] = 'enterprise';
    echo '<div class="acym_area">
            <div class="acym_area_title">'.acym_translation('ACYM_BOUNCE_HANDLING').'</div>';
    include acym_getView('dashboard', 'upgrade');
    echo '</div>';
} ?>
