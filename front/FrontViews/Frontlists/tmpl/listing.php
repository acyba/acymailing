<form id="acym_form" action="<?php echo acym_completeLink(acym_getVar('cmd', 'ctrl')); ?>" method="post" name="acyForm"
    <?php echo !empty($data['menuClass']) ? 'class="'.acym_escape($data['menuClass']).'"' : ''; ?> >
    <?php
    $isEmpty = empty($data['lists']) && empty($data['search']) && empty($data['tag']) && empty($data['status']);
    if (!$isEmpty) {
        $data['toolbar']->displayToolbar($data);
    }
    ?>
	<div id="acym__lists" class="acym__content">
        <?php if ($isEmpty) {
            include acym_getView('lists', 'listing_empty', true);
        } else {
            include acym_getView('lists', 'listing_listing', true);
        } ?>
	</div>
    <?php acym_formOptions(); ?>
</form>
