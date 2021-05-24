<?php if (empty($data['allUsers'])) { ?>
	<h1 class="cell acym__listing__empty__search__title text-center"><?php echo acym_translation('ACYM_NO_RESULTS_FOUND'); ?></h1>
<?php } else { ?>
	<div class="cell grid-x margin-top-1">
		<div class="grid-x acym__listing__actions cell margin-bottom-1">
            <?php
            $actions = [
                'delete' => acym_translation('ACYM_DELETE'),
                'setActive' => acym_translation('ACYM_ENABLE'),
                'setInactive' => acym_translation('ACYM_DISABLE'),
            ];
            echo acym_listingActions($actions);
            ?>
		</div>
		<div class="cell grid-x">
			<div class="auto cell acym_vcenter">
                <?php
                $options = [
                    '' => ['ACYM_ALL', $data['userNumberPerStatus']['all']],
                    'active' => ['ACYM_ACTIVE', $data['userNumberPerStatus']['active']],
                    'inactive' => ['ACYM_INACTIVE', $data['userNumberPerStatus']['inactive']],
                    'confirmed' => ['ACYM_CONFIRMED', $data['userNumberPerStatus']['confirmed']],
                    'unconfirmed' => ['ACYM_NOT_CONFIRMED', $data['userNumberPerStatus']['unconfirmed']],
                ];
                echo acym_filterStatus($options, $data['status'], 'users_status');
                ?>
			</div>
			<div class="cell acym_listing_sort-by auto">
                <?php echo acym_sortBy(
                    [
                        'id' => acym_strtolower(acym_translation('ACYM_ID')),
                        'email' => acym_translation('ACYM_EMAIL'),
                        'name' => acym_translation('ACYM_NAME'),
                        'creation_date' => acym_translation('ACYM_DATE_CREATED'),
                        'active' => acym_translation('ACYM_ACTIVE'),
                        'confirmed' => acym_translation('ACYM_CONFIRMED'),
                    ],
                    'users',
                    $data['ordering']
                ); ?>
			</div>
		</div>
	</div>
	<div class="grid-x acym__listing">
		<div class="grid-x cell acym__listing__header">
			<div class="medium-shrink small-1 cell">
				<input id="checkbox_all" type="checkbox" name="checkbox_all">
			</div>
			<div class="grid-x medium-auto small-11 cell acym__listing__header__title__container">
				<div class="cell medium-4 small-7 xlarge-3 acym__listing__header__title">
                    <?php echo acym_translation('ACYM_EMAIL'); ?>
				</div>
				<div class="cell hide-for-small-only hide-for-medium-only large-2 acym__listing__header__title">
                    <?php echo acym_translation('ACYM_NAME'); ?>
				</div>
				<div class="cell hide-for-small-only hide-for-medium-only large-2 xlarge-1 acym__listing__header__title">
                    <?php echo acym_translation('ACYM_DATE_CREATED'); ?>
				</div>
                <?php
                if (!empty($data['fields'])) {
                    foreach ($data['fields'] as $field) {
                        ?>
						<div class="cell medium-auto hide-for-small-only acym__listing__header__title">
                            <?php echo acym_escape(acym_translation($field)); ?>
						</div>
                        <?php
                    }
                }
                ?>
				<div class="cell medium-auto hide-for-small-only acym__listing__header__title">
                    <?php echo acym_translation('ACYM_LISTS'); ?>
				</div>
                <?php if (acym_isAdmin()) { ?>
					<div class="cell medium-1 hide-for-small-only acym__listing__header__title">
                        <?php echo acym_translationSprintf('ACYM_CMS_USER', ACYM_CMS_TITLE); ?>
					</div>
                <?php } ?>
				<div class="cell medium-1 small-5 small-text-right medium-text-center acym__listing__header__title">
                    <?php echo acym_translation('ACYM_STATUS'); ?>
				</div>
				<div class="cell medium-shrink hide-for-small-only text-center acym__listing__header__title acym__listing__id">
                    <?php echo acym_translation('ACYM_ID'); ?>
				</div>
			</div>
		</div>
        <?php
        foreach ($data['allUsers'] as $user) {
            ?>
			<div class="grid-x cell acym__listing__row">
				<div class="medium-shrink small-1 cell">
					<input id="checkbox_<?php echo acym_escape($user->id); ?>" type="checkbox" name="elements_checked[]" value="<?php echo acym_escape($user->id); ?>">
				</div>
				<div class="grid-x medium-auto small-11 cell acym__listing__title__container">
					<div class="grid-x cell small-9 medium-4 xlarge-3 acym__listing__title">
						<a class="cell auto" href="<?php echo acym_completeLink(acym_getVar('cmd', 'ctrl').'&task=edit&id='.$user->id); ?>">
							<div><?php echo acym_escape($user->email); ?></div>
						</a>
					</div>
					<div class="cell hide-for-small-only hide-for-medium-only large-2">
                        <?php echo acym_escape($user->name); ?>
					</div>
					<div class="cell hide-for-small-only hide-for-medium-only large-2 xlarge-1">
                        <?php
                        echo acym_tooltip(
                            acym_date(
                                $user->creation_date,
                                acym_translation('ACYM_DATE_FORMAT_LC5'),
                                false
                            ),
                            $user->creation_date
                        );
                        ?>
					</div>
                    <?php
                    if (!empty($user->fields)) {
                        foreach ($user->fields as $field) {
                            ?>
							<div class="medium-auto hide-for-small-only cell">
                                <?php echo acym_escape($field); ?>
							</div>
                            <?php
                        }
                    }
                    ?>
					<div class="acym__users__subscription medium-auto small-11 cell">
                        <?php
                        if (!empty($data['usersSubscriptions'][$user->id])) {
                            $subscriptionsCount = count($data['usersSubscriptions'][$user->id]);
                            $counter = 0;
                            foreach ($data['usersSubscriptions'][$user->id] as $oneSub) {
                                $classes = 'acym_subscription ';
                                $classes .= intval($oneSub->status) === 1 ? 'acymicon-circle' : 'acymicon-radio_button_unchecked';
                                if ($counter >= 5 && $subscriptionsCount !== 6) $classes .= ' acym_subscription_more';

                                echo acym_tooltip(
                                    '<i class="'.$classes.'" style="color:'.acym_escape($oneSub->color).'"></i>',
                                    acym_translationSprintf(
                                        intval($oneSub->status) === 1 ? 'ACYM_SUBSCRIBED_TO_LIST' : 'ACYM_UNSUBSCRIBED_FROM_LIST',
                                        acym_escape($oneSub->name)
                                    )
                                );
                                $counter++;
                            }

                            if ($counter > 5 && $subscriptionsCount !== 6) {
                                $counter = $counter - 5;
                                echo '<span class="acym__user__show-subscription acymicon-stack" data-iscollapsed="0" acym-data-value="'.$counter.'">
										<i class="acym__user__button__showsubscription acymicon-circle acymicon-stack-2x"></i>
										<span class="acym__listing__text acym__user__show-subscription-bt acymicon-stack-1x">+'.$counter.'</span>
									</span>';
                            }
                        }
                        ?>
					</div>
                    <?php if (acym_isAdmin()) { ?>
						<div class="cell hide-for-small-only medium-1">
                            <?php
                            if (empty($user->cms_id)) {
                                echo '-';
                            } else {
                                echo '<a href="'.acym_getCmsUserEdit($user->cms_id).'" target="_blank">';
                                echo $user->cms_username.'<br />';
                                echo acym_translation('ACYM_ID').': '.$user->cms_id;
                                echo '</a>';
                            }
                            ?>
						</div>
                    <?php } ?>
					<div class="acym__listing__controls acym__users__controls small-1 text-center cell">
                        <?php
                        if ($user->active == 1) {
                            $class = 'acymicon-check-circle acym__color__green" data-acy-newvalue="0';
                            $tooltip = 'ACYM_ACTIVATED';
                        } else {
                            $class = 'acymicon-times-circle acym__color__red" data-acy-newvalue="1';
                            $tooltip = 'ACYM_DEACTIVATED';
                        }
                        echo acym_tooltip(
                            '<i data-acy-table="user" data-acy-field="active" data-acy-elementid="'.acym_escape($user->id).'" class="acym_toggleable '.$class.'"></i>',
                            acym_translation($tooltip)
                        );

                        if ($this->config->get('require_confirmation', '0') == '1') { ?>
                            <?php
                            if ($user->confirmed == 1) {
                                $class = 'acymicon-check-circle acym__color__green" data-acy-newvalue="0';
                                $tooltip = 'ACYM_CONFIRMED';
                            } else {
                                $class = 'acymicon-times-circle acym__color__red" data-acy-newvalue="1';
                                $tooltip = 'ACYM_NOT_CONFIRMED';
                            }
                            echo acym_tooltip(
                                '<i data-acy-table="user" data-acy-field="confirmed" data-acy-elementid="'.acym_escape($user->id).'" class="acym_toggleable '.$class.'"></i>',
                                acym_translation($tooltip),
                                'secondary_status'
                            );
                        }
                        ?>
					</div>
					<div class="text-center medium-shrink hide-for-small-only acym__listing__text acym__listing__id"><?php echo acym_escape($user->id); ?></div>
				</div>
			</div>
            <?php
        }
        ?>
	</div>
    <?php
    echo $data['pagination']->display('users');
}
