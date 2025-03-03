<div class="cell grid-x" id="acym__scenario__top__actions">
	<a href="<?php echo acym_completeLink('scenarios'); ?>" class="cell medium-2 acym_vcenter" id="acym__scenario__top__actions__back">
		<i class="acymicon-chevron-left"></i>
		<span><?php echo acym_translation('ACYM_RETURN_LISTING'); ?></span>
	</a>
	<div class="cell medium-8 text-center acym__scenario__workflow__container">
        <?php
        if (empty($data['scenario']->id)) {
            $data['workflowHelper']->disabledAfter = 'editScenario';
        }
        echo $data['workflowHelper']->displayNew($this->steps, $this->step, true, '', 'scenarioId');
        ?>
	</div>
	<div class="cell medium-2 text-right">
        <?php if (empty($data['isPerformance'])) { ?>
			<button type="button" id="acym__scenario__top__actions__configuration">
				<i class="acymicon-cog"></i>
			</button>
        <?php } ?>
	</div>
</div>
