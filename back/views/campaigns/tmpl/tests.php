<form id="acym_form"
	  action="<?php echo acym_completeLink(acym_getVar('cmd', 'ctrl').'&task='.acym_getVar('string', 'task').'&id='.acym_getVar('string', 'id')); ?>"
	  method="post"
	  name="acyForm"
	  class="acym__form__campaign__edit">
	<input type="hidden" name="id" value="<?php echo acym_escape($data['id']); ?>">
	<div class="cell grid-x">
		<div class="cell medium-auto"></div>
		<div id="acym__campaigns__tests" class="cell xxlarge-9 grid-x acym__content">
            <?php
            $this->addSegmentStep($data['displaySegmentTab']);
            $workflow = $data['workflowHelper'];
            echo $workflow->display($this->steps, $this->step);
            ?>
			<div class="cell grid-x grid-margin-x" id="campaigns_tests_step">
				<div class="cell large-5">
                    <?php
                    include acym_getView('campaigns', 'tests_email_checker');
                    include acym_getView('campaigns', 'tests_mailtester');
                    ?>
				</div>
				<div class="cell large-1 margin-top-2 acym_zone_separator"></div>
                <?php include acym_getView('campaigns', 'tests_send'); ?>
			</div>
            <?php include acym_getView('campaigns', 'tests_actions'); ?>
		</div>
		<div class="medium-auto cell"></div>
	</div>
    <?php acym_formOptions(true, 'edit', 'tests'); ?>
</form>
