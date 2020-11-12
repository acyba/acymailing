<div class="grid-x acym__listing">
    <?php foreach ($data['unsubscribe'] as $oneUnsubscription) { ?>
		<div class="grid-x cell acym__listing__row">
			<div class="grid-x medium-5 cell acym__users__display__list__name">
                <?php echo '<i class="cell shrink acymicon-circle" style="color:'.acym_escape($oneUnsubscription->color).'"></i>'; ?>
				<h6 class="cell auto"><?php echo acym_escape($oneUnsubscription->name); ?></h6>
			</div>
			<div class="medium-4 small-6 cell">
			</div>
			<div id="<?php echo acym_escape($oneUnsubscription->id); ?>" class="medium-3 cell acym__users__display__list--action acym__user__action--subscribe acym__color__dark-gray">
				<i class="acymicon-add"></i><span><?php echo acym_strtolower(acym_translation('ACYM_RESUBSCRIBE')); ?></span>
			</div>
		</div>
    <?php } ?>
</div>
