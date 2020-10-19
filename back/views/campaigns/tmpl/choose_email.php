<form id="acym_form" action="<?php echo acym_completeLink(acym_getVar('cmd', 'ctrl')); ?>" method="post" name="acyForm">
	<input type="hidden" value="<?php echo acym_escape($data['campaignID']); ?>" name="id" id="acym__campaign__choose__campaign">
	<input type="hidden" name="mail[id]" value="<?php echo empty($data['mailInformation']->id) ? '' : intval($data['mailInformation']->id); ?>" />
	<div id="acym__templates__choose" class="acym__content">
        <?php
        $this->addSegmentStep($data['displaySegmentTab']);
        $workflow = $data['workflowHelper'];
        if (empty($data['campaignID'])) $workflow->disabledAfter = 'chooseTemplate';
        echo $workflow->display($this->steps, $this->step);

        include acym_getView('mails', 'choose_template', true);
        ?>
	</div>
    <?php acym_formOptions(false, 'edit', 'chooseTemplate'); ?>
</form>
