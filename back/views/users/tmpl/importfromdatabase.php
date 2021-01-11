<div id="acym__users__import__from_database" class="grid-x padding-vertical-2 padding-horizontal-2">
	<div class="cell large-3"></div>
	<div class="cell large-6 grid-x margin-y">
		<label class="cell medium-4" for="acym__users__import__from_database__field--tablename"><?php echo acym_translation('ACYM_TABLE_NAME'); ?></label>
        <?php
		echo '<div class="cell medium-8">';
        array_unshift($data['tables'], acym_translation('ACYM_SELECT_TABLE'));
        echo acym_select(
            $data['tables'],
            'tablename',
            null,
            [
                'class' => 'acym__select',
            ],
            'value',
            'name',
            'acym__users__import__from_database__field--tablename'
        );
        echo '</div>';

        $userFields = acym_getColumns('user');
        if (!empty($userFields)) {
            foreach ($userFields as $oneUserField) {
                if (!in_array($oneUserField, ['id', 'key', 'automation'])) {
                    echo '<label class="cell medium-4" for="acym__users__import__from_database__field--'.$oneUserField.'">'.$oneUserField.'</label>';
                    echo '<div class="cell medium-8">';
                    echo '<select acym-data-infinite class="acym__users__import__from_database__fields acym__select" name="fields['.$oneUserField.']" id="acym__users__import__from_database__field--'.$oneUserField.'"></select>';
                    echo '</div>';
                }
            }
        }
        if ($this->config->get('require_confirmation')) { ?>
			<div class="cell grid-x">
                <?php echo acym_switch('import_confirmed_database', 1, acym_translation('ACYM_IMPORT_USERS_AS_CONFIRMED')); ?>
			</div>
        <?php } ?>

		<div class="cell grid-x grid-margin-x">
			<div class="cell hide-for-small-only medium-auto"></div>
            <?php echo acym_cancelButton('ACYM_CANCEL', '', 'button medium-6 large-shrink margin-bottom-0'); ?>
			<button type="button" class="button cell medium-shrink margin-bottom-0" data-open="acym__user__import__add-subscription__modal" data-from="database">
                <?php echo acym_translation('ACYM_IMPORT'); ?>
			</button>
			<button id="submit_import_database" class="acym__import__submit is-hidden" data-from="database"></button>
			<div class="cell hide-for-small-only medium-auto"></div>
		</div>
	</div>
	<div class="cell large-3"></div>
</div>
