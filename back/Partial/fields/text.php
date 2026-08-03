<?php
// context verification
?>
<div class="cell">
	<input type="text"
	       class="cell"
	       id="<?php echo acym_escape($id); ?>"
	       name="<?php echo acym_escape($name); ?>"
	       v-model="<?php echo acym_escape($vModel); ?>"
        <?php echo isset($option['placeholder']) ? 'placeholder="'.acym_escape($option['placeholder']).'"' : ''; ?>>
</div>
