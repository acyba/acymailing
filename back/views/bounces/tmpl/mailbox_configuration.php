<div class="acym__content cell grid-x large-6 margin-bottom-1 margin-y">
	<span class="acym__content__title__light-blue"><?php echo acym_translation('ACYM_CONFIGURATION'); ?></span>
	<label class="cell grid-x">
		<span class="cell medium-4"><?php echo acym_translation('ACYM_SMTP_SERVER'); ?></span>
		<input class="cell medium-6" type="text" name="mailbox[server]" value="<?php echo acym_escape($data['mailboxActions']->server); ?>">
	</label>
	<label class="cell grid-x">
		<span class="cell medium-4"><?php echo acym_translation('ACYM_SMTP_USERNAME'); ?></span>
		<input class="cell medium-6" type="text" name="mailbox[username]" value="<?php echo acym_escape($data['mailboxActions']->username); ?>">
	</label>
	<label class="cell grid-x">
		<span class="cell medium-4"><?php echo acym_translation('ACYM_SMTP_PASSWORD'); ?></span>
		<input class="cell medium-6" type="text" name="mailbox[password]" value="<?php echo str_repeat('*', strlen($data['mailboxActions']->password)); ?>">
	</label>
	<div class="cell grid-x">
		<label class="cell medium-4"><?php echo acym_translation('ACYM_CONNECTION_METHOD').acym_info('ACYM_CONNECTION_METHOD_DESC'); ?></label>
		<div class="cell medium-6">
            <?php
            echo acym_select(
                [
                    '' => '---',
                    'imap' => 'IMAP ('.acym_translation('ACYM_RECOMMENDED').')',
                    'pop3' => 'POP3',
                    'pear' => 'POP3 ('.acym_translation('ACYM_WITHOUT_IMAP_EXT').')',
                ],
                'mailbox[connection_method]',
                $data['mailboxActions']->connection_method,
                [
                    'class' => 'acym__select',
                    'acym-data-infinite' => '',
                ]
            );
            ?>
		</div>
	</div>
	<div class="cell grid-x">
		<label class="cell medium-4"><?php echo acym_translation('ACYM_SMTP_SECURE'); ?></label>
		<div class="cell medium-6">
            <?php
            echo acym_select(
                [
                    '' => '---',
                    'ssl' => 'SSL',
                    'tls' => 'TLS',
                ],
                'mailbox[secure_method]',
                $data['mailboxActions']->secure_method,
                [
                    'class' => 'acym__select',
                    'acym-data-infinite' => '',
                ]
            );
            ?>
		</div>
	</div>
	<label class="cell grid-x">
		<span class="cell medium-4"><?php echo acym_translation('ACYM_SMTP_PORT').acym_info('ACYM_BOUNCE_PORT_DESC'); ?></span>
		<input class="cell medium-6"
			   type="number"
			   name="mailbox[port]"
			   value="<?php echo acym_escape($data['mailboxActions']->port); ?>">
	</label>
	<div class="cell grid-x">
        <?php
        echo acym_switch(
            'mailbox[self_signed]',
            $data['mailboxActions']->self_signed,
            acym_translation('ACYM_SELF_SIGNED_CERTIFICATE'),
            [],
            'small-9 medium-4'
        );
        ?>
	</div>
	<div class="cell grid-x acym__mailbox__edition__configuration__test">
		<button type="button" data-task="testMailboxAction" class="button button-secondary cell medium-4 large-shrink" id="acym__mailbox__edition__configuration__test-test">
            <?php echo acym_translation('ACYM_TEST_CONNECTION'); ?>
		</button>
		<span class="acymicon-spin acymicon-circle-o-notch acym_vcenter cell shrink margin-left-1" id="acym__mailbox__edition__configuration__test-loader"></span>
		<span class="cell shrink acym_vcenter margin-left-1" id="acym__mailbox__edition__configuration__test-result"></span>
		<span class="acymicon-check-circle acym__color__green acymicon-times-circle acym__color__red cell shrink acym_vcenter" id="acym__mailbox__edition__configuration__test-icon"></span>
	</div>
</div>
