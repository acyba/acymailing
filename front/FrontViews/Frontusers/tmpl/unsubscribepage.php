<div class="acym__unsubscribe__page grid-x grid-padding-x">
    <?php if ($this->config->get('display_unsub_image') === '1') { ?>
		<div class="acym__unsubscribe__image__container cell hide-for-small-only">
            <?php if (!empty($this->config->get('unsubscribe_image'))) { ?>
				<img class="cell padding-3"
					 src="<?php echo $this->config->get('unsubscribe_image'); ?>"
					 alt="Unsubscribe Image">
            <?php } else { ?>
				<div class="cell padding-3 acym__unsubscribe__image__svg">
                    <?php echo $data['svgImage']; ?>
				</div>
            <?php } ?>
		</div>
    <?php } ?>
    <?php $classContainer = $this->config->get('display_unsub_image') === '1' ? '' : 'no-image'; ?>
	<div class="acym__unsubscribe__form__container cell <?php echo $classContainer; ?>">
		<div class="cell">
			<form action="<?php echo acym_frontendLink('frontusers'); ?>" name="unsubscribepage" class="acym_front_page acym__unsubscribe__form">
				<fieldset class="grid-x grid-margin-x align-center">
					<legend class="show-for-sr"><?php echo acym_escape(acym_translation('ACYM_EMAIL_PREFERENCES')); ?></legend>
					<div class="acym__unsubscribe__container cell">
						<div class="acy__unsubscribe__form__header margin-bottom-3">
							<div class="acym__unsubscribe__form__header__image cell padding-bottom-1">
                                <?php if (!empty($this->config->get('unsubscribe_logo'))) { ?>
									<img class="cell" src="<?php echo $this->config->get('unsubscribe_logo'); ?>" alt="Unsubscribe Logo">
                                <?php } ?>
							</div>
							<h1 class="acym__title acym__unsubscribe__title margin-bottom-1">
                                <?php if (!empty($this->config->get('unsubscribe_title'))) {
                                    echo acym_escape($this->config->get('unsubscribe_title'));
                                } else {
                                    echo acym_translation('ACYM_EMAIL_PREFERENCES');
                                } ?>
							</h1>
                            <?php if (!empty($data['languages'])) {
                                echo acym_select(
                                    $data['languages'],
                                    'language',
                                    $data['lang'],
                                    ['aria-label' => acym_translation('ACYM_LANGUAGE')],
                                    'value',
                                    'text',
                                    'acym__unsubscribe__language__select'
                                );
                            }
                            ?>
						</div>
						<div class="acym__unsubscribe__lists__container cell margin-bottom-1">
                            <?php if (empty($data['subscriptions'])) { ?>
								<p><?php echo acym_translation('ACYM_NO_DATA_TO_DISPLAY'); ?></p>
								<p><?php echo acym_translation('ACYM_NO_LIST_FOUND_CAN_UNSUB'); ?></p>
                            <?php } else { ?>
								<div class="acym__unsubscribe__lists__listing margin-bottom-1">
									<ul class="acym__unsubscribe__lists">
                                        <?php foreach ($data['subscriptions'] as $list) {
                                            if (empty($list->visible)) continue; ?>
											<li class="acym__unsubscribe__list__item grid-x">
												<div class="acym__unsubscribe__lists__listing cell margin-bottom-1">
													<p class="acym__unsubscribe__list__display__name">
                                                        <?php echo !empty($list->display_name) ? $list->display_name : $list->name; ?>
													</p>
													<p class="acym__unsubscribe__list__description">
                                                        <?php echo !empty($list->description) ? $list->description : ''; ?>
													</p>
												</div>
												<div class="acym__unsubscribe__list__switch margin-bottom-1 cell">
                                                    <?php
                                                    $listName = !empty($list->display_name) ? $list->display_name : $list->name;
                                                    echo acym_switch(
                                                        'lists['.$list->id.']',
                                                        $list->status,
                                                        null,
                                                        [
                                                            'name' => 'lists['.$list->id.']',
                                                            'aria-label' => $listName,
                                                        ]
                                                    );
                                                    ?>
												</div>
											</li>
                                        <?php } ?>
									</ul>
								</div>
                            <?php } ?>
							<div class="acym__unsubscribe__reason__section cell margin-bottom-1">
								<div class="acym__unsubscribe__reason__label">
									<p class="acym__unsubscribe__reason"><?php echo acym_translation('ACYM_SHARE_YOUR_REASONS'); ?></p>
									<span class="acym__optional"><?php echo acym_translation('ACYM_OPTIONAL'); ?></span>
								</div>
                                <?php if (!empty($data['surveyAnswers'])) {
                                    echo acym_select($data['surveyAnswers'], 'unsubscribe_selector_reason', 'acym__unsubscribe__reason__select');
                                    ?>
									<div class="acym__unsubscribe__reason__label acym__custom__reason__label padding-top-1 is-hidden">
										<p class="acym__unsubscribe__reason"><?php echo acym_translation('ACYM_CAN_YOU_TELL_MORE'); ?></p>
										<span class="acym__optional"><?php echo acym_translation('ACYM_OPTIONAL'); ?></span>
									</div>
									<input type="text"
										   id="acym__custom__unsubscribe__reason"
										   name="unsubscribe_custom_reason"
										   class="is-hidden"
										   aria-label="<?php echo acym_escape(acym_translation('ACYM_CAN_YOU_TELL_MORE')); ?>">
									<input type="hidden" name="unsubscribe_reason">
                                <?php } else { ?>
									<input type="text"
										   name="unsubscribe_reason"
										   class="acym__unsubscribe__input__reason"
										   aria-label="<?php echo acym_escape(acym_translation('ACYM_SHARE_YOUR_REASONS')); ?>">
                                <?php } ?>
							</div>
							<div class="acym__unsubscribe__actions grid-x align-center">
                                <?php if (count($data['subscriptions']) > 1 || empty($data['subscriptions'])) { ?>
									<button type="button"
											class="button button-secondary cell margin-bottom-1"
											id="acym__unsub__all"
											onclick="return acymSubmitForm('unsubscribeAll', this);">
                                        <?php echo acym_translation('ACYM_UNSUBSCRIBE_ALL'); ?>
									</button>
                                <?php }
                                if (empty($data['subscriptions'])) { ?>
									<button type="button"
											class="button button-primary cell margin-bottom-1"
											id="acym__unsub__direct"
											onclick="return acymSubmitForm('unsubscribe', this);">
                                        <?php echo acym_translation('ACYM_UNSUBSCRIBE'); ?>
									</button>
                                <?php } else { ?>
									<button type="button"
											class="button button-primary cell margin-bottom-1"
											id="acym__save"
											onclick="return acymSubmitForm('saveSubscriptions', this);">
                                        <?php echo acym_translation('ACYM_UPDATE_PREFERENCES'); ?>
									</button>
                                <?php } ?>
							</div>
						</div>
					</div>
                    <?php
                    if ($this->config->get('display_built_by', 1)) { ?>
						<img class="cell acym__powered__by__image"
							 src="<?php echo ACYM_IMAGES.'editor/poweredby_black.png'; ?>"
							 alt="Powered by AcyMailing">
                    <?php } ?>

				</fieldset>
                <?php acym_formOptions(); ?>
				<input type="hidden" name="user_id" value="<?php echo $data['user']->id; ?>">
				<input type="hidden" name="user_key" value="<?php echo acym_escape($data['user']->key); ?>">
				<input type="hidden" name="mail_id" value="<?php echo $data['mail_id']; ?>">
				<input type="hidden" name="displayed_checked_lists" id="displayed_checked_lists">
			</form>
		</div>
	</div>
