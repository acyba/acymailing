<form id="acym_form" action="<?php echo acym_completeLink(acym_getVar('cmd', 'ctrl')); ?>" method="post" name="acyForm">
    <?php
    if (empty($data['totalOverall'])) { ?>
		<div class="acym__content cell">
            <?php include acym_getView('scenarios', 'listing_empty'); ?>
		</div>
    <?php } else {
        $data['toolbar']->displayToolbar($data);
        ?>
		<div id="acym__scenarios__listing" class="acym__content cell">
            <?php include acym_getView('scenarios', 'listing_listing'); ?>
		</div>
        <?php
    }
    acym_formOptions();
    ?>
</form>
