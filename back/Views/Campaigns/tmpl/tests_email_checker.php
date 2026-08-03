<?php
// context verification
if (!empty($data['recipients']) && $data['recipients'] > 200) {
    ?>
	<div id="email_checker_ad" class="cell grid-x">
		<h6 class="acym__title acym__title__secondary margin-bottom-0">
            <?php echo acym_escapeHtml(acym_translation('ACYM_ACYCHECKER_TEST_AD')); ?>
			<img class="acychecker_logo" alt="logo AcyChecker" src="<?php echo acym_escapeUrl(ACYM_IMAGES.'icons/logo_acychecker.png'); ?>" />
		</h6>
		<div class="margin-bottom-1 grid-x acychecker_ad">
            <?php echo acym_escapeHtmlWithAllowedTags(acym_translation('ACYM_ACYCHECKER_TEST_AD_DESC'), ['br' => []]); ?>
			<a class="cell shrink button button-secondary" href="<?php echo acym_escapeUrl(acym_completeLink('dashboard&task=acychecker')); ?>">
                <?php echo acym_escapeHtml(acym_translation('ACYM_ACYCHECKER_MORE_INFORMATION')); ?>
			</a>
		</div>
	</div>
    <?php
}
