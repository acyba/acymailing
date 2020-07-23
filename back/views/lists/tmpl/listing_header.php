<div class="grid-x grid-margin-x">
	<div class="large-auto medium-8 cell">
        <?php echo acym_filterSearch($data['search'], 'lists_search', 'ACYM_SEARCH'); ?>
	</div>
	<div class="large-auto medium-4 cell">
        <?php
        $allTags = new stdClass();
        $allTags->name = acym_translation('ACYM_ALL_TAGS');
        $allTags->value = '';
        array_unshift($data['tags'], $allTags);

        echo acym_select(
            $data['tags'],
            'lists_tag',
            $data['tag'],
            'class="acym__lists__filter__tags acym__select"',
            'value',
            'name'
        );
        ?>
	</div>
	<div class="xxlarge-4 xlarge-3 hide-for-large-only medium-auto hide-for-small-only cell"></div>
	<div class="large-shrink medium-6 small-12 cell">
		<button type="submit" id="acym__list__export" data-task="export" data-ctrl="users" class="button expanded button-secondary acy_button_submit">
            <?php echo acym_translation('ACYM_EXPORT'); ?> (<span id="acym__lists__listing__number_to_export" data-default="0"></span>)
		</button>
		<input type="hidden" name="preselectList" value="1" />
	</div>
	<div class="medium-shrink cell">
		<button data-task="settings" class="button acy_button_submit"><?php echo acym_translation('ACYM_CREATE_NEW_LIST'); ?></button>
	</div>
</div>
