<div class="acym__content cell grid-x margin-bottom-1 margin-y">
	<span class="cell acym__content__title__light-blue"><?php echo acym_translation('ACYM_ACTIONS'); ?></span>
	<div class="cell"><?php echo acym_translation('ACYM_EMAIL_REMOVED_AFTER_ACTIONS'); ?></div>

	<div class="cell grid-x margin-y">
		<input type="hidden" id="acym__mailbox__edition__action__number" value="0">
		<input type="hidden" id="acym__mailbox__edition__actions" value="<?php echo acym_escape($data['mailboxActions']->actions); ?>">
		<template id="acym__mailbox__edition__action__template">
			<div class="acym__mailbox__edition__action__one cell grid-x" data-action-number="__num__">
				<div class="acym__mailbox__edition__action__and cell grid-x margin-top-1">
					<h6 class="cell medium-shrink small-11 acym__title acym__title__secondary"><?php echo acym_translation('ACYM_AND'); ?></h6>
					<div class="cell medium-4 hide-for-small-only"></div>
					<i class="cell medium-shrink small-1 cursor-pointer acymicon-close acym__color__red acym__mailbox__edition__action__delete"></i>
				</div>
				<div class="large-5 cell">
                    <?php echo $data['initialAction']; ?>
				</div>
			</div>
		</template>

		<div class="acym__mailbox__edition__action__one cell grid-x" data-action-number="0">
			<div class="large-5 cell">
                <?php echo str_replace('__num__', 0, $data['initialAction']); ?>
			</div>
		</div>
		<div class="cell grid-x">
			<button type="button" id="acym__mailbox__edition__action__new" class="button-secondary button medium-shrink margin-top-1">
                <?php echo acym_translation('ACYM_ADD_ACTION'); ?>
			</button>
		</div>
	</div>

	<div class="cell grid-x">
        <?php
        echo acym_switch(
            'mailbox[senderfrom]',
            $data['mailboxActions']->senderfrom,
            acym_translation('ACYM_SENDER_AS_FROM').acym_info('ACYM_SENDER_AS_FROM_DESC'),
            [],
            'medium-4 small-9'
        );
        ?>
	</div>
	<div class="cell grid-x">
        <?php
        echo acym_switch(
            'mailbox[senderto]',
            $data['mailboxActions']->senderto,
            acym_translation('ACYM_SENDER_AS_REPLY_TO').acym_info('ACYM_SENDER_AS_REPLY_TO_DESC'),
            [],
            'medium-4 small-9'
        );
        ?>
	</div>
</div>
