<?php if (empty($data['scenarios'])) { ?>
	<h1 class="cell acym__listing__empty__search__title text-center"><?php echo acym_translation('ACYM_NO_RESULTS_FOUND'); ?></h1>
<?php } else { ?>
	<div class="cell grid-x margin-top-1">
		<div class="grid-x acym__listing__actions cell margin-bottom-1">
            <?php
            $actions = [
                'duplicate' => acym_translation('ACYM_DUPLICATE'),
                'delete' => acym_translation('ACYM_DELETE'),
            ];
            echo acym_listingActions($actions);
            ?>
		</div>
		<div class="cell grid-x align-justify">
			<div class="cell grid-x large-shrink acym_vcenter">
                <?php
                $options = [
                    '' => ['ACYM_ALL', $data['scenariosNumberStatus']->all],
                    'active' => ['ACYM_ACTIVE', $data['scenariosNumberStatus']->active],
                    'inactive' => ['ACYM_INACTIVE', $data['scenariosNumberStatus']->inactive],
                ];
                echo acym_filterStatus($options, $data['status'], 'scenarios_status');
                ?>
			</div>
			<div class="cell large-shrink acym_listing_sort-by">
                <?php
                if (empty($data['ordering'])) {
                    $data['ordering'] = 'ordering';
                }
                if (empty($data['ordering_sort_order'])) {
                    $data['ordering_sort_order'] = 'asc';
                }
                echo acym_sortBy(
                    [
                        'id' => acym_strtolower(acym_translation('ACYM_ID')),
                        'name' => acym_translation('ACYM_NAME'),
                        'active' => acym_translation('ACYM_ACTIVE'),
                        'ordering' => acym_translation('ACYM_ORDERING'),
                    ],
                    'scenarios',
                    $data['ordering'],
                    $data['ordering_sort_order']
                ); ?>
			</div>
		</div>
	</div>
	<div class="grid-x acym__listing acym__sortable__listing acym__scenario__listing cell grid-x" data-sort-ctrl="scenarios">
	<div class="grid-x cell acym__listing__header">
		<div class="medium-shrink small-1 cell">
			<input id="checkbox_all" type="checkbox" name="checkbox_all">
		</div>
		<div class="grid-x medium-auto small-11 cell acym__listing__header__title__container">
			<div class="cell medium-4 small-6 large-4 acym__listing__header__title">
                <?php echo acym_translation('ACYM_SCENARIO'); ?>
			</div>
			<div class="cell medium-4 small-5 large-4 acym__listing__header__title">
                <?php echo acym_translation('ACYM_TRIGGER'); ?>
			</div>
			<div class="cell medium-3 hide-for-small-only large-3 acym__listing__header__title text-center">
                <?php echo acym_translation('ACYM_ACTIVE'); ?>
			</div>
			<div class="cell medium-1 small-1 large-1 acym__listing__header__title">
                <?php echo acym_translation('ACYM_ID'); ?>
			</div>
		</div>
	</div>
    <?php
    foreach ($data['scenarios'] as $scenario) {
        $linkMessage = acym_completeLink(acym_getVar('cmd', 'ctrl').'&task=edit&step=editScenario&scenarioId='.$scenario->id);
        ?>
		<div data-acy-elementid="<?php echo acym_escape($scenario->id); ?>" class="grid-x cell align-middle acym__listing__row"
			 data-id-element="<?php echo acym_escape($scenario->id); ?>">
			<div class="medium-shrink small-1 cell">
				<input id="checkbox_<?php echo acym_escape($scenario->id); ?>"
					   type="checkbox"
					   name="elements_checked[]"
					   value="<?php echo acym_escape($scenario->id); ?>">
			</div>
			<div class="cell small-1 acym_vcenter align-center acym__scenario__listing__handle acym__listing__handle">
				<div class="grabbable acym__sortable__listing__handle grid-x">
					<i class="acymicon-ellipsis-h cell acym__color__dark-gray"></i>
					<i class="acymicon-ellipsis-h cell acym__color__dark-gray"></i>
				</div>
			</div>
			<div class="grid-x medium-auto small-11 cell acym__listing__title__container">
				<div class="grid-x cell medium-4 small-6 large-4 acym__listing__title">
					<a class="cell auto" href="<?php echo $linkMessage; ?>">
						<div><?php echo acym_escape($scenario->name); ?></div>
					</a>
				</div>
				<div class="grid-x cell medium-4 small-6 large-4">
                    <?php echo acym_escape($scenario->trigger); ?>
				</div>
				<div class="grid-x cell medium-3 hide-for-small-only large-3">
					<div class="text-center cell">
                        <?php
                        $class = $scenario->active == 1 ? 'acymicon-check-circle acym__color__green" data-acy-newvalue="0'
                            : 'acymicon-times-circle acym__color__red" data-acy-newvalue="1';
                        echo '<i data-acy-table="scenario" data-acy-field="active" data-acy-elementid="'.acym_escape(
                                $scenario->id
                            ).'" class="acym_toggleable '.$class.'"></i>';
                        ?>
					</div>
				</div>
				<div class="grid-x cell medium-1 small-1 large-1">
                    <?php echo acym_escape($scenario->id); ?>
				</div>
			</div>
		</div>
        <?php
    }
    ?>
	</div>
    <?php
    echo $data['pagination']->display('scenarios');
}
