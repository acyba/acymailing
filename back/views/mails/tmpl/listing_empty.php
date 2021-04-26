<div class="xlarge-4 medium-auto cell text-center cell grid-x">
	<div class="cell">
		<div class="grid-x grid-margin-x">
			<div class="cell margin-bottom-1">
                <?php
                echo acym_externalLink(
                    'ACYM_SEE_OUR_TEMPLATES_PACK',
                    ACYM_ACYMAILLING_WEBSITE.'pack-template-newsletter/?utm_source=acymailing_plugin&utm_campaign=purchase_templates_pack&utm_medium=button_template_listing'
                );
                ?>
			</div>
            <?php
            echo acym_modal(
                acym_translation('ACYM_IMPORT'),
                $data['templateImportView'],
                null,
                '',
                'class="button cell medium-auto button-secondary" data-reload="true" data-ajax="false"'
            );
            ?>
			<button type="button" id="acym__mail__install-default" class="button cell auto button-secondary acy_button_submit" data-task="installDefaultTmpl">
                <?php echo acym_translation('ACYM_ADD_DEFAULT_TMPL'); ?>
			</button>
            <?php
            echo acym_modal(
                acym_translation('ACYM_CREATE_TEMPLATE'),
                '<div class="cell grid-x grid-margin-x">
								<button type="button" data-task="edit" data-editor="html" class="acym__create__template button cell large-auto small-6 margin-top-1 button-secondary">'.acym_translation(
                    'ACYM_HTML_EDITOR'
                ).'</button>
								<button type="button" data-task="edit" data-editor="acyEditor" class="acym__create__template button cell medium-auto margin-top-1">'.acym_translation(
                    'ACYM_DD_EDITOR'
                ).'</button>
							</div>',
                '',
                '',
                'class="button cell auto"',
                true,
                false
            );
            ?>
		</div>
	</div>
</div>
