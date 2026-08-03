<?php
// context verification
?>
<h2 class="cell acym__title acym__title__secondary margin-top-2"><?php echo acym_escapeHtml(acym_translation('ACYM_DISPLAY')); ?></h2>

<div class="cell grid-x large-12 margin-top-1">
    <?php acym_switch([
        'name' => 'field[backend_edition]',
        'value' => $data['field']->backend_edition,
        'label' => acym_translationSprintf('ACYM_BACKEND_X', acym_translation('ACYM_EDITION')),
        'labelClass' => 'auto',
        'switchContainerClass' => 'shrink',
        'switchClass' => 'margin-0',
    ]); ?>
</div>
<?php if (empty($data['field']->core) || $data['field']->type === 'language') { ?>
	<div class="cell grid-x large-12 margin-top-1">
        <?php acym_switch([
            'name' => 'field[backend_listing]',
            'value' => $data['field']->backend_listing,
            'label' => acym_translationSprintf('ACYM_BACKEND_X', acym_translation('ACYM_LISTING')),
            'labelClass' => 'auto',
            'switchContainerClass' => 'shrink',
            'switchClass' => 'margin-0',
        ]); ?>
	</div>
<?php } else {
    echo '<input type="hidden" name="field[backend_listing]" value="1">';
} ?>

<?php if ('joomla' === ACYM_CMS) { ?>
	<div class="cell grid-x large-12 margin-top-1">
        <?php acym_switch([
            'name' => 'field[frontend_edition]',
            'value' => $data['field']->frontend_edition,
            'label' => acym_translationSprintf('ACYM_FRONTEND_X', acym_translation('ACYM_EDITION')),
            'labelClass' => 'auto',
            'switchContainerClass' => 'shrink',
            'switchClass' => 'margin-0',
        ]); ?>
	</div>
	<div class="cell grid-x large-12 margin-top-1">
        <?php acym_switch([
            'name' => 'field[frontend_listing]',
            'value' => $data['field']->frontend_listing,
            'label' => acym_translationSprintf('ACYM_FRONTEND_X', acym_translation('ACYM_LISTING')),
            'labelClass' => 'auto',
            'switchContainerClass' => 'shrink',
            'switchClass' => 'margin-0',
        ]); ?>
	</div>
<?php } ?>
