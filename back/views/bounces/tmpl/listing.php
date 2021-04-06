<?php

use AcyMailing\Helpers\BounceHelper;

if (acym_getVar('boolean', 'run_bounce')) {
    $bounceHelper = new BounceHelper();
    $bounceHelper->report = true;
    if ($bounceHelper->init() && $bounceHelper->connect()) {
        $nbMessages = $bounceHelper->getNBMessages();
        $bounceHelper->close();
        $messages = [
            acym_translationSprintf('ACYM_BOUNCE_CONNECT_SUCC', $this->config->get('bounce_username')),
            acym_translationSprintf('ACYM_NB_MAIL_MAILBOX', $nbMessages),
        ];

        if (!empty($nbMessages)) {
            $messages[] = acym_modal(
                acym_translation('ACYM_CLICK_BOUNCE'),
                '',
                null,
                '',
                'data-ajax="true" data-iframe="&ctrl=bounces&task=process" class="acym__color__light-blue cursor-pointer" style="margin: 0"'
            );
        }

        acym_display('<div>'.implode('</div><div>', $messages).'</div>', 'info', false);
    }
}
?>
<form id="acym_form" action="<?php echo acym_completeLink(acym_getVar('cmd', 'ctrl')); ?>" method="post" name="acyForm">
    <?php $data['toolbar']->displayToolbar($data); ?>
	<div id="acym__bounces" class="acym__content">
		<div class="cell grid-x acym__listing__actions">
            <?php
            $actions = [
                'delete' => acym_translation('ACYM_DELETE'),
                'setInactive' => acym_translation('ACYM_DISABLE'),
                'setActive' => acym_translation('ACYM_ENABLE'),
            ];
            echo acym_listingActions($actions);
            ?>
		</div>

		<div class="grid-x margin-top-1 acym__listing">
			<div class="cell grid-x acym__listing__header">
				<div class="medium-shrink small-1 cell">
					<input id="checkbox_all" type="checkbox" name="checkbox_all">
				</div>
				<div class="cell medium-1"></div>
				<div class="grid-x medium-auto cell">
					<div class="cell medium-4 acym__listing__header__title">
                        <?php echo acym_translation('ACYM_BOUNCE_RULE'); ?>
					</div>
					<div class="cell medium-3 acym__listing__header__title">
                        <?php echo acym_translation('ACYM_ACTION_ON_USER'); ?>
					</div>
					<div class="cell medium-4 acym__listing__header__title">
                        <?php echo acym_translation('ACYM_ACTION_ON_EMAIL'); ?>
					</div>
					<div class="cell medium-1 acym__listing__header__title text-center">
                        <?php echo acym_translation('ACYM_ACTIVE'); ?>
					</div>
				</div>
			</div>
			<div class="acym__sortable__listing acym__bounce__listing cell grid-x" data-sort-ctrl="bounces">
                <?php foreach ($data['allRules'] as $oneRule) { ?>
					<div class="grid-x cell acym__listing__row" data-id-element="<?php echo acym_escape($oneRule->id); ?>">
						<div class="medium-shrink small-1 cell acym_vcenter">
							<input id="checkbox_<?php echo acym_escape($oneRule->id); ?>"
								   type="checkbox"
								   name="elements_checked[]"
								   value="<?php echo acym_escape($oneRule->id); ?>">
						</div>
						<div class="medium-1 cell acym_vcenter align-center acym__bounce__listing__handle">
							<div class="grabbable acym__sortable__listing__handle grid-x">
								<i class="acymicon-ellipsis-h cell acym__color__dark-gray"></i>
								<i class="acymicon-ellipsis-h cell acym__color__dark-gray"></i>
							</div>
						</div>
						<div class="grid-x medium-auto small-11 cell acym__field__listing acym_vcenter">
							<div class="medium-4 acym__listing__title">
								<a href="<?php echo acym_completeLink('bounces&task=edit&id='.$oneRule->id); ?>" class="shrink">
									<h6 class="acym__listing__title__important"><?php echo acym_escape(acym_translation($oneRule->name)); ?></h6>
								</a>
							</div>
							<div class="cell medium-3 acym__listing__text">
								<h6>
                                    <?php if (in_array('delete_user_subscription', $oneRule->action_user)) {
                                        echo acym_translation('ACYM_REMOVE_SUB').'<br />';
                                    }
                                    if (in_array('unsubscribe_user', $oneRule->action_user)) {
                                        echo acym_translation('ACYM_UNSUB_USER').'<br />';
                                    }
                                    if (in_array('subscribe_user', $oneRule->action_user)) {
                                        echo acym_translation('ACYM_SUBSCRIBE_USER').' ( '.$data['lists'][$oneRule->action_user['subscribe_user_list']].' )<br />';
                                    }
                                    if (in_array('block_user', $oneRule->action_user)) {
                                        echo acym_translation('ACYM_BLOCK_USER').'<br />';
                                    }
                                    if (in_array('delete_user', $oneRule->action_user)) {
                                        echo acym_translation('ACYM_DELETE_USER').'<br />';
                                    }
                                    if (in_array('empty_queue_user', $oneRule->action_user)) {
                                        echo acym_translation('ACYM_EMPTY_QUEUE_USER');
                                    }
                                    ?>
								</h6>
							</div>
							<div class="cell medium-4 acym__listing__text">
								<h6>
                                    <?php if (in_array('save_message', $oneRule->action_message)) {
                                        echo acym_translation('ACYM_SAVE_MESSAGE_DATABASE').'<br />';
                                    }
                                    if (in_array('delete_message', $oneRule->action_message)) {
                                        echo acym_translation('ACYM_DELETE_MESSAGE_FROM_MAILBOX').'<br />';
                                    }
                                    if (in_array('forward_message', $oneRule->action_message) && !empty($oneRule->action_message['forward_to'])) {
                                        echo acym_translation('ACYM_FORWARD_EMAIL').' '.$oneRule->action_message['forward_to'];
                                    }
                                    ?>
								</h6>
							</div>
							<div class="cell medium-1 text-center acym__listing__controls">
                                <?php
                                $class = $oneRule->active == 1 ? 'acymicon-check-circle acym__color__green" data-acy-newvalue="0' : 'acymicon-times-circle acym__color__red" data-acy-newvalue="1';
                                echo '<i data-acy-table="rule" data-acy-field="active" data-acy-elementid="'.acym_escape(
                                        $oneRule->id
                                    ).'" class="acym_toggleable cursor-pointer '.$class.'"></i>';
                                ?>
							</div>
						</div>
					</div>
                <?php } ?>
			</div>
		</div>
	</div>
    <?php acym_formOptions(); ?>
</form>
