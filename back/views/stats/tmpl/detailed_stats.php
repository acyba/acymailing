<div class="acym__content acym__stats" id="acym_stats_detailed">
    <?php if (!empty($data['emptyDetailed']) && $data['emptyDetailed'] == 'campaigns') { ?>
		<h1 class="acym__listing__empty__title text-center cell"><?php echo acym_translation('ACYM_DONT_HAVE_STATS_CAMPAIGN'); ?> <a href="<?php echo acym_completeLink('campaigns&task=edit&step=chooseTemplate'); ?>"><?php echo acym_translation('ACYM_CREATE_ONE'); ?></a></h1>
    <?php } elseif (!empty($data['emptyDetailed']) && $data['emptyDetailed'] == 'stats') { ?>
		<h1 class="acym__listing__empty__title text-center cell"><?php echo acym_translation('ACYM_DONT_HAVE_STATS_THIS_CAMPAIGN'); ?></a></h1>
    <?php } elseif (empty($data['detailed_stats'])) { ?>
		<h1 class="acym__listing__empty__title text-center cell"><?php echo acym_translation('ACYM_NO_DETAILED_STATS'); ?></a></h1>
    <?php } else { ?>
		<div class="cell grid-x">
			<div class="cell grid-x">
				<div class="cell grid-x auto">
					<div class="medium-5 small-12 cell acym_stats_detailed_search">
                        <?php echo acym_filterSearch($data['search'], 'detailed_stats_search', 'ACYM_SEARCH'); ?>
					</div>
					<div class="medium-4 small-12 cell acym__stats__campaign-choose">
					</div>
				</div>
				<div class="cell auto align-right grid-x">
					<button type="button" class="cell shrink button primary acy_button_submit acym__stats__export__button " data-task="exportDetailed"><?php echo acym_translation('ACYM_EXPORT'); ?></button>
				</div>
			</div>
			<div class="grid-x cell align-right">
				<div class="cell acym_listing_sort-by">
                    <?php echo acym_sortBy(
                        [
                            'send_date' => acym_translation('ACYM_SEND_DATE'),
                            'subject' => acym_translation('ACYM_NAME'),
                            'email' => acym_translation('ACYM_EMAIL'),
                            'open' => acym_translation('ACYM_MAILS_OPEN'),
                            'open_date' => acym_translation('ACYM_OPEN_DATE'),
                            'sent' => acym_translation('ACYM_SENT'),
                        ],
                        "detailed_stats",
                        $data['ordering']
                    ); ?>
				</div>
			</div>
		</div>
		<div class="grid-x acym__listing cell">
			<div class="grid-x cell acym__listing__header">
				<div class="grid-x medium-auto small-11 cell">
					<div class="large-2 medium-3 small-3 cell acym__listing__header__title">
                        <?php echo acym_translation('ACYM_SEND_DATE'); ?>
					</div>
					<div class="large-2 medium-3 small-3 cell acym__listing__header__title">
                        <?php echo acym_translation('ACYM_NAME'); ?>
					</div>
					<div class="large-2 medium-3 small-4 cell acym__listing__header__title">
                        <?php echo acym_translation('ACYM_USER'); ?>
					</div>
					<div class="large-1 medium-1 small-1 cell acym__listing__header__title text-center">
                        <?php echo acym_translation('ACYM_TOTAL_CLICK'); ?>
					</div>
					<div class="large-1 medium-1 small-1 cell acym__listing__header__title text-center">
                        <?php echo acym_translation('ACYM_OPENED'); ?>
					</div>
					<div class="large-1 hide-for-small-only hide-for-medium-only cell acym__listing__header__title text-center">
                        <?php echo acym_translation('ACYM_OPEN_DATE'); ?>
					</div>
					<div class="large-1 hide-for-small-only hide-for-medium-only cell acym__listing__header__title text-center">
                        <?php echo acym_translation('ACYM_BOUNCES'); ?>
					</div>
                    <?php if (acym_isTrackingSalesActive()) { ?>
						<div class="large-1 hide-for-small-only hide-for-medium-only cell acym__listing__header__title text-center">
                            <?php echo acym_translation('ACYM_INCOME'); ?>
						</div>
                    <?php } ?>
					<div class="large-1 medium-1 small-1 cell acym__listing__header__title text-center">
                        <?php echo acym_translation('ACYM_SENT'); ?>
					</div>
				</div>
			</div>
            <?php
            foreach ($data['detailed_stats'] as $detailed_stat) { ?>
				<div class="grid-x cell acym__listing__row">
					<div class="grid-x medium-auto small-11 cell">
						<div class="large-2 medium-3 small-3 cell acym__listing__detailed__stats__content">
                            <?php
                            echo acym_tooltip('<p>'.acym_date(acym_getTime($detailed_stat->send_date), 'd F H:i').'</p>', acym_date(acym_getTime($detailed_stat->send_date), 'd F Y H:i:s'));
                            ?>
						</div>
						<div class="large-2 medium-3 small-3 cell acym__listing__detailed__stats__content">
                            <?php
                            if (!empty($detailed_stat->campaign_id) && acym_isAllowed('campaigns')) {
                                if (empty($detailed_stat->parent_id)) {
                                    $link = acym_completeLink('campaigns&task=edit&step=editEmail&id='.$detailed_stat->campaign_id);
                                } else {
                                    $link = acym_completeLink('campaigns&task=summaryGenerated&id='.$detailed_stat->campaign_id);
                                }

                                $name = '<a href="'.$link.'" class="word-break acym__color__blue">'.$detailed_stat->name.'</a>';
                            } else {
                                $name = $detailed_stat->name;
                            }
                            echo acym_tooltip($name, acym_translation('ACYM_EMAIL_SUBJECT').' : '.$detailed_stat->subject);

                            ?>
						</div>
						<div class="large-2 medium-3 small-4 cell acym__listing__detailed__stats__content">
                            <?php if (acym_isAllowed('users')) { ?>
								<a href="<?php echo acym_completeLink('users&task=edit&id='.$detailed_stat->user_id); ?>" class="acym__color__blue word-break"><?php echo $detailed_stat->email; ?></a>
                            <?php } else { ?>
                                <?php echo $detailed_stat->email; ?>
                            <?php } ?>
						</div>
						<div class="large-1 medium-1 small-1 cell acym__listing__detailed__stats__content text-center">
							<p class="hide-for-medium-only hide-for-small-only"><?php echo empty($detailed_stat->total_click) ? 0 : $detailed_stat->total_click; ?></p>
						</div>
						<div class="large-1 medium-1 small-1 cell acym__listing__detailed__stats__content text-center">
							<p class="hide-for-medium-only hide-for-small-only"><?php echo $detailed_stat->open; ?></p>
						</div>
						<div class="large-1 hide-for-small-only hide-for-medium-only cell acym__listing__detailed__stats__content text-center">
                            <?php
                            echo empty($detailed_stat->open_date) ? '' : acym_tooltip('<p>'.acym_date(acym_getTime($detailed_stat->open_date), 'd F H:i').'</p>', acym_date(acym_getTime($detailed_stat->open_date), 'd F Y H:i:s')); ?>
						</div>
						<div class="large-1 hide-for-small-only hide-for-medium-only cell acym__listing__detailed__stats__content text-center">
                            <?php
                            //echo $detailed_stat->bounce
                            echo empty($detailed_stat->bounce) ? $detailed_stat->bounce : acym_tooltip($detailed_stat->bounce, $detailed_stat->bounce_rule);
                            ?>
						</div>
                        <?php if (acym_isTrackingSalesActive()) { ?>
							<div class="large-1 hide-for-small-only hide-for-medium-only cell acym__listing__detailed__stats__content text-center">
                                <?php
                                if (!empty($detailed_stat->sales) && !empty($detailed_stat->currency)) {
                                    echo round($detailed_stat->sales, 2).' '.$detailed_stat->currency;
                                } else {
                                    echo '-';
                                }
                                ?>
							</div>
                        <?php } ?>
						<div class="large-1 medium-1  small-1 cell acym__listing__detailed__stats__content text-center cursor-default">
                            <?php
                            $targetSuccess = '<i class="acymicon-check acym__listing__detailed_stats_sent__success" ></i>';
                            $targetFail = '<i class="acymicon-times acym__listing__detailed_stats_sent__fail" ></i>';
                            echo acym_tooltip(empty($detailed_stat->fail) ? $targetSuccess : $targetFail, acym_translation('ACYM_SENT').' : '.$detailed_stat->sent.' '.acym_translation('ACYM_FAIL').' : '.$detailed_stat->fail);
                            ?>
						</div>
					</div>
				</div>
                <?php
            }
            ?>
		</div>
        <?php
        echo $data['pagination']->display('detailed_stats');
    } ?>
</div>
<?php
acym_formOptions();
