<h2 class="acym__title acym__title__secondary text-center cell margin-bottom-0"><?php echo acym_translation('ACYM_ACYMAILING_NEWS_AND_COUPON_CODE'); ?></h2>
<h3 class="acym__walkthrough__sub-title cell"><?php echo acym_translation('ACYM_DO_YOU_WANT_NEWS'); ?></h3>
<div class="cell grid-x margin-top-2">
	<label class="cell small-9 margin-auto grid-x cell text-center"><?php echo acym_translation('ACYM_CONTACT_EMAIL'); ?></label>
</div>
<input type="email"
	   value="<?php echo acym_escape($data['email']); ?>"
	   class="cell small-9 margin-auto"
	   placeholder="<?php echo acym_escape(acym_translation('ACYM_YOUR_EMAIL')); ?>">

<div class="cell text-center margin-bottom-1">
	<button class="button acym__walk-through__content__save large-shrink margin-bottom-1" id="acym__subscribe__news"><?php echo acym_translation('ACYM_SURE_LETS_DO_IT'); ?></button>
	<input type="hidden" name="nextStep" value="email">
	<button class="cell acym__color__dark-gray acym__walk-through-1__content__later small-shrink cursor-pointer acy_button_submit" data-task="saveStepSubscribe">
        <?php echo acym_translation('ACYM_NO_THANK_YOU'); ?>
	</button>
</div>
