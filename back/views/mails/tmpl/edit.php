<div id="acym__editor__content" class="grid-x acym__content acym__editor__area">
	<div class="cell grid-x grid-margin-x margin-left-0 margin-right-0 align-right">
		<input type="hidden" id="acym__mail__edit__editor" value="<?php echo acym_escape($data['mail']->editor); ?>">
		<input type="hidden" class="acym__wysid__hidden__save__thumbnail" id="editor_thumbnail" name="editor_thumbnail" value="<?php echo acym_escape($data['mail']->thumbnail); ?>" />
		<input type="hidden" id="acym__mail__edit__editor__social__icons" value="<?php echo empty($data['social_icons']) ? '{}' : acym_escape($data['social_icons']); ?>">
		<input type="hidden" id="acym__mail__type" name="mail[type]" value="<?php echo empty($data['mail']->type) ? 'standard' : $data['mail']->type; ?>">
        <?php include acym_getView('mails', 'edit_actions'); ?>
	</div>
	<div class="cell grid-x grid-padding-x acym__editor__content__options">
        <?php
        if (!empty($data['return'])) echo '<input type="hidden" name="return" value="'.acym_escape($data['return']).'"/>';
        if (!empty($data['fromId'])) echo '<input type="hidden" name="fromId" value="'.acym_escape($data['fromId']).'"/>';

        if ($data['mail']->type == 'notification') {
            include acym_getView('mails', 'edit_info_notification');
        } elseif ($data['mail']->type == 'automation') {
            include acym_getView('mails', 'edit_info_automation');
        } else {
            include acym_getView('mails', 'edit_info_global');
        }
        ?>
	</div>
</div>
<input type="hidden" name="mail[id]" value="<?php echo acym_escape($data['mail']->id); ?>" />
<input type="hidden" name="id" value="<?php echo acym_escape($data['mail']->id); ?>" />
<input type="hidden" name="thumbnail" value="<?php echo empty($data['mail']->thumbnail) ? '' : acym_escape($data['mail']->thumbnail); ?>" />
<?php
acym_formOptions();

$editor = acym_get('helper.editor');
$editor->content = $data['mail']->body;
$editor->autoSave = !empty($data['mail']->autosave) ? $data['mail']->autosave : '';
if (!empty($data['mail']->editor)) $editor->editor = $data['mail']->editor;
if (!empty($data['mail']->id)) $editor->mailId = $data['mail']->id;
if (!empty($data['mail']->type)) $editor->automation = $data['isAutomationAdmin'];
if (!empty($data['mail']->settings)) $editor->settings = $data['mail']->settings;
if (!empty($data['mail']->stylesheet)) $editor->stylesheet = $data['mail']->stylesheet;
echo $editor->display();
