<div id="send_test_zone" class="cell large-6">
	<h6 class="acym__title acym__title__secondary"><?php echo acym_translation('ACYM_SEND_TEST_TO'); ?></h6>
    <?php

    echo acym_selectMultiple(
        $data['test_emails'],
        'test_emails',
        $data['test_emails'],
        [
            'class' => 'acym__multiselect__email',
            'placeholder' => acym_translation('ACYM_TEST_ADDRESS'),
        ]
    );

    ?>
	<label class="margin-top-1">
        <?php echo acym_translation('ACYM_TEST_NOTE'); ?>
		<textarea
				id="acym__wysid__send__test__note"
				name="test_note"
				type="text"
				placeholder="<?php echo acym_translation('ACYM_TEST_NOTE_PLACEHOLDER', true); ?>"></textarea>
	</label>
	<div class="grid-x">
		<button id="acym__campaign__send-test" type="button" class="button button-secondary margin-top-1">
            <?php echo acym_translation('ACYM_SEND_TEST'); ?>
		</button>
		<div class="cell shrink margin-top-1 margin-left-1" id="acym__campaigns__send-test__spinner" style="display: none">
			<i class="acymicon-circle-o-notch acymicon-spin"></i>
		</div>
	</div>
</div>
