<div class="cell grid-x acym__toggle__arrow">
	<p class="cell medium-shrink acym__toggle__arrow__trigger"><?php echo acym_translation('ACYM_ADVANCED_OPTIONS'); ?> <i class="acymicon-keyboard_arrow_down"></i></p>
	<div class="cell acym__toggle__arrow__contain">
		<div class="grid-x grid-padding-x margin-y">
            <?php if (!empty($data['mail']->type) && in_array(
                    $data['mail']->type,
                    [$data['mailClass']::TYPE_WELCOME, $data['mailClass']::TYPE_TEMPLATE, $data['mailClass']::TYPE_UNSUBSCRIBE, $data['mailClass']::TYPE_NOTIFICATION]
                )) { ?>
				<div class="cell xlarge-3 medium-6">
					<label for="acym__mail__edit__from-name" class="cell">
                        <?php
                        echo acym_translation('ACYM_FROM_NAME');
                        ?>
					</label>
					<input type="text"
						   id="acym__mail__edit__from-name"
						   class="cell"
						   maxlength="100"
						   value="<?php echo acym_escape($data['mail']->from_name); ?>"
						   name="mail[from_name]">
				</div>
				<div class="cell xlarge-3 medium-6">
					<label for="acym__mail__edit__from-email" class="cell">
                        <?php
                        echo acym_translation('ACYM_FROM_EMAIL');
                        ?>
					</label>
					<input type="email"
						   id="acym__mail__edit__from-email"
						   class="cell"
						   maxlength="100"
						   value="<?php echo acym_escape($data['mail']->from_email); ?>"
						   name="mail[from_email]">
				</div>
				<div class="cell xlarge-3 medium-6">
					<label for="acym__mail__edit__replyto-name" class="cell">
                        <?php
                        echo acym_translation('ACYM_REPLYTO_NAME');
                        ?>
					</label>
					<input type="text"
						   id="acym__mail__edit__replyto-name"
						   class="cell"
						   maxlength="100"
						   value="<?php echo acym_escape($data['mail']->reply_to_name); ?>"
						   name="mail[reply_to_name]">
				</div>
				<div class="cell xlarge-3 medium-6">
					<label for="acym__mail__edit__replyto-email" class="cell">
                        <?php
                        echo acym_translation('ACYM_REPLYTO_EMAIL');
                        ?>
					</label>
					<input type="email"
						   id="acym__mail__edit__replyto-email"
						   class="cell"
						   maxlength="100"
						   value="<?php echo acym_escape($data['mail']->reply_to_email); ?>"
						   name="mail[reply_to_email]">
				</div>
            <?php } ?>
            <?php if (empty($data['multilingual']) && empty($data['abtest'])) { ?>
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
            <?php if (!empty($data['mail']->type) && $data['mail']->type === $data['mailClass']::TYPE_TEMPLATE) { ?>
				<div class="cell grid-x">
					<div class="cell grid-x medium-6">
						<label for="acym__mail__edit__thumbnail" class="cell shrink">
                            <?php
                            echo acym_translation('ACYM_EMAIL_CUSTOM_THUMBNAIL');
                            echo acym_info('ACYM_EMAIL_CUSTOM_THUMBNAIL_DESC');
                            ?>
						</label>
						<button class="cell shrink button button-secondary" type="button" id="acym__mail__edit__thumbnail--input">
                            <?php echo acym_translation('ACYM_UPLOAD_IMAGE') ?>
						</button>
						<input id="acym__mail__edit__thumbnail" name="custom_thumbnail" type="file">
						<span id="acym__mail__edit__thumbnail--file" class="cell shrink acym_vcenter margin-left-1"></span>
						<button type="button" id="acym__mail__edit__thumbnail--delete" class="acymicon-close acym__color__red cursor-pointer acym_vcenter"></button>
                        <?php if (!empty($data['mail']->thumbnail) && strpos($data['mail']->thumbnail, '_custom_') !== false) { ?>
							<div id="acym__mail__edit__thumbnail--saved" class="cell shrink grid-x">
								<span id="acym__mail__edit__thumbnail--file-saved" class="cell shrink acym_vcenter margin-left-1">
									<?php echo $data['mail']->thumbnail; ?>
								</span>
								<button type="button" id="acym__mail__edit__thumbnail--delete-saved" class="acymicon-close acym__color__red cursor-pointer acym_vcenter"></button>
								<input type="hidden" name="custom_thumbnail_reset" value="0">
							</div>
                        <?php } ?>
					</div>
				</div>
            <?php } ?>
            <?php if (empty($data['mail']->drag_editor)) { ?>
				<div class="cell grid-x medium-6" id="acym__mail__edit__html__stylesheet__container">
					<div class="cell medium-shrink">
						<label for="acym__mail__edit__html__stylesheet">
                            <?php
                            echo acym_tooltip(
                                [
                                    'hoveredText' => acym_translation('ACYM_CUSTOM_ADD_STYLESHEET'),
                                    'textShownInTooltip' => acym_translation('ACYM_STYLESHEET_HTML_DESC'),
                                ]
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
