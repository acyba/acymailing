<h2 class="cell acym__title text-center"><?php echo acym_translation('ACYM_FIRST_LIST'); ?></h2>
<div class="cell margin-top-1 margin-bottom-2">
	<p class="acym__walkthrough__text">
        <?php echo acym_translation('ACYM_TEST_LIST_TEXT_1'); ?><br />
        <?php echo acym_translation('ACYM_TEST_LIST_TEXT_2'); ?>
	</p>
</div>

<div class="cell margin-top-2 text-center">
	<table id="acym__walkthrough__list__receivers">
		<tr>
			<th colspan="2"><?php echo acym_translation('ACYM_TEST_LIST_RECEIVER'); ?></th>
		</tr>
        <?php foreach ($data['users'] as $user) { ?>
			<tr>
				<td><input type="hidden" name="addresses[]" value="<?php echo acym_escape($user); ?>"><?php echo acym_escape($user); ?></td>
				<td><i class="acymicon-remove acym__walkthrough__list__receivers__remove"></i></td>
			</tr>
        <?php } ?>
	</table>
</div>
<div class="cell margin-bottom-3">
	<button type="button" class="button button-secondary" id="acym__walkthrough__list__new"><?php echo acym_translation('ACYM_ADD_NEW'); ?></button>
	<div id="acym__walkthrough__list__add-zone" style="display: none;">
		<label for="acym__walkthrough__list__new-address"><?php echo acym_translation('ACYM_EMAIL_ADDRESS'); ?></label>
		<input type="text" id="acym__walkthrough__list__new-address" />
		<button type="button" class="button button-secondary" id="acym__walkthrough__list__add"><?php echo acym_translation('ACYM_ADD'); ?></button>
	</div>
</div>

<div class="cell text-center margin-top-3">
	<button type="button" class="acy_button_submit button" data-task="saveStepList" data-condition="walkthroughList"><?php echo acym_translation('ACYM_SAVE_CONTINUE'); ?></button>
</div>
