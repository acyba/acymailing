<div id="acym__editor__content" class="grid-x acym__content acym__editor__area">
	<div class="cell grid-x grid-margin-x margin-left-0 margin-right-0 align-right">
		<input type="hidden" id="acym__mail__edit__editor" value="<?php echo acym_escape($data['mail']->editor); ?>">
		<input type="hidden"
			   class="acym__wysid__hidden__save__thumbnail"
			   id="editor_thumbnail"
			   name="editor_thumbnail"
			   value="<?php echo acym_escape($data['mail']->thumbnail); ?>" />
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
        } elseif (in_array($data['mail']->type, ['automation', 'override'])) {
            include acym_getView('mails', 'edit_info_all');
        } elseif ($data['mail']->type == 'followup') {
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
