<?php if (empty($data['allCampaigns'])) { ?>
	<h1 class="cell acym__listing__empty__search__title text-center"><?php echo acym_translation('ACYM_NO_RESULTS_FOUND'); ?></h1>
<?php } else { ?>
	<div class="cell margin-bottom-1 acym__listing__actions grid-x">
        <?php
        echo acym_listingActions(
            [
                'deleteFollowup' => acym_translation('ACYM_DELETE'),
            ]
        );
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
                        'active' => acym_translation('ACYM_ACTIVE'),
                    ],
                    $data['campaign_type'],
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
				<div class="cell auto acym__listing__header__title">
                    <?php echo acym_translation('ACYM_FOLLOW_UP'); ?>
				</div>
				<div class="cell small-3 xlarge-2 acym__listing__header__title">
                    <?php echo acym_translation('ACYM_EMAILS'); ?>
				</div>
				<div class="cell show-for-large large-3 xxlarge-2 acym__listing__header__title text-center">
                    <?php echo acym_translation('ACYM_STATUS'); ?>
				</div>
				<div class="cell show-for-large large-1 acym__listing__header__title text-center">
                    <?php echo acym_translation('ACYM_OPEN'); ?>
				</div>
				<div class="cell show-for-large large-1 acym__listing__header__title text-center">
                    <?php echo acym_translation('ACYM_CLICK'); ?>
				</div>
                <?php if (acym_isTrackingSalesActive()) { ?>
					<div class="cell show-for-xlarge xlarge-1 acym__listing__header__title text-center">
                        <?php echo acym_translation('ACYM_INCOME'); ?>
					</div>
                <?php } ?>
				<div class="cell small-2 large-1 acym__listing__header__title text-center">
                    <?php echo acym_translation('ACYM_ACTIVE'); ?>
				</div>
				<div class="cell show-for-large large-1 acym__listing__header__title text-center">
                    <?php echo acym_translation('ACYM_ID'); ?>
				</div>
			</div>
		</div>
        <?php
        foreach ($data['allCampaigns'] as $followup) {
            ?>
			<div class="grid-x cell acym__listing__row">
				<div class="medium-shrink small-1 cell">
					<input id="checkbox_<?php echo acym_escape($followup->id); ?>" type="checkbox" name="elements_checked[]" value="<?php echo acym_escape($followup->id); ?>">
				</div>
				<div class="grid-x medium-auto small-11 cell acym__followup__listing acym__listing__title__container">
					<div class="cell auto grid-x acym__listing__title acym__followup__title">
						<a class="cell shrink" href="<?php echo acym_completeLink(acym_getVar('cmd', 'ctrl').'&task=edit&step=followupEmail&id='.intval($followup->id)); ?>">
							<h6 class="acym_text_ellipsis">
                                <?php echo empty($followup->name) ? '<i class="acym__color__orange acym__followup__listing__unnamed__icon acymicon-exclamation-triangle"></i> '.acym_translation(
                                        'ACYM_UNNAMED_FOLLOWUP'
                                    ) : acym_escape($followup->name); ?>
							</h6>
						</a>
						<div class="cell shrink">
                            <?php
                            if (empty($data['allTriggers'][$followup->trigger])) {
                                $details = [$followup->trigger];
                            } else {
                                $details = [$data['allTriggers'][$followup->trigger]];
                            }
                            $details[] = '';
                            if (!empty($followup->condition)) {
                                if (is_array($followup->condition)) {
                                    foreach ($followup->condition as $oneCondition) {
                                        $details[] = $oneCondition;
                                    }
                                } else {
                                    $details[] = acym_translation('ACYM_MISSING_ADDON');
                                }
                            }
                            echo acym_info(
                                implode('<br />', $details),
                                '',
                                'acym__tooltip__listing'
                            );
                            ?>
						</div>
					</div>
					<div class="cell small-3 xlarge-2 grid-x">
						<div class="cell align-center acym__followup__emails__count acym__followup_listing_main_element">
                            <?php
                            echo '<span>'.acym_escape(acym_translationSprintf('ACYM_X_EMAILS', $followup->nbEmails)).'</span>';
                            if (!empty($followup->nbEmails)) {
                                ?>
								<i class="acym__followup__emails__toggle acymicon-keyboard_arrow_down" acym-data-id="<?php echo intval($followup->id); ?>"></i>
                                <?php
                            }
                            ?>
						</div>
						<div class="cell acym__followup__emails__listing__subject grid-x">

						</div>
					</div>
					<div class="cell show-for-large large-3 xxlarge-2 acym__campaign__status text-center grid-x">
						<div class="cell acym__campaign__status__status acym__background-color__green acym__followup_listing_main_element">
                            <?php
                            echo acym_translationSprintf(
                                'ACYM_TRIGGERED_FOR_X',
                                empty($followup->subscribers) ? '0' : $followup->subscribers
                            );
                            ?>
						</div>
						<div class="cell acym__followup__emails__listing__status grid-x">

						</div>
					</div>
					<div class="cell show-for-large large-1 text-center grid-x">
						<div class="cell acym__followup_listing_main_element">
                            <?php
                            if (!empty($followup->subscribers) && isset($followup->open)) {
                                echo $followup->open.'%';
                            } else {
                                echo '-';
                            }
                            ?>
						</div>
						<div class="cell acym__followup__emails__listing__open grid-x">

						</div>
					</div>
					<div class="cell show-for-large large-1 text-center grid-x">
						<div class="cell acym__followup_listing_main_element">
                            <?php
                            if (!empty($followup->subscribers) && isset($followup->click)) {
                                echo $followup->click.'%';
                            } else {
                                echo '-';
                            }
                            ?>
						</div>
						<div class="cell acym__followup__emails__listing__click grid-x">

						</div>
					</div>
                    <?php if (acym_isTrackingSalesActive()) { ?>
						<div class="cell show-for-xlarge xlarge-1 text-center grid-x">
							<div class="cell acym__followup_listing_main_element">
                                <?php
                                if (!empty($followup->sale) && !empty($followup->currency)) {
                                    echo round($followup->sale, 2).' '.$followup->currency;
                                } else {
                                    echo '-';
                                }
                                ?>
							</div>
							<div class="cell acym__followup__emails__listing__income grid-x">

							</div>
						</div>
                    <?php } ?>
					<div class="cell small-2 large-1 text-center">
                        <?php
                        $class = $followup->active == 1 ? 'acymicon-check-circle acym__color__green" data-acy-newvalue="0' : 'acymicon-times-circle acym__color__red" data-acy-newvalue="1';
                        echo '<i data-acy-table="followup" data-acy-field="active" data-acy-elementid="'.acym_escape($followup->id).'" class="acym_toggleable '.$class.'"></i>';
                        ?>
					</div>
					<div class="cell show-for-large large-1 text-center acym__listing__text">
                        <?php echo acym_escape($followup->id); ?>
					</div>
				</div>
			</div>
            <?php
        }
        ?>
	</div>
    <?php echo $data['pagination']->display('campaigns');
}
