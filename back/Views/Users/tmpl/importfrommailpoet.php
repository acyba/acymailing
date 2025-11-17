<div id="acym__users__import__mailpoet_users" class="grid-x padding-vertical-2 padding-horizontal-2 align-center">
	<div class="cell medium-8 grid-x grid-margin-x acym_vcenter">
		<div class="cell medium-4">
            <?php echo acym_translation('ACYM_ONLY_IMPORT_FROM_MAILPOET_LISTS').acym_info(['textShownInTooltip' => 'ACYM_ONLY_IMPORT_FROM_MAILPOET_LISTS_DESC']); ?>
		</div>
		<div class="cell shrink medium-8">
            <?php
            echo acym_selectMultiple($data['mailpoet_list'], 'mailpoet_lists', [], ['class' => 'acym__select',], 'id', 'name');
            ?>
		</div>
		<div class="cell grid-x grid-margin-x margin-top-2">
			<div class="cell hide-for-small-only medium-auto"></div>
            <?php echo acym_cancelButton('ACYM_CANCEL', '', 'button medium-6 large-shrink margin-bottom-0'); ?>
			<button data-open="acym__user__import__add-subscription__modal" type="button" class="button cell medium-shrink margin-bottom-0">
                <?php echo acym_translation('ACYM_IMPORT'); ?>
			</button>
			<button id="submit_import_mailpoet" class="acym__import__submit is-hidden" data-from="mailpoet"></button>
			<div class="cell hide-for-small-only medium-auto"></div>
		</div>
		<input type="hidden" name="new_list" id="acym__import__new-list" value="" />
	</div>
</div>
