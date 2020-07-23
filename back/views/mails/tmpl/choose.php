<form id="acym_form" action="<?php echo acym_completeLink(acym_getVar('cmd', 'ctrl')); ?>" method="post" name="acyForm">
	<div id="acym__templates__choose" class="acym__content">
        <?php
        include(ACYM_VIEW.'mails'.DS.'tmpl'.DS.'choose_template.php');
        ?>
	</div>
    <?php acym_formOptions(false, 'choose'); ?>
</form>
