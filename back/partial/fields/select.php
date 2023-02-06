<div class="cell">
	<select2 :name="'<?php echo acym_escape($name); ?>'"
			 :value="'<?php echo acym_escape($value); ?>'"
			 :options="<?php echo acym_escape($option['options']); ?>"
			 v-model="<?php echo acym_escape($vModel); ?>"></select2>
</div>
