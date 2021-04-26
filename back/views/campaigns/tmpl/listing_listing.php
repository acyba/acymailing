<?php if (empty($data['allCampaigns'])) { ?>
	<h1 class="cell acym__listing__empty__search__title text-center"><?php echo acym_translation('ACYM_NO_RESULTS_FOUND'); ?></h1>
<?php } else { ?>
	<div class="cell margin-bottom-1 acym__listing__actions grid-x">
        <?php
        $actions = ['delete' => acym_translation('ACYM_DELETE'), 'duplicate' => acym_translation('ACYM_DUPLICATE')];
        echo acym_listingActions($actions);
        ?>
	</div>
	<div class="cell grid-x">
		<div class="grid-x acym__listing__actions cell auto">
			<div class="medium-auto cell">
                <?php
                if ($data['campaign_type'] == 'campaigns_auto') {
                    $options = [
                        '' => ['ACYM_ALL', $data['allStatusFilter']->all],
                        'generated' => [
                            'ACYM_GENERATED',
                            $data['allStatusFilter']->generated,
                            $data['generatedPending'] ? 'pending' : '',
                        ],
                    ];
                } else {
                    $options = [
                        '' => ['ACYM_ALL', $data['allStatusFilter']->all],
                        'scheduled' => ['ACYM_SCHEDULED', $data['allStatusFilter']->scheduled],
                        'sent' => ['ACYM_SENT', $data['allStatusFilter']->sent],
                        'draft' => ['ACYM_DRAFT', $data['allStatusFilter']->draft],
                    ];
                }
                if (isset($data['campaign_type'])) {
                    echo acym_filterStatus($options, $data['status'], $data['campaign_type'].'_status');
                }
                ?>
			</div>
		</div>
		<div class="grid-x cell auto">
			<div class="cell acym_listing_sort-by">
                <?php echo acym_sortBy(
                    [
                        'id' => acym_strtolower(acym_translation('ACYM_ID')),
                        'name' => acym_translation('ACYM_NAME'),
                        'sending_date' => acym_translation('ACYM_SENDING_DATE'),
                        'creation_date' => acym_translation('ACYM_DATE_CREATED'),
                        'draft' => acym_translation('ACYM_DRAFT'),
                        'active' => acym_translation('ACYM_ACTIVE'),
                        'sent' => acym_translation('ACYM_SENT'),
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
				<div class="medium-auto small-11 cell acym__listing__header__title">
                    <?php echo acym_translation('ACYM_CAMPAIGNS'); ?>
				</div>
				<div class="<?php echo $data['campaign_type'] == 'campaigns_auto' ? 'large-1' : 'large-2'; ?> medium-3 hide-for-small-only cell acym__listing__header__title">
                    <?php echo acym_translation('ACYM_LISTS'); ?>
				</div>
				<div class="large-2 medium-4 hide-for-small-only cell acym__listing__header__title text-center">
                    <?php echo acym_translation($data['campaign_type'] == 'campaigns_auto' ? 'ACYM_FREQUENCY' : 'ACYM_STATUS'); ?>
				</div>
                <?php if ($data['campaign_type'] == 'campaigns_auto') { ?>
					<div class="large-1 hide-for-small-only hide-for-medium-only text-center cell acym__listing__header__title acym__campaign__auto__generation">
                        <?php echo acym_translation('ACYM_LAST_GENERATION'); ?>
					</div>
					<div class="large-1 hide-for-small-only hide-for-medium-only text-center cell acym__listing__header__title acym__campaign__auto__generation">
                        <?php echo acym_translation('ACYM_NEXT_TRIGGER'); ?>
					</div>
                <?php } ?>
				<div class="large-1 hide-for-small-only hide-for-medium-only text-center cell acym__listing__header__title">
                    <?php echo acym_translation('ACYM_OPEN'); ?>
				</div>
				<div class="large-1 hide-for-small-only hide-for-medium-only text-center cell acym__listing__header__title">
                    <?php echo acym_translation('ACYM_CLICK'); ?>
				</div>
                <?php if (acym_isTrackingSalesActive()) { ?>
					<div class="large-1 hide-for-small-only hide-for-medium-only text-center cell acym__listing__header__title">
                        <?php echo acym_translation('ACYM_INCOME'); ?>
					</div>
                <?php } ?>
                <?php if (acym_isAdmin()) { ?>
					<div class="large-1 cell hide-for-small-only hide-for-medium-only text-center acym__listing__header__title">
                        <?php echo acym_translation('ACYM_VISIBLE').acym_info('ACYM_VISIBLE_CAMPAIGN_DESC', 'acym__tooltip__in__listing__header'); ?>
					</div>
                <?php } ?>
				<div class="large-1 cell hide-for-small-only hide-for-medium-only text-center acym__listing__header__title">
                    <?php echo acym_translation('ACYM_ID'); ?>
				</div>
			</div>
		</div>
        <?php
        foreach ($data['allCampaigns'] as $campaign) {
            if (isset($campaign->display) && !$campaign->display) continue;
            ?>
			<div class="grid-x cell acym__listing__row">
				<div class="medium-shrink small-1 cell">
					<input id="checkbox_<?php echo acym_escape($campaign->id); ?>" type="checkbox" name="elements_checked[]" value="<?php echo acym_escape($campaign->id); ?>">
				</div>
				<div class="grid-x medium-auto small-11 cell acym__campaign__listing acym__listing__title__container">
					<div class="cell medium-auto small-7 acym__listing__title acym__campaign__title">
                        <?php $linkTask = 'generated' == $data['status'] ? 'summaryGenerated' : 'edit&step=editEmail'; ?>
						<a class="cell auto" href="<?php echo acym_completeLink(acym_getVar('cmd', 'ctrl').'&task='.$linkTask.'&id='.intval($campaign->id)); ?>">
							<h6 class='acym__listing__title__primary acym_text_ellipsis'>
                                <?php echo acym_escape($campaign->name); ?>
							</h6>
						</a>
						<p class='acym__listing__title__secondary'>
                            <?php
                            if (!empty($campaign->sending_date) && (!$campaign->scheduled || $campaign->sent)) {
                                echo acym_translation('ACYM_SENDING_DATE').' : '.acym_date($campaign->sending_date, 'ACYM_DATE_FORMAT_LC3');
                            } elseif ($data['statusAuto'] === $campaign->sending_type) {
                                $numberCampaignsGenerated = empty($campaign->sending_params['number_generated']) ? '0' : $campaign->sending_params['number_generated'];
                                echo acym_translationSprintf('ACYM_X_CAMPAIGN_GENERATED', $numberCampaignsGenerated);
                            }
                            ?>
						</p>
					</div>
					<div class="<?php echo $data['campaign_type'] == 'campaigns_auto' ? 'large-1' : 'large-2'; ?> medium-3 small-5 cell">
                        <?php
                        if (!empty($campaign->lists)) {
                            echo '<div class="grid-x cell text-center">';
                            foreach ($campaign->lists as $list) {
                                echo acym_tooltip('<i class="acym_subscription acymicon-circle" style="color:'.acym_escape($list->color).'"></i>', acym_escape($list->name));
                            }
                            echo '</div>';
                        } else {
                            echo '<div class="cell medium-12">'.(empty($campaign->automation)
                                    ? acym_translation('ACYM_NO_LIST_SELECTED')
                                    : acym_translation(
                                        'ACYM_SENT_WITH_AUTOMATION'
                                    )).'</div>';
                        }
                        ?>
					</div>
					<div class="large-2 medium-4 small-11 text-center cell acym__campaign__status">
						<div class="grid-x text-center">
                            <?php
                            // Has been sent (for both now and scheduled)
                            if ($campaign->sent) {
                                if (!isset($campaign->subscribers)) $campaign->subscribers = 'x';
                                echo '<div class="cell acym__campaign__status__status acym__background-color__green"><span>';
                                echo acym_translation('ACYM_SENT').' : '.acym_escape($campaign->subscribers).' '.acym_translation('ACYM_RECIPIENTS');
                                echo '</span></div>';
                                // Is currently a valid scheduled but not sent yet
                            } elseif ($campaign->scheduled && !$campaign->draft) {
                                echo '<div class="cell acym__campaign__status__status acym__background-color__orange"><span>'.acym_translation('ACYM_SCHEDULED').' : '.acym_date(
                                        $campaign->sending_date,
                                        'ACYM_DATE_FORMAT_LC3'
                                    ).'</span></div>';
                                $target = '<div class="acym__campaign__listing__scheduled__stop grid-x cell xlarge-shrink acym_vcenter" data-campaignid="'.acym_escape(
                                        $campaign->id
                                    ).'"><i class="acymicon-times-circle cell shrink show-for-xlarge"></i><span class="cell xlarge-shrink">'.acym_translation(
                                        'ACYM_CANCEL_SCHEDULING'
                                    ).'</span></div>';
                                echo '<div class="cell acym__campaign__listing__status__controls"><div class="grid-x text-center"><div class="cell auto"></div>'.acym_tooltip(
                                        $target,
                                        acym_translation('ACYM_STOP_THE_SCHEDULING_AND_SET_CAMPAIGN_AS_DRAFT')
                                    ).'<div class="cell auto"></div></div></div>';
                                // Is an activated auto campaign
                            } elseif ($data['statusAuto'] === $campaign->sending_type && !$campaign->draft) {
                                $numberCampaignsGenerated = empty($campaign->sending_params['number_generated']) ? '0' : $campaign->sending_params['number_generated'];
                                $tooltip = acym_translationSprintf('ACYM_X_CAMPAIGN_GENERATED', $numberCampaignsGenerated);

                                echo acym_tooltip(
                                    '<div class="cell acym__campaign__status__status acym__background-color__purple">
																<span class="acym__color__white">'.$campaign->sending_params['trigger_text'].'</span>
															</div>',
                                    $tooltip,
                                    'cell'
                                );
                                $target = '<div class="acym__campaign__listing__automatic__deactivate grid-x cell xlarge-shrink acym_vcenter" data-campaignid="'.acym_escape(
                                        $campaign->id
                                    ).'">
															<i class="'.($campaign->active ? 'acymicon-times-circle' : 'acymicon-power-off').' cell shrink show-for-xlarge"></i>
															<span class="cell xlarge-shrink">'.($campaign->active
                                        ? acym_translation('ACYM_DEACTIVATE')
                                        : acym_translation(
                                            'ACYM_ACTIVATE'
                                        )).'</span>
														</div>';
                                echo '<div class="cell acym__campaign__listing__status__controls">
													<div class="grid-x text-center">
														<div class="cell auto"></div>'.$target.'<div class="cell auto"></div>
													</div>
												</div>';
                                // Is a generated campaign that needs a confirmation
                            } elseif ('generated' == $data['status'] && $campaign->draft) {
                                echo '<div class="cell acym__campaign__status__status acym__background-color__orange"><span>'.acym_translation(
                                        'ACYM_WAITING_FOR_CONFIRMATION'
                                    ).'</span></div>';
                                // Is a generated campaign that has been disabled
                            } elseif ('generated' == $data['status'] && !$campaign->active) {
                                echo '<div class="cell acym__campaign__status__status acym__background-color__red"><span class="acym__color__white">'.acym_translation(
                                        'ACYM_DISABLED'
                                    ).'</span></div>';
                                // Is a draft
                            } elseif ($campaign->draft) {
                                echo '<div class="cell acym__campaign__status__status acym__campaign__status__draft"><span>'.acym_translation('ACYM_DRAFT').'</span></div>';
                            }
                            ?>
						</div>
					</div>
                    <?php if ($data['campaign_type'] == 'campaigns_auto') { ?>
						<div class="large-1 hide-for-small-only hide-for-medium-only cell text-center acym__campaign__auto__generation">
                            <?php
                            if (empty($campaign->last_generated)) {
                                echo '-';
                            } else {
                                echo acym_date($campaign->last_generated, acym_translation('ACYM_DATE_FORMAT_LC2'));
                            }
                            ?>
						</div>
						<div class="large-1 hide-for-small-only hide-for-medium-only cell text-center acym__campaign__auto__generation">
                            <?php
                            if (empty($campaign->next_trigger)) {
                                echo '-';
                            } else {
                                echo acym_date($campaign->next_trigger, acym_translation('ACYM_DATE_FORMAT_LC2'));
                            }
                            ?>
						</div>
                    <?php } ?>
					<div class="large-1 hide-for-small-only hide-for-medium-only cell text-center">
                        <?php
                        if (($campaign->sent || $data['campaign_type'] == 'campaigns_auto') && !empty($campaign->subscribers) && isset($campaign->open)) {
                            echo $campaign->open.'%';
                        } else {
                            echo '-';
                        }
                        ?>
					</div>
					<div class="large-1 hide-for-small-only hide-for-medium-only cell text-center">
                        <?php
                        if (($campaign->sent || $data['campaign_type'] == 'campaigns_auto') && !empty($campaign->subscribers) && isset($campaign->click)) {
                            echo $campaign->click.'%';
                        } else {
                            echo '-';
                        }
                        ?>
					</div>
                    <?php if (acym_isTrackingSalesActive()) { ?>
						<div class="large-1 hide-for-small-only hide-for-medium-only cell text-center">
                            <?php
                            if (!empty($campaign->tracking_sale) && !empty($campaign->currency)) {
                                acym_trigger('getCurrency', [&$campaign->currency]);
                                echo round($campaign->tracking_sale, 2).' '.$campaign->currency;
                            } else {
                                echo '-';
                            }
                            ?>
						</div>
                    <?php } ?>
                    <?php if (acym_isAdmin()) { ?>
						<div class="large-1 hide-for-small-only hide-for-medium-only cell text-center">
                            <?php
                            if ($campaign->visible == 1) {
                                $class = 'acymicon-eye" data-acy-newvalue="0';
                                $tooltip = 'ACYM_VISIBLE';
                            } else {
                                $class = 'acymicon-eye-slash acym__color__dark-gray" data-acy-newvalue="1';
                                $tooltip = 'ACYM_INVISIBLE';
                            }
                            echo acym_tooltip(
                                '<i data-acy-table="campaign" data-acy-field="visible" data-acy-elementid="'.acym_escape($campaign->id).'" class="acym_toggleable '.$class.'"></i>',
                                acym_translation($tooltip),
                                'secondary_status'
                            );
                            ?>
						</div>
                    <?php } ?>
					<h6 class="large-1 hide-for-medium-only hide-for-small-only cell text-center acym__listing__text acym__campaign__listing__id">
                        <?php echo acym_escape($campaign->id); ?>
					</h6>
				</div>
			</div>
            <?php
        }
        ?>
	</div>
    <?php echo $data['pagination']->display('campaigns');
}
