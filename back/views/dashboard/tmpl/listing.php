<div id="acym__dashboard">
	<div class="acym__dashboard__card cell grid-x large-up-3 grid-margin-x grid-margin-y medium-up-2 small-up-1 margin-right-0 margin-bottom-2 align-center">
        <?php if (acym_isAllowed('lists') || acym_isAllowed('segments')) { ?>
			<div class="cell acym__content acym__dashboard__one-card text-center grid-x">
				<div class="cell acym__dashboard__card__picto__audience acym__dashboard__card__picto">
					<i class="acymicon-insert_chart acym__dashboard__card__icon__audience"></i>
				</div>
				<h1 class="cell acym__dashboard__card__title"><?php echo acym_translation('ACYM_AUDIENCE'); ?></h1>
				<hr class="cell small-10">
                <?php if (acym_isAllowed('lists')) { ?>
					<a class="acym__dashboard__card__link" href="<?php echo acym_completeLink('lists'); ?>">
                        <?php echo acym_translation('ACYM_VIEW_ALL_LISTS'); ?>
					</a>
					<a class="acym__dashboard__card__link" href="<?php echo acym_completeLink('lists&task=edit&step=settings'); ?>">
                        <?php echo acym_translation('ACYM_CREATE_LIST'); ?>
					</a>
                <?php } ?>
                <?php if (acym_isAllowed('segments')) { ?>
					<a class="acym__dashboard__card__link" href="<?php echo acym_completeLink('segments&task=edit'); ?>">
                        <?php echo acym_translation('ACYM_CREATE_SEGMENT'); ?>
					</a>
                <?php } ?>
			</div>
        <?php } ?>
        <?php if (acym_isAllowed('campaigns') || acym_isAllowed('mails')) { ?>
			<div class="cell acym__content acym__dashboard__one-card text-center grid-x">
				<div class="acym__dashboard__card__picto__campaigns acym__dashboard__card__picto">
					<i class="acymicon-email acym__dashboard__card__icon__campaigns"></i>
				</div>
				<h1 class="acym__dashboard__card__title"><?php echo acym_translation('ACYM_EMAILS'); ?></h1>
				<hr class="cell small-10">
                <?php if (acym_isAllowed('campaigns')) { ?>
					<a class="acym__dashboard__card__link" href="<?php echo acym_completeLink('campaigns'); ?>">
                        <?php echo acym_translation('ACYM_VIEW_ALL_EMAILS'); ?>
					</a>
					<a class="acym__dashboard__card__link" href="<?php echo acym_completeLink('campaigns&task=newEmail'); ?>">
                        <?php echo acym_translation('ACYM_CREATE_NEW_EMAIL'); ?>
					</a>
                <?php } ?>
                <?php if (acym_isAllowed('mails')) { ?>
					<a class="acym__dashboard__card__link" href="<?php echo acym_completeLink('mails&task=edit&type_editor=acyEditor'); ?>">
                        <?php echo acym_translation('ACYM_CREATE_TEMPLATE'); ?>
					</a>
                <?php } ?>
			</div>
        <?php } ?>
        <?php if (acym_isAllowed('automation')) {
            $disabledLinks = !acym_level(ACYM_ENTERPRISE);
            ?>
			<div class="cell acym__content acym__dashboard__one-card text-center grid-x">
				<div class="acym__dashboard__card__picto__automation acym__dashboard__card__picto">
					<i class="acymicon-cog acym__dashboard__card__icon__automation"></i>
				</div>
				<h1 class="acym__dashboard__card__title"><?php echo acym_translation('ACYM_AUTOAMTION'); ?></h1>
				<hr class="cell small-10">
				<a class="acym__dashboard__card__link"
                    <?php echo $disabledLinks ? 'data-acym-tooltip="'.acym_translation('ACYM_ONLY_AVAILABLE_ENTERPRISE_VERSION', true).'"' : '' ?>
				   href="<?php echo $disabledLinks ? '#' : acym_completeLink('automation&task=listing'); ?>">
                    <?php echo acym_translation('ACYM_VIEW_ALL_AUTOMATIONS'); ?>
				</a>
				<a class="acym__dashboard__card__link"
                    <?php echo $disabledLinks ? 'data-acym-tooltip="'.acym_translation('ACYM_ONLY_AVAILABLE_ENTERPRISE_VERSION', true).'"' : '' ?>
				   href="<?php echo $disabledLinks ? '#' : acym_completeLink('automation&task=edit&step=info'); ?>">
                    <?php echo acym_translation('ACYM_NEW_AUTOMATION'); ?>
				</a>
				<a class="acym__dashboard__card__link"
                    <?php echo $disabledLinks ? 'data-acym-tooltip="'.acym_translation('ACYM_ONLY_AVAILABLE_ENTERPRISE_VERSION', true).'"' : '' ?>
				   href="<?php echo $disabledLinks ? '#' : acym_completeLink('automation&task=edit&step=filter'); ?>">
                    <?php echo acym_translation('ACYM_NEW_MASS_ACTION'); ?>
				</a>
			</div>
        <?php } ?>
	</div>

    <?php if (acym_isAllowed('stats')) { ?>
		<div id="acym_stats">
            <?php
            include acym_getView('stats', 'global_stats_data', true);
            ?>
		</div>
    <?php } ?>

    <?php if (acym_isAllowed('queue')) { ?>
		<div class="cell acym__dashboard__active-campaigns acym__content">
			<h1 class="acym__title"><?php echo acym_translation('ACYM_CAMPAIGNS_SCHEDULED'); ?></h1>
			<div class="acym__dashboard__active-campaigns__listing">
                <?php if (empty($data['campaignsScheduled'])) { ?>
					<h1 class="acym__dashboard__active-campaigns__none"><?php echo acym_translation('ACYM_NONE_OF_YOUR_CAMPAIGN_SCHEDULED_GO_SCHEDULE_ONE'); ?></h1>
                <?php } else { ?>
                    <?php
                    $nbCampaigns = count($data['campaignsScheduled']);
                    $i = 0;
                    foreach ($data['campaignsScheduled'] as $campaign) {
                        $i++;
                        ?>
						<div class="cell grid-x acym__dashboard__active-campaigns__one-campaign">
							<a class="acym__dashboard__active-campaigns__one-campaign__title medium-4 small-12"
							   href="<?php echo acym_completeLink('campaigns&task=edit&step=editEmail&id=').$campaign->id; ?>"><?php echo $campaign->name; ?></a>
							<div class="acym__dashboard__active-campaigns__one-campaign__state medium-2 small-12 acym__background-color__blue text-center">
								<span><?php echo acym_translation('ACYM_SCHEDULED').' : '.acym_getDate($campaign->sending_date, 'ACYM_DATE_FORMAT_LC3'); ?></span></div>
							<p id="<?php echo intval($campaign->id); ?>"
							   class="medium-6 small-12 acym__dashboard__active-campaigns__one-campaign__action acym__color__dark-gray"><?php echo acym_translation(
                                    'ACYM_CANCEL_SCHEDULING'
                                ); ?></p>
						</div>
                        <?php if ($i < $nbCampaigns) { ?>
							<hr class="cell small-12">
                        <?php }
                    }
                } ?>
			</div>
		</div>
    <?php } ?>
</div>