</div>

<style>
	#acym__unsub__all:hover{
		background-color: <?php echo $data['hoverColor']; ?> !important;
		border-color: <?php echo $data['hoverColor']; ?> !important;
	}

	#acym__save:hover{
		background-color: <?php echo $data['hoverColor']; ?> !important;
		border-color: <?php echo $data['hoverColor']; ?> !important;
	}

	.switch input:checked ~ .switch-paddle{
		background-color: <?php echo $data['unsubscribeColor']; ?> !important;
		border-color: <?php echo $data['unsubscribeColor']; ?> !important;
	}

	.acym__unsub__reason__selected{
		border-color: <?php echo $data['unsubscribeColor']; ?> !important;
	}

	.button-primary{
		background-color: <?php echo $data['unsubscribeColor']; ?> !important;
		border-color: <?php echo $data['unsubscribeColor']; ?> !important;
	}

	.button-primary:hover{
		background-color: <?php echo $data['hoverColor']; ?> !important;
		border-color: <?php echo $data['hoverColor']; ?> !important;
	}

	.button-secondary{
		border-color: <?php echo $data['unsubscribeColor']; ?> !important;
		color: <?php echo $data['unsubscribeColor']; ?> !important;
	}

	.button-secondary:hover{
		border-color: <?php echo $data['hoverColor']; ?> !important;
	}
</style>

<?php if ('wordpress' == ACYM_CMS) exit; ?>
