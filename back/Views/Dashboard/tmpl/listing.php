<div id="acym__dashboard">
    <?php include acym_getView('dashboard', 'important_notice'); ?>

    <?php if ($this->config->get('show_beginner_steps') === 1) { ?>
		<div class="acym__dashboard__card grid-x grid-margin-x">
            <?php if (acym_isAllowed('configuration')) { ?>
				<div id="beginner-steps-container" class="cell large-6 margin-bottom-2">
                    <?php include acym_getView('dashboard', 'beginning_steps'); ?>
				</div>

				<div class="cell large-6 margin-bottom-2">
                    <?php include acym_getView('dashboard', 'engage_community'); ?>
				</div>
            <?php } ?>
		</div>

        <?php if (acym_isAllowed('stats')) { ?>
			<div class="acym__dashboard__card acym__dashboard__stats__wrapper grid-x grid-margin-x">
                <?php include acym_getView('dashboard', 'stats'); ?>
			</div>
        <?php } ?>
    <?php } else { ?>
        <?php if (acym_isAllowed('stats')) { ?>
			<div class="acym__dashboard__card acym__dashboard__stats__wrapper grid-x grid-margin-x">
                <?php include acym_getView('dashboard', 'stats'); ?>
			</div>
        <?php } ?>

        <?php if (acym_isAllowed('configuration')) { ?>
			<div class="acym__dashboard__card grid-x margin-bottom-2">
                <?php include acym_getView('dashboard', 'engage_community'); ?>
			</div>
        <?php } ?>
    <?php } ?>

    <?php if (acym_isAllowed('campaigns')) { ?>
		<div class="acym__dashboard__card grid-x margin-bottom-2">
            <?php include acym_getView('dashboard', 'campaign_progress'); ?>
		</div>

		<div class="acym__dashboard__card grid-x margin-bottom-2">
            <?php include acym_getView('dashboard', 'recent_campaign'); ?>
		</div>
    <?php } ?>

	<div class="acym__dashboard__card acym__dashboard__small__wrapper grid-x grid-margin-x">
        <?php if (acym_isAllowed('users')) { ?>
			<div class="cell large-6 margin-bottom-2">
                <?php include acym_getView('dashboard', 'subscribers'); ?>
			</div>
        <?php } ?>
        <?php if (acym_isAllowed('lists')) { ?>
			<div class="cell large-6">
                <?php include acym_getView('dashboard', 'main_list'); ?>
			</div>
        <?php } ?>
	</div>
</div>
