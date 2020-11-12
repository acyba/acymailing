<div class="cell grid-x acym_vcenter padding-bottom-1 acym__segments__edit__filters__info__users">
	<h3 class="cell shrink acym__title__primary__color margin-right-1"><?php echo acym_translation('ACYM_NUMBER_OF_ACYMAILING_USERS_MATCHING_CONDITIONS'); ?></h3>
	<span class="cell shrink acym__segments__edit__filters__total"></span>
	<span class="cell shrink acym__segments__edit__filters__no-users" style="display: none"><?php echo acym_translation('ACYM_NO_USERS_SELECTED_YET_PLEASE_SELECT_FILTER'); ?></span>
</div>
<div class="cell grid-x acym__segments__one__filter" id="acym__segments__and__example" style="display: none;">
	<div class="acym__automation__and cell grid-x margin-top-2">
		<h6 class="cell medium-shrink small-11 acym__content__title__light-blue"><?php echo acym_translation('ACYM_AND'); ?></h6>
	</div>
	<div class="medium-5 cell acym__segments__and__example__classic__select">
        <?php echo acym_select($data['filter_name'], 'filters_name', null, 'class="acym__segments__select__classic__filter" data-class="acym__select"'); ?>
	</div>
</div>

<div class="cell grid-x acym__automation__filter__container margin-top-1" id="acym__automation__filters__type__classic">
	<input type="hidden" value="<?php echo acym_escape($data['filter_option']); ?>" id="acym__automation__filter__classic__options">
	<div class="cell grid-x acym__content acym__segments__group__filter" data-filter-number="0">
		<div class="auto cell hide-for-medium-only hide-for-small-only"></div>
		<div class="cell grid-x acym__segments__one__filter acym__segments__one__filter__classic">
			<div class="medium-5 cell">
                <?php
                echo acym_select($data['filter_name'], 'filters_name', null, 'class="acym__select acym__segments__select__classic__filter"');
                ?>
			</div>
		</div>
		<div class="cell grid-x margin-top-2">
			<button data-filter-type="classic" data-block="0" type="button" class="button-secondary button medium-shrink acym__segments__add-filter"><?php echo acym_translation('ACYM_ADD_FILTER'); ?></button>
		</div>
	</div>
</div>
