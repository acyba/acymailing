<?php if (empty($data['allMailboxes'])) { ?>
	<h1 class="cell acym__listing__empty__search__title text-center"><?php echo acym_translation('ACYM_NO_RESULTS_FOUND'); ?></h1>
<?php } else { ?>
	<div class="cell margin-bottom-1 acym__listing__actions grid-x">
        <?php
        $actions = [
            'duplicateMailboxAction' => acym_translation('ACYM_DUPLICATE'),
            'deleteMailboxAction' => acym_translation('ACYM_DELETE'),
        ];
        echo acym_listingActions($actions);
        ?>
	</div>
	<div class="cell grid-x align-justify">
		<div class="cell grid-x large-shrink acym_vcenter">
			<div class="auto cell acym_vcenter">
                <?php
                $options = [
                    '' => ['ACYM_ALL', $data['allStatusFilters']['all']],
                    'active' => ['ACYM_ACTIVE', $data['allStatusFilters']['active']],
                    'inactive' => ['ACYM_INACTIVE', $data['allStatusFilters']['inactive']],
                ];
                echo acym_filterStatus($options, $data['status'], 'mailboxes_status');
                ?>
			</div>
		</div>
		<div class="cell large-shrink acym_listing_sort-by">
            <?php echo acym_sortBy(
                [
                    'name' => acym_translation('ACYM_NAME'),
                    'username' => acym_translation('ACYM_USERNAME'),
                    'id' => acym_translation('ACYM_ID'),
                    'active' => acym_translation('ACYM_ACTIVE'),
                ],
                'mailboxes',
                $data['ordering']
            ); ?>
		</div>
	</div>
	<div class="grid-x acym__listing">
		<div class="grid-x cell acym__listing__header">
			<div class="medium-shrink small-1 cell">
				<input id="checkbox_all" type="checkbox">
			</div>
			<div class="grid-x medium-auto small-11 cell acym__listing__header__title__container">
				<div class="large-4 medium-4 cell acym__listing__header__title">
                    <?php echo acym_translation('ACYM_NAME'); ?>
				</div>
				<div class="large-2 medium-2 hide-for-small-only cell acym__listing__header__title">
                    <?php echo acym_translation('ACYM_EMAIL'); ?>
				</div>
				<div class="auto hide-for-small-only cell acym__listing__header__title">
                    <?php echo acym_translation('ACYM_ACTIONS'); ?>
				</div>
				<div class="large-1 medium-1 text-center hide-for-small-only cell acym__listing__header__title">
                    <?php echo acym_translation('ACYM_ACTIVE'); ?>
				</div>
				<div class="large-1 medium-1 text-center hide-for-small-only cell acym__listing__header__title">
                    <?php echo acym_translation('ACYM_ID'); ?>
				</div>
			</div>
		</div>
        <?php
        foreach ($data['allMailboxes'] as $mailbox) {
            ?>
			<div class="grid-x cell align-middle acym__listing__row">
				<div class="medium-shrink small-1 cell">
					<input id="checkbox_<?php echo acym_escape($mailbox->id); ?>" type="checkbox" name="elements_checked[]" value="<?php echo acym_escape($mailbox->id); ?>">
				</div>
				<div class="grid-x medium-auto small-11 cell acym__listing__title__container">
					<div class="grid-x large-4 medium-4 small-11 cell acym__listing__title">
						<a class="cell" href="<?php echo acym_completeLink('bounces&task=mailboxAction&mailboxId='.intval($mailbox->id)); ?>">
							<h6 class="acym__listing__title__important"><?php echo acym_escape($mailbox->name); ?></h6>
						</a>
					</div>
					<div class="cell large-2 medium-2 hide-for-small-only">
                        <?php echo $mailbox->username; ?>
					</div>
					<div class="cell auto hide-for-small-only grid-x">
                        <?php foreach ($mailbox->actionsRendered as $actionRendered) { ?>
							<span class="cell acym_text_ellipsis"><?php echo $actionRendered; ?></span>
                        <?php } ?>
					</div>
					<div class="cell small-1 acym__listing__controls text-center">
                        <?php
                        $class = $mailbox->active == 1 ? 'acymicon-check-circle acym__color__green" data-acy-newvalue="0'
                            : 'acymicon-times-circle acym__color__red" data-acy-newvalue="1';
                        echo '<i data-acy-table="mailbox_action" data-acy-field="active" data-acy-elementid="'.acym_escape(
                                $mailbox->id
                            ).'" class="acym_toggleable '.$class.'"></i>';
                        ?>
					</div>
					<div class="cell medium-1 hide-for-small-only text-center">
                        <?php echo acym_escape($mailbox->id); ?>
					</div>
				</div>
			</div>
        <?php } ?>
	</div>
    <?php echo $data['pagination']->display('mailboxes');
}
