<?php
$min = isset($option['min']) ? 'min="'.intval($option['min']).'"' : '';
$max = isset($option['max']) ? 'max="'.intval($option['max']).'"' : '';
?>

<div class="cell grid-x acym_vcenter">
	<input type="number" <?php echo $min; ?> <?php echo $max; ?>
		   class="cell medium-3 margin-right-1"
		   v-model="<?php echo acym_escape($vModel); ?>"
		   id="<?php echo acym_escape($id); ?>"
		   name="<?php echo acym_escape($name); ?>">
    <?php
    if (!empty($option['unit'])) {
        echo '<span class="cell shrink">'.$option['unit'].'</span>';
    }
    ?>
</div>
