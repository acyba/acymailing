<div id="acym__editor__content" class="grid-x acym__content acym__editor__area">
	<div class="cell grid-x align-right grid-margin-x margin-bottom-0 margin-y">
		<input type="hidden" id="acym__mail__edit__editor" value="<?php echo acym_escape($data['mail']->editor); ?>">
		<input type="hidden"
			   class="acym__wysid__hidden__save__thumbnail"
			   id="editor_thumbnail"
			   name="editor_thumbnail"
			   value="<?php echo acym_escape($data['mail']->thumbnail); ?>" />
		<input type="hidden" id="acym__mail__edit__editor__social__icons" value="<?php echo empty($data['social_icons']) ? '{}' : acym_escape($data['social_icons']); ?>">
		<input type="hidden" id="acym__mail__type" name="mail[type]" value="<?php echo empty($data['mail']->type) ? $data['mailClass']::TYPE_STANDARD : $data['mail']->type; ?>">
        <?php include acym_getView('mails', 'edit_actions'); ?>
	</div>
	<div class="cell grid-x grid-padding-x acym__editor__content__options margin-y">
        <?php
        if (!empty($data['return'])) echo '<input type="hidden" name="return" value="'.acym_escape($data['return']).'"/>';
        if (!empty($data['fromId'])) echo '<input type="hidden" name="fromId" value="'.acym_escape($data['fromId']).'"/>';

        if ($data['mail']->type == $data['mailClass']::TYPE_NOTIFICATION) {
            include acym_getView('mails', 'edit_info_notification');
        } elseif (in_array($data['mail']->type, [$data['mailClass']::TYPE_AUTOMATION, $data['mailClass']::TYPE_OVERRIDE])) {
            include acym_getView('mails', 'edit_info_all');
        } elseif ($data['mail']->type == $data['mailClass']::TYPE_FOLLOWUP) {
            include acym_getView('mails', 'edit_info_followup');
        } else {
            include acym_getView('mails', 'edit_info_global');
        }

        include acym_getView('mails', 'edit_info_advanced_options');
        ?>
	</div>
</div>
<input type="hidden" name="mail[id]" value="<?php echo acym_escape($data['mail']->id); ?>" />
<input type="hidden" name="id" value="<?php echo acym_escape($data['mail']->id); ?>" />
<input type="hidden" name="thumbnail" value="<?php echo empty($data['mail']->thumbnail) ? '' : acym_escape($data['mail']->thumbnail); ?>" />
<?php
acym_formOptions();

echo $data['editor']->display();
