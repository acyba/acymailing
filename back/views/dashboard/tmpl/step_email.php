<h2 class="acym__title text-center cell"><?php echo acym_translation('ACYM_YOUR_FIRST_EMAIL'); ?></h2>
<div class="cell grid-x margin-top-1">
	<p class="cell text-center acym__walkthrough__text"><?php echo acym_translation('ACYM_WE_ARE_GOING_TO_CONFIGURE_ACY_SIMPLE_TEST'); ?></p>
	<p class="cell text-center acym__walkthrough__text"><?php echo acym_translation('ACYM_HERE_IT_LOOKS_FEEL_FREE_TO_MODIFY'); ?></p>
</div>
<div class="cell grid-x margin-top-3">
	<input type="hidden" name="mail[id]" value="<?php echo acym_escape($data['mail']->id); ?>" />
	<input type="hidden" name="id" value="<?php echo acym_escape($data['mail']->id); ?>" />
	<input type="hidden" id="acym__mail__edit__editor__social__icons" value="<?php echo empty($data['social_icons']) ? '{}' : acym_escape($data['social_icons']); ?>">
	<input type="hidden" id="acym__mail__type" name="mail[type]" value="<?php echo $data['mailClass']::TYPE_STANDARD; ?>">
	<input type="hidden" name="task">
    <?php echo $data['editor']->display(); ?>
</div>
<div class="cell grid-x align-center margin-top-3">
	<button type="button" class="acy_button_submit button" data-task="saveStepEmail"><?php echo acym_translation('ACYM_SAVE_CONTINUE'); ?></button>
</div>
