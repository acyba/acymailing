<form id="acym_form" action="<?php echo acym_completeLink(acym_getVar('cmd', 'ctrl')); ?>" method="post" name="acyForm">
    <?php
    $isEmpty = empty($data['allCampaigns']) && empty($data['search']) && empty($data['tag']);
    if (!$isEmpty) {
        $data['toolbar']->displayToolbar($data);
    }
    ?>
	<div id="acym__unsubscribe-emails" class="acym__content">
        <?php
        $workflow = $data['workflowHelper'];
        echo $workflow->displayTabs($this->tabs, 'unsubscribe');

        if ($isEmpty) {
            include acym_getView('campaigns', 'listing_empty', true);
        } else {
            include acym_getView('campaigns', 'listing_emails_listing', true);
        }
        ?>
	</div>
    <?php acym_formOptions(true, 'unsubscribe'); ?>
</form>
