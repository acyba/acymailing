<form id="acym_form" action="<?php echo acym_completeLink(acym_getVar('cmd', 'ctrl')); ?>" method="post" name="acyForm"
    <?php echo !empty($data['menuClass']) ? 'class="'.acym_escape($data['menuClass']).'"' : ''; ?> >
    <?php
    $isEmpty = empty($data['allUsers']) && empty($data['search']) && empty($data['status']);
    if (!$isEmpty) {
        $data['toolbar']->displayToolbar($data);
    }
    ?>
	<div id="acym__users" class="acym__content cell">
        <?php if ($isEmpty) {
            include acym_getView('users', 'listing_empty', true);
        } else {
            include acym_getView('users', 'listing_listing', true);
        } ?>
	</div>
    <?php acym_formOptions(); ?>
</form>
