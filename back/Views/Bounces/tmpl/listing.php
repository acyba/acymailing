<?php
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- View file, its variables are local to the include scope, not true globals.
// context verification
$currentTask = acym_getVar('string', 'task', 'bounces');
$workflow = $data['workflowHelper'];
$isEmpty = empty($data['allMailboxes']) && empty($data['search']) && empty($data['status']);
?>
<form id="acym_form" action="<?php echo acym_escapeUrl(acym_completeLink(acym_getVar('cmd', 'ctrl'))); ?>" method="post" name="acyForm">
    <?php if ($currentTask === 'bounces' || !$isEmpty) {
        $data['toolbar']->displayToolbar($data);
    } ?>
	<div id="acym__<?php echo acym_escape($currentTask); ?>" class="acym__content">
        <?php
        $workflow->displayTabs($this->tabs, $currentTask);
        if ($currentTask === 'bounces') {
            include acym_getView('bounces', 'listing_bounces', true);
        } elseif ($currentTask === 'mailboxes') {
            if (!$isEmpty) {
                include acym_getView('bounces', 'listing_mailboxes', true);
            } else {
                include acym_getView('bounces', 'listing_mailboxes_empty', true);
            }
        }
        ?>
	</div>
    <?php acym_formOptions(true, $currentTask); ?>
</form>
