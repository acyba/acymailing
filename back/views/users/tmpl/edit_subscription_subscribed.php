<div class="grid-x acym__listing">
    <?php if (!empty($data['subscriptions']) || !empty($data['unsubscribe'])) { ?>
        <?php foreach ($data['subscriptions'] as $oneSubscription) { ?>
            <div class="grid-x cell align-middle acym__listing__row">
                <div class="grid-x small-6 large-8 cell acym__users__display__list__name">
                    <?php echo '<i class="cell shrink acymicon-circle" style="color:'.acym_escape($oneSubscription->color).'"></i>'; ?>
                    <h6 class="cell auto"><?php echo acym_escape($oneSubscription->name); ?></h6>
                    <span class="cell medium-auto">
					<?php
                    if (acym_isDateValid($oneSubscription->subscription_date)) {
                        echo acym_escape(acym_date(acym_getTime($oneSubscription->subscription_date), acym_translation('ACYM_DATE_FORMAT_LC2')));
                    } else {
                        echo acym_translation('ACYM_INVALID_DATE');
                    }
                    ?>
                </div>
                <div acym-data-id="<?php echo intval($oneSubscription->id); ?>"
                     class="cell small-3 medium-auto medium-margin-right-1 acym__users__display__list--action acym__user__action--reset">
                    <i class="acymicon-trash-o"></i>
                    <span><?php echo acym_strtolower(acym_translation('ACYM_RESET')); ?></span>
                </div>
                <div acym-data-id="<?php echo intval($oneSubscription->id); ?>"
                     class="cell small-3 medium-shrink acym__users__display__list--action acym__user__action--unsubscribe">
                    <i class="acymicon-times-circle"></i>
                    <span><?php echo acym_strtolower(acym_translation('ACYM_UNSUBSCRIBE')); ?></span>
                </div>
            </div>
        <?php } ?>
    <?php } ?>
</div>
