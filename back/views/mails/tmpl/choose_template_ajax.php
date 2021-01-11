<div class="acym__template__choose__modal__loader cell grid-x acym_vcenter align-center">
    <?php echo acym_loaderLogo(); ?>
</div>
<div class="grid-x grid-margin-x acym__template__edit__choose-template__ajax">
	<input type="hidden" name="tag_template_choose" id="acym_tag_template_choose__ajax" value="">
	<input type="hidden" name="search_template_choose" id="acym_search_template_choose__ajax" value="">
	<div class="cell grid-x grid-margin-x">
		<div class="medium-4 cell">
            <?php echo acym_filterSearch('', 'mailchoose_search__ajax', 'ACYM_SEARCH'); ?>
		</div>

		<div class="medium-4 cell">
            <?php
            $allTags = new stdClass();
            $allTags->name = acym_translation('ACYM_ALL_TAGS');
            $allTags->value = '';
            array_unshift($data['allTags'], $allTags);
            echo acym_select(
                $data['allTags'],
                'mailchoose_tag__ajax',
                null,
                'class="acym__templates__filter__tags__ajax acym__select"',
                'value',
                'name'
            );
            ?>
		</div>
	</div>
	<div class="grid-x cell">
	</div>
	<div class="acym__template__choose__ajax cell grid-x ">
	</div>
</div>
