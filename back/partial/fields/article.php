<div class="cell">
	<select2ajax :name="'<?php echo acym_escape($name); ?>'"
				 :value="'<?php echo acym_escape($value); ?>'"
				 v-model="<?php echo acym_escape($vModel); ?>"
				 :urlselected="'&ctrl=forms&task=getArticlesById&article_id='"
				 :ctrl="'forms'"
				 :task="'getArticles'"></select2ajax>
</div>
