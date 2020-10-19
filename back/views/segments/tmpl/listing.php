<form id="acym_form" action="<?php echo acym_completeLink(acym_getVar('cmd', 'ctrl')); ?>" method="post" name="acySegments">
    <?php
    $isEmpty = empty($data['segments']) && empty($data['search']) && empty($data['active']);
    if (!$isEmpty) {
        $data['toolbar']->displayToolbar($data);
    }

    ?>
	<div id="acym__forms" class="acym__content cell">
        <?php if ($isEmpty) {
            include acym_getView('segments', 'listing_empty');
        } else {
            include acym_getView('segments', 'listing_listing');
        } ?>
	</div>
    <?php acym_formOptions(); ?>
</form>
