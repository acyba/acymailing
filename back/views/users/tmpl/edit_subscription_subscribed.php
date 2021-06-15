<div class="grid-x acym__listing">
    <?php if (!empty($data['subscriptions']) || !empty($data['unsubscribe'])) { ?>
        <?php foreach ($data['subscriptions'] as $oneSubscription) { ?>
			<div class="grid-x cell acym__listing__row">
				<div class="grid-x medium-6 large-8 cell acym__users__display__list__name">
                    <?php echo '<i class="cell shrink acymicon-circle" style="color:'.acym_escape($oneSubscription->color).'"></i>'; ?>
					<h6 class="cell auto"><?php echo acym_escape($oneSubscription->name); ?></h6>
				</div>
				<div acym-data-id="<?php echo intval($oneSubscription->id); ?>"
					 class="cell medium-auto margin-right-1 acym__users__display__list--action acym__user__action--reset">
					<i class="acymicon-trash-o"></i>
					<span><?php echo acym_strtolower(acym_translation('ACYM_RESET')); ?></span>
				</div>
				<div acym-data-id="<?php echo intval($oneSubscription->id); ?>"
					 class="cell medium-shrink acym__users__display__list--action acym__user__action--unsubscribe">
					<i class="acymicon-times-circle"></i>
					<span><?php echo acym_strtolower(acym_translation('ACYM_UNSUBSCRIBE')); ?></span>
				</div>
			</div>
        <?php } ?>
    <?php } ?>
</div>
