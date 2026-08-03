<?php
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- View file, its variables are local to the include scope, not true globals.
// context verification
?>
<form id="acym_form" action="<?php echo acym_escapeUrl(acym_completeLink(acym_getVar('cmd', 'ctrl'))); ?>" method="post" name="acyForm">
	<input type="hidden" value="<?php echo acym_escape($data['campaignID']); ?>" name="campaignId" id="acym__campaign__choose__campaign">
	<input type="hidden" name="mail[id]" value="<?php echo empty($data['mailInformation']->id) ? '' : intval($data['mailInformation']->id); ?>" />
	<div id="acym__templates__choose" class="acym__content">
        <?php
        $this->addSegmentStep($data['displaySegmentTab']);
        $workflow = $data['workflowHelper'];
        if (empty($data['campaignID'])) $workflow->disabledAfter = 'chooseTemplate';
        $workflow->display($this->steps, $this->step, true, false, '', 'campaignId');

        include acym_getView('mails', 'choose_template', true);
        ?>
	</div>
    <?php acym_formOptions(false, 'edit', 'chooseTemplate'); ?>
</form>
