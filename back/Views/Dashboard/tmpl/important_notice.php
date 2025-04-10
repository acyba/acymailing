<?php if (!empty($data['dashboardNotifications'])) { ?>
	<div class="acym__content cell margin-bottom-1" id="acym__dashboard__notifications">
		<div class="cell acym__title acym__dashboard__title"><?php echo acym_translation('ACYM_IMPORTANT_NOTICE') ?></div>
		<span class="separator"></span>
        <?php echo $data['dashboardNotifications']; ?>
	</div>
<?php } ?>
