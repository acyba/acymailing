<?php
if (!empty($data['recipients']) && $data['recipients'] > 200) {
    ?>
	<div id="email_checker_ad" class="cell grid-x">
		<h6 class="acym__title acym__title__secondary margin-bottom-0">
            <?php echo acym_translation('ACYM_ACYCHECKER_TEST_AD'); ?>
			<img class="acychecker_logo" alt="logo check this email" src="<?php echo ACYM_IMAGES.'icons/logo_acychecker.png'; ?>" />
		</h6>
		<div class="margin-bottom-1 grid-x acychecker_ad">
            <?php echo acym_translation('ACYM_ACYCHECKER_TEST_AD_DESC'); ?>
			<a class="cell shrink button button-secondary" href="<?php echo acym_completeLink('dashboard&task=cte'); ?>">
                <?php echo acym_translation('ACYM_ACYCHECKER_MORE_INFORMATION'); ?>
			</a>
		</div>
	</div>
    <?php
}
