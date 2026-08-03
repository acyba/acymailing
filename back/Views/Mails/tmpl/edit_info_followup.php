<?php
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- View file, its variables are local to the include scope, not true globals.
// context verification
?>
<div class="cell medium-6">
	<label>
        <?php echo acym_escapeHtml(acym_translation('ACYM_NAME')); ?>
		<input name="mail[name]" type="text" class="acy_required_field" value="<?php echo acym_escape($data['mail']->name); ?>" required>
	</label>
</div>
<div class="cell medium-6">
	<label>
        <?php echo acym_escapeHtml(acym_translation('ACYM_EMAIL_SUBJECT')); ?>
		<input name="mail[subject]" type="text" value="<?php echo acym_escape($data['mail']->subject); ?>" <?php echo in_array(
            $data['mail']->type,
            [$data['mailClass']::TYPE_WELCOME, $data['mailClass']::TYPE_UNSUBSCRIBE, $data['mailClass']::TYPE_AUTOMATION]
        ) ? 'required' : ''; ?>>
	</label>
</div>
<div class="cell xlarge-3 medium-6">
	<label>
        <?php
        echo acym_escapeHtml(acym_translation('ACYM_FROM_NAME'));
        $fromName = acym_escape(empty($data['mail']->from_name) ? '' : $data['mail']->from_name);
        ?>
		<input name="mail[from_name]" type="text" placeholder="<?php echo acym_escape($this->config->get('from_name')); ?>" value="<?php echo acym_escape($fromName); ?>">
	</label>
</div>
<div class="cell xlarge-3 medium-6">
	<label>
        <?php
        echo acym_escapeHtml(acym_translation('ACYM_FROM_EMAIL'));
        $fromEmail = acym_escape(empty($data['mail']->from_email) ? '' : $data['mail']->from_email);
        ?>
		<input name="mail[from_email]" type="text" placeholder="<?php echo acym_escape($this->config->get('from_email')); ?>" value="<?php echo acym_escape($fromEmail); ?>">
	</label>
</div>
<div class="cell xlarge-3 medium-6">
	<label>
        <?php
        echo acym_escapeHtml(acym_translation('ACYM_REPLYTO_NAME'));
        $replyToNameValue = acym_escape(empty($data['mail']->reply_to_name) ? '' : $data['mail']->reply_to_name);
        ?>
		<input name="mail[reply_to_name]"
		       type="text"
		       placeholder="<?php echo acym_escape($this->config->get('replyto_name')); ?>"
		       value="<?php echo acym_escape($replyToNameValue); ?>">
	</label>
</div>
<div class="cell xlarge-3 medium-6">
	<label>
        <?php
        echo acym_escapeHtml(acym_translation('ACYM_REPLYTO_EMAIL'));
        $replyToEmailValue = acym_escape(empty($data['mail']->reply_to_email) ? '' : $data['mail']->reply_to_email);
        ?>
		<input name="mail[reply_to_email]"
		       type="text"
		       placeholder="<?php echo acym_escape($this->config->get('replyto_email')); ?>"
		       value="<?php echo acym_escape($replyToEmailValue); ?>">
	</label>
</div>
<div class="cell large-6 grid-x acym_vcenter acym__mail__edit__followup">
    <?php
    $inputDelay = '<input type="number" 
    					class="cell large-1 medium-3 margin-left-1" 
    					min="0" 
    					name="followup[delay]" 
    					value="'.acym_escape(empty($data['mail']->delay) ? 0 : $data['mail']->delay).'">';
    $selectDelayUnit = '<span class="cell large-3 medium-5 margin-left-1 margin-right-1">'.acym_select(
            $data['delay_unit'],
            'followup[delay_unit]',
            empty($data['mail']->delay_unit) ? $data['default_delay_unit'] : $data['mail']->delay_unit,
            ['class' => 'acym__select']
        ).'</span>';
    echo acym_escapeHtmlWithAllowedTags(
        acym_translationSprintf('ACYM_SEND_IT_X_X_AFTER_TRIGGER', $inputDelay, $selectDelayUnit),
        [
            'input' => [
                'type' => true,
                'class' => true,
                'min' => true,
                'name' => true,
                'value' => true,
            ],
            'span' => [
                'class' => true,
            ],
            'select' => [
                'name' => true,
                'id' => true,
                'class' => true,
            ],
            'option' => [
                'value' => true,
                'selected' => true,
            ],
        ]
    );
    ?>
	<input type="hidden" name="followup[id]" value="<?php echo empty($data['followup_id']) ? 0 : acym_escape($data['followup_id']); ?>">
</div>

<?php if (!empty($data['langChoice'])) { ?>
	<div class="cell large-6 xlarge-3">
		<label class="cell">
            <?php
            echo acym_escapeHtml(acym_translation('ACYM_EMAIL_LANGUAGE'));
            acym_info(['textShownInTooltip' => 'ACYM_EMAIL_LANGUAGE_DESC']);
            acym_languageOption($data['langChoice']['links'], $data['langChoice']['name']);
            ?>
		</label>
	</div>
<?php } ?>
