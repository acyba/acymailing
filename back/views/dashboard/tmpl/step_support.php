<h2 class="cell acym__title text-center margin-bottom-2"><?php echo acym_translation('ACYM_WALKTHROUGH_HOUSTON'); ?></h2>

<div class="cell margin-top-2 margin-bottom-2">
	<i class="acymicon-ambulance" style="font-size: 8rem;"></i>
</div>

<div class="cell margin-top-2 margin-bottom-3">
	<p class="acym__walkthrough__text">
        <?php echo acym_translation('ACYM_CONTACT_WELL_CONTACT_YOU'); ?><br />
        <?php echo acym_translation('ACYM_CONTACT_NEEDED_INFO'); ?><br /> <br />
        <?php echo acym_translationSprintf(
            'ACYM_CONTACT_DIRECT',
            '<a class="acym__color__blue" href="'.ACYM_ACYMAILLING_WEBSITE.'contact" target="_blank">'.acym_translation('ACYM_GET_IN_TOUCH').'</a>'
        ); ?>
	</p>
</div>

<div class="cell margin-top-3">
	<button type="button" class="acy_button_submit button" data-task="saveStepSupport"><?php echo acym_translation('ACYM_CONTINUE'); ?></button>
</div>
