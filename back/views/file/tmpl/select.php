<?php echo $data['warnings']; ?>
<form id="acym_form" enctype="multipart/form-data" action="<?php echo acym_completeLink(acym_getVar('cmd', 'ctrl')); ?>" method="post" name="acyForm">
	<div id="acym__file__select">
		<div class="acym__file__select grid-x">
			<div class="acym__file__select__area cell grid-x">
                <?php
                $fileTreeType = $data['fileTreeType'];
                echo $fileTreeType->display($data['folders'], $data['uploadFolder'], 'currentFolder');

                if (empty($data['files'])) {
                    echo acym_translation('ACYM_NO_FILE_HERE');
                } else {
                    $preparedFiles = [];
                    foreach ($data['files'] as $k => $file) {
                        $ext = strtolower(substr($file, strrpos($file, '.') + 1));

                        if (!in_array($ext, $data['allowedExtensions'])) {
                            continue;
                        }

                        if (in_array($ext, $data['imageExtensions'])) {
                            $srcImg = ACYM_LIVE.rtrim($data['uploadFolder'], DS).'/'.$file;
                        } else {
                            $srcImg = ACYM_LIVE.ACYM_MEDIA_FOLDER.'/images/file.png';
                        }

                        if (strlen($file) > 20) {
                            $title = '<span class="cell acym__file__select__title" title="'.acym_escape($file).'">'.substr(rtrim($file, $ext), 0, 12).'...'.$ext.'</span>';
                        } else {
                            $title = '<span class="cell acym__file__select__title">'.$file.'</span>';
                        }

                        $selected = $data['selectedFile'] === $file ? 'acym_clickme' : '';

                        $preparedFiles[] = [
                            'title' => $title,
                            'image' => $srcImg,
                            'file' => $file,
                            'selected' => $selected,
                        ];
                    }

                    // Switch between grid and list views button
                    ?>
					<div id="acym__file__select__area__switch" class="cell medium-1 grid-x align-right">
						<button type="button" id="acym__file__select__area__switch__grid" class="is-hidden"><i class="acymicon-th"></i></button>
						<button type="button" id="acym__file__select__area__switch__list"><i class="acymicon-menu"></i></button>
					</div>
                    <?php

                    // Display the grid view
                    echo '<div id="acym__file__select__area__grid" class="margin-top-1 cell grid-x large-up-4 medium-up-3 small-up-2 grid-margin-x align-center">';
                    foreach ($preparedFiles as $oneFile) {
                        ?>
						<div class="cell acym__file__select__onepic text-center">
							<a href="#" class="acym__file__select__add grid-x <?php echo $oneFile['selected']; ?>" mapdata="<?php echo acym_escape($oneFile['file']); ?>">
                                <?php echo $oneFile['title']; ?>
								<div class="cell">
									<img src="<?php echo acym_escape($oneFile['image']); ?>" alt="" />
								</div>
							</a>
						</div>
                        <?php
                    }
                    echo '</div>';

                    // Display the list view
                    echo '<div id="acym__file__select__area__list" class="margin-top-1 is-hidden cell grid-x">';
                    foreach ($preparedFiles as $oneFile) {
                        ?>
						<a href="#" class="acym__file__select__add cell grid-x" mapdata="<?php echo acym_escape($oneFile['file']); ?>">
                            <?php echo $oneFile['file']; ?>
						</a>
                        <?php
                    }
                    echo '</div>';
                }
                ?>
				<input type="hidden" id="acym__file__select__mapid" value="<?php echo acym_escape($data['map']); ?>">
			</div>
			<div class="acym__file__select__area cell grid-x text-center">
                <?php echo acym_inputFile('uploadedFile', '', '', 'cell medium-shrink'); ?>
				<input type="hidden" name="currentFolder" value="<?php echo acym_escape($data['uploadFolder']); ?>" />
				<input type="hidden" name="id" value="<?php echo acym_escape($data['map']); ?>" />
				<div class="cell medium-auto hide-for-small-only"></div>
				<button type="button"
						class="cell medium-shrink button button-secondary acy_button_submit"
						type="submit"
						data-task="select"> <?php echo acym_translation('ACYM_IMPORT'); ?> </button>
			</div>
		</div>
	</div>
    <?php acym_formOptions(); ?>
</form>
