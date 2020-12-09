<div class="cell" id="acym__wysid__edit__languages">
	<div id="acym__wysid__edit__languages__legend"><?php echo acym_translation('ACYM_CONFIGURATION_LANGUAGES').acym_info('ACYM_MULTILINGUAL_DESC'); ?></div>

    <?php $code = strtolower(substr($data['main_language']->code, 3)); ?>
	<div class="acym__wysid__edit__languages__selection acym__wysid__edit__languages-selected">
        <?php
        echo acym_tooltip(
            '<i class="acymicon-pencil acym__wysid__edit__languages__selection__edition"></i>
						<i class="acymicon-check acym__wysid__edit__languages__selection__check"></i>
						<img acym-data-lang="main" src="'.acym_getFlagByCode($code).'" alt="'.$code.' flag">',
            $data['main_language']->name
        );
        ?>

		<input type="hidden" name="mail[language]" value="<?php echo acym_escape($data['main_language']->code); ?>">
		<input type="hidden" name="multilingual[main][subject]" value="">
		<input type="hidden" name="multilingual[main][preview]" value="">
		<input type="hidden" name="multilingual[main][content]" value="">
		<input type="hidden" name="multilingual[main][autosave]" value="">
	</div>
    <?php
    foreach ($data['languages'] as $oneLanguage) {
        $code = strtolower(substr($oneLanguage->code, 3));
        $containerClass = 'acym__wysid__edit__languages__selection';
        if (empty($oneLanguage->subject)) {
            $containerClass .= ' acym__wysid__edit__languages__selection-empty';
        } else {
            $containerClass .= ' acym__wysid__edit__languages__selection-done';
        }
        ?>

		<div class="acym__wysid__edit__languages__separator"></div>
		<div class="<?php echo $containerClass; ?>">
            <?php echo acym_tooltip(
                '<i class="acymicon-pencil acym__wysid__edit__languages__selection__edition"></i>
							<i class="acymicon-check acym__wysid__edit__languages__selection__check"></i>
							<img acym-data-lang="'.$oneLanguage->code.'" src="'.acym_getFlagByCode($code).'" alt="'.$code.' flag">',
                $oneLanguage->name
            ); ?>

			<input type="hidden" name="multilingual[<?php echo $oneLanguage->code; ?>][subject]" value="<?php echo acym_escape($oneLanguage->subject); ?>">
			<input type="hidden" name="multilingual[<?php echo $oneLanguage->code; ?>][preview]" value="<?php echo acym_escape($oneLanguage->preview); ?>">
			<input type="hidden" name="multilingual[<?php echo $oneLanguage->code; ?>][content]" value="<?php echo acym_escape($oneLanguage->content); ?>">
			<input type="hidden" name="multilingual[<?php echo $oneLanguage->code; ?>][autosave]" value="<?php echo acym_escape($oneLanguage->autosave); ?>">
		</div>
        <?php
    }
    ?>
</div>

<div id="acym__wysid__edit__multilingual__creation" class="cell grid-x grid-margin-x text-center align-center is-hidden">
	<h5><?php echo acym_translation('ACYM_MULTILINGUAL_CREATION_TITLE'); ?></h5>
	<div class="cell"><?php echo acym_translation('ACYM_MULTILINGUAL_CREATION_DESCRIPTION'); ?></div>
	<button type="button" class="cell button xlarge-4 large-6 margin-top-1 button-secondary" id="acym__wysid__edit__multilingual__creation__default">
        <?php echo acym_translation('ACYM_MULTILINGUAL_CREATION_FROM_DEFAULT'); ?>
	</button>
	<button type="button" class="cell button xlarge-4 large-6 margin-top-1 button-secondary" id="acym__wysid__edit__multilingual__creation__scratch">
        <?php echo acym_translation('ACYM_MULTILINGUAL_CREATION_FROM_SCRATCH'); ?>
	</button>
</div>
