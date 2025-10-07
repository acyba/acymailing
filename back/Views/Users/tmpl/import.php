<form id="acym_form" enctype="multipart/form-data" action="<?php echo acym_completeLink(acym_getVar('cmd', 'ctrl')); ?>" method="post" name="acyForm">
	<div class="acym__content acym__content__tab">
        <?php
        $data['tab']->startTab(acym_translation('ACYM_IMPORT_FROM_FILE'));
        include acym_getView('users', 'importfromfile', true);
        $data['tab']->endTab();

        $data['tab']->startTab(acym_translation('ACYM_IMPORT_FROM_TEXT'));
        include acym_getView('users', 'importfromtext', true);
        $data['tab']->endTab();

        $data['tab']->startTab(acym_translationSprintf('ACYM_IMPORT_CMS_USERS', ACYM_CMS_TITLE));
        include acym_getView('users', 'importcmsusers', true);
        $data['tab']->endTab();

        if (ACYM_CMS === 'joomla') {
            $data['tab']->startTab(acym_translationSprintf('ACYM_IMPORT_CMS_USERS', strtolower(acym_translation('ACYM_CONTACT'))));
            include acym_getView('users', 'importfromcontact', true);
            $data['tab']->endTab();
        }

        $data['tab']->startTab(acym_translation('ACYM_IMPORT_DATABASE'));
        include acym_getView('users', 'importfromdatabase', true);
        $data['tab']->endTab();

        //__START__wordpress_
        if (ACYM_CMS == 'wordpress' && acym_isExtensionActive('mailpoet/mailpoet.php')) {
            $data['tab']->startTab(acym_translation('ACYM_IMPORT_MAILPOET'));
            include acym_getView('users', 'importfrommailpoet', true);
            $data['tab']->endTab();
        }
        //__END__wordpress_

        $data['tab']->display('import');
        ?>
	</div>
    <?php
    $modalData = $data['entitySelect']->entitySelect(
        'list',
        ['join' => ''],
        $data['entitySelect']->getColumnsForList(),
        [],
        true,
        $data['importHelper']->additionalDataUsersImport(false)
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
