<form id="acym_form" action="<?php echo acym_completeLink(acym_getVar('cmd', 'ctrl')); ?>" method="post" name="acyForm">
	<input type="hidden" name="filename" id="filename" value="<?php echo acym_getVar('cmd', 'filename'); ?>" />
	<input type="hidden" name="import_columns" id="import_columns" value="" />
	<input type="hidden" name="new_list" id="acym__import__new-list" value="" />
	<div id="acym__users__import__generic" class="acym__content">
		<div class="grid-x grid-margin-y acym_area">
			<div class="">
				<div class="acym__title"><?php echo acym_translation('ACYM_FIELD_MATCHING'); ?></div>
				<p class="acym__users__import__generic__instructions"><?php echo acym_translation('ACYM_ASSIGN_COLUMNS'); ?></p>
			</div>
			<div class="cell acym_vcenter">
				<input type="checkbox" id="acym__users__import__from_file__ignore__checkbox" name="acym__users__import__from_file__ignore__checkbox">
				<label for="acym__users__import__from_file__ignore__checkbox"><?php echo acym_translation('ACYM_IGNORE_UNASSIGNED'); ?></label>
			</div>

			<div class="cell grid-x" id="acym__users__import__generic__matchdata">
                <?php include_once ACYM_BACK.'views'.DS.'users'.DS.'tmpl'.DS.'ajaxencoding.php'; ?>
			</div>
		</div>

		<div class="grid-x acym_area">
			<div class="acym__title"><?php echo acym_translation('ACYM_PARAMETERS'); ?></div>
			<div class="cell grid-x grid-margin-x margin-y">
				<div class="cell large-6 grid-x">
					<label for="acyencoding" class="cell medium-6">File charset</label>
					<div class="cell medium-6">
                        <?php
                        $encodingHelper = new AcyMailing\Helpers\EncodingHelper();
                        $default = $encodingHelper->detectEncoding($this->content);
                        $urlEncodedFilename = urlencode($filename);
                        $attribs = [
                            'data-filename' => $urlEncodedFilename,
                            'class' => 'acym__select',
                        ];
                        echo $encodingHelper->charsetField('acyencoding', $default, $attribs);
                        ?>
					</div>
				</div>
                <?php if ($this->config->get('require_confirmation')) { ?>
					<div class="cell large-6 grid-x">
                        <?php
                        echo acym_switch(
                            'import_confirmed_generic',
                            $this->config->get('import_confirmed', 1),
                            acym_translation('ACYM_IMPORT_USERS_AS_CONFIRMED')
                        );
                        ?>
					</div>
                <?php } ?>
				<div class="cell large-6 grid-x">
                    <?php
                    echo acym_switch(
                        'import_generate_generic',
                        $this->config->get('import_generate', 1),
                        acym_translation('ACYM_GENERATE_NAME').acym_info('ACYM_GENERATE_NAME_DESC')
                    );
                    ?>
				</div>
				<div class="cell large-6 grid-x">
                    <?php
                    echo acym_switch(
                        'import_overwrite_generic',
                        $this->config->get('import_overwrite', 1),
                        acym_translation('ACYM_OVERWRITE_EXISTING')
                    );
                    ?>
				</div>
			</div>
		</div>

		<div class="cell grid-x grid-margin-x margin-top-1">
			<div class="cell hide-for-small-only medium-auto"></div>
            <?php
            echo acym_cancelButton(
                'ACYM_CANCEL',
                '',
                'button medium-6 large-shrink margin-bottom-0'
            );

            $entityHelper = new AcyMailing\Helpers\EntitySelectHelper();
            $importHelper = new AcyMailing\Helpers\ImportHelper();
            $modalData = $entityHelper->entitySelect(
                'list',
                ['join' => ''],
                $entityHelper->getColumnsForList(),
                [],
                true,
                $importHelper->additionalDataUsersImport(true)
            );
            echo acym_modal(
                acym_translation('ACYM_IMPORT_SUBSCRIBERS'),
                $modalData,
                'acym__user__import__add-subscription__modal',
                '',
                'class="button margin-bottom-0"'
            );
            ?>
			<div class="cell hide-for-small-only medium-auto"></div>
		</div>
	</div>
    <?php acym_formOptions(true, 'finalizeImport'); ?>
</form>
