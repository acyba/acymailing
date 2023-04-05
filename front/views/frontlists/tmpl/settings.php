<div id="acym__list__settings" class="acym__content <?php echo !empty($data['menuClass']) ? acym_escape($data['menuClass']) : ''; ?>">
	<form id="acym_form" action="<?php echo acym_completeLink(acym_getVar('cmd', 'ctrl')); ?>" method="post" name="acyForm" data-abide novalidate>
		<div class="cell grid-x text-right grid-margin-x margin-left-0 margin-right-0 align-right  margin-bottom-1">
            <?php include acym_getView('lists', 'settings_actions'); ?>
		</div>
		<div class="grid-x margin-bottom-1">
			<div class="cell grid-x margin-bottom-1 acym__content">
                <?php include acym_getView('frontlists', 'settings_information'); ?>
			</div>
			<div class="cell grid-x margin-bottom-1 text-center">
                <?php include acym_getView('lists', 'settings_overview', true); ?>
			</div>
			<div class="cell grid-x align-middle text-center acym__list__settings__stats acym__content margin-0">
                <?php include acym_getView('lists', 'settings_statistics', true); ?>
			</div>
		</div>

        <?php include acym_getView('lists', 'settings_subscribers', true); ?>

		<input type="hidden" name="listId" value="<?php echo acym_escape($data['listInformation']->id); ?>">
		<input type="hidden" name="list[welcome_id]" value="<?php echo acym_escape($data['listInformation']->welcome_id); ?>">
		<input type="hidden" name="list[unsubscribe_id]" value="<?php echo acym_escape($data['listInformation']->unsubscribe_id); ?>">
        <?php acym_formOptions(true, 'settings'); ?>
	</form>
</div>
