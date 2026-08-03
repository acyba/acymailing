<?php
// context verification
?>

<div class="cell grid-x acym_vcenter">
	<input type="number"
        <?php echo isset($option['min']) ? 'min="'.intval($option['min']).'"' : ''; ?>
        <?php echo isset($option['max']) ? 'max="'.intval($option['max']).'"' : ''; ?>
		   class="cell medium-3 margin-next-1"
		   v-model="<?php echo acym_escape($vModel); ?>"
		   id="<?php echo acym_escape($id); ?>"
		   name="<?php echo acym_escape($name); ?>">
    <?php
    if (!empty($option['unit'])) {
        echo '<span class="cell shrink">'.acym_escapeHtml($option['unit']).'</span>';
    }
    ?>
</div>
