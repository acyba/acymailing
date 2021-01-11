<div class="cell grid-x acym__content margin-bottom-1" id="acym__forms__information">
	<div class="cell">
		<div class="grid-x grid-margin-x margin-y">
			<div class="cell medium-6 grid-x acym_vcenter">
				<label for="acym__forms__information__name" class="cell medium-6 large-4">
                    <?php echo acym_translation('ACYM_FORM_NAME'); ?>
				</label>
				<input type="text" id="acym__forms__information__name" class="cell large-5" name="form[name]" v-model="form.name">
			</div>
            <?php if ($data['form']->type != 'shortcode') { ?>
				<div class="cell medium-6 grid-x acym_vcenter">
					<label for="acym__forms__information__page" class="cell medium-6 large-4">
                        <?php echo acym_translation('ACYM_PAGE_SELECTION').acym_info('ACYM_PAGE_SELECTION_DESC'); ?>
					</label>
					<div class="cell medium-6">
						<select2multiple v-model="form.pages"
										 :name="'form[pages]'"
										 :value="<?php echo acym_escape(json_encode($data['form']->pages)); ?>"
										 :options="<?php echo acym_escape(json_encode($data['all_pages'])); ?>"></select2multiple>
					</div>
				</div>
            <?php } ?>
			<div class="cell medium-6 grid-x acym_vcenter">
                <?php
                echo acym_switch(
                    'form[active]',
                    $data['form']->active,
                    acym_translation('ACYM_ACTIVATED'),
                    [],
                    'large-4 medium-6 small-9',
                    'auto',
                    '',
                    null,
                    true,
                    'v-model="form.active"'
                );
                ?>
			</div>
            <?php if ($data['form']->type == 'popup') { ?>
				<div class="cell medium-6 grid-x acym_vcenter">
					<label for="acym__forms__information__delay" class="cell large-4 medium-6">
                        <?php echo acym_translation('ACYM_DELAY').acym_info('ACYM_DELAY_DESC'); ?>
					</label>
					<input required type="number" name="form[delay]" id="acym__forms__information__delay" class="cell large-2 medium-4 small-auto" v-model="form.delay">
					<span class="margin-left-1"><?php echo acym_translation('ACYM_SECONDS'); ?></span>
				</div>
            <?php } ?>
            <?php
            if ($data['form']->type == 'shortcode') {
                echo '<div class="cell grid-x acym_vcenter">';
                echo '<p class="cell shrink" v-if="!form.id"><i class="acymicon-exclamation-triangle acym__color__orange acym__forms__information__shortcode__warning margin-right-1"></i>'.acym_translation(
                        'ACYM_PLEASE_SAVE_FORM_TO_GET_SHORTCODE'
                    ).'</p>';
                echo '<p class="cell shrink" v-if="form.id">'.acym_translation('ACYM_SHORTCODE_COPY_PASTE').'</p>';
                echo '<code class="cell shrink acym__forms__information__shortcode margin-left-1" v-if="form.id">[acymailing_form_shortcode id="{{ form.id }}"]</code>';
                echo '</div>';
            }
            ?>
		</div>
	</div>
</div>
