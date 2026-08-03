<?php
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- View file, its variables are local to the include scope, not true globals.
// context verification
?>
<form id="acym_form" action="<?php echo acym_escapeUrl(acym_completeLink(acym_getVar('cmd', 'ctrl'))); ?>" method="post" name="acyForm"
    <?php echo !empty($data['menuClass']) ? 'class="'.acym_escape($data['menuClass']).'"' : ''; ?> >
	<input type="hidden" name="preselectList" value="1" />
    <?php
    $isEmpty = empty($data['lists']) && empty($data['search']) && empty($data['tag']) && empty($data['status']);
    if (!$isEmpty) {
        $data['toolbar']->displayToolbar($data);
    }
    ?>
	<div id="acym__lists" class="acym__content">
        <?php if ($isEmpty) {
            include acym_getView('lists', 'listing_empty', true);
        } else {
            include acym_getView('lists', 'listing_listing', true);
        } ?>
	</div>
    <?php acym_formOptions(); ?>
</form>
