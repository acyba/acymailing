<div class="acym__content acym_center_baseline cell grid-x large-4 margin-bottom-1">
	<label class="cell grid-x">
		<span class="cell medium-4 acym__label"><?php echo acym_translation('ACYM_NAME'); ?></span>
		<input required class="cell medium-7" type="text" name="bounce[name]" value="<?php echo empty($data['rule']) ? '' : $data['rule']->name; ?>">
	</label>
	<div class="cell grid-x margin-top-1"><?php echo acym_switch(
            'bounce[active]',
            (empty($data['rule']) ? 1 : $data['rule']->active),
            acym_translation('ACYM_ENABLED'),
            [],
            'medium-4'
        ); ?></div>
</div>
