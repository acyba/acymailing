<div id="acym__dashboard">
    <?php include acym_getView('dashboard', 'important_notice'); ?>

    <?php if ($this->config->get('show_beginner_steps') === 1) { ?>
		<div class="acym__dashboard__card grid-x grid-margin-x">
			<div id="beginner-steps-container" class="cell large-6 margin-bottom-2">
                <?php include acym_getView('dashboard', 'beginning_steps'); ?>
			</div>

			<div class="cell large-6 margin-bottom-2">
                <?php include acym_getView('dashboard', 'engage_community'); ?>
			</div>
		</div>

		<div class="acym__dashboard__card acym__dashboard__stats__wrapper grid-x grid-margin-x">
            <?php include acym_getView('dashboard', 'stats'); ?>
		</div>
    <?php } else { ?>
		<div class="acym__dashboard__card acym__dashboard__stats__wrapper grid-x grid-margin-x">
            <?php include acym_getView('dashboard', 'stats'); ?>
		</div>

		<div class="acym__dashboard__card grid-x margin-bottom-2">
            <?php include acym_getView('dashboard', 'engage_community'); ?>
		</div>
    <?php } ?>

	<div class="acym__dashboard__card grid-x margin-bottom-2">
        <?php include acym_getView('dashboard', 'campaign_progress'); ?>
	</div>

	<div class="acym__dashboard__card grid-x margin-bottom-2">
        <?php include acym_getView('dashboard', 'recent_campaign'); ?>
	</div>

	<div class="acym__dashboard__card acym__dashboard__small__wrapper grid-x grid-margin-x">
		<div class="cell large-6 margin-bottom-2">
            <?php include acym_getView('dashboard', 'subscribers'); ?>
		</div>
		<div class="cell large-6">
            <?php include acym_getView('dashboard', 'main_list'); ?>
		</div>
	</div>
</div>
