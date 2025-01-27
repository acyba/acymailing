<form id="acym_form" action="<?php echo acym_completeLink(acym_getVar('cmd', 'ctrl')); ?>" method="post" name="acyForm" enctype="multipart/form-data">
	<input type="hidden" id="acym_create_template_type_editor" name="type_editor">
	<input type="hidden" id="acym_create_template_type_tmpl" name="type" value="<?php echo $data['mailClass']::TYPE_TEMPLATE; ?>">
    <?php
    $isEmpty = empty($data['allMails']) && empty($data['search']) && empty($data['tag']) && empty($data['status']);
    if (!$isEmpty) {
        $data['toolbar']->displayToolbar($data);
    }
    ?>
	<div id="acym__templates" class="acym__content">
        <?php if ($isEmpty) { ?>
			<div class="grid-x text-center">
				<h1 class="acym__listing__empty__title cell"><?php echo acym_translation('ACYM_YOU_DONT_HAVE_ANY_TEMPLATE'); ?></h1>
				<h1 class="acym__listing__empty__subtitle cell"><?php echo acym_translation('ACYM_CREATE_AN_AMAZING_TEMPLATE_WITH_OUR_AMAZING_EDITOR'); ?></h1>
				<div class="medium-3"></div>
				<div class="medium-6 small-12 cell">
                    <?php include acym_getView('mails', 'listing_empty'); ?>
				</div>
				<div class="medium-3"></div>
			</div>
        <?php } else { ?>
			<div class="grid-x grid-margin-x">
                <?php include acym_getView('mails', 'listing_listing'); ?>
			</div>
        <?php } ?>
	</div>
    <?php acym_formOptions(); ?>
</form>
