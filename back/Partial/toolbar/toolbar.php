<?php
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- View file, its variables are local to the include scope, not true globals.
// context verification

use AcyMailing\Helpers\SecurityHelper;

if (!empty($data['status_toolbar'])) {
    echo '<input type="hidden" id="acym__toolbar__statuses-value" value="'.acym_escape(json_encode($data['status_toolbar'])).'">';
}
?>
<div class="grid-x acym__toolbar acym__content align-justify">
	<div class="cell">
		<div class="grid-x grid-margin-x margin-y">
            <?php if (!empty($data['searchBarInformation'])) { ?>
				<div class="large-3 medium-6 small-12 cell">
                    <?php $data['toolbarHelper']->displaySearchBar(); ?>
				</div>
                <?php if (!empty($data['filteringOptions'])) { ?>
					<button id="acym__toolbar__button-more-filters" type="button" class="medium-6 button button-secondary cell large-shrink">
						<i class="acymicon-filter"></i>
                        <?php echo acym_escapeHtml(acym_translation('ACYM_SHOW_FILTERS')); ?>
					</button>
                <?php } ?>
            <?php } ?>
			<div class="cell large-3 xlarge-auto show-for-large"></div>
            <?php $data['toolbarHelper']->displayActionButtons(); ?>
		</div>
	</div>
</div>
<div class="grid-x acym__toolbar__more-filters acym__content" style="display: none;">
	<div class="cell">
		<div class="grid-x grid-margin-x grid-margin-y margin-bottom-1">
            <?php foreach ($data['filteringOptions'] as $filterOption) { ?>
				<div class="cell medium-3 margin-left-1">
					<div class="cell grid-x shrink acym__toolbar__filters__select">
						<label class="cell">
                            <?php echo acym_escapeHtml($filterOption['title']); ?>
						</label>
                        <?php
                        echo acym_escapeHtmlWithAllowedTags(
                            $filterOption['select'],
                            SecurityHelper::ALLOWED_HTML_SELECT
                        );
                        ?>
					</div>
				</div>
            <?php } ?>
		</div>
	</div>
	<div class="cell">
		<div class="grid-x grid-margin-x grid-margin-y align-right">
            <?php if (!empty($data['cleartask'])) { ?>
				<input type="hidden" name="cleartask" value="<?php echo acym_escape($data['cleartask']); ?>" />
            <?php } ?>
			<button data-task="clearFilters"
			        type="button"
			        class="cell medium-shrink acy_button_submit button button-secondary">
                <?php echo acym_escapeHtml(acym_translation('ACYM_CLEAR_FILTERS')); ?>
			</button>
			<button id="acym__toolbar__more-filters-apply" type="button" class="cell medium-shrink button">
                <?php echo acym_escapeHtml(acym_translation('ACYM_APPLY_FILTERS')); ?>
			</button>
		</div>
	</div>
</div>

