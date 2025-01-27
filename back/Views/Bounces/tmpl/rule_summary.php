<?php
$texts = [
    'senderInfo' => acym_translation('ACYM_SENDER_INFORMATION'),
    'subject' => acym_translation('ACYM_EMAIL_SUBJECT'),
    'body' => acym_translation('ACYM_BODY'),
];
?>

<div class="acym__bounces__summary acym__content cell grid-x large-8 margin-bottom-1">
	<div id="acym__bounces__summary__changes" class="cell small-9 acym__color__orange" style="display:none;"><?php echo acym_translation('ACYM_CHANGES_PLEASE_SAVE'); ?></div>

	<!-- GLOBAL -->
	<div class="cell grid-x padding-left-1 padding-bottom-1">
		<label class="cell grid-x grid-margin-x">
			<span class="cell medium-3 acym__label text-right"><?php echo acym_translation('ACYM_REGEX'); ?>:</span>
			<div class="cell medium-9 acym__label">
				<span class="acym__color__dark-gray">#</span>
				<span id="acym__bounces__sum__regex"><?php echo acym_escape((empty($data['rule']) || empty($data['rule']->regex)) ? ' ' : $data['rule']->regex); ?></span>
				<span class="acym__color__dark-gray">#ims</span>
			</div>
		</label>
		<label class="cell grid-x grid-margin-x">
			<span class="cell medium-3 acym__label text-right"><?php echo acym_translation('ACYM_APPLIED_ON'); ?>:</span>
            <?php
            $appliedOn = [];
            foreach ($data['rule']->executed_on as $oneApplied) {
                $appliedOn[] = $texts[$oneApplied];
            }
            ?>
			<span class="cell medium-9" id="acym__bounces__sum__applied"><?php echo implode(', ', $appliedOn); ?></span>
		</label>
        <?php
        $classStat = '';
        if (!$data['rule']->increment_stats) $classStat = ' style="display: none;"';
        ?>
		<span class="cell acym__label" id="acym__bounces__sum__stats" <?php echo $classStat; ?>><?php echo acym_translation(
                'ACYM_INCREMENT_BOUNCE_STATISTICS_IF_RULE_MATCHES'
            ); ?></span>
	</div>
	<!-- USER -->
	<div class="cell grid-x padding-left-1 padding-bottom-1">
		<label class="cell grid-x grid-margin-x">
			<span class="cell medium-3 text-right acym__title acym__title__secondary"><?php echo acym_translation('ACYM_ACTION_ON_USER'); ?>:</span>
			<div class="cell medium-9 acym__label grid-x">
                <?php
                echo '<div class="cell">'.acym_translationSprintf(
                        'ACYM_EXECUTE_ACTIONS_AFTER',
                        '<span id="acym__bounces__sum__exec">'.$data['rule']->execute_action_after.'</span>'
                    ).'<br /></div>';
                $actionsUsers = [
                    'delete_user_subscription' => ['id' => 'acym__bounces__sum__delsub', 'text' => 'ACYM_REMOVE_SUB'],
                    'unsubscribe_user' => ['id' => 'acym__bounces__sum__unsub', 'text' => 'ACYM_UNSUB_USER'],
                    'block_user' => ['id' => 'acym__bounces__sum__block', 'text' => 'ACYM_BLOCK_USER'],
                    'delete_user' => ['id' => 'acym__bounces__sum__deluser', 'text' => 'ACYM_DELETE_USER'],
                    'empty_queue_user' => ['id' => 'acym__bounces__sum__emptyqueue', 'text' => 'ACYM_EMPTY_QUEUE_USER'],
                    'subscribe_user' => ['id' => 'acym__bounces__sum__sub', 'text' => 'ACYM_SUBSCRIBE_USER'],
                ];
                echo '<ul class="acym__ul">';
                foreach ($actionsUsers as $keyAction => $oneAction) {
                    if (empty($data['rule']->action_user) || !in_array($keyAction, $data['rule']->action_user)) continue;
                    echo '<li id="'.$oneAction['id'].'" class="cell">'.acym_translation($oneAction['text']);
                    if ($keyAction == 'subscribe_user') {
                        $subscribeTo = ' ';
                        if (!empty($data['rule']->action_user['subscribe_user_list'])) $subscribeTo = $data['lists'][$data['rule']->action_user['subscribe_user_list']];
                        echo ' ( <span id="acym__bounces__sum__sub__details">'.$subscribeTo.'</span> )';
                    }
                    echo '</li>';
                }
                echo '</ul>';
                ?>
			</div>
		</label>
	</div>
	<!-- EMAIL -->
	<div class="cell grid-x padding-left-1 margin-bottom-1">
		<label class="cell grid-x grid-margin-x">
			<span class="cell medium-3 text-right acym__title acym__title__secondary"><?php echo acym_translation('ACYM_ACTION_ON_EMAIL'); ?>:</span>
			<span class="cell medium-9 acym__label grid-x">
					<?php
                    $actionsMsg = [
                        'save_message' => ['id' => 'acym__bounces__sum__savedb', 'text' => 'ACYM_SAVE_MESSAGE_DATABASE'],
                        'delete_message' => ['id' => 'acym__bounces__sum__deletemsg', 'text' => 'ACYM_DELETE_MESSAGE_FROM_MAILBOX'],
                        'forward_message' => ['id' => 'acym__bounces__sum__forward', 'text' => 'ACYM_FORWARD_EMAIL'],
                    ];
                    echo '<ul class="acym__ul">';
                    foreach ($actionsMsg as $keyMsg => $oneMsg) {
                        if (empty($data['rule']->action_message) || !in_array($keyMsg, $data['rule']->action_message)) continue;
                        echo '<li id="'.$oneMsg['id'].'" class="cell">'.acym_translation($oneMsg['text']);
                        if ($keyMsg == 'forward_message') {
                            $forwardTo = '';
                            if (!empty($data['rule']->action_message['forward_to'])) $forwardTo = $data['rule']->action_message['forward_to'];
                            echo ' <span id="acym__bounces__sum__forward__details">'.$forwardTo.'</span>';
                        }
                        echo '</li>';
                    }
                    echo '</ul>';
                    ?>
				</span>
		</label>
	</div>
	<!-- BUTTON -->
	<div class="cell grid-x align-center">
		<a class="button" id="acym__bounces__display_details"><?php echo acym_translation('ACYM_EDIT'); ?></a>
	</div>
</div>
