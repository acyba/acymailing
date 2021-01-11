<div id="acym__users__import__from_text" class="grid-x padding-vertical-2 padding-horizontal-2">
	<h6 class="cell margin-bottom-1 text-center"><?php echo acym_translation('ACYM_IMPORT_TEXT_DESC'); ?></h6>
	<div class="cell large-2"></div>
	<div class="cell large-8 grid-x">
		<textarea rows="10" name="acym__users__import__from_text__textarea" class="cell">
name,email
Sloan,sloan@example.com
John,john@example.com
</textarea>

		<div class="cell grid-x grid-margin-x margin-top-1">
			<div class="cell hide-for-small-only medium-auto"></div>
            <?php echo acym_cancelButton('ACYM_CANCEL', '', 'button medium-6 large-shrink margin-bottom-0'); ?>
			<button type="button" class="button cell medium-shrink acym__import__submit margin-bottom-0" data-from="textarea">
                <?php echo acym_translation('ACYM_IMPORT'); ?>
			</button>
			<div class="cell hide-for-small-only medium-auto"></div>
		</div>
	</div>
	<div class="cell large-2"></div>
</div>
