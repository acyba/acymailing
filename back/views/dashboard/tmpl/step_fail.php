<h2 class="cell acym__title text-center"><?php echo acym_translation('ACYM_WHATS_NEXT'); ?></h2>
<div class="cell grid-x margin-top-2">
	<p class="cell text-center acym__walkthrough__text"><?php echo acym_translation('ACYM_SEEMS_SOMETHING_WENT_WRONG'); ?></p>
</div>
<div class="cell grid-x margin-top-3 align-center">
	<div class="cell grid-x align-center grid-margin-x medium-9">
		<span class="acym__walkthrough__fail__choice cell small-6 text-right" data-show="contact"><?php echo acym_translation('ACYM_CONTACT_ME'); ?></span>
		<span class="acym__walkthrough__fail__choice cell small-6 text-left selected" data-show="gmail"><?php echo acym_translation('ACYM_TRY_WITH_GMAIL'); ?></span>
	</div>
</div>
<div class="cell grid-x acym__walkthrough__fail__toggle-div acym__walkthrough__fail__contact" style="display: none">
	<div class="cell auto hide-for-small-only"></div>
	<div class="cell grid-x medium-9 align-center margin-top-1">
		<div class="cell grid-x align-center margin-top-1">
			<p class="cell text-center acym__walkthrough__text"><?php echo acym_translation('ACYM_DONT_WORRY_OUR_SUPPORT_WILL_TAKE_A_LOOK'); ?></p>
			<p class="cell text-center acym__walkthrough__text"><?php echo acym_translation('ACYM_SIMPLY_PROVIDE_US_EMAIL_WE_GET_BACK'); ?></p>
		</div>
		<input type="email"
			   name="email"
			   class="cell small-8 margin-top-1 text-center"
			   placeholder="<?php echo acym_translation('ACYM_YOUR_EMAIL'); ?>"
			   value="<?php echo acym_escape(empty($data['email']) ? '' : $data['email']); ?>">
	</div>
	<div class="cell auto hide-for-small-only"></div>
</div>
<div class="cell grid-x align-center acym__walkthrough__fail__toggle-div acym__walkthrough__fail__gmail">
	<div class="cell auto hide-for-small-only"></div>
	<div class="cell grid-x medium-9 align-center margin-top-1">
		<p class="cell text-center acym__walkthrough__text"><?php echo acym_translation('ACYM_THESE_INFO_ARE_ONLY_ASK_TO_SEND_TEST'); ?></p>
		<div class="cell grid-x grid-margin-x margin-top-2">
			<input type="email" name="gmail_address" class="cell medium-6" placeholder="<?php echo acym_translation('ACYM_GMAIL_EMAIL'); ?>">
			<input type="password" name="gmail_password" class="cell medium-6" placeholder="<?php echo acym_translation('ACYM_GMAIL_PASSWORD'); ?>">
		</div>
	</div>
	<div class="cell auto hide-for-small-only"></div>
</div>
<div class="cell grid-x align-center margin-top-3">
	<input type="hidden" name="choice" value="gmail">
	<button type="button"
			class="acy_button_submit button acym__walkthrough__fail__toggle-div acym__walkthrough__fail__contact"
			data-task="saveStepFail"
			style="display: none"><?php echo acym_translation('ACYM_ASK_FOR_SUPPORT'); ?></button>
	<button type="button" class="acy_button_submit button acym__walkthrough__fail__toggle-div acym__walkthrough__fail__gmail" data-task="saveStepFail"><?php echo acym_translation(
            'ACYM_SEND_NEW_TEST'
        ); ?></button>
</div>
<div class="cell text-center cursor-pointer margin-top-1 acym__color__dark-gray" id="acym__walkthrough__skip__fail"><?php echo acym_translation(
        'ACYM_SKIP_AND_IMPORT_USERS'
    ); ?></div>
