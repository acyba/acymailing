<?php
// context verification
?>
<div class="cell">
	<select2multiple :name="'<?php echo acym_escape($name); ?>'"
	                 :value="'<?php echo acym_escape(json_encode($value)); ?>'"
	                 :options="<?php echo acym_escape(json_encode($option['options'])); ?>"
	                 v-model="<?php echo acym_escape($vModel); ?>"></select2multiple>
</div>
