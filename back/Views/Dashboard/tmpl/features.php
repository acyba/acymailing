<div id="acym__dashboard__splashscreen" class="cell grid-x acym__content">
	<form id="acym_form" action="<?php echo acym_completeLink(acym_getVar('cmd', 'ctrl')); ?>" method="post" name="acyForm" class="cell grid-x">
        <?php echo $data['content']; ?>
        <?php acym_formOptions(); ?>
	</form>
</div>
