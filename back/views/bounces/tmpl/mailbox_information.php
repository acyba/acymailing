<div class="acym__content cell grid-x large-6 margin-bottom-1">
	<div class="cell grid-x margin-y" id="acym__mailbox__edition__information">
		<span class="acym__content__title__light-blue"><?php echo acym_translation('ACYM_INFORMATION'); ?></span>
		<label class="cell grid-x">
			<span class="cell medium-4 acym__label"><?php echo acym_translation('ACYM_NAME'); ?></span>
			<input required class="cell medium-7" type="text" name="mailbox[name]" value="<?php echo acym_escape($data['mailboxActions']->name); ?>">
		</label>
		<div class="cell grid-x">
            <?php
            echo acym_switch(
                'mailbox[active]',
                empty($data['mailboxActions']->active) ? 0 : 1,
                acym_translation('ACYM_ENABLED'),
                [],
                'medium-4 small-9'
            );
            ?>
		</div>
		<label for="delayvalue1" class="cell medium-4 acym__label"><?php echo acym_translation('ACYM_FREQUENCY'); ?></label>
		<div class="cell medium-7">
            <?php
            echo $data['delayType']->display(
                'mailbox[frequency]',
                $data['mailboxActions']->frequency,
                2
            );
            ?>
		</div>
		<label class="cell grid-x">
			<span class="cell medium-4 acym__label"><?php echo acym_translation('ACYM_DESCRIPTION'); ?></span>
			<textarea rows="10" class="cell medium-7" name="mailbox[description]"><?php echo acym_escape($data['mailboxActions']->description); ?></textarea>
		</label>
	</div>
</div>
