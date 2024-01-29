<div class="grid-x acym__listing">
    <?php foreach ($data['unsubscribe'] as $oneUnsubscription) { ?>
        <div class="grid-x cell align-middle acym__listing__row">
            <div class="grid-x small-6 large-8 cell acym__users__display__list__name">
                <?php echo '<i class="cell shrink acymicon-circle" style="color:'.acym_escape($oneUnsubscription->color).'"></i>'; ?>
                <h6 class="cell auto"><?php echo acym_escape($oneUnsubscription->name); ?></h6>
                <span class="cell medium-auto">
					<?php
                    if (!acym_isDateValid($oneUnsubscription->subscription_date)) {
                        $subscriptionDate = acym_translation('ACYM_NEVER_SUBSCRIBED');
                    } else {
                        $subscriptionDate = acym_date(acym_getTime($oneUnsubscription->subscription_date), acym_translation('ACYM_DATE_FORMAT_LC2'));
                    }
                    if (!acym_isDateValid($oneUnsubscription->unsubscribe_date)) {
                        $unsubscribeDate = acym_translation('ACYM_INVALID_DATE');
                    } else {
                        $unsubscribeDate = acym_date(acym_getTime($oneUnsubscription->unsubscribe_date), acym_translation('ACYM_DATE_FORMAT_LC2'));
                    }
                    echo acym_escape(acym_translationSprintf('ACYM_SUBSCRIPTION_DATES', $subscriptionDate, $unsubscribeDate));
                    ?>
				</span>
            </div>
            <div acym-data-id="<?php echo intval($oneUnsubscription->id); ?>"
                 class="cell small-3 medium-auto margin-right-1 acym__users__display__list--action acym__user__action--reset">
                <i class="acymicon-trash-o"></i>
                <span><?php echo acym_strtolower(acym_translation('ACYM_RESET')); ?></span>
            </div>
            <div acym-data-id="<?php echo intval($oneUnsubscription->id); ?>"
                 class="cell small-3 medium-shrink acym__users__display__list--action acym__user__action--subscribe acym__color__dark-gray">
                <i class="acymicon-add"></i>
                <span><?php echo acym_strtolower(acym_translation('ACYM_RESUBSCRIBE')); ?></span>
            </div>
        </div>
    <?php } ?>
</div>
