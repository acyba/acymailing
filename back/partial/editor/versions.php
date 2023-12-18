<?php
if (empty($data['mailInformation'])) $data['mailInformation'] = $data['mail'];

$isAbTest = !empty($data['abtest']);

?>
<input type="hidden" name="version_type" value="<?php echo $isAbTest ? 'abtest' : 'multilingual'; ?>">
<div class="cell acym__wysid__edit__versions-<?php echo $isAbTest ? 'abtest' : 'multilingual'; ?>" id="acym__wysid__edit__versions">
	<div class="acym__title acym__title__secondary">
        <?php
        if ($isAbTest) {
            echo acym_translation('ACYM_VERSIONS');
        } else {
            echo acym_translation('ACYM_CONFIGURATION_LANGUAGES').acym_info('ACYM_MULTILINGUAL_DESC');
        } ?>
	</div>

	<div class="acym__wysid__edit__versions__selection acym__wysid__edit__versions-selected">
        <?php
        if ($isAbTest) {
            echo '<i class="acymicon-pencil acym__wysid__edit__versions__selection__edition"></i>
						<i class="acymicon-check acym__wysid__edit__versions__selection__check"></i>
						<span class="acym__wysid__edit__versions__selection__element" acym-data-version="main">'.$data['main_version']->name.'</span>';
        } else {
            echo acym_tooltip(
                '<i class="acymicon-pencil acym__wysid__edit__versions__selection__edition"></i>
						<i class="acymicon-check acym__wysid__edit__versions__selection__check"></i>
						<img 
						class="acym__wysid__edit__versions__selection__element" 
						acym-data-version="main" 
						src="'.acym_getFlagByCode($data['main_language']->code).'" 
						alt="'.$data['main_language']->code.' flag">',
                $data['main_language']->name
            );
        }

        ?>

        <?php
        $code = $isAbTest ? $data['main_version']->name : $data['main_language']->code;
        ?>
        <?php if (!$isAbTest) { ?>
			<input type="hidden" name="mail[language]" value="<?php echo acym_escape($code); ?>">
        <?php } ?>
		<input type="hidden" name="versions[main][subject]" value="">
		<input type="hidden" name="versions[main][preview]" value="">
		<input type="hidden" name="versions[main][content]" value="">
		<input type="hidden" name="versions[main][autosave]" value="">
		<input type="hidden" name="versions[main][settings]" value="">
		<input type="hidden" name="versions[main][stylesheet]" value="">
	</div>
    <?php
    $versions = $isAbTest ? $data['versions'] : $data['languages'];
    foreach ($versions as $version) {
        $containerClass = 'acym__wysid__edit__versions__selection';
        if (empty($version->subject)) {
            $containerClass .= ' acym__wysid__edit__versions__selection-empty';
        } else {
            $containerClass .= ' acym__wysid__edit__versions__selection-done';
        }
        ?>

		<div class="acym__wysid__edit__versions__separator"></div>
		<div class="<?php echo $containerClass; ?>">
            <?php
            if ($isAbTest) {
                echo '<i class="acymicon-pencil acym__wysid__edit__versions__selection__edition"></i>
						<i class="acymicon-check acym__wysid__edit__versions__selection__check"></i>
						<span class="acym__wysid__edit__versions__selection__element" acym-data-version="'.$version->code.'">'.$version->name.'</span>';
            } else {
                echo acym_tooltip(
                    '<i class="acymicon-pencil acym__wysid__edit__versions__selection__edition"></i>
							<i class="acymicon-check acym__wysid__edit__versions__selection__check"></i>
							<img 
							class="acym__wysid__edit__versions__selection__element" 
							acym-data-version="'.$version->code.'" 
							src="'.acym_getFlagByCode($version->code).'" 
							alt="'.$version->code.' flag">',
                    $version->name
                );
            }
            ?>

			<input type="hidden" name="versions[<?php echo $version->code; ?>][subject]" value="<?php echo acym_escape($version->subject); ?>">
			<input type="hidden" name="versions[<?php echo $version->code; ?>][preview]" value="<?php echo acym_escape($version->preview); ?>">
			<input type="hidden" name="versions[<?php echo $version->code; ?>][content]" value="<?php echo acym_escape($version->content); ?>">
			<input type="hidden" name="versions[<?php echo $version->code; ?>][autosave]" value="<?php echo acym_escape($version->autosave); ?>">
			<input type="hidden" name="versions[<?php echo $version->code; ?>][settings]" value="<?php echo acym_escape($version->settings); ?>">
			<input type="hidden" name="versions[<?php echo $version->code; ?>][stylesheet]" value="<?php echo acym_escape($version->stylesheet); ?>">
		</div>
        <?php
    }
    ?>
</div>

<div id="acym__wysid__edit__versions__creation" class="cell grid-x grid-margin-x text-center align-center is-hidden">
	<h5><?php echo acym_translation($isAbTest ? 'ACYM_VERSION_CREATION_TITLE' : 'ACYM_MULTILINGUAL_CREATION_TITLE'); ?></h5>
	<div class="cell"><?php echo acym_translation($isAbTest ? 'ACYM_VERSION_CREATION_DESCRIPTION' : 'ACYM_MULTILINGUAL_CREATION_DESCRIPTION'); ?></div>
	<button type="button" class="cell button xlarge-4 large-4 margin-top-1 button-secondary" id="acym__wysid__edit__versions__creation__default">
        <?php echo acym_translation('ACYM_MULTILINGUAL_CREATION_FROM_DEFAULT'); ?>
	</button>
	<button type="button" class="cell button xlarge-4 large-4 margin-top-1 button-secondary" id="acym__wysid__edit__versions__creation__scratch">
        <?php echo acym_translation('ACYM_MULTILINGUAL_CREATION_FROM_SCRATCH'); ?>
	</button>
	<button type="button"
			data-open="acym__template__choose__modal"
			aria-controls="acym__template__choose__modal"
			tabindex="0"
			aria-haspopup="true"
			class="cell button xlarge-4 large-4 margin-top-1 button-secondary">
        <?php echo acym_translation('ACYM_START_FROM_TEMPLATE'); ?>
	</button>
    <?php
    $dataForTemplate = ['allTags' => $data['tagClass']->getAllTagsByType('mail')];
    echo acym_modal_include(
        '',
        ACYM_VIEW.'mails'.DS.'tmpl'.DS.'choose_template_ajax.php',
        'acym__template__choose__modal',
        $dataForTemplate,
        '',
        'acym__template__choose__modal__listing'
    );
    ?>
</div>
