<form id="acym_form" action="<?php echo acym_completeLink(acym_getVar('cmd', 'ctrl')); ?>" method="post" name="acyForm">
	<div id="acym__templates__choose" class="acym__content">
        <?php include acym_getView('mails', 'choose_template', true); ?>
	</div>
    <?php acym_formOptions(false, 'choose'); ?>
</form>
