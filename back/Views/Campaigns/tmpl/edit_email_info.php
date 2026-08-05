<?php
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- View file, its variables are local to the include scope, not true globals.
// context verification
?>
<div class="cell grid-x grid-margin-x margin-y">
	<div class="cell large-6">
		<label>
            <?php echo acym_escapeHtml(acym_translation('ACYM_CAMPAIGN_NAME')); ?>
			<input name="mail[name]" type="text" value="<?php echo acym_escape($data['mailInformation']->name ?? ''); ?>">
		</label>
	</div>
	<div class="cell large-6">
		<label>
            <?php
            echo acym_escapeHtml(acym_translation('ACYM_TAGS'));
            acym_selectMultiple(
                $data['allTags'],
                'template_tags',
                empty($data['mailInformation']->tags) ? [] : $data['mailInformation']->tags,
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

    <?php
    if (empty($data['multilingual']) && empty($data['abtest'])) {
        $preheaderSize = 'large-6';
        include acym_getView('campaigns', 'edit_email_info_content');
    } ?>

	<div class="cell grid-x">
        <?php
        acym_switch([
            'name' => 'visible',
            'value' => $data['mailInformation']->visible,
            'label' => acym_translation('ACYM_VISIBLE'),
            'tip' => ['textShownInTooltip' => 'ACYM_VISIBLE_CAMPAIGN_DESC'],
            'labelClass' => 'shrink',
        ])
        ?>
	</div>
</div>
