<?php
// context verification
?>
<div class="cell">
	<textarea class="cell"
	          name="<?php echo acym_escape($name); ?>"
	          v-model="<?php echo acym_escape($vModel); ?>">
		<?php echo acym_escapeHtml($value); ?>
	</textarea>
</div>
