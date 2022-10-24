<h2 class="cell acym__title text-center"><?php echo acym_translation('ACYM_WHATS_NEXT'); ?></h2>
<div class="cell grid-x margin-top-2">
	<p class="cell text-center"><?php echo acym_translation('ACYM_SEEMS_SOMETHING_WENT_WRONG'); ?></p>
</div>
<div class="cell grid-x acym__walkthrough__fail__contact">
	<div class="cell auto hide-for-small-only"></div>
	<div class="cell grid-x medium-9 align-center margin-top-1">
		<div class="cell grid-x align-center margin-top-1">
			<p class="cell text-center"><?php echo acym_translation('ACYM_DONT_WORRY_OUR_SUPPORT_WILL_TAKE_A_LOOK'); ?></p>
			<p class="cell text-center"><?php echo acym_translation('ACYM_SIMPLY_PROVIDE_US_EMAIL_WE_GET_BACK'); ?></p>
		</div>
		<input type="email"
			   name="email"
			   class="cell small-8 margin-top-1 text-center"
			   placeholder="<?php echo acym_translation('ACYM_YOUR_EMAIL'); ?>"
			   value="<?php echo acym_escape(empty($data['email']) ? '' : $data['email']); ?>">
	</div>
	<div class="cell auto hide-for-small-only"></div>
</div>
<div class="cell grid-x align-center margin-top-3">
	<button type="button"
			class="acy_button_submit button"
			data-task="saveStepFail"
	><?php echo acym_translation('ACYM_ASK_FOR_SUPPORT'); ?></button>
</div>
<div class="cell text-center cursor-pointer margin-top-1 acym__color__dark-gray" id="acym__walkthrough__skip__fail"><?php echo acym_translation(
        'ACYM_SKIP_AND_IMPORT_USERS'
    ); ?></div>
