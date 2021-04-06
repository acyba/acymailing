<div class="cell grid-x acym__toggle__arrow">
	<p class="cell medium-shrink acym__toggle__arrow__trigger"><?php echo acym_translation('ACYM_ADVANCED_OPTIONS'); ?> <i class="acymicon-keyboard_arrow_down"></i></p>
	<div class="cell acym__toggle__arrow__contain">
		<div class="grid-x grid-padding-x margin-y">
            <?php if (empty($data['multilingual'])) { ?>
				<div class="cell grid-x">
					<div class="cell medium-shrink">
						<label for="acym__mail__edit__preheader">
                            <?php
                            echo acym_translation('ACYM_EMAIL_PREHEADER');
                            echo acym_info('ACYM_EMAIL_PREHEADER_DESC');
                            ?>
						</label>
					</div>
					<input id="acym__mail__edit__preheader" name="mail[preheader]" type="text" maxlength="255" value="<?php echo acym_escape($data['mail']->preheader); ?>">
				</div>
            <?php } ?>
            <?php if (empty($data['mail']->drag_editor)) { ?>
				<div class="cell grid-x medium-6" id="acym__mail__edit__html__stylesheet__container">
					<div class="cell medium-shrink">
						<label for="acym__mail__edit__html__stylesheet">
                            <?php
                            echo acym_tooltip(
                                acym_translation('ACYM_CUSTOM_ADD_STYLESHEET'),
                                acym_translation('ACYM_STYLESHEET_HTML_DESC')
                            );
                            $stylesheet = empty($data['mail']->stylesheet) ? '' : $data['mail']->stylesheet;
                            ?>
						</label>
					</div>
					<textarea
							name="editor_stylesheet"
							id="acym__mail__edit__html__stylesheet"
							cols="30"
							rows="15"
							type="text"><?php echo $stylesheet; ?></textarea>
				</div>
            <?php } ?>
			<div class="cell medium-auto">
				<label for="acym__mail__edit__custom__header"><?php
                    echo acym_translation('ACYM_CUSTOM_HEADERS');
                    echo acym_info('ACYM_EMAIL_CUSTOM_HEADERS_DESC');
                    ?>
				</label>
				<textarea id="acym__mail__edit__custom__header" name="editor_headers" cols="30" rows="15" type="text"><?php echo acym_escape(
                        $data['mail']->headers
                    ); ?></textarea>
			</div>
            <?php if (!empty($data['mail']->type) && !in_array(
                    $data['mail']->type,
                    [$data['mailClass']::TYPE_STANDARD, $data['mailClass']::TYPE_TEMPLATE]
                )) { ?>
				<div class="cell grid-x">
					<div class="cell grid-x medium-6">
                        <?php include acym_getPartial('editor', 'attachments'); ?>
					</div>
				</div>
            <?php } ?>
		</div>
	</div>
</div>
