<div class="cell grid-x acym__list__settings__tmpls acym__content">
	<div class="cell grid-y medium-4 text-center acym__list__settings__subscriber__nb">
		<div class="cell small-2 acym__list__settings__tmpls__title grid-x align-center acym_vcenter">
			<label>
                <?php echo acym_translation('ACYM_SUBSCRIBERS'); ?>
			</label>
			<i class="acymicon-group margin-left-1"></i>
		</div>
		<div class="cell small-10 align-center acym_vcenter acym__list__settings__subscriber__nb__display grid-x">
			<div class="cell grid-x">
                <?php
                $linkType = acym_isAdmin() ? 'a' : 'span';
                $url = acym_completeLink((acym_isAdmin() ? '' : 'front').'users&users_list='.intval($data['listInformation']->id).'&list_status=sub');
                ?>
				<div class="cell small-4 text-right acym__list__settings__subscriber__nb__line">
					<<?php echo $linkType; ?> href="<?php echo $url; ?>" class="acym__color__blue"><?php echo $data['listInformation']->subscribers['sendable_users']; ?>&nbsp;</<?php echo $linkType; ?>>
				</div>
				<div class="cell small-8 text-left acym__list__settings__subscriber__nb__line">
					<<?php echo $linkType; ?> href="<?php echo $url; ?>"><?php echo acym_translation('ACYM_SUBSCRIBED'); ?></<?php echo $linkType; ?>>
				</div>
                <?php
                if ($this->config->get('require_confirmation', 1) == 1) {
                    $url = acym_completeLink((acym_isAdmin() ? '' : 'front').'users&users_list='.intval($data['listInformation']->id).'&list_status=sub&users_status=unconfirmed');
                    ?>
					<div class="cell small-4 text-right acym__list__settings__subscriber__nb__line">
						<<?php echo $linkType; ?> href="<?php echo $url; ?>" class="acym__color__blue"><?php echo $data['listInformation']->subscribers['unconfirmed_users']; ?>&nbsp;</<?php echo $linkType; ?>>
					</div>
					<div class="cell small-8 text-left acym__list__settings__subscriber__nb__line">
						<<?php echo $linkType; ?> href="<?php echo $url; ?>"><?php echo acym_translation('ACYM_NOT_CONFIRMED'); ?></<?php echo $linkType; ?>>
					</div>
                    <?php
                }
                $url = acym_completeLink((acym_isAdmin() ? '' : 'front').'users&users_list='.intval($data['listInformation']->id).'&list_status=sub&users_status=inactive');
                ?>
				<div class="cell small-4 text-right acym__list__settings__subscriber__nb__line">
					<<?php echo $linkType; ?> href="<?php echo $url; ?>" class="acym__color__blue"><?php echo $data['listInformation']->subscribers['inactive_users']; ?>&nbsp;</<?php echo $linkType; ?>>
				</div>
				<div class="cell small-8 text-left acym__list__settings__subscriber__nb__line">
					<<?php echo $linkType; ?> href="<?php echo $url; ?>"><?php echo acym_translation('ACYM_INACTIVE'); ?></<?php echo $linkType; ?>>
				</div>
                <?php $url = acym_completeLink((acym_isAdmin() ? '' : 'front').'users&users_list='.intval($data['listInformation']->id).'&list_status=unsub'); ?>
				<div class="cell small-4 text-right">
					<<?php echo $linkType; ?> href="<?php echo $url; ?>" class="acym__color__blue"><?php echo $data['listInformation']->subscribers['unsubscribed_users']; ?>&nbsp;</<?php echo $linkType; ?>>
				</div>
				<div class="cell small-8 text-left">
					<<?php echo $linkType; ?> href="<?php echo $url; ?>"><?php echo acym_translation('ACYM_UNSUBSCRIBED'); ?></<?php echo $linkType; ?>>
				</div>
			</div>
		</div>
	</div>
	<div class="cell grid-y medium-4 acym__list__settings__tmpls__welcome">
		<div class="cell small-2 acym__list__settings__tmpls__title align-center acym_vcenter">
            <?php
			echo acym_tooltip(
                [
					'hoveredText' => '<label>'.acym_translation('ACYM_WELCOME_MAIL').'</label>',
                	'textShownInTooltip' => '('.acym_translation('ACYM_OPTIONAL').') '.acym_translation('ACYM_WELCOME_MAIL_DESC')
				]
            );
			?>
			<i class="acymicon-email margin-left-1"></i>
		</div>
		<div class="cell grid-x acym__template__block align-center acym_vcenter small-10 acym__list__button__add__mail">
            <?php
            if (!acym_isAllowed('mails')) {
                echo acym_tooltip(
                    [
						'hoveredText' => '<i class="acymicon-ban acym__list__button__add__mail__disabled"></i>',
                    	'textShownInTooltip' => acym_translation('ACYM_UNAUTHORIZED_ACCESS')
					]
                );
            } elseif (empty($data['listInformation']->id)) {
                echo acym_tooltip(
                    [
						'hoveredText' => '<i class="acymicon-ban acym__list__button__add__mail__disabled"></i>',
                	    'textShownInTooltip' => acym_translation('ACYM_SAVE_LIST_FIRST')
					]
                );
            } elseif (empty($data['listInformation']->welcome_id)) { ?>
				<a class="acym_vcenter text-center align-center acym__color__white acym__list__button__add__mail__welcome__unsub"
				   href="<?php echo $data['tmpls']['welcomeTmplUrl']; ?>">
					<i class="acymicon-add"></i>
				</a>
            <?php } else { ?>
				<button type="button"
						template="<?php echo acym_escape($data['listInformation']->welcome_id); ?>"
						class="cell acym__lists__oneMail acym__listing__block acym_template_option">

					<!-- Icon delete -->
					<div class="acym__listing__block__delete">
						<div>
							<div class="js-acym__listing__block__delete__trash acym__listing__block__delete__trash acym__icon">
								<i class="acymicon-trash-o"></i>
							</div>
							<div class="acym__listing__block__delete__action">
								<div class="acym__icon acym__listing__block__delete__submit acy_button_submit"
								 	data-task="unsetWelcome">
									<i class="acymicon-trash-o"></i>
								</div>
								<div class="acym__listing__block__delete__cancel acym__background-color__very-dark-gray acym__color__white">
									<i class="acymicon-keyboard_arrow_right acym__color__white"></i>
								</div>
							</div>
						</div>
					</div>

					<!-- Mail preview -->
					<a href="<?php echo $data['tmpls']['welcomeTmplUrl']; ?>">
						<div class="cell grid-x text-center">
							<div class="cell acym__templates__pic text-center">
								<img src="<?php echo acym_getMailThumbnail($data['tmpls']['welcome']->thumbnail); ?>"
									 alt="<?php echo acym_escape($data['tmpls']['welcome']->name); ?>" />
							</div>
							<div class="cell grid-x text-center acym__templates__footer">
								<div class="cell acym__template__footer__title"><?php echo acym_escape($data['tmpls']['welcome']->name); ?></div>
							</div>
						</div>
					</a>
				</button>
            <?php } ?>
		</div>
	</div>
	<div class="cell grid-y medium-4 acym__list__settings__tmpls__unsubscribe">
		<div class="cell small-2 acym__list__settings__tmpls__title align-center acym_vcenter">
            <?php echo acym_tooltip(
                [
					'hoveredText' => '<label>'.acym_translation('ACYM_UNSUBSCRIBE_MAIL').'</label>',
                	'textShownInTooltip' => '('.acym_translation('ACYM_OPTIONAL').') '.acym_translation('ACYM_UNSUBSCRIBE_MAIL_DESC')
				]
            ); ?>
			<i class="acymicon-email margin-left-1"></i>
		</div>
		<div class="cell grid-x acym__template__block align-center acym_vcenter small-10 acym__list__button__add__mail">
            <?php
            if (!acym_isAllowed('mails')) {
                echo acym_tooltip(
                    [
						'hoveredText' => '<i class="acymicon-ban acym__list__button__add__mail__disabled"></i>',
                    	'textShownInTooltip' => acym_translation('ACYM_UNAUTHORIZED_ACCESS')
					]
                );
            } elseif (empty($data['listInformation']->id)) {
                echo acym_tooltip(
                    [
						'hoveredText' => '<i class="acymicon-ban acym__list__button__add__mail__disabled"></i>',
                    	'textShownInTooltip' => acym_translation('ACYM_SAVE_LIST_FIRST')
					]
                );
            } elseif (empty($data['listInformation']->unsubscribe_id)) { ?>
				<a class="acym_vcenter text-center align-center acym__color__white acym__list__button__add__mail__welcome__unsub"
				   href="<?php echo $data['tmpls']['unsubTmplUrl']; ?>">
					<i class="acymicon-add"></i>
				</a>
            <?php } else { ?>
				<button type="button"
						template="<?php echo acym_escape($data['listInformation']->unsubscribe_id); ?>"
						class="cell acym__lists__oneMail acym__listing__block acym_template_option">
					<!-- Icon delete -->
					<div class="acym__listing__block__delete">
						<div>
							<div class="js-acym__listing__block__delete__trash acym__listing__block__delete__trash acym__icon">
								<i class="acymicon-trash-o"></i>
							</div>
							<div class="acym__listing__block__delete__action">
								<div class="acym__icon acym__listing__block__delete__submit acy_button_submit"
								 	data-task="unsetUnsubscribe">
									<i class="acymicon-trash-o"></i>
								</div>
								<div class="acym__listing__block__delete__cancel acym__background-color__very-dark-gray acym__color__white">
									<i class="acymicon-keyboard_arrow_right acym__color__white"></i>
								</div>
							</div>
						</div>
					</div>

					<!-- Mail preview -->
					<a href="<?php echo $data['tmpls']['unsubTmplUrl']; ?>">
						<div class="cell grid-x text-center">
							<div class="cell acym__templates__pic text-center">
								<img src="<?php echo acym_getMailThumbnail($data['tmpls']['unsubscribe']->thumbnail); ?>"
									 alt="<?php echo acym_escape($data['tmpls']['unsubscribe']->name); ?>" />
							</div>
							<div class="cell grid-x text-center acym__templates__footer">
								<div class="cell acym__template__footer__title"><?php echo acym_escape($data['tmpls']['unsubscribe']->name); ?></div>
							</div>
						</div>
					</a>
				</button>
            <?php } ?>
		</div>
	</div>
</div>
