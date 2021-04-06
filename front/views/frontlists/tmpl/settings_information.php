<div class="cell grid-x grid-margin-x margin-left-0 margin-right-0 margin-y">
    <?php
    if (!empty($data['translation_languages'])) {
        echo acym_displayLanguageRadio($data['translation_languages'], 'list[translation]', $data['listInformation']->translation, acym_translation('ACYM_LANGUAGE_LIST_DESC'));
    } ?>
	<div class="cell">
		<label>
            <?php echo acym_translation('ACYM_LIST_NAME'); ?>
			<input name="list[name]" type="text" class="acy_required_field" value="<?php echo acym_escape($data['listInformation']->name); ?>" required>
		</label>
	</div>
	<div class="cell grid-x acym__list__settings__active small-6">
        <?php echo acym_switch('list[active]', acym_escape($data['listInformation']->active), acym_translation('ACYM_ACTIVE'), [], 'shrink', 'shrink', 'margin-0'); ?>
	</div>
	<div class="cell margin-bottom-1 small-6 grid-x acym_vcenter align-left" id="acym__lists__settings__list-color">
        <?php echo acym_translation('ACYM_COLOR'); ?> :
		<input type="text" name="list[color]" id="acym__list__settings__color-picker" class="cell small-8" value="<?php echo acym_escape($data['listInformation']->color); ?>" />
	</div>
	<div class="cell grid-x acym__list__settings__visible small-6">
        <?php echo acym_switch('list[visible]', acym_escape($data['listInformation']->visible), acym_translation('ACYM_VISIBLE'), [], 'shrink', 'shrink', 'margin-0'); ?>
	</div>
    <?php if (!empty($data['listInformation']->id)) { ?>
		<p class="cell margin-bottom-1 small-6 text-left" id="acym__list__settings__list-id"><?php echo acym_translation('ACYM_LIST_ID'); ?> :
			<b class="acym__color__blue"><?php echo acym_escape($data['listInformation']->id); ?></b></p>
    <?php } ?>
	<div class="cell grid-x small-6">
        <?php
        $label = acym_translation('ACYM_TRACK_THIS_LIST');
        $label .= acym_info('ACYM_TRACK_THIS_LIST_DESC');
        echo acym_switch('list[tracking]', $data['listInformation']->tracking, $label, [], 'small-6', 'shrink', 'margin-0'); ?>
	</div>
	<div class="cell small-6">
        <?php echo acym_translation('ACYM_DATE_CREATED'); ?> : <b><?php echo acym_date(
                empty($data['listInformation']->id) ? time() : $data['listInformation']->creation_date,
                'ACYM_DATE_FORMAT_LC3'
            ); ?></b>
	</div>
</div>
