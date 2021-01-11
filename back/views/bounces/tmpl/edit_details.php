<div class="acym__content cell grid-x" id="acy_bounces_details" style="<?php echo !empty($data['id']) ? 'display: none;' : ''; ?>">
	<div class="acym__title acym__title__secondary cell"><?php echo acym_translation('ACYM_BOUNCE_RULE_CONDITION'); ?></div>

	<div class="cell grid-x large-6 padding-left-1">
		<label class="cell grid-x">
			<span class="cell medium-5 acym__label">
				<?php echo acym_translation('ACYM_REGEX');
                echo acym_info('ACYM_BOUNCES_REGEX_DESC'); ?>
			</span>
			<span class="cell medium-7 acym__label">
				# <input class="intext_input_large intext_input"
						 type="text"
						 name="bounce[regex]"
						 value="<?php echo acym_escape((empty($data['rule']) || empty($data['rule']->regex)) ? '' : $data['rule']->regex); ?>"> #ims
			</span>
		</label>

        <?php
        $valuesRegex = [
            'senderInfo' => '<div>'.acym_translation('ACYM_SENDER_INFORMATION').'</div>',
            'subject' => '<div>'.acym_translation('ACYM_EMAIL_SUBJECT').'</div>',
            'body' => '<div>'.acym_translation('ACYM_BODY').'</div>',
        ];
        acym_checkbox(
            $valuesRegex,
            'bounce[executed_on][]',
            (empty($data['rule']) || empty($data['rule']->executed_on)) ? [] : $data['rule']->executed_on,
            acym_translation('ACYM_EXECUTE_REGEX_ON'),
            'cell margin-top-1 grid-x acym__bounces__rules__cb',
            'medium-5 margin-right-1'
        ); ?>
		<div class="cell grid-x margin-top-1">
            <?php echo acym_switch(
                'bounce[increment_stats]',
                !empty($data['rule']) ? $data['rule']->increment_stats : 1,
                acym_translation('ACYM_INCREMENT_BOUNCE_STATISTICS_IF_RULE_MATCHES'),
                [],
                'medium-5'
            ); ?>
		</div>
	</div>

	<div class="cell">
		<div class="acym__title acym__title__secondary margin-top-1">
            <?php echo acym_translation('ACYM_ACTION_ON_USER');
            echo acym_info('ACYM_BOUNCES_ACTION_USER_DESC'); ?></div>
		<div class="cell grid-x padding-left-1">
			<p class="acym__label">
                <?php echo acym_translationSprintf(
                    'ACYM_EXECUTE_ACTIONS_AFTER',
                    '<input type="number" min="0" name="bounce[execute_action_after]" value="'.acym_escape(
                        !empty($data['rule']) ? $data['rule']->execute_action_after : '0'
                    ).'" class="intext_input">'
                ); ?>
			</p>
            <?php
            $subscribeUserAction = '<div class="cell shrink margin-right-1 acym__label">'.acym_translation('ACYM_SUBSCRIBE_USER_TO').'</div>';
            $subscribeUserAction .= '<div class="cell large-6 input__in__checkbox acym__bounce__select__subscribe">';
            $subscribeUserAction .= acym_select(
                    $data['lists'],
                    'bounce[subscribe_user_list]',
                    (!empty($data['rule']) && !empty($data['rule']->action_user['subscribe_user_list'])) ? $data['rule']->action_user['subscribe_user_list'] : '',
                    'class="acym__select shrink"'
                ).'</div>';

            $valuesActionUser = [
                'delete_user_subscription' => '<div>'.acym_translation('ACYM_DELETE_USER_SUBSCRITION').'</div>',
                'unsubscribe_user' => '<div>'.acym_translation('ACYM_UNSUBSCRIBE_USER').'</div>',
                'block_user' => '<div>'.acym_translation('ACYM_BLOCK_USER').'</div>',
                'delete_user' => '<div>'.acym_translation('ACYM_DELETE_USER').'</div>',
                'empty_queue_user' => '<div>'.acym_translation('ACYM_EMPTY_QUEUE_USER').'</div>',
                'subscribe_user' => '<div class="cell grid-x large-8 margin-left-0 acym__bounces__details__label">'.$subscribeUserAction.'</div>',
            ];
            $fieldToUpdate = [
                'delete_user_subscription' => 'acym__bounces__sum__delsub',
                'unsubscribe_user' => 'acym__bounces__sum__unsub',
                'block_user' => 'acym__bounces__sum__block',
                'delete_user' => 'acym__bounces__sum__deluser',
                'empty_queue_user' => 'acym__bounces__sum__emptyqueue',
                'subscribe_user' => 'acym__bounces__sum__sub',
            ];
            echo '<div class="margin-top-1 margin-left-2">';
            acym_checkbox(
                $valuesActionUser,
                'bounce[action_user][]',
                (empty($data['rule']) || empty($data['rule']->action_user)) ? [] : $data['rule']->action_user,
                '',
                'acym__bounces__rules__cb',
                '',
                $fieldToUpdate
            );
            echo '</div>'; ?>
		</div>
	</div>

	<div class="cell">
		<div class="acym__title acym__title__secondary margin-top-1">
            <?php echo acym_translation('ACYM_ACTION_ON_EMAIL');
            echo acym_info('ACYM_BOUNCES_ACTION_MSG_DESC'); ?></div>
		<div class="cell grid-x padding-left-1">
            <?php
            $forwardMsg = '<div class="cell grid-x"><span class="medium-4 cell acym__label">'.acym_translation('ACYM_FORWARD_EMAIL').'</span>';
            $forwardMsg .= '<input class="medium-7 input__in__checkbox cell" type="email" name="bounce[action_message][forward_to]" value="'.(!empty($data['rule']) && in_array(
                    'forward_message',
                    $data['rule']->action_message
                ) ? $data['rule']->action_message['forward_to'] : '').'"></div>';

            $valuesActionEmail = [
                'save_message' => '<div>'.acym_translation('ACYM_SAVE_MESSAGE_DATABASE').'</div>',
                'delete_message' => '<div>'.acym_translation('ACYM_DELETE_MESSAGE_FROM_MAILBOX').'</div>',
                'forward_message' => '<div class="cell grid-x large-8 margin-left-0">'.$forwardMsg.'</div>',
            ];
            $fieldToUpdate = [
                'save_message' => 'acym__bounces__sum__savedb',
                'delete_message' => 'acym__bounces__sum__deletemsg',
                'forward_message' => 'acym__bounces__sum__forward',
            ];
            echo '<div class="cell grid-x margin-top-1 margin-left-2">';
            acym_checkbox(
                $valuesActionEmail,
                'bounce[action_message][]',
                (!empty($data['rule']) && !empty($data['rule']->action_message)) ? $data['rule']->action_message : [],
                '',
                'acym__bounces__rules__cb',
                '',
                $fieldToUpdate
            );
            echo '</div>';
            ?>
		</div>
	</div>
</div>
