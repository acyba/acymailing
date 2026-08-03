<?php
// context verification
use AcyMailing\Classes\MailClass;

?>
<div class="cell xlarge-3 medium-6">
	<label>
        <?php echo acym_escapeHtml(acym_translation('ACYM_NAME')); ?>
		<input name="mail[name]" type="text" class="acy_required_field" value="<?php echo acym_escape($data['mail']->name); ?>" required>
	</label>
</div>
<?php if (empty($data['multilingual']) && empty($data['abtest'])) { ?>
	<div class="cell xlarge-3 medium-6">
		<label>
            <?php echo acym_escapeHtml(acym_translation('ACYM_EMAIL_SUBJECT')); ?>
			<input name="mail[subject]" type="text" value="<?php echo acym_escape($data['mail']->subject ?? ''); ?>" <?php echo in_array(
                $data['mail']->type,
                [
                    MailClass::TYPE_WELCOME,
                    MailClass::TYPE_UNSUBSCRIBE,
                    MailClass::TYPE_AUTOMATION,
                ]
            ) ? 'required' : ''; ?>>
		</label>
	</div>
<?php } ?>
<div class="cell xlarge-3 medium-6">
	<label>
        <?php
        echo acym_escapeHtml(acym_translation('ACYM_TAGS'));
        acym_selectMultiple(
            $data['allTags'],
            'template_tags',
            $data['mail']->tags,
            [
                'id' => 'acym__tags__field',
                'placeholder' => acym_translation('ACYM_ADD_TAGS'),
            ],
            'name',
            'name',
            true
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
                echo acym_escapeHtml(acym_translation('ACYM_TEMPLATE_ACCESS'));
                acym_info(['textShownInTooltip' => 'ACYM_TEMPLATE_ACCESS_DESC']);
                acym_selectMultiple(
                    acym_getGroups(),
                    'mail[access]',
                    $data['mail']->access,
                    [
                        'class' => 'acym__select',
                    ],
                    'value',
                    'text',
                    true
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
            echo acym_escapeHtml(acym_translation('ACYM_EMAIL_LANGUAGE'));
            acym_info(['textShownInTooltip' => 'ACYM_EMAIL_LANGUAGE_DESC']);
            acym_languageOption($data['langChoice']['links'], $data['langChoice']['name']);
            ?>
		</label>
	</div>
<?php } ?>

<?php if (!empty($data['lists'])) { ?>
	<div class="cell xlarge-3 medium-6">
		<label>
            <?php
            echo acym_escapeHtml(acym_translation('ACYM_SELECT_ONE_OR_MORE_LIST'));
            acym_selectMultiple($data['lists'], 'list_ids', empty($data['list_id']) ? [] : $data['list_id'], ['class' => 'acym__select'], 'value', 'text', true);
            ?>
		</label>
	</div>
<?php } ?>
