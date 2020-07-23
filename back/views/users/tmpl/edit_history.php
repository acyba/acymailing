<?php if (!empty($data['userHistory'])) { ?>
	<div class="cell grid-x align-middle acym__users__display__history acym__content">
		<h5 class="cell font-bold margin-bottom-1"><?php echo acym_translation('ACYM_HISTORY'); ?></h5>
		<div class="grid-x cell text-center grid-margin-x acym__listing__header" id="acym__listing__header__user_history">
			<div class="medium-2 hide-for-small-only cell acym__listing__header__title">
                <?php echo acym_translation('ACYM_DATE'); ?>
			</div>
			<div class="medium-2 hide-for-small-only cell acym__listing__header__title">
                <?php echo acym_translation('ACYM_IP'); ?>
			</div>
			<div class="medium-2 hide-for-small-only cell acym__listing__header__title">
                <?php echo acym_translation('ACYM_ACTIONS'); ?>
			</div>
			<div class="medium-3 hide-for-small-only cell acym__listing__header__title">
                <?php echo acym_translation('ACYM_DETAILS'); ?>
			</div>
			<div class="medium-3 hide-for-small-only cell acym__listing__header__title">
                <?php echo acym_translation('ACYM_SOURCE'); ?>
			</div>
		</div>
		<div id="acym__users__display__history__listing" class="grid-x cell">
            <?php
            foreach ($data['userHistory'] as $key => $oneHistory) { ?>
				<div class="grid-x cell text-center acym__listing__row grid-margin-x">
					<div class="cell small-12 medium-2">
                        <?php echo acym_date($oneHistory->date, 'Y-m-d H:i'); ?>
					</div>
					<div class="cell small-6 medium-2">
                        <?php echo acym_escape($oneHistory->ip); ?>
					</div>
					<div class="cell small-6 medium-2">
                        <?php echo acym_translation('ACYM_ACTION_'.strtoupper(acym_escape($oneHistory->action))); ?>
					</div>
					<div class="cell small-6 medium-3">
                        <?php if (!empty($oneHistory->data)) echo $oneHistory->data; ?>
					</div>
					<div class="cell small-6 medium-3">
                        <?php if (!empty($oneHistory->source)) echo $oneHistory->source; ?>
					</div>
				</div>
            <?php } ?>
		</div>
	</div>
    <?php
}
