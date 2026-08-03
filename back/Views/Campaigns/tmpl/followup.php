<?php
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- View file, its variables are local to the include scope, not true globals.
// context verification
?>
<form id="acym_form" action="<?php echo acym_escapeUrl(acym_completeLink(acym_getVar('cmd', 'ctrl'))); ?>" method="post" name="acyForm">
    <?php
    $isEmpty = empty($data['allCampaigns']) && empty($data['search']);
    if (!$isEmpty) {
        $data['toolbar']->displayToolbar($data);
    }
    ?>
	<div id="acym__campaigns" class="acym__content">
        <?php
        $workflow = $data['workflowHelper'];
        $workflow->displayTabs($this->tabs, 'followup');

        if (acym_level(ACYM_ENTERPRISE)) {
        } else {
            include acym_getView('campaigns', 'followup_splashscreen', true);
        }
        ?>
	</div>
    <?php acym_formOptions(true, 'followup'); ?>
	<input type="hidden" name="email_type" value="followup">
</form>
