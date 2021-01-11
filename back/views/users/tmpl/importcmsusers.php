<div id="acym__users__import__cms_users" class="grid-x padding-vertical-2 padding-horizontal-2">
	<div class="cell large-2"></div>
	<div class="cell large-8">
		<div class="text-center">
			<h6><?php echo acym_translationSprintf('ACYM_IMPORT_NB_WEBSITE_USERS', $data['nbUsersCMS']); ?></h6>
			<h6><?php echo acym_translationSprintf('ACYM_IMPORT_NB_ACYM_USERS', $data['nbUsersAcymailing']); ?></h6>
		</div>
		<br>
		<div class="text-left">
            <?php echo acym_translation('ACYM_IMPORT_CMS_1'); ?>
			<ol>
				<li><?php echo acym_translationSprintf('ACYM_IMPORT_CMS_2', ACYM_CMS_TITLE); ?></li>
				<li><?php echo acym_translationSprintf('ACYM_IMPORT_CMS_3', ACYM_CMS_TITLE); ?></li>
				<li><?php echo acym_translationSprintf('ACYM_IMPORT_CMS_4', ACYM_CMS_TITLE); ?></li>
				<li><?php echo acym_translationSprintf('ACYM_IMPORT_CMS_5', ACYM_CMS_TITLE); ?></li>
			</ol>
		</div>

		<div class="margin-top-1 grid-x">
			<div class="cell medium-5 acym_vcenter"><?php echo acym_translation('ACYM_IMPORT_CMS_GROUPS').acym_info('ACYM_IMPORT_CMS_GROUPS_DESC'); ?></div>
			<div class="cell medium-7">
                <?php
                echo acym_selectMultiple(
                    acym_getGroups(),
                    'groups',
                    explode(',', $this->config->get('import_groups', '')),
                    [
                        'class' => 'acym__select',
                    ]
                );
                ?>
			</div>
		</div>

		<div class="cell grid-x grid-margin-x margin-top-2">
			<div class="cell hide-for-small-only medium-auto"></div>
            <?php echo acym_cancelButton('ACYM_CANCEL', '', 'button medium-6 large-shrink margin-bottom-0'); ?>
			<button data-open="acym__user__import__add-subscription__modal" type="button" class="button cell medium-shrink margin-bottom-0">
                <?php echo acym_translation('ACYM_IMPORT'); ?>
			</button>
			<button id="submit_import_cms" class="acym__import__submit is-hidden" data-from="cms"></button>
			<div class="cell hide-for-small-only medium-auto"></div>
		</div>
	</div>
	<div class="cell large-2"></div>
	<input type="hidden" name="new_list" id="acym__import__new-list" value="" />
</div>
