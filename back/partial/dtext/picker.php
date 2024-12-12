<div id="acym__dtext__picker" class="is-hidden">
	<div id="acym__dtext__picker__modal" class="padding-1">
		<i class="acymicon-close" id="acym__dtext__picker__modal__close"></i>
		<div class="grid-x grid-margin-x align-center">
			<input type="text" id="acym__dtext__picker__modal__code" class="cell small-8 medium-4" readonly="readonly" />
			<button type="button" id="acym__dtext__picker__modal__insert" class="cell small-4 medium-shrink button button-primary">
                <?php echo acym_escape(acym_translation('ACYM_INSERT')); ?>
			</button>
		</div>
        <?php
        foreach ($data['plugins'] as $onePlugin) {
            if (!empty($data['options']['context']) && empty($onePlugin->{$data['options']['context']})) {
                continue;
            }

            $data['tabHelper']->startTab($onePlugin->name);
            $defaultValues = new \stdClass();
            acym_trigger('textPopup', [$defaultValues], $onePlugin->plugin);
            $data['tabHelper']->endTab();
        }

        $data['tabHelper']->display('dtext_picker');
        ?>
	</div>
</div>
