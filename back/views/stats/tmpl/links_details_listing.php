<div class="acym__content acym__stats" id="acym_stats_links_details">
    <?php
    if (empty($data['links_details'])) { ?>
		<div class="cell grid-x">
			<div class="cell grid-x auto">
				<div class="cell grid-x auto">
					<div class="medium-5 small-12 cell acym_stats_detailed_search">
                        <?php echo acym_filterSearch($data['search'], 'links_details_search', 'ACYM_SEARCH'); ?>
					</div>
					<div class="medium-4 small-12 cell acym__stats__campaign-choose">
					</div>
				</div>
			</div>
			<div class="grid-x cell align-right auto">
				<div class="cell acym_listing_sort-by">
                    <?php echo acym_sortBy(
                        [
                            'id' => acym_translation('ACYM_ID'),
                            'name' => acym_translation('ACYM_URL'),
                            'total_click' => acym_translation('ACYM_TOTAL_CLICKS'),
                            'unique_click' => acym_translation('ACYM_UNIQUE_CLICKS'),
                        ],
                        'links_details',
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
                        <?php echo acym_filterSearch($data['search'], 'links_details_search', 'ACYM_SEARCH'); ?>
					</div>
					<div class="medium-4 small-12 cell acym__stats__campaign-choose">
					</div>
				</div>
				<div class="cell auto align-right grid-x">
					<button type="button"
							class="cell shrink button primary acy_button_submit acym__stats__export__button "
							data-task="exportLinksDetails"><?php echo acym_translation(
                            'ACYM_EXPORT'
                        ); ?></button>
				</div>
			</div>
			<div class="grid-x cell align-right">
				<div class="cell acym_listing_sort-by">
                    <?php echo acym_sortBy(
                        [
                            'id' => acym_translation('ACYM_ID'),
                            'name' => acym_translation('ACYM_URL'),
                            'total_click' => acym_translation('ACYM_TOTAL_CLICKS'),
                            'unique_click' => acym_translation('ACYM_UNIQUE_CLICKS'),
                        ],
                        'links_details',
                        $data['ordering']
                    ); ?>
				</div>
			</div>
		</div>
		<div class="grid-x acym__listing cell">
			<div class="grid-x cell acym__listing__header">
				<div class="grid-x medium-auto small-11 cell">
					<div class="auto cell acym__listing__header__title">
                        <?php echo acym_translation('ACYM_URL'); ?>
					</div>
					<div class="large-2 medium-3 small-4 cell acym__listing__header__title text-center">
                        <?php echo acym_translation('ACYM_UNIQUE_CLICKS'); ?>
					</div>
					<div class="large-2 medium-3 small-4 cell acym__listing__header__title text-center">
                        <?php echo acym_translation('ACYM_TOTAL_CLICKS'); ?>
					</div>
				</div>
			</div>
            <?php
            foreach ($data['links_details'] as $linkDetails) { ?>
				<div class="grid-x cell acym__listing__row">
					<div class="grid-x medium-auto small-11 cell">
						<div class="auto cell acym__listing__links_details__content">
                            <?php
                            echo $linkDetails->name.'<a href="'.$linkDetails->name.'" class="acym__listing__links_details__content__links" target="_blank"><i class="acymicon-external-link"></i></a>';
                            echo '<a href="'.acym_completeLink(
                                    'stats&task=userClickDetails&user_links_details_search='.urlencode(base64_encode($linkDetails->name))
                                ).'" class="acym__listing__links_details__content__links" target="_blank"><i class="acymicon-bar-chart"></i></a>';
                            ?>
						</div>
						<div class="large-2 medium-3 small-4 cell acym__listing__links_details__content text-center">
                            <?php
                            echo empty($linkDetails->unique_click) ? 0 : $linkDetails->unique_click;
                            ?>
						</div>
						<div class="large-2 medium-3 small-4 cell acym__listing__links_details__content text-center">
                            <?php
                            echo empty($linkDetails->total_click) ? 0 : $linkDetails->total_click;
                            ?>
						</div>
					</div>
				</div>
                <?php
            }
            ?>
		</div>
        <?php
        echo $data['pagination']->display('links_details');
    } ?>
</div>
<?php
acym_formOptions();
