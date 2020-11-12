<?php if (empty($data['allCampaigns'])) { ?>
	<h1 class="cell acym__listing__empty__search__title text-center"><?php echo acym_translation('ACYM_NO_RESULTS_FOUND'); ?></h1>
<?php } else { ?>
	<div class="cell margin-bottom-1 acym__listing__actions grid-x">
        <?php
        $actions = ['delete' => acym_translation('ACYM_DELETE')];
        echo acym_listingActions($actions, '', 'mails');
        ?>
	</div>
	<div class="cell grid-x">
		<div class="grid-x cell auto">
			<div class="cell acym_listing_sort-by">
                <?php echo acym_sortBy(
                    [
                        'id' => acym_strtolower(acym_translation('ACYM_ID')),
                        'name' => acym_translation('ACYM_NAME'),
                        'creation_date' => acym_translation('ACYM_DATE_CREATED'),
                    ],
                    $data['email_type'],
                    $data['ordering'],
                    $data['orderingSortOrder']
                ); ?>
			</div>
		</div>
	</div>
	<div class="grid-x acym__listing">
		<div class="grid-x cell acym__listing__header">
			<div class="medium-shrink small-1 cell">
				<input id="checkbox_all" type="checkbox">
			</div>
			<div class="grid-x medium-auto small-11 cell acym__listing__header__title__container">
				<div class="medium-auto small-11 cell acym__listing__header__title">
                    <?php echo acym_translation('ACYM_EMAILS'); ?>
				</div>
				<div class="large-3 medium-3 hide-for-small-only cell acym__listing__header__title">
                    <?php echo acym_translation('ACYM_LIST'); ?>
				</div>
				<div class="large-1 hide-for-small-only hide-for-medium-only text-center cell acym__listing__header__title">
                    <?php echo acym_translation('ACYM_OPEN'); ?>
				</div>
				<div class="large-1 hide-for-small-only hide-for-medium-only text-center cell acym__listing__header__title">
                    <?php echo acym_translation('ACYM_CLICK'); ?>
				</div>
				<div class="large-1 cell hide-for-small-only hide-for-medium-only text-center acym__listing__header__title">
                    <?php echo acym_translation('ACYM_ID'); ?>
				</div>
			</div>
		</div>
        <?php
        foreach ($data['allCampaigns'] as $email) {
            if (isset($email->display) && !$email->display) continue;
            ?>
			<div class="grid-x cell acym__listing__row">
				<div class="medium-shrink small-1 cell">
					<input id="checkbox_<?php echo acym_escape($email->id); ?>" type="checkbox" name="elements_checked[]" value="<?php echo acym_escape($email->id); ?>">
				</div>
				<div class="grid-x medium-auto small-11 cell acym__campaign__listing acym__listing__title__container">
					<div class="cell medium-auto small-7 acym__listing__title acym__campaign__title">
                        <?php
                        $isFront = !acym_isAdmin() && ACYM_CMS == 'joomla';
                        $controller = $isFront ? 'frontmails' : 'mails';
                        $linkTask = 'edit&step=editEmail&type=welcome&type_editor=acyEditor';
                        $returnLink = acym_getVar('cmd', 'ctrl').'&task='.acym_getVar('cmd', 'task');
                        $return = '&return='.urlencode(base64_encode($isFront ? acym_frontendLink($returnLink) : acym_completeLink($returnLink))); ?>
						<a class="cell auto" href="<?php echo acym_completeLink($controller.'&task='.$linkTask.'&id='.intval($email->id).$return); ?>">
							<h6 class='acym__listing__title__primary acym_text_ellipsis'>
                                <?php echo acym_escape($email->name); ?>
							</h6>
						</a>
						<p class='acym__listing__title__secondary'>
                            <?php
                            echo acym_date(acym_getTime($email->creation_date), 'Y-m-d H:i:s');
                            ?>
						</p>
					</div>
					<div class="large-3 medium-3 small-5 cell">
                        <?php
                        if (!empty($email->lists)) {
                            echo '<div class="grid-x cell text-center">';
                            foreach ($email->lists as $list) {
                                echo acym_tooltip('<i class="acym_subscription acymicon-circle" style="color:'.acym_escape($list->color).'"></i>', acym_escape($list->name));
                            }
                            echo '</div>';
                        } else {
                            echo '<div class="cell medium-12">'.(empty($email->automation)
                                    ? acym_translation('ACYM_NO_LIST_SELECTED')
                                    : acym_translation(
                                        'ACYM_SENT_WITH_AUTOMATION'
                                    )).'</div>';
                        }
                        ?>
					</div>
					<div class="large-1 hide-for-small-only hide-for-medium-only cell text-center">
                        <?php
                        if (!empty($email->subscribers) && isset($email->open)) {
                            echo $email->open.'%';
                        } else {
                            echo '-';
                        }
                        ?>
					</div>
					<div class="large-1 hide-for-small-only hide-for-medium-only cell text-center">
                        <?php
                        if (!empty($email->subscribers) && isset($email->click)) {
                            echo $email->click.'%';
                        } else {
                            echo '-';
                        }
                        ?>
					</div>
					<h6 class="large-1 hide-for-medium-only hide-for-small-only cell text-center acym__listing__text"><?php echo acym_escape($email->id); ?></h6>
				</div>
			</div>
            <?php
        }
        ?>
	</div>
    <?php echo $data['pagination']->display('campaigns');
}
