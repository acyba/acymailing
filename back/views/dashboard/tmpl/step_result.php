<h2 class="acym__title cell text-center"><?php echo acym_translation('ACYM_EMAIL_SENT'); ?></h2>
<h2 class="acym__title cell text-center"><?php echo acym_translation('ACYM_DID_YOU_RECEIVE_IT'); ?></h2>
<div class="cell grid-x margin-top-2">
	<p class="cell text-center acym__walkthrough__text"><?php echo acym_translation('ACYM_RESULT_TEXT'); ?></p>
</div>
<div class="cell grid-x grid-margin-x margin-top-2 align-center" id="acym__walkthrough__result__choice">
	<div class="cell medium-shrink grid-x align-center acym__walkthrough__result__choice__one" id="acym__walkthrough__result__choice__yes" data-value="1">
		<i class="acymicon-check cell shrink"></i>
		<span class="cell"><?php echo acym_translation('ACYM_YES_I_DID'); ?></span>
	</div>
	<div class="cell hide-for-small-only medium-3"></div>
	<div class="cell medium-shrink grid-x align-center acym__walkthrough__result__choice__one" id="acym__walkthrough__result__choice__no" data-value="0">
		<i class="acymicon-remove cell shrink"></i>
		<span class="cell"><?php echo acym_translation('ACYM_NO_I_DIDNT'); ?></span>
	</div>
</div>
<div class="cell grid-x margin-top-2" id="acym__walkthrough__result__spam">
	<p class="cell text-center acym__walkthrough__text"><?php echo acym_translation('ACYM_MAKE_SURE_NOT_IN_SPAM'); ?></p>
</div>
<div class="cell grid-x align-center margin-top-3">
	<input type="hidden" value="" name="result">
	<button disabled type="button" class="acy_button_submit button cell shrink" data-task="saveStepResult"><?php echo acym_translation('ACYM_CONTINUE'); ?></button>
</div>
