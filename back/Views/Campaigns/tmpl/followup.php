<form id="acym_form" action="<?php echo acym_completeLink(acym_getVar('cmd', 'ctrl')); ?>" method="post" name="acyForm">
    <?php
    $isEmpty = empty($data['allCampaigns']) && empty($data['search']);
    if (!$isEmpty) {
        $data['toolbar']->displayToolbar($data);
    }
    ?>
	<div id="acym__campaigns" class="acym__content">
        <?php
        $workflow = $data['workflowHelper'];
        echo $workflow->displayTabs($this->tabs, 'followup');

        if (acym_level(ACYM_ENTERPRISE)) {
            //__START__enterprise_
            if ($isEmpty) {
                include acym_getView('campaigns', 'listing_empty', true);
            } else {
                include acym_getView('campaigns', 'listing_listing_followup', true);
            }
            //__END__enterprise_
        } else {
            include acym_getView('campaigns', 'followup_splashscreen', true);
        }
        ?>
	</div>
    <?php acym_formOptions(true, 'followup'); ?>
	<input type="hidden" name="email_type" value="followup">
</form>
