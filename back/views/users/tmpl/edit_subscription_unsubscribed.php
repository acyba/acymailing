<div class="grid-x acym__listing">
    <?php foreach ($data['unsubscribe'] as $oneUnsubscription) { ?>
		<div class="grid-x cell acym__listing__row">
			<div class="grid-x medium-6 large-8 cell acym__users__display__list__name">
                <?php echo '<i class="cell shrink acymicon-circle" style="color:'.acym_escape($oneUnsubscription->color).'"></i>'; ?>
				<h6 class="cell auto"><?php echo acym_escape($oneUnsubscription->name); ?></h6>
			</div>
			<div acym-data-id="<?php echo intval($oneUnsubscription->id); ?>"
				 class="cell medium-auto margin-right-1 acym__users__display__list--action acym__user__action--reset">
				<i class="acymicon-trash-o"></i>
				<span><?php echo acym_strtolower(acym_translation('ACYM_RESET')); ?></span>
			</div>
			<div acym-data-id="<?php echo intval($oneUnsubscription->id); ?>"
				 class="medium-shrink cell acym__users__display__list--action acym__user__action--subscribe acym__color__dark-gray">
				<i class="acymicon-add"></i>
				<span><?php echo acym_strtolower(acym_translation('ACYM_RESUBSCRIBE')); ?></span>
			</div>
		</div>
    <?php } ?>
</div>
