<?php if (empty($data['allMails'])) { ?>
	<h1 class="cell acym__listing__empty__search__title text-center"><?php echo acym_translation('ACYM_NO_RESULTS_FOUND'); ?></h1>
<?php } else { ?>
	<div class="cell margin-bottom-1 acym__listing__actions grid-x">
        <?php
        $actions = [
            'delete' => acym_translation('ACYM_DELETE'),
            'massDuplicate' => acym_translation('ACYM_DUPLICATE'),
        ];
        echo acym_listingActions($actions);
        ?>
		<div class="margin-left-1 cell medium-auto hide-for-small-only">
            <?php echo acym_externalLink(
                'ACYM_SEE_OUR_TEMPLATES_PACK',
                ACYM_ACYMAILLING_WEBSITE.'pack-template-newsletter/?utm_source=acymailing_plugin&utm_campaign=purchase_templates_pack&utm_medium=button_template_listing'
            ); ?>
		</div>
		<div class="grid-x cell auto">
			<div class="cell acym_listing_sort-by">
                <?php
                echo acym_sortBy(
                    [
                        'name' => acym_translation('ACYM_NAME'),
                        'creation_date' => acym_translation('ACYM_DATE_CREATED'),
                    ],
                    'mails',
                    $data['ordering'],
                    $data['orderingSortOrder']
                );
                ?>
			</div>
		</div>
	</div>
	<div class="grid-x grid-padding-x grid-padding-y grid-margin-x grid-margin-y xxlarge-up-6 large-up-4 medium-up-3 small-up-1 cell margin-bottom-2">
        <?php foreach ($data['allMails'] as $oneTemplate) { ?>
			<div class="cell grid-x acym__templates__oneTpl acym__listing__block text-center" data-acy-elementid="<?php echo acym_escape($oneTemplate->id); ?>">
				<a href="<?php echo acym_completeLink('mails&task=edit&id='.acym_escape($oneTemplate->id)); ?>" class="cell grid-x text-center">
					<div class="cell acym__templates__pic">
                        <?php echo '<img src="'.acym_escape(acym_getMailThumbnail($oneTemplate->thumbnail)).'" alt="'.acym_escape($oneTemplate->name).'"/>'; ?>
					</div>
				</a>
				<button type="button" data-task="export" data-template="<?php echo intval($oneTemplate->id); ?>" class="text-center cell button acym__listing__block__export">
                    <?php echo acym_translation('ACYM_DOWNLOAD'); ?> <i class="acymicon-file_download"></i>
				</button>
				<a href="<?php echo acym_completeLink('mails&task=edit&id='.acym_escape($oneTemplate->id)); ?>" class="cell grid-x text-center">
					<div class="cell grid-x acym__templates__footer text-center">
						<div class="cell acym__templates__footer__title acym_text_ellipsis" title="<?php echo acym_escape($oneTemplate->name); ?>">
                            <?php echo acym_escape($oneTemplate->name); ?>
						</div>
						<div class="cell"><?php echo acym_date($oneTemplate->creation_date, 'ACYM_DATE_FORMAT_LC3'); ?></div>
					</div>
				</a>
				<div class="text-center cell acym__listing__block__icons acym__listing__block__select">
					<input id="checkbox_<?php echo acym_escape($oneTemplate->id); ?>"
						   type="checkbox"
						   name="elements_checked[]"
						   value="<?php echo acym_escape($oneTemplate->id); ?>">
				</div>
				<button type="button"
						data-task="oneDuplicate"
						data-template="<?php echo intval($oneTemplate->id); ?>"
						class="text-center cell acym__listing__block__icons acym__listing__block__duplicate acym__background-color__blue">
					<i class="acym__color__white acymicon-content_copy"></i>
				</button>
				<div class="text-center cell acym__listing__block__icons acym__listing__block__delete acym__background-color__red">
					<div>
						<i class="acymicon-trash-o acym__listing__block__delete__trash acym__color__white"></i>
						<p class="acym__listing__block__delete__submit acym_toggle_delete acym__color__white"
						   data-acy-table="mail"
						   data-acy-elementid="<?php echo acym_escape($oneTemplate->id); ?>">
							<i class="acymicon-trash-o acym__color__white"></i>
						</p>
						<p class="acym__listing__block__delete__cancel acym__background-color__very-dark-gray acym__color__white">
							<i class="acymicon-keyboard_arrow_right acym__color__white"></i>
						</p>

					</div>
				</div>
			</div>
        <?php } ?>
		<input type="hidden" name="templateId" value="" />
	</div>
    <?php
    echo $data['pagination']->display('mails');
}
