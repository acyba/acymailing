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
        } else {
            include acym_getView('campaigns', 'followup_splashscreen', true);
        }
        ?>
	</div>
    <?php acym_formOptions(true, 'followup'); ?>
</form>
