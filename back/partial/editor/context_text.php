<div id="acym__wysid__context__text" style="display: none" class="grid-x padding-1">
	<p class="cell acym__wysid__right__toolbar__p__open acym__wysid__right__toolbar__p acym__title">
        <?php echo acym_translation('ACYM_DYNAMIC_TEXT_TYPE').acym_info('ACYM_DYNAMIC_TEXT_TYPE_DESC'); ?>
		<i class="acymicon-keyboard_arrow_up"></i>
	</p>
	<div class="grid-x cell acym__wysid__context__modal__container grid-margin-x margin-y">
        <?php
        foreach ($data['plugins'] as $onePlugin) {
            if (empty($onePlugin)) continue;

            echo '<button type="button"
						class="cell medium-4 button button-secondary"
						acym-button-switch-type="'.$onePlugin->plugin.'">'.$onePlugin->name.'</button>';

            $data['tabHelper']->startTab($onePlugin->plugin);
            $defaultValues = new \stdClass();
            acym_trigger('textPopup', [$defaultValues], $onePlugin->plugin);
            $data['tabHelper']->endTab();
        }
        ?>
	</div>
	<p class="cell acym__wysid__right__toolbar__p__open acym__wysid__right__toolbar__p acym__title">
        <?php echo acym_translation('ACYM_CONTENT_TO_INSERT').acym_info('ACYM_CONTENT_TO_INSERT_DESC'); ?>
		<i class="acymicon-keyboard_arrow_up"></i>
	</p>
	<div class="grid-x cell acym__wysid__context__modal__container grid-margin-x margin-y">
        <?php $data['tabHelper']->display('dtext_options'); ?>
	</div>
	<div id="acym__dynamic__texts__insert__zone" class="cell grid-x">
		<input title="<?php echo acym_translation('ACYM_DYNAMIC_TEXT'); ?>"
			   type="text"
			   class="cell large-auto margin-right-1"
			   id="dtextcode"
			   name="dtextcode"
			   value=""
			   onclick="this.select();">
		<div class="cell large-shrink">
			<button type="button" class="button" id="insertButton"><?php echo acym_translation('ACYM_INSERT_DYNAMIC_TEXT'); ?></button>
		</div>
	</div>
</div>
