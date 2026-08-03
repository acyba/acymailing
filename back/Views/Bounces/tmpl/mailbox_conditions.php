<?php
// context verification
?>
<div class="acym__content cell grid-x margin-bottom-1">
	<span class="cell acym__content__title__light-blue"><?php echo acym_escapeHtml(acym_translation('ACYM_CONDITIONS')); ?></span>
	<div class="cell grid-x large-6 margin-y">
		<label class="cell medium-4">
            <?php echo acym_escapeHtml(acym_translation('ACYM_ALLOWED_SENDER'));
            acym_info(['textShownInTooltip' => 'ACYM_ALLOWED_SENDER_DESC']); ?>
		</label>
		<div class="cell medium-7 grid-x margin-y">
			<div class="cell">
                <?php
                acym_select(
                    [
                        '' => 'ACYM_EVERYONE',
                        'specific' => 'ACYM_SPECIFIC_ADDRESSES',
                        'groups' => 'ACYM_USER_GROUPS',
                        'lists' => 'ACYM_ACYMAILING_LISTS',
                    ],
                    'mailbox[conditions][sender]',
                    $data['mailboxActions']->conditions['sender'],
                    [
                        'class' => 'acym__select',
                        'acym-data-infinite' => '',
                    ],
                    '',
                    '',
                    '',
                    true,
                    true
                );
                ?>
			</div>

			<div id="acym__mailbox__edition__conditions_user" class="cell">
				<input name="mailbox[conditions][specific]"
				       id="acym__mailbox__edition__conditions_sender__specific"
				       class="acym__mailbox__edition__conditions_sender__option"
				       type="text"
				       placeholder="sender@example.com,other@example.com"
				       value="<?php echo acym_escape($data['mailboxActions']->conditions['specific']); ?>">
				<div id="acym__mailbox__edition__conditions_sender__groups" class="acym__mailbox__edition__conditions_sender__option">
                    <?php
                    acym_selectMultiple(
                        $data['groups'],
                        'mailbox[conditions][groups]',
                        $data['mailboxActions']->conditions['groups'],
                        [
                            'class' => 'acym__select',
                            'acym-data-infinite' => '',
                        ],
                        'value',
                        'text',
                        true
                    );
                    ?>
				</div>
				<div id="acym__mailbox__edition__conditions_sender__lists" class="acym__mailbox__edition__conditions_sender__option">
                    <?php
                    if (!is_array($data['mailboxActions']->conditions['lists'])) {
                        $data['mailboxActions']->conditions['lists'] = [$data['mailboxActions']->conditions['lists']];
                    }
                    acym_selectMultiple(
                        $data['lists'],
                        'mailbox[conditions][lists]',
                        $data['mailboxActions']->conditions['lists'],
                        [
                            'class' => 'acym__select',
                            'acym-data-infinite' => '',
                        ],
                        'value',
                        'text',
                        true
                    );
                    ?>
				</div>
			</div>
		</div>
	</div>
	<div class="separator-center"></div>
	<div class="cell grid-x large-6 margin-y">
		<label class="cell medium-4">
            <?php echo acym_escapeHtml(acym_translation('ACYM_ALLOWED_SUBJECT'));
            acym_info(['textShownInTooltip' => 'ACYM_ALLOWED_SUBJECT_DESC']); ?>
		</label>
		<div class="cell medium-7 grid-x margin-y">
			<div class="cell">
                <?php
                acym_select(
                    [
                        '' => 'ACYM_ANY_SUBJECT',
                        'begins' => 'ACYM_BEGINS_WITH',
                        'contains' => 'ACYM_CONTAINS',
                        'ends' => 'ACYM_ENDS_WITH',
                        'regex' => 'ACYM_REGEX',
                    ],
                    'mailbox[conditions][subject]',
                    $data['mailboxActions']->conditions['subject'],
                    [
                        'class' => 'acym__select',
                        'acym-data-infinite' => '',
                    ],
                    '',
                    '',
                    '',
                    true,
                    true
                );
                ?>
			</div>

			<input name="mailbox[conditions][subject_text]"
			       id="acym__mailbox__edition__conditions_subject__text"
			       class="cell acym__mailbox__edition__conditions_subject__option"
			       type="text"
			       value="<?php echo acym_escape($data['mailboxActions']->conditions['subject_text']); ?>">

			<div id="acym__mailbox__edition__conditions_subject__regex" class="cell acym__mailbox__edition__conditions_subject__option">
				# <input name="mailbox[conditions][subject_regex]"
				         type="text"
				         value="<?php echo acym_escape($data['mailboxActions']->conditions['subject_regex']); ?>"> #ims
			</div>
		</div>
		<div class="cell grid-x acym__mailbox__edition__conditions_subject__option" id="acym__mailbox__edition__conditions_subject__remove">
            <?php
            acym_switch([
                'name' => 'mailbox[conditions][subject_remove]',
                'value' => $data['mailboxActions']->conditions['subject_remove'],
                'label' => acym_translation('ACYM_REMOVE_FROM_SUBJECT'),
                'tip' => ['textShownInTooltip' => 'ACYM_REMOVE_FROM_SUBJECT_DESC'],
                'labelClass' => 'medium-4 small-9',
            ]);
            ?>
		</div>
	</div>
	<div class="cell grid-x margin-top-1">
        <?php
        acym_switch([
            'name' => 'mailbox[delete_wrong_emails]',
            'value' => $data['mailboxActions']->delete_wrong_emails,
            'label' => acym_translation('ACYM_DELETE_NOT_ALLOWED_EMAILS'),
            'tip' => ['textShownInTooltip' => 'ACYM_DELETE_NOT_ALLOWED_EMAILS_DESC'],
            'labelClass' => 'large-2 medium-4 small-9',
        ]);
        ?>
	</div>
</div>
