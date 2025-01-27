<?php
$multilingualLanguages = new \stdClass();
$currentLanguageTag = acym_getLanguageTag();
foreach (acym_getMultilingualLanguages() as $key => $languages) {
    $multilingualLanguages->$key = $languages->name;
}
?>
<div class="cell">
	<multi-language :languageforselect2="'<?php echo acym_escape($multilingualLanguages); ?>'"
					:currentlangue="'<?php echo acym_escape($currentLanguageTag); ?>'"
					:place="'<?php echo acym_escape(acym_translation('ACYM_SUBSCRIBE')); ?>'"
					:value="<?php echo acym_escape($value); ?>"
					v-model="<?php echo acym_escape($vModel); ?>">
</div>

