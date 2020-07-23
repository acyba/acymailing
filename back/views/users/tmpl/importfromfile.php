<div id="acym__users__import__from_file" class="grid-x padding-vertical-2 padding-horizontal-2">
	<div class="cell grid-x text-center">
		<h6 class="cell">
            <?php echo acym_translation('ACYM_CHOOSE_FILE_WITH_USER_DATA'); ?>
		</h6>
		<p class="acym__color__dark-gray cell text-center"><?php echo acym_translation('ACYM_IMPORT_USER_FROM_FILE_INFORMATION_MESSAGE_BELOW_CHOOSE_FILE_BUTTON'); ?></p>
		<div class="cell grid-x acym__users__import__from_file__choose">
			<div class="cell medium-auto"></div>
            <?php
            $maxupload = acym_bytes(ini_get('upload_max_filesize'));
            $maxpost = acym_bytes(ini_get('post_max_size'));
            $maxupload = $maxupload > $maxpost ? $maxpost : $maxupload;
            ?>
			<input type="hidden" name="MAX_FILE_SIZE" value="<?php echo acym_escape($maxupload); ?>" />
			<input type="file" name="import_file" class="show-for-sr" id="acym__users__import__from_file__import__input" accept=".csv">
			<label for="acym__users__import__from_file__import__input" class="cell acym__color__blue medium-shrink margin-top-1 acym__users__import__from_file__import__label">
                <?php echo acym_translation('ACYM_CHOOSE_FILE'); ?>
			</label>
			<div class="cell medium-auto"></div>
		</div>
		<div class="acym__users__import__from_file__file cell grid-x" style="display: none;">
			<div class="cell medium-auto"></div>
			<div class="cell medium-shrink margin-top-2">
				<span class="acym__users__import__from_file__file-name acym__color__dark-gray"></span>
				<i title="<?php echo acym_translation('ACYM_DELETE'); ?>" class="acymicon-close acym__users__import__from_file__file__close  acym__color__red"></i>
			</div>
			<div class="cell medium-auto"></div>
		</div>

		<div class="cell grid-x grid-margin-x margin-top-2">
			<div class="cell hide-for-small-only medium-auto"></div>
            <?php echo acym_cancelButton('ACYM_CANCEL', '', 'button medium-6 large-shrink margin-bottom-0'); ?>
			<button class="cell button medium-shrink margin-bottom-0 acym__users__import__from_file__button-valid" disabled>
                <?php echo acym_translation('ACYM_IMPORT_THIS_FILE'); ?>
			</button>
			<div class="cell hide-for-small-only medium-auto"></div>
		</div>
	</div>
</div>

