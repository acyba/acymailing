<?php
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- View file, its variables are local to the include scope, not true globals.
// context verification
?>
<form id="acym_form" action="<?php echo acym_escapeUrl(acym_completeLink(acym_getVar('cmd', 'ctrl'))); ?>" method="post" name="acyForm">
	<input type="hidden" name="acym_import_filename" id="filename" value="<?php echo acym_escape(acym_getVar('cmd', 'acym_import_filename')); ?>" />
	<input type="hidden" name="import_columns" id="import_columns" value="" />
	<input type="hidden" name="new_list" id="acym__import__new-list" value="" />
	<div id="acym__users__import__generic" class="acym__content">
		<div class="grid-x grid-margin-y acym_area">
			<div class="">
				<div class="acym__title"><?php echo acym_escapeHtml(acym_translation('ACYM_FIELD_MATCHING')); ?></div>
				<p class="acym__users__import__generic__instructions"><?php echo acym_escapeHtmlWithAllowedTags(acym_translation('ACYM_ASSIGN_COLUMNS'), ['b' => []]); ?></p>
			</div>

			<div class="cell grid-x" id="acym__users__import__generic__matchdata">
                <?php include acym_getView('users', 'ajaxencoding'); ?>
			</div>
		</div>

		<div class="grid-x acym_area">
			<div class="acym__title"><?php echo acym_escapeHtml(acym_translation('ACYM_PARAMETERS')); ?></div>
			<div class="cell grid-x grid-margin-x margin-y">
				<div class="cell large-6 grid-x">
					<label for="acyencoding" class="cell medium-6"><?php echo acym_escapeHtml(acym_translation('ACYM_FILE_CHARSET')); ?></label>
					<div class="cell medium-6">
                        <?php
                        $encodingHelper = new AcyMailing\Helpers\EncodingHelper();
                        $default = $encodingHelper->detectEncoding($this->content);
                        $urlEncodedFilename = urlencode($filename);
                        $attribs = [
                            'data-filename' => $urlEncodedFilename,
                            'class' => 'acym__select',
                        ];
                        $encodingHelper->charsetField('acyencoding', $default, $attribs, true);
                        ?>
					</div>
				</div>
                <?php if ($this->config->get('require_confirmation')) { ?>
					<div class="cell large-6 grid-x">
                        <?php
                        acym_switch([
                            'name' => 'import_confirmed_generic',
                            'value' => $this->config->get('import_confirmed', 1),
                            'label' => acym_translation('ACYM_IMPORT_USERS_AS_CONFIRMED'),
                        ]);
                        ?>
					</div>
                <?php } ?>
				<div class="cell large-6 grid-x">
                    <?php
                    acym_switch([
                        'name' => 'import_generate_generic',
                        'value' => $this->config->get('import_generate', 1),
                        'label' => acym_translation('ACYM_GENERATE_NAME'),
                        'tip' => ['textShownInTooltip' => 'ACYM_GENERATE_NAME_DESC'],
                    ]);
                    ?>
				</div>
				<div class="cell large-6 grid-x">
                    <?php
                    acym_switch([
                        'name' => 'import_overwrite_generic',
                        'value' => $this->config->get('import_overwrite', 1),
                        'label' => acym_translation('ACYM_OVERWRITE_EXISTING'),
                    ]);
                    ?>
				</div>
			</div>
		</div>

		<div class="cell grid-x grid-margin-x margin-top-1">
			<div class="cell hide-for-small-only medium-auto"></div>
            <?php
            acym_cancelButton(
                'ACYM_CANCEL',
                '',
                'button medium-6 large-shrink margin-bottom-0'
            );

            $entityHelper = new AcyMailing\Helpers\EntitySelectHelper();
            $importHelper = new AcyMailing\Helpers\ImportHelper();
            $modalData = $entityHelper->entitySelect(
                [
                    'entity' => 'list',
                    'columnsToDisplay' => $entityHelper->getColumnsForList(),
                    'additionalData' => $importHelper->additionalDataUsersImport(true),
                ]
            );
            acym_modal(
                acym_translation('ACYM_IMPORT_SUBSCRIBERS'),
                $modalData,
                'acym__user__import__add-subscription__modal',
                [],
                ['class' => 'button margin-bottom-0']
            );
            ?>
			<div class="cell hide-for-small-only medium-auto"></div>
		</div>
	</div>
    <?php acym_formOptions(true, 'finalizeImport'); ?>
</form>
