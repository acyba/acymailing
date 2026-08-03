<?php
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- View file, its variables are local to the include scope, not true globals.
// context verification
?>
<div class="cell grid-x grid-margin-x margin-left-0 margin-right-0 margin-y">
    <?php
    if (!empty($data['translation_languages'])) {
        acym_displayLanguageRadio(
            $data['translation_languages'],
            'list[translation]',
            $data['listInformation']->translation,
            acym_translation('ACYM_LANGUAGE_LIST_DESC'),
            '',
            'list'
        );
    } ?>
	<div class="cell">
		<label>
            <?php echo acym_escapeHtml(acym_translation('ACYM_LIST_NAME')); ?>
			<input name="list[name]" type="text" class="acy_required_field" value="<?php echo acym_escape($data['listInformation']->name); ?>" required>
		</label>
	</div>
	<div class="cell grid-x acym__list__settings__active small-6">
        <?php acym_switch([
            'name' => 'list[active]',
            'value' => acym_escape($data['listInformation']->active),
            'label' => acym_translation('ACYM_ACTIVE'),
            'labelClass' => 'shrink',
            'switchContainerClass' => 'shrink',
            'switchClass' => 'margin-0',
        ]); ?>
	</div>
	<div class="cell margin-bottom-1 small-6 grid-x acym_vcenter align-left" id="acym__lists__settings__list-color">
        <?php echo acym_escapeHtml(acym_translation('ACYM_COLOR')); ?> :
		<input type="text" name="list[color]" id="acym__list__settings__color-picker" class="cell small-8" value="<?php echo acym_escape($data['listInformation']->color); ?>" />
	</div>
	<div class="cell grid-x acym__list__settings__visible small-6">
        <?php acym_switch([
            'name' => 'list[visible]',
            'value' => acym_escape($data['listInformation']->visible),
            'label' => acym_translation('ACYM_VISIBLE'),
            'labelClass' => 'shrink',
            'switchContainerClass' => 'shrink',
            'switchClass' => 'margin-0',
        ]); ?>
	</div>
    <?php if (!empty($data['listInformation']->id)) { ?>
		<p class="cell margin-bottom-1 small-6 text-left" id="acym__list__settings__list-id">
            <?php echo acym_escapeHtml(acym_translation('ACYM_LIST_ID')); ?> :
			<b class="acym__color__blue"><?php echo acym_escapeHtml($data['listInformation']->id); ?></b>
		</p>
    <?php } ?>
	<div class="cell grid-x small-6">
        <?php
        acym_switch([
            'name' => 'list[tracking]',
            'value' => $data['listInformation']->tracking,
            'label' => acym_translation('ACYM_TRACK_THIS_LIST'),
            'tip' => ['textShownInTooltip' => 'ACYM_TRACK_THIS_LIST_DESC'],
            'labelClass' => 'small-6',
            'switchContainerClass' => 'shrink',
            'switchClass' => 'margin-0',
        ]); ?>
	</div>
	<div class="cell small-6">
        <?php echo acym_escapeHtml(acym_translation('ACYM_DATE_CREATED')); ?> : <b><?php echo acym_escapeHtml(
                acym_date(
                    empty($data['listInformation']->id) ? time() : $data['listInformation']->creation_date,
                    'ACYM_DATE_FORMAT_LC3'
                )
            ); ?></b>
	</div>
</div>
