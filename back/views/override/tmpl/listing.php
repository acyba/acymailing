<form id="acym_form" action="<?php echo acym_completeLink(acym_getVar('cmd', 'ctrl')); ?>" method="post" name="acyForm">
    <?php $data['toolbar']->displayToolbar($data); ?>
	<div id="acym__override" class="acym__content cell">
        <?php
        $workflow = $data['workflowHelper'];
        echo $workflow->displayTabs($this->tabs, 'listing&overrideMailSource='.$data['source']);
        ?>
		<div class="cell grid-x margin-top-1">
			<div class="grid-x acym__listing__actions cell margin-bottom-1">
                <?php
                $actions = [
                    'setActive' => acym_translation('ACYM_ACTIVATE'),
                    'setInactive' => acym_translation('ACYM_DEACTIVATE'),
                    'reset' => acym_translation('ACYM_RESET'),
                ];
                echo acym_listingActions($actions);
                ?>
			</div>
			<div class="cell grid-x">
				<div class="auto cell acym_vcenter">
                    <?php
                    $options = [
                        '' => ['ACYM_ALL', $data['overrideNumberPerStatus']['all']],
                        'active' => ['ACYM_ACTIVE', $data['overrideNumberPerStatus']['active']],
                        'inactive' => ['ACYM_INACTIVE', $data['overrideNumberPerStatus']['inactive']],
                    ];
                    echo acym_filterStatus($options, $data['status'], 'emails_override_status');
                    ?>
				</div>
				<div class="cell acym_listing_sort-by auto">
                    <?php echo acym_sortBy(
                        [
                            'active' => acym_translation('ACYM_ACTIVE'),
                        ],
                        'override',
                        $data['ordering']
                    ); ?>
				</div>
			</div>
		</div>
		<div class="grid-x acym__listing acym__listing__view__override">
			<div class="grid-x cell acym__listing__header">
				<div class="medium-shrink small-1 cell">
					<input id="checkbox_all" type="checkbox" name="checkbox_all">
				</div>
				<div class="grid-x medium-auto small-11 cell acym__listing__header__title__container">
					<div class="acym__listing__header__title cell medium-4">
                        <?php echo acym_translation('ACYM_NAME'); ?>
					</div>
					<div class="acym__listing__header__title cell hide-for-small-only medium-7">
                        <?php echo acym_translation('ACYM_DESCRIPTION'); ?>
					</div>
					<div class="acym__listing__header__title cell hide-for-small-only text-center medium-1">
                        <?php echo acym_translation('ACYM_ACTIVE'); ?>
					</div>
				</div>
			</div>
            <?php foreach ($data['allEmailsOverride'] as $override) { ?>
				<div data-acy-elementid="<?php echo acym_escape($override->id); ?>" class="grid-x cell acym__listing__row">
					<div class="medium-shrink small-1 cell">
						<input id="checkbox_<?php echo acym_escape($override->id); ?>" type="checkbox" name="elements_checked[]" value="<?php echo acym_escape($override->id); ?>">
					</div>
					<div class="grid-x medium-auto small-11 cell acym__listing__title__container">
						<div class="cell medium-4 acym__listing__title">
							<a href="<?php echo acym_completeLink('mails&task=edit&type=override&id='.intval($override->mail_id).'&return='.urlencode(acym_currentURL())); ?>">
								<p><?php
                                    $subject = preg_replace('#^{trans:([A-Z_]+)(|.+)*}$#', '$1', $override->subject);
                                    echo acym_translationSprintf($subject, '{param1}', '{param2}');
                                    ?></p>
							</a>
						</div>
						<div class="cell medium-6">
                            <?php echo acym_translation($override->description); ?>
						</div>
						<div class="cell medium-1"></div>
						<div class="cell medium-1 text-center acym__listing__controls">
                            <?php
                            if ($override->active == 1) {
                                $class = 'acymicon-check-circle acym__color__green" data-acy-newvalue="0';
                                $tooltip = 'ACYM_ACTIVE';
                            } else {
                                $class = 'acymicon-times-circle acym__color__red" data-acy-newvalue="1';
                                $tooltip = 'ACYM_INACTIVE';
                            }
                            echo acym_tooltip(
                                '<i data-acy-table="mail_override" data-acy-field="active" data-acy-elementid="'.acym_escape(
                                    $override->id
                                ).'" class="acym_toggleable '.$class.'"></i>',
                                acym_translation($tooltip)
                            );
                            ?>
						</div>
					</div>
				</div>
            <?php } ?>
		</div>
        <?php echo $data['pagination']->display('override'); ?>

	</div>
    <?php acym_formOptions(); ?>
</form>
