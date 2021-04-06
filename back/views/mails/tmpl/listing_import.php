<?php

$postMaxSize = ini_get('post_max_size');
$uploadMaxSize = ini_get('upload_max_filesize');
$maxSize = acym_translationSprintf(
    'ACYM_MAX_UPLOAD',
    acym_bytes($uploadMaxSize) > acym_bytes($postMaxSize) ? $postMaxSize : $uploadMaxSize
);
?>

<div class="cell grid-x align-center" id="acym__template__import">
	<div class="cell margin-top-2 margin-bottom-2 large-7 medium-10 grid-x">
		<div class="acym__template__import__info cell grid-x ">
            <?php echo acym_translation('ACYM_IMPORT_INFO'); ?>
			<ul class="acym__ul">
				<li><?php echo acym_translation('ACYM_TEMLPATE_ZIP_IMPORT'); ?></li>
				<ul class="acym__ul">
					<li>/template.html -> <?php echo acym_translation('ACYM_TEMPLATE_HTML_IMPORT'); ?></li>
					<li>/css -> <?php echo acym_translation('ACYM_TEMPLATE_CSS_IMPORT'); ?></li>
					<li>/images -> <?php echo acym_translation('ACYM_TEMPLATE_IMAGES_IMPORT'); ?></li>
					<li>/thumbnail.png -> <?php echo acym_translation('ACYM_TEMPLATE_THUMBNAIL_IMPORT'); ?></li>
				</ul>
			</ul>
		</div>
		<div class="cell grid-x align-center">
			<img id="acym__template__import__image__template" src="<?php echo ACYM_IMAGES.'import/template.png'; ?>" alt="template picture" class="cell shrink">
			<input type="file" name="uploadedfile" class="cell" />
			<div class="cell align-center grid-x grid-margin-x acym_vcenter">
				<button type="button" class="cell shrink button button-secondary" id="acym__template__import__file"><?php echo acym_translation('ACYM_CHOOSE_FILE'); ?></button>
				<p class="cell shrink" id="acym__template__import__filename"><?php echo acym_translation('ACYM_NO_FILE_CHOSEN'); ?></p>
			</div>
			<div class="cell text-center margin-top-1"><?php echo $maxSize; ?></div>
			<div class="cell grid-x align-center margin-top-3">
				<button type="button" data-task="doUploadTemplate" class="acy_button_submit button cell shrink"><?php echo acym_translation('ACYM_IMPORT'); ?></button>
			</div>
		</div>
	</div>
</div>
<img src="<?php echo ACYM_IMAGES.'import/spaceman_template.png'; ?>" alt="spaceman with smoke" id="acym__template__import__image__spaceman">
<img src="<?php echo ACYM_IMAGES.'import/smoke_rocket.png'; ?>" alt="smoke of rocket" id="acym__template__import__image__smoke">

