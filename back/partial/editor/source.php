<div id="acym__wysid__editor__source">
	<div class="acym__wysid__editor__source__buttons">
		<button type="button" class="button acym__wysid__editor__source__button margin-right-1" id="acym__wysid__editor__source-revert" @click="revert"><?php echo acym_translation(
                'ACYM_REVERT'
            ); ?></button>
		<button type="button" class="button acym__wysid__editor__source__button" id="acym__wysid__editor__source-keep" @click="keep"><?php echo acym_translation(
                'ACYM_APPLY'
            ); ?></button>
	</div>
	<vue-prism-editor v-model="code" :language="language" lineNumbers="true"></vue-prism-editor>
</div>
