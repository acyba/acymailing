<div id="acym__dashboard__splashscreen" class="cell grid-x acym__content">
	<form id="acym_form" action="<?php echo acym_completeLink(acym_getVar('cmd', 'ctrl')); ?>" method="post" name="acyForm" class="cell grid-x">
        <?php echo $data['content']; ?>
		<div class="cell grid-x align-center">
			<button class="cell shrink button margin-right-1 acy_button_submit" type="button" data-task="listing"><?php echo acym_translation('ACYM_OK'); ?></button>
			<a href="https://www.acymailing.com/change-log/" class="cell shrink button-secondary button margin-left-1" target="_blank"><?php echo acym_translation('ACYM_SEE_FULL_CHANGELOG'); ?></a>
		</div>
        <?php acym_formOptions(); ?>
	</form>
</div>
