<h2 class="cell acym__title acym__title__secondary margin-top-2"><?php echo acym_translation('ACYM_DISPLAY'); ?></h2>

<div class="cell grid-x large-12 margin-top-1">
    <?php echo acym_switch(
        'field[backend_edition]',
        $data['field']->backend_edition,
        acym_translationSprintf('ACYM_BACKEND_X', acym_translation('ACYM_EDITION')),
        [],
        'auto',
        'shrink',
        'margin-0'
    ); ?>
</div>

<div class="cell grid-x large-12 margin-top-1">
    <?php echo acym_switch(
        'field[backend_listing]',
        $data['field']->backend_listing,
        acym_translationSprintf('ACYM_BACKEND_X', acym_translation('ACYM_LISTING')),
        [],
        'auto',
        'shrink',
        'margin-0'
    ); ?>
</div>

<?php if ('joomla' === ACYM_CMS) { ?>
	<div class="cell grid-x large-12 margin-top-1">
        <?php echo acym_switch(
            'field[frontend_edition]',
            $data['field']->frontend_edition,
            acym_translationSprintf('ACYM_FRONTEND_X', acym_translation('ACYM_EDITION')),
            [],
            'auto',
            'shrink',
            'margin-0'
        ); ?>
	</div>
	<div class="cell grid-x large-12 margin-top-1">
        <?php echo acym_switch(
            'field[frontend_listing]',
            $data['field']->frontend_listing,
            acym_translationSprintf('ACYM_FRONTEND_X', acym_translation('ACYM_LISTING')),
            [],
            'auto',
            'shrink',
            'margin-0'
        ); ?>
	</div>
<?php } ?>
