<div id="acym__wysid__send__test" class="cell grid-y" style="display: flex">
	<div class="cell text-center margin-bottom-3 margin-top-1">
		<i class="acymicon-paper-plane-o acym__wysid__send__test__icon"></i>
		<div class="acym__wysid__send__test__icon__loader"><?php echo acym_loaderLogo(); ?></div>
	</div>
	<i class="acymicon-close acym__wysid__send__test-close"></i>
	<div class="cell acym__wysid__send__test__title margin-bottom-2 acym__title acym__title__secondary">
        <?php echo acym_translation('ACYM_SEE_HOW_AMAZING_YOUR_EMAIL'); ?>
	</div>
	<label for="acym__wysid__send__test__select"><?php echo acym_translation('ACYM_SEND_TEST_TO'); ?></label>
    <?php
    if (acym_isAdmin()) {
        echo acym_selectMultiple(
            $this->emailsTest,
            'emails_test',
            $this->emailsTest,
            [
                'class' => 'acym__multiselect__email',
                'placeholder' => acym_translation('ACYM_TEST_ADDRESS'),
            ]
        );
    } else {
        echo '<input type="text" name="emails_test" value="'.acym_escape(implode(',', $this->emailsTest)).'">';
    }
    ?>
	<label class="margin-top-1">
        <?php echo acym_translation('ACYM_TEST_NOTE'); ?>
		<textarea
				id="acym__wysid__send__test__note"
				name="test_note"
				type="text"
				placeholder="<?php echo acym_translation('ACYM_TEST_NOTE_PLACEHOLDER'); ?>"></textarea>
	</label>
	<div class="cell grid-x align-center acym__wysid__send__test__container__button">
		<button type="button" class="cell shrink button" id="acym__wysid__send__test__button"><?php echo acym_translation('ACYM_SAVE_AND_SEND_TEST'); ?></button>
	</div>
</div>
