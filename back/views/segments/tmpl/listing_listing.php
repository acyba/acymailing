<?php if (empty($data['segments'])) { ?>
	<h1 class="cell acym__listing__empty__search__title text-center"><?php echo acym_translation('ACYM_NO_RESULTS_FOUND'); ?></h1>
<?php } else { ?>
	<div class="cell grid-x margin-top-1">
		<div class="grid-x acym__listing__actions cell margin-bottom-1">
            <?php
            $actions = [
                'delete' => acym_translation('ACYM_DELETE'),
                'setActive' => acym_translation('ACYM_ENABLE'),
                'setInactive' => acym_translation('ACYM_DISABLE'),
            ];
            echo acym_listingActions($actions);
            ?>
		</div>
		<div class="grid-x cell">
			<div class="auto cell acym_vcenter">
                <?php
                $options = [
                    '' => ['ACYM_ALL', $data['segmentsNumberPerStatus']['all']],
                    'active' => ['ACYM_ACTIVE', $data['segmentsNumberPerStatus']['active']],
                    'inactive' => ['ACYM_INACTIVE', $data['segmentsNumberPerStatus']['inactive']],
                ];
                echo acym_filterStatus($options, $data["status"], 'segments_status');
                ?>
			</div>
			<div class="cell acym_listing_sort-by auto">
                <?php echo acym_sortBy(
                    [
                        'id' => acym_strtolower(acym_translation('ACYM_ID')),
                        'name' => acym_translation('ACYM_NAME'),
                        'active' => acym_translation('ACYM_ACTIVE'),
                    ],
                    'segments',
                    $data['ordering'],
                    'asc'
                ); ?>
			</div>
		</div>
	</div>
	<div class="grid-x acym__listing">
		<div class="grid-x cell acym__listing__header">
			<div class="medium-shrink small-1 cell">
				<input id="checkbox_all" type="checkbox" name="checkbox_all">
			</div>
			<div class="grid-x medium-auto small-11 cell acym__listing__header__title__container">
				<div class="large-4 medium-4 cell acym__listing__header__title">
                    <?php echo acym_translation('ACYM_NAME'); ?>
				</div>
				<div class="auto hide-for-small-only cell acym__listing__header__title">
                    <?php echo acym_translation('ACYM_DATE_CREATED'); ?>
				</div>
				<div class="large-1 medium-1 text-center hide-for-small-only cell acym__listing__header__title">
                    <?php echo acym_translation('ACYM_ACTIVE'); ?>
				</div>
				<div class="large-1 medium-1 text-center hide-for-small-only cell acym__listing__header__title">
                    <?php echo acym_translation('ACYM_ID'); ?>
				</div>
			</div>
		</div>
        <?php foreach ($data['segments'] as $segment) { ?>
			<div data-acy-elementid="<?php echo acym_escape($segment->id); ?>" class="grid-x cell acym__listing__row">
				<div class="medium-shrink small-1 cell">
					<input id="checkbox_<?php echo acym_escape($segment->id); ?>" type="checkbox" name="elements_checked[]" value="<?php echo acym_escape($segment->id); ?>">
				</div>
				<div class="grid-x medium-auto small-11 cell acym__listing__title__container">
					<div class="grid-x large-4 medium-4 small-11 cell acym__listing__title">
						<a class="cell" href="<?php echo acym_completeLink('segments&task=edit&id='.intval($segment->id)); ?>">
							<h6 class="acym__listing__title__important"><?php echo acym_escape($segment->name); ?></h6>
						</a>
					</div>
					<div class="cell auto hide-for-small-only">
                        <?php
                        echo acym_tooltip(
                            acym_date(
                                $segment->creation_date,
                                acym_translation('ACYM_DATE_FORMAT_LC5'),
                                false
                            ),
                            $segment->creation_date
                        );
                        ?>
					</div>
					<div class="cell small-1 acym__listing__controls text-center">
                        <?php
                        $class = $segment->active == 1 ? 'acymicon-check-circle acym__color__green" data-acy-newvalue="0' : 'acymicon-times-circle acym__color__red" data-acy-newvalue="1';
                        echo '<i data-acy-table="segment" data-acy-field="active" data-acy-elementid="'.acym_escape($segment->id).'" class="acym_toggleable '.$class.'"></i>';
                        ?>
					</div>
					<div class="cell medium-1 hide-for-small-only text-center">
                        <?php echo acym_escape($segment->id); ?>
					</div>
				</div>
			</div>
        <?php } ?>
        <?php echo $data['pagination']->display('form'); ?>
	</div>
<?php } ?>
