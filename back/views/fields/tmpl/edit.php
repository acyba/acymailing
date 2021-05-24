<?php
$beforeSave = '';
if (!empty($data['translation_languages'])) {
    $beforeSave = 'acym-data-before="acym_helperSelectionMultilingual.changeLanguage_field(acym_helperSelectionMultilingual.mainLanguage)"';
}
?>
<form id="acym_form" action="<?php echo acym_completeLink(acym_getVar('cmd', 'ctrl')); ?>" method="post" name="acyForm" data-abide novalidate>
	<input type="hidden" name="id" value="<?php echo empty($data['field']->id) ? '' : intval($data['field']->id); ?>">
	<input type="hidden" name="field[namekey]" value="<?php echo empty($data['field']->namekey) ? '' : acym_escape($data['field']->namekey); ?>">
	<div id="acym__fields__edit" class="acym__content grid-x cell">
        <?php include acym_getView('fields', 'edit_actions'); ?>

		<div class="cell grid-x grid-margin-x">
			<div class="xlarge-4 cell grid-x acym__fields__edit__field__general acym__content grid-margin-x margin-bottom-1 acym_center_baseline">
                <?php include acym_getView('fields', 'edit_information'); ?>
                <?php include acym_getView('fields', 'edit_display'); ?>
			</div>

            <?php
            $display = '';
            if ($data['field']->id == $data['languageFieldId']) $display = 'style="display:none;"';
            $classes = 'cell xlarge-8 grid-x grid-margin-x margin-y margin-bottom-1';
            $classes .= ' acym__content acym__fields__edit__properties acym_center_baseline';
            ?>
			<div class="<?php echo $classes; ?>" <?php echo $display; ?>>
                <?php include acym_getView('fields', 'edit_content'); ?>
                <?php include acym_getView('fields', 'edit_style'); ?>
                <?php include acym_getView('fields', 'edit_values'); ?>
			</div>
		</div>
        <?php acym_formOptions(); ?>
	</div>
</form>
