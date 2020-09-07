<div class="cell xlarge-3 medium-6">
	<label>
        <?php echo acym_translation('ACYM_TEMPLATE_NAME'); ?>
		<input name="mail[name]" type="text" class="acy_required_field" value="<?php echo acym_escape($data['mail']->name); ?>" required>
	</label>
</div>
<div class="cell xlarge-3 medium-6">
	<label>
        <?php echo acym_translation('ACYM_EMAIL_SUBJECT'); ?>
		<input name="mail[subject]" type="text" value="<?php echo acym_escape($data['mail']->subject); ?>" <?php echo in_array($data['mail']->type, ['welcome', 'unsubscribe', 'automation']) ? 'required' : ''; ?>>
	</label>
</div>
<div class="cell xlarge-3 medium-6">
	<label>
        <?php
        echo acym_translation('ACYM_TAGS');
        echo acym_selectMultiple(
            $data['allTags'],
            'template_tags',
            $data['mail']->tags,
            [
                'id' => 'acym__tags__field',
                'placeholder' => acym_translation('ACYM_ADD_TAGS'),
            ],
            'name',
            'name'
        );
        ?>
	</label>
</div>
<div class="cell shrink"></div>

<?php
if ($data['mail']->type === 'standard') {
    if (acym_level(2) && ACYM_CMS === 'joomla') {
        ?>
		<div class="cell xlarge-3 medium-6">
			<label class="cell">
                <?php
                echo acym_translation('ACYM_TEMPLATE_ACCESS');
                echo acym_info('ACYM_TEMPLATE_ACCESS_DESC');
                echo acym_selectMultiple(
                    acym_getGroups(),
                    'mail[access]',
                    $data['mail']->access,
                    [
                        'class' => 'acym__select',
                    ]
                );
                ?>
			</label>
		</div>
        <?php
    }
}
if (!empty($data['langChoice'])) {
    ?>
	<div class="cell xlarge-3 medium-6">
		<label class="cell">
            <?php
            echo acym_translation('ACYM_EMAIL_LANGUAGE');
            echo acym_info('ACYM_EMAIL_LANGUAGE_DESC');
            echo $data['langChoice'];
            ?>
		</label>
	</div>
<?php } ?>

<?php if (!empty($data['lists'])) { ?>
	<div class="cell xlarge-3 medium-6">
		<label>
            <?php
            echo acym_translation('ACYM_SELECT_ONE_OR_MORE_LIST');
            echo acym_selectMultiple($data['lists'], 'list_ids', $data['list_id'], ['class' => 'acym__select']);
            ?>
		</label>
	</div>
<?php } ?>

<div class="cell grid-x acym__toggle__arrow">
	<p class="cell medium-shrink acym__toggle__arrow__trigger"><?php echo acym_translation('ACYM_ADVANCED_OPTIONS'); ?> <i class="acymicon-keyboard_arrow_down"></i></p>
	<div class="cell acym__toggle__arrow__contain">
		<div class="grid-x grid-padding-x">
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
					<textarea class="acym__blue" name="editor_stylesheet" id="acym__mail__edit__html__stylesheet" cols="30" rows="15" type="text"><?php echo $stylesheet; ?></textarea>
				</div>
            <?php } ?>
			<div class="cell medium-auto">
				<label for="acym__mail__edit__custom__header"><?php
                    echo acym_translation('ACYM_CUSTOM_HEADERS');
                    echo acym_info(acym_translation('ACYM_EMAIL_CUSTOM_HEADERS_DESC'));
                    ?>
				</label>
				<textarea class="acym__blue" id="acym__mail__edit__custom__header" name="editor_headers" cols="30" rows="15" type="text"><?php echo acym_escape($data['mail']->headers); ?></textarea>
			</div>
		</div>
	</div>
</div>
