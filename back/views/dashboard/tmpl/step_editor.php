<?php

use AcyMailing\Classes\MailClass;

?>

<form id="acym_form" action="<?php echo acym_completeLink('dashboard'); ?>" method="post" name="acyForm" data-abide novalidate>
	<div id="acym__walkthrough">
		<div class="acym__walkthrough cell grid-x" id="acym__walkthrough__editor">
			<div class="acym__content cell text-center grid-x acym__walkthrough__content align-center">
				<h1 class="cell acym__title text-center"><?php echo acym_translation('ACYM_YOUR_FIRST_EMAIL'); ?></h1>

				<div class="cell grid-x align-center margin-top-1">
					<div class="cell xxlarge-6 large-8 grid-x">
						<div class="cell"><?php echo acym_translation('ACYM_PREVIEW_OF_THE_EMAIL'); ?></div>
					</div>
				</div>

				<div id="acym__walkthrough__editor__container" class="cell grid-x xxlarge-7 large-8">
					<input type="hidden" name="mail[id]" value="<?php echo acym_escape($data['mail']->id); ?>" />
					<input type="hidden" name="id" value="<?php echo acym_escape($data['mail']->id); ?>" />
					<input type="hidden"
						   id="acym__mail__edit__editor__social__icons"
						   value="<?php echo acym_escape($data['social_icons']); ?>">
					<input type="hidden" id="acym__mail__type" name="mail[type]" value="<?php echo acym_escape(MailClass::TYPE_STANDARD); ?>">
					<input type="hidden" name="task">
                    <?php echo $data['editor']->display(); ?>
				</div>

				<div class="cell grid-x grid-margin-x align-center margin-top-2">
					<button type="button" data-task="startUsing" class="cell shrink button button-secondary acy_button_submit">
                        <?php echo acym_translation('ACYM_START_USING'); ?>
					</button>
				</div>
			</div>
		</div>
	</div>
    <?php acym_formOptions(true, '', '', 'dashboard'); ?>
</form>
