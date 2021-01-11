<form id="acym_form" action="<?php echo acym_completeLink('dashboard'); ?>" method="post" name="acyForm" data-abide novalidate>
	<div id="acym__walkthrough">
		<div class="acym__walkthrough cell grid-x" id="acym__walkthrough__<?php echo $data['step']; ?>">
            <?php if ('subscribe' === $data['step']) { ?>
				<div class="cell text-center grid-x">
					<h1 class="cell acym__title text-center"><?php echo acym_translation('ACYM_THANKS_FOR_INSTALLING_ACYM'); ?></h1>
					<h2 class="acym__walkthrough__title__subwelcome cell"><?php echo acym_translation('ACYM_WALK_THROUGH_STEPS_TO_GET_STARTED'); ?></h2>
				</div>
            <?php } ?>
			<div class="cell xlarge-3 medium-1"></div>

			<div class="acym__content cell text-center grid-x xlarge-6 medium-10 small-12 acym__walkthrough__content text-center align-center">
                <?php if ('subscribe' !== $data['step']) { ?>
					<span class="acy_button_submit acym__color__dark-gray"
						  id="acym__walkthrough__skip"
						  data-force-submit="true"
						  data-task="passWalkThrough"><?php echo acym_translation('ACYM_SKIP'); ?></span>
                <?php } ?>
                <?php include acym_getView('dashboard', 'step_'.$data['step']); ?>
			</div>
		</div>
	</div>
    <?php acym_formOptions(true, '', null, 'dashboard'); ?>
</form>
