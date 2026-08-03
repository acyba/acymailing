<?php
// context verification
?>
<div id="acym__users__import__cms_contact" class="grid-x padding-vertical-2 padding-horizontal-2">
	<div class="cell large-2"></div>
	<div class="cell large-8">
		<div class="text-center">
			<h6><?php echo acym_escapeHtml(acym_translationSprintf('ACYM_X_CONTACTS', $data['nbUsersContact'])); ?></h6>
		</div>
		<br>
		<div class="text-left">
            <?php echo acym_escapeHtml(acym_translation('ACYM_IMPORT_CMS_1')); ?>
			<ol>
				<li><?php echo acym_escapeHtmlWithAllowedTags(acym_translation('ACYM_IMPORT_CONTACT_1'), ['b' => []]); ?></li>
				<li><?php echo acym_escapeHtmlWithAllowedTags(acym_translation('ACYM_IMPORT_CONTACT_2'), ['b' => []]); ?></li>
				<li><?php echo acym_escapeHtmlWithAllowedTags(acym_translation('ACYM_IMPORT_CONTACT_3'), ['b' => []]); ?></li>
			</ol>
		</div>

		<div class="margin-top-1 grid-x">
			<div class="cell medium-5 acym_vcenter">
                <?php echo acym_escapeHtml(acym_translation('ACYM_IMPORT_CONTACTS_CATEGORIES'));
                acym_info(['textShownInTooltip' => 'ACYM_IMPORT_CONTACTS_CATEGORIES_DESC']); ?>
			</div>
			<div class="cell medium-7">
                <?php
                acym_selectMultiple(
                    $data['contactCategories'],
                    'contact_categories',
                    explode(',', $this->config->get('import_contact_categories', '')),
                    [
                        'class' => 'acym__select',
                    ],
                    'value',
                    'text',
                    true
                );
                ?>
			</div>
		</div>

		<div class="cell grid-x grid-margin-x margin-top-2">
			<div class="cell hide-for-small-only medium-auto"></div>
            <?php acym_cancelButton('ACYM_CANCEL', '', 'button medium-6 large-shrink margin-bottom-0'); ?>
			<button data-open="acym__user__import__add-subscription__modal" type="button" class="button cell medium-shrink margin-bottom-0">
                <?php echo acym_escapeHtml(acym_translation('ACYM_IMPORT')); ?>
			</button>
			<button id="submit_import_contact" class="acym__import__submit is-hidden" data-from="contact"></button>
			<div class="cell hide-for-small-only medium-auto"></div>
		</div>
	</div>
	<div class="cell large-2"></div>
	<input type="hidden" name="new_list" id="acym__import__new-list" value="" />
</div>
