<h2 class="cell acym__title text-center"><?php echo acym_translation('ACYM_WHATS_NEXT'); ?></h2>
<div class="cell grid-x margin-top-2">
	<p class="cell text-center acym__walkthrough__text"><?php echo acym_translation('ACYM_SEEMS_SOMETHING_WENT_WRONG'); ?></p>
	<p class="cell text-center acym__walkthrough__text"><?php echo acym_translation('ACYM_DONT_WORRY_OUR_SUPPORT_WILL_TAKE_A_LOOK'); ?></p>
	<p class="cell text-center acym__walkthrough__text"><?php echo acym_translation('ACYM_SIMPLY_PROVIDE_US_EMAIL_WE_GET_BACK'); ?></p>
</div>
<div class="cell grid-x margin-bottom-3">
	<div class="cell auto hide-for-small-only"></div>
	<div class="cell grid-x medium-7 align-center margin-top-1 grid-margin-x acym_vcenter">
		<span class="acym__walkthrough__fail__label cell medium-4"><?php echo acym_translation('ACYM_CONTACT_ME'); ?></span>
		<input required
			   type="email"
			   name="email"
			   class="cell medium-8 margin-top-1 text-center"
			   placeholder="<?php echo acym_translation('ACYM_YOUR_EMAIL'); ?>"
			   value="<?php echo acym_escape(empty($data['email']) ? '' : $data['email']); ?>">
	</div>
	<div class="cell auto hide-for-small-only"></div>
</div>
<div class="cell grid-x align-center">
	<button type="button"
			class="acy_button_submit button acym__walkthrough__fail__toggle-div acym__walkthrough__fail__contact"
			data-task="saveStepFaillocal"><?php echo acym_translation('ACYM_ASK_FOR_SUPPORT'); ?></button>
</div>
<div class="cell text-center cursor-pointer margin-top-1 acym__color__dark-gray" id="acym__walkthrough__skip__fail"><?php echo acym_translation(
        'ACYM_SKIP_AND_IMPORT_USERS'
    ); ?></div>
