<form id="acym_form" action="<?php echo acym_completeLink(acym_getVar('cmd', 'ctrl')); ?>" method="post" name="acyForm"
    <?php echo !empty($data['menuClass']) ? 'class="'.acym_escape($data['menuClass']).'"' : ''; ?> >
    <?php
    $isEmpty = empty($data['allCampaigns']) && empty($data['search']) && empty($data['tag']);
    if (!$isEmpty) {
        $data['toolbar']->displayToolbar($data);
    }
    ?>
	<div id="acym__welcome-emails" class="acym__content">
        <?php
        $workflow = $data['workflowHelper'];
        echo $workflow->displayTabs($this->tabs, 'welcome');

        if ($isEmpty) {
            include acym_getView('campaigns', 'listing_empty', true);
        } else {
            include acym_getView('campaigns', 'listing_emails_listing', true);
        }
        ?>
	</div>
    <?php acym_formOptions(true, 'welcome'); ?>
	<input type="hidden" name="email_type" value="welcome">
</form>
