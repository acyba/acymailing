<div class="acym__content cell">
	<div class="cell acym__title acym__dashboard__title"><?php echo acym_translation('ACYM_IN_PROGRESS'); ?></div>
	<span class="separator"></span>
    <?php if ($data['campaigns']) { ?>
		<div class="grid-x acym__listing cell">
			<div class="grid-x cell acym__listing__header">
				<div class="grid-x medium-auto small-12">
					<div class="cell small-6 acym__listing__header__title">
                        <?php echo acym_translation('ACYM_CAMPAIGN'); ?>
					</div>
					<div class="cell small-3 acym__listing__header__title">
                        <?php echo acym_translation('ACYM_STATUS'); ?>
					</div>
					<div class="cell small-3 acym__listing__header__title">
                        <?php echo acym_translation('ACYM_PROGRESS'); ?>
					</div>
				</div>
			</div>

            <?php
            $activeCampaigns = $data['campaigns'];
            foreach ($activeCampaigns as $index => $campaigns) { ?>
				<div acym-data-domain="coucou" class="grid-x cell align-middle acym__listing__row">
					<div class="cell small-6">
                        <?php echo $campaigns->name; ?>
					</div>
					<div class="cell small-3 large">
						<span class="<?php echo $results = $campaigns->active == 1 ? 'acym__dashboard__campaign__active' : 'acym__dashboard__campaign__paused'; ?>">
							<?php echo $results = $campaigns->active == 1 ? acym_translation('ACYM_IN_PROGRESS') : acym_translation('ACYM_STOPPED'); ?>
						</span>
					</div>
					<div class="cell small-3 ">
                        <?php echo ($campaigns->recipients - $campaigns->nbqueued).' / '.$campaigns->recipients; ?>
					</div>
				</div>
            <?php } ?>
		</div>
    <?php } else { ?>
		<div class="cell grid-x">
			<div class="cell text-center acym__dashboard__empty">
                <?php echo acym_translation('ACYM_QUEUE_IS_EMPTY'); ?>
			</div>
		</div>
    <?php } ?>
</div>
