<?php
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- View file, its variables are local to the include scope, not true globals.
// context verification
?>
<form id="acym_form" action="<?php echo acym_escapeUrl(acym_completeLink(acym_getVar('cmd', 'ctrl'))); ?>" method="post" name="acyForm">
    <?php
    $isEmpty = empty($data['allUsers']) && empty($data['search']) && empty($data['status']) && empty($data['list']) && empty($data['segment']);
    if (!$isEmpty) {
        $data['toolbar']->displayToolbar($data);
    } ?>
	<div id="acym__users" class="acym__content cell">
        <?php if ($isEmpty) {
            include acym_getView('users', 'listing_empty');
        } else {
            include acym_getView('users', 'listing_listing');
        } ?>
	</div>
    <?php acym_formOptions(); ?>
</form>
