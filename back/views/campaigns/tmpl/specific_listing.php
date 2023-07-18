<form id="acym_form" action="<?php echo acym_completeLink(acym_getVar('cmd', 'ctrl')); ?>" method="post" name="acyForm">
    <?php
    $isEmpty = empty($data['allCampaigns']) && empty($data['search']) && empty($data['status']) && empty($data['tag']);
    if (!$isEmpty) {
        $data['toolbar']->displayToolbar($data);
    }
    ?>
	<div id="acym__campaigns" class="acym__content">
        <?php
        $workflow = $data['workflowHelper'];
        echo $workflow->displayTabs($this->tabs, $data['typeWorkflowTab']);

        if ($isEmpty) {
            include acym_getView('campaigns', 'listing_empty', true);
        } else {
            include acym_getView('campaigns', 'listing_listing_specific', true);
        }
        ?>
	</div>
    <?php acym_formOptions(true, 'specificListing'); ?>
	<input type="hidden" name="type" value="<?php echo empty($data['type']) ? '' : $data['type']; ?>">
	<input type="hidden" name="email_type" value="<?php echo empty($data['type']) ? '' : $data['type']; ?>">
</form>
