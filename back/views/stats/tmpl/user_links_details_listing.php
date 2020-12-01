<div class="acym__content acym__stats" id="acym_stats_user_links_details">
    <?php
    if (empty($data['user_links_details'])) { ?>
		<div class="cell grid-x">
			<div class="cell grid-x auto">
				<div class="cell grid-x auto">
					<div class="medium-5 small-12 cell acym_stats_detailed_search">
                        <?php echo acym_filterSearch($data['search'], 'user_links_details_search', 'ACYM_SEARCH'); ?>
					</div>
					<div class="medium-4 small-12 cell acym__stats__campaign-choose">
					</div>
				</div>
			</div>
			<div class="grid-x cell align-right auto">
				<div class="cell acym_listing_sort-by">
                    <?php echo acym_sortBy(
                        [
                            'user_id' => acym_translation('ACYM_USER_ID'),
                            'url_id' => acym_translation('ACYM_URL_ID'),
                        ],
                        "user_links_details",
                        $data['ordering']
                    ); ?>
				</div>
			</div>
		</div>
		<h1 class="acym__listing__empty__title text-center cell"><?php echo acym_translation('ACYM_NO_DETAILED_STATS'); ?></h1>
    <?php } else { ?>
		<div class="cell grid-x">
			<div class="cell grid-x">
				<div class="cell grid-x auto">
					<div class="medium-5 small-12 cell acym_stats_detailed_search">
                        <?php echo acym_filterSearch($data['search'], 'user_links_details_search', 'ACYM_SEARCH'); ?>
					</div>
					<div class="medium-4 small-12 cell acym__stats__campaign-choose">
					</div>
				</div>
				<div class="cell auto align-right grid-x">
					<button type="button"
							class="cell shrink button primary acy_button_submit acym__stats__export__button "
							data-task="exportUserLinksDetails"><?php echo acym_translation(
                            'ACYM_EXPORT'
                        ); ?></button>
				</div>
			</div>
			<div class="grid-x cell align-right">
				<div class="cell acym_listing_sort-by">
                    <?php echo acym_sortBy(
                        [
                            'user_id' => acym_translation('ACYM_USER_ID'),
                            'url_id' => acym_translation('ACYM_URL_ID'),
                            'email' => acym_translation('ACYM_EMAIL'),
                            'user_name' => acym_translation('ACYM_USER_NAME'),
                            'url_name' => acym_translation('ACYM_URL'),
                            'date_click' => acym_translation('ACYM_CLICK_DATE'),
                            'click' => acym_translation('ACYM_TOTAL_CLICKS'),
                        ],
                        "user_links_details",
                        $data['ordering']
                    ); ?>
				</div>
			</div>
		</div>
		<div class="grid-x acym__listing cell">
			<div class="grid-x cell acym__listing__header">
				<div class="grid-x medium-auto small-11 cell">
					<div class="auto cell acym__listing__header__title">
                        <?php echo acym_translation('ACYM_USER'); ?>
					</div>
					<div class="auto cell acym__listing__header__title hide-for-small-only">
                        <?php echo acym_translation('ACYM_URL'); ?>
					</div>
					<div class="large-2 medium-3 cell acym__listing__header__title text-center hide-for-small-only acym__listing__user_links_details__click-date">
                        <?php echo acym_translation('ACYM_CLICK_DATE'); ?>
					</div>
					<div class="large-2 medium-3 cell acym__listing__header__title text-center hide-for-small-only">
                        <?php echo acym_translation('ACYM_TOTAL_CLICKS'); ?>
					</div>
				</div>
			</div>
            <?php
            foreach ($data['user_links_details'] as $userLinkDetails) { ?>
				<div class="grid-x cell acym__listing__row">
					<div class="grid-x medium-auto small-11 cell">
						<div class="medium-auto small-12 cell acym__listing__user_links_details__content acym_text_ellipsis">
                            <?php
                            $link = acym_completeLink('users&task=edit&id='.$userLinkDetails->user_id);
                            echo '<a href="'.$link.'">'.$userLinkDetails->email.' - '.$userLinkDetails->user_name.'</a>';
                            ?>
						</div>
						<div class="medium-auto small-12 cell acym__listing__user_links_details__content">
                            <?php
                            echo $userLinkDetails->url_name.'<a href="'.$userLinkDetails->url_name.'" class="acym__listing__user_links_details__content__links" target="_blank"><i class="acymicon-external-link"></i></a>';
                            ?>
						</div>
						<div class="large-2 medium-3 hide-for-small-only cell acym__listing__user_links_details__content acym__listing__user_links_details__click-date text-center">
                            <?php
                            echo empty($userLinkDetails->date_click) ? '-' : acym_date($userLinkDetails->date_click, 'Y-m-d H:i:s');
                            ?>
						</div>
						<div class="large-2 medium-3 small-4 cell acym__listing__user_links_details__content text-center">
                            <?php
                            echo empty($userLinkDetails->click) ? 0 : $userLinkDetails->click;
                            ?>
						</div>
					</div>
				</div>
                <?php
            }
            ?>
		</div>
        <?php
        echo $data['pagination']->display('user_links_details');
    } ?>
</div>
<?php
acym_formOptions();
