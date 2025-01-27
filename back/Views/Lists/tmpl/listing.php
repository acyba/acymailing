<form id="acym_form" action="<?php echo acym_completeLink(acym_getVar('cmd', 'ctrl')); ?>" method="post" name="acyForm">
    <?php
    $isEmpty = empty($data['lists']) && empty($data['search']) && empty($data['tag']) && empty($data['status']);
    if (!$isEmpty) {
        $data['toolbar']->displayToolbar($data);
    } ?>
	<div id="acym__lists" class="acym__content">
        <?php if ($isEmpty) {
            include acym_getView('lists', 'listing_empty');
        } else {
            include acym_getView('lists', 'listing_listing');
        } ?>
	</div>
    <?php acym_formOptions(); ?>
</form>
