<?php
$postMaxSize = ini_get('post_max_size');
$uploadMaxSize = ini_get('upload_max_filesize');
$maxSize = acym_translation_sprintf(
    'ACYM_MAX_UPLOAD',
    acym_bytes($uploadMaxSize) > acym_bytes($postMaxSize) ? $postMaxSize : $uploadMaxSize
);
$templateTips = '<div class="text-center padding-0 cell grid-x text-center align-center">
							<input type="file" style="width:auto" name="uploadedfile" class="cell"/>
							<div class="cell">'.$maxSize.'</div>
						</div>
						<div class="cell margin-top-2 margin-bottom-2">
							'.acym_translation('ACYM_IMPORT_INFO').'
							<ul>
								<li>'.acym_translation('ACYM_TEMLPATE_ZIP_IMPORT').'</li>
								<ul>
									<li>/template.html -> '.acym_translation('ACYM_TEMPLATE_HTML_IMPORT').'</li>
									<li>/css -> '.acym_translation('ACYM_TEMPLATE_CSS_IMPORT').'</li>
									<li>/images -> '.acym_translation('ACYM_TEMPLATE_IMAGES_IMPORT').'</li>
									<li>/thumbnail.png -> '.acym_translation('ACYM_TEMPLATE_THUMBNAIL_IMPORT').'</li>
								</ul>
							</ul>
						</div>
					   <div class="cell grid-x align-center">
							<button type="button" data-task="doUploadTemplate" class="acy_button_submit button cell shrink margin-1">'.acym_translation('ACYM_IMPORT').'</button>
					   </div>';
$mailController = acym_get('controller.mails');
$data['templateTips'] = $templateTips;
$mailController->prepareToolbar($data);
