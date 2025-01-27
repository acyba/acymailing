<form id="acym_form" enctype="multipart/form-data" action="<?php echo acym_completeLink(acym_getVar('cmd', 'ctrl')); ?>" method="post" name="acyForm"
    <?php echo !empty($data['menuClass']) ? 'class="'.acym_escape($data['menuClass']).'"' : ''; ?> >
	<div class="acym__content acym__content__tab">
        <?php
        $data['tab']->startTab(acym_translation('ACYM_IMPORT_FROM_FILE'));
        include acym_getView('users', 'importfromfile', true);
        $data['tab']->endTab();

        $data['tab']->startTab(acym_translation('ACYM_IMPORT_FROM_TEXT'));
        include acym_getView('users', 'importfromtext', true);
        $data['tab']->endTab();

        $data['tab']->display('import');
        ?>
	</div>
    <?php
    $modalData = $data['entitySelect']->entitySelect(
        'list',
        ['join' => ''],
        $data['entitySelect']->getColumnsForList(),
        [
            'text' => acym_translation('ACYM_IMPORT_SUBSCRIBERS'),
            'class' => 'acym__users__import__button',
        ],
        true,
        $data['importHelper']->additionalDataUsersImport(true)
    );

    echo acym_modal(
        acym_translation('ACYM_IMPORT_SUBSCRIBERS'),
        $modalData,
        'acym__user__import__add-subscription__modal',
        '',
        'style="display: none"'
    );

    ?>
	<input type="hidden" name="import_from" />
    <?php acym_formOptions(true, "doImport"); ?>
</form>
