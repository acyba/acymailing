<div class="acym__content cell grid-x acym__users__display__subscriptions--list">
	<h5 class="cell acym__title acym__title__secondary"><?php echo acym_translation('ACYM_LISTS'); ?></h5>
	<div class="cell acym__content__tab">
        <?php
        $subscriptionIdentifier = $data['tab']->startTab(acym_translation('ACYM_SUBSCRIBED').' ('.count($data['subscriptions']).')', false, '', !empty($data['subscriptions']));
        include acym_getView('users', 'edit_subscription_subscribed', true);
        $data['tab']->endTab();

        $unsubscriptionIdentifier = $data['tab']->startTab(acym_translation('ACYM_UNSUBSCRIBED').' ('.count($data['unsubscribe']).')', false, '', !empty($data['unsubscribe']));
        include acym_getView('users', 'edit_subscription_unsubscribed', true);
        $data['tab']->endTab();

        $data['tab']->addElementInBar(
            '<div class="acym__users__display__list--action acym__user__action--unsubscribeall">
				<i class="acymicon-times-circle"></i><span>'.acym_translation('ACYM_UNSUBSCRIBE_ALL').'</span>
			</div>',
            $subscriptionIdentifier
        );

        $data['tab']->addElementInBar(
            '<div class="acym__users__display__list--action acym__user__action--resubscribeall">
				<i class="acymicon-add"></i><span>'.acym_translation('ACYM_RESUBSCRIBE_ALL').'</span>
			</div>',
            $unsubscriptionIdentifier
        );

        $data['tab']->display('lists_user');
        ?>
	</div>
</div>
