<div class="grid-x grid-margin-x">
	<div class="large-3 medium-8 cell">
        <?php echo acym_filterSearch($data['search'], 'campaigns_search', 'ACYM_SEARCH'); ?>
	</div>
	<div class="large-3 medium-4 cell">
        <?php
        $allTags = new stdClass();
        $allTags->name = acym_translation('ACYM_ALL_TAGS');
        $allTags->value = '';
        array_unshift($data['allTags'], $allTags);

        echo acym_select(
            $data['allTags'],
            'campaigns_tag',
            acym_escape($data['tag']),
            [
                'class' => 'acym__campaigns__filter__tags acym__select',
            ],
            'value',
            'name'
        );
        ?>
	</div>
	<div class="large-auto hide-for-large-only hide-for-medium-only hide-for-small-only cell"></div>
	<div class="large-shrink medium-6 cell">
		<button data-task="newEmail" class="button expanded acy_button_submit"><?php echo acym_translation('ACYM_CREATE_NEW_EMAIL'); ?></button>
	</div>
</div>
