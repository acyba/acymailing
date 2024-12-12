<?php if (empty($data['lists'])) { ?>
	<h1 class="cell acym__listing__empty__search__title text-center"><?php echo acym_translation('ACYM_NO_RESULTS_FOUND'); ?></h1>
<?php } else { ?>
	<div class="cell grid-x margin-top-1">
		<div class="grid-x acym__listing__actions cell margin-bottom-1">
            <?php
            $actions = [
                'setActive' => acym_translation('ACYM_ENABLE'),
                'setInactive' => acym_translation('ACYM_DISABLE'),
                'delete' => acym_translation('ACYM_DELETE'),
            ];
            echo acym_listingActions($actions);
            ?>
		</div>
		<div class="cell grid-x align-justify">
			<div class="cell grid-x large-shrink acym_vcenter">
                <?php
                $options = [
                    '' => ['ACYM_ALL', $data['listNumberPerStatus']['all']],
                    'active' => ['ACYM_ACTIVE', $data['listNumberPerStatus']['active']],
                    'inactive' => ['ACYM_INACTIVE', $data['listNumberPerStatus']['inactive']],
                    'visible' => ['ACYM_VISIBLE', $data['listNumberPerStatus']['visible']],
                    'invisible' => ['ACYM_INVISIBLE', $data['listNumberPerStatus']['invisible']],
                ];
                echo acym_filterStatus($options, $data['status'], 'lists_status');
                ?>
			</div>
			<div class="cell large-shrink acym_listing_sort-by">
                <?php echo acym_sortBy(
                    [
                        'id' => acym_strtolower(acym_translation('ACYM_ID')),
                        'name' => acym_translation('ACYM_NAME'),
                        'creation_date' => acym_translation('ACYM_DATE_CREATED'),
                        'active' => acym_translation('ACYM_ACTIVE'),
                        'visible' => acym_translation('ACYM_VISIBLE'),
                    ],
                    'lists',
                    $data['ordering']
                ); ?>
			</div>
		</div>
	</div>
    <?php
    $columnClassesText = acym_isAdmin() ? 'show-for-xxlarge medium-1 xxlarge-2' : 'is-hidden medium-1';
    $columnClassesIcon = acym_isAdmin() ? 'hide-for-small-only hide-for-xxlarge medium-1 xxlarge-2' : 'hide-for-small-only medium-1';
    $columnWidthClass = acym_isAdmin() ? 'medium-1 xxlarge-2' : 'medium-1';
    ?>
	<div class="grid-x acym__listing acym__listing__view__list">
		<div class="grid-x cell acym__listing__header">
			<div class="medium-shrink small-1 cell">
				<input id="checkbox_all" type="checkbox" name="checkbox_all">
			</div>
			<div class="grid-x medium-auto small-11 cell acym__listing__header__title__container">
				<div class="acym__listing__header__title cell auto">
                    <?php echo acym_translation('ACYM_LIST'); ?>
				</div>
				<div class="acym__listing__header__title cell <?php echo $columnClassesText; ?> text-center">
                    <?php echo acym_translation('ACYM_SUBSCRIBED'); ?>
				</div>
				<div class="acym__listing__header__title cell <?php echo $columnClassesIcon; ?> text-center">
                    <?php
                    echo acym_tooltip(
                        [
                            'hoveredText' => '<i class="acymicon-user-check"></i>',
                            'textShownInTooltip' => acym_translation('ACYM_SUBSCRIBED'),
                        ]
                    );
                    ?>
				</div>
                <?php if ($this->config->get('require_confirmation', 1) == 1) { ?>
					<div class="acym__listing__header__title cell show-for-xxlarge <?php echo $columnClassesText; ?> text-center">
                        <?php echo acym_translation('ACYM_NOT_CONFIRMED'); ?>
					</div>
					<div class="acym__listing__header__title cell <?php echo $columnClassesIcon; ?> text-center">
                        <?php
                        echo acym_tooltip(
                            [
                                'hoveredText' => '<i class="acymicon-hourglass-2"></i>',
                                'textShownInTooltip' => acym_translation('ACYM_NOT_CONFIRMED'),
                            ]
                        );
                        ?>
					</div>
                <?php } ?>
				<div class="acym__listing__header__title cell <?php echo $columnClassesText; ?> text-center">
                    <?php echo acym_translation('ACYM_UNSUBSCRIBED'); ?>
				</div>
				<div class="acym__listing__header__title cell <?php echo $columnClassesIcon; ?> text-center">
                    <?php
                    echo acym_tooltip(
                        [
                            'hoveredText' => '<i class="acymicon-user-minus"></i>',
                            'textShownInTooltip' => acym_translation('ACYM_UNSUBSCRIBED'),
                        ]
                    );
                    ?>
				</div>
				<div class="acym__listing__header__title cell <?php echo $columnClassesText; ?> text-center">
                    <?php echo acym_translation('ACYM_INACTIVE'); ?>
				</div>
				<div class="acym__listing__header__title cell <?php echo $columnClassesIcon; ?> text-center">
                    <?php
                    echo acym_tooltip(
                        [
                            'hoveredText' => '<i class="acymicon-remove"></i>',
                            'textShownInTooltip' => acym_translation('ACYM_INACTIVE'),
                        ]
                    );
                    ?>
				</div>
				<div class="acym__listing__header__title cell hide-for-small-only medium-2 large-1 text-center">
                    <?php echo acym_translation('ACYM_STATUS'); ?>
				</div>
				<div class="large-1 cell hide-for-small-only hide-for-medium-only text-center acym__listing__header__title">
                    <?php echo acym_translation('ACYM_ACTIONS') ?>
				</div>
				<div class="acym__listing__header__title cell hide-for-small-only medium-shrink text-center acym__listing__id">
                    <?php echo acym_translation('ACYM_ID'); ?>
				</div>
			</div>
		</div>
        <?php foreach ($data['lists'] as $list) {
            $linkList = acym_completeLink(acym_getVar('cmd', 'ctrl').'&task=settings&listId='.intval($list->id));
            ?>
			<div data-acy-elementid="<?php echo acym_escape($list->id); ?>" class="grid-x cell align-middle acym__listing__row">
				<div class="medium-shrink small-1 cell">
					<input id="checkbox_<?php echo acym_escape($list->id); ?>" type="checkbox" name="elements_checked[]" value="<?php echo acym_escape($list->id); ?>">
				</div>
				<div class="grid-x medium-auto small-11 cell acym__listing__title__container">
					<div class="cell auto grid-x acym__listing__title">
						<i class='cell shrink acymicon-circle' style="color:<?php echo acym_escape($list->color); ?>"></i>
						<a class="cell auto" href="<?php echo $linkList; ?>">
                            <?php
                            echo '<h6 class="acym__listing__title__primary">'.acym_escape($list->name).'</h6>';
                            echo '<p class="acym__listing__title__secondary" title="'.acym_escape($list->description).'">'.acym_escape($list->description).'</p>';
                            ?>
						</a>
					</div>
					<div class="small-1 <?php echo $columnWidthClass; ?> text-center small-up-1 cell">
                        <?php
                        echo acym_tooltip(
                            [
                                'hoveredText' => $list->sendable_users,
                                'textShownInTooltip' => acym_translation('ACYM_SUBSCRIBED'),
                            ]
                        );

                        if (!empty($list->newSub)) {
                            echo acym_tooltip(
                                [
                                    'hoveredText' => ' <span class="acym__listing__evol-green">(+'.$list->newSub.')</span>',
                                    'textShownInTooltip' => acym_translation('ACYM_EVOL_SUB'),
                                ]
                            );
                        } ?>
					</div>
                    <?php if ($this->config->get('require_confirmation', 1) == 1) { ?>
						<div class="small-1 <?php echo $columnWidthClass; ?> text-center small-up-1 cell">
                            <?php
                            echo acym_tooltip(
                                [
                                    'hoveredText' => $list->unconfirmed_users,
                                    'textShownInTooltip' => acym_translation('ACYM_NOT_CONFIRMED'),
                                ]
                            );
                            ?>
						</div>
                    <?php } ?>
					<div class="small-1 <?php echo $columnWidthClass; ?> text-center small-up-1 cell">
                        <?php
                        echo acym_tooltip(
                            [
                                'hoveredText' => $list->unsubscribed_users,
                                'textShownInTooltip' => acym_translation('ACYM_UNSUBSCRIBED'),
                            ]
                        );
                        ?>
                        <?php
                        if (!empty($list->newUnsub)) {
                            echo acym_tooltip(
                                [
                                    'hoveredText' => ' <span class="acym__listing__evol-red">(+'.$list->newUnsub.')</span>',
                                    'textShownInTooltip' => acym_translation('ACYM_EVOL_UNSUB'),
                                ]
                            );
                        } ?>
					</div>
					<div class="small-1 <?php echo $columnWidthClass; ?> text-center small-up-1 cell">
                        <?php
                        echo acym_tooltip(
                            [
                                'hoveredText' => $list->inactive_users,
                                'textShownInTooltip' => acym_translation('ACYM_INACTIVE'),
                            ]
                        );
                        ?>
					</div>
					<div class="cell small-1 medium-2 large-1 text-center acym__listing__controls acym__lists__controls acym__icon__table">
                        <?php
                        if ($list->active == 1) {
                            $class = 'acymicon-check-circle acym__color__green" data-acy-newvalue="0';
                            $tooltip = 'ACYM_ACTIVE';
                        } else {
                            $class = 'acymicon-times-circle acym__color__red" data-acy-newvalue="1';
                            $tooltip = 'ACYM_INACTIVE';
                        }
                        echo acym_tooltip(
                            [
                                'hoveredText' => '<i data-acy-table="list" data-acy-field="active" data-acy-elementid="'.acym_escape(
                                        $list->id
                                    ).'" class="acym_toggleable '.$class.'"></i>',
                                'textShownInTooltip' => acym_translation($tooltip),
                            ]
                        );

                        if (acym_isAdmin()) {
                            if ($list->visible == 1) {
                                $class = 'acymicon-eye" data-acy-newvalue="0';
                                $tooltip = 'ACYM_VISIBLE';
                            } else {
                                $class = 'acymicon-eye-slash acym__color__dark-gray" data-acy-newvalue="1';
                                $tooltip = 'ACYM_INVISIBLE';
                            }
                            echo acym_tooltip(
                                [
                                    'hoveredText' => '<i data-acy-table="list" data-acy-field="visible" data-acy-elementid="'.acym_escape(
                                            $list->id
                                        ).'" class="acym_toggleable '.$class.'"></i>',
                                    'textShownInTooltip' => acym_translation($tooltip),
                                ]
                            );
                        }
                        ?>
					</div>
					<div class="large-1 hide-for-small-only hide-for-medium-only cell text-center acym__icon__table">
						<a href="<?php echo $linkList; ?>"><i class="acymicon-pencil" title="<?php echo acym_translation('ACYM_EDIT'); ?>"></i></a>
                        <?php if (acym_isAdmin()) { ?>
							<a><i class="acymicon-download fastActions"
								  data-action="export"
								  data-ctrl="users"
								  data-acy-elementid="<?php echo acym_escape($list->id); ?>"
								  title="<?php echo acym_translation('ACYM_EXPORT'); ?>"></i></a>
                        <?php } ?>
						<i class="cursor-pointer acymicon-trash-o fastActions deleteFastAction"
						   data-action="delete"
						   data-acy-elementid="<?php echo acym_escape($list->id); ?>"
						   title="<?php echo acym_translation('ACYM_DELETE'); ?>"></i>
					</div>
					<div class="medium-shrink hide-for-small-only text-center acym__listing__id">
                        <?php echo acym_escape($list->id); ?>
					</div>
				</div>
			</div>
        <?php } ?>
	</div>
    <?php echo $data['pagination']->display('lists'); ?>
<?php } ?>
