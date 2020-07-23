<div class="grid-x acym__listing">
    <?php if (!empty($data['subscriptions']) || !empty($data['unsubscribe'])) { ?>
        <?php foreach ($data['subscriptions'] as $oneSubscription) { ?>
			<div class="grid-x cell acym__listing__row">
				<div class="grid-x medium-9 cell acym__users__display__list__name">
                    <?php echo '<i class="cell shrink acymicon-circle" style="color:'.acym_escape($oneSubscription->color).'"></i>'; ?>
					<h6 class="cell auto"><?php echo acym_escape($oneSubscription->name); ?></h6>
				</div>
				<div id="<?php echo acym_escape($oneSubscription->id); ?>" class="medium-3 cell acym__users__display__list--action acym__user__action--unsubscribe">
					<i class="acymicon-times-circle"></i><span><?php echo strtolower(acym_translation('ACYM_UNSUBSCRIBE')); ?></span>
				</div>
			</div>
        <?php } ?>
    <?php } ?>
</div>
