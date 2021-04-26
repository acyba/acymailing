<div class="cell xlarge-3 medium-6">
	<label>
        <?php echo acym_translation('ACYM_TEMPLATE_NAME'); ?>
		<input name="mail[name]" type="text" class="acy_required_field" value="<?php echo acym_escape($data['mail']->name); ?>" required>
	</label>
</div>
<?php if (empty($data['multilingual'])) { ?>
	<div class="cell xlarge-3 medium-6">
		<label>
            <?php echo acym_translation('ACYM_EMAIL_SUBJECT'); ?>
			<input name="mail[subject]" type="text" value="<?php echo acym_escape($data['mail']->subject); ?>" <?php echo in_array(
                $data['mail']->type,
                [$data['mailClass']::TYPE_WELCOME, $data['mailClass']::TYPE_UNSUBSCRIBE, $data['mailClass']::TYPE_AUTOMATION]
            ) ? 'required' : ''; ?>>
		</label>
	</div>
<?php } ?>
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
if ($data['mail']->type === $data['mailClass']::TYPE_TEMPLATE) {
    if (acym_level(ACYM_ENTERPRISE) && ACYM_CMS === 'joomla') {
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
            echo acym_selectMultiple($data['lists'], 'list_ids', empty($data['list_id']) ? [] : $data['list_id'], ['class' => 'acym__select']);
            ?>
		</label>
	</div>
<?php } ?>
