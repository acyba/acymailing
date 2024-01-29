<form id="acym_form" action="<?php echo acym_completeLink(acym_getVar('cmd', 'ctrl')); ?>" method="post" name="acyForm" data-abide novalidate>
    <?php
    $isEmpty = empty($data['allElements']) && empty($data['search']) && empty($data['tag']) && empty($data['status']);
    if (!$isEmpty) {
        $data['toolbar']->displayToolbar($data);
    }
    ?>
	<div id="acym__queue" class="acym__content">
        <?php
        $workflow = $data['workflowHelper'];
        echo $workflow->displayTabs($this->steps, 'scheduled');
        ?>

        <?php if ($isEmpty) { ?>
			<div class="grid-x text-center">
				<h1 class="acym__listing__empty__title cell"><?php echo acym_translation('ACYM_YOU_DONT_HAVE_ANY_CAMPAIGN_IN_QUEUE'); ?></h1>
				<h1 class="acym__listing__empty__subtitle cell"><?php echo acym_translation('ACYM_SEND_ONE_AND_SEE_HOW_AMAZING_QUEUE_IS'); ?></h1>
			</div>
        <?php } else { ?>
            <?php if (empty($data['allElements'])) { ?>
				<h1 class="cell acym__listing__empty__search__title text-center"><?php echo acym_translation('ACYM_NO_RESULTS_FOUND'); ?></h1>
            <?php } else { ?>
				<div class="grid-x acym__listing acym__listing__view__squeue">
					<div class="cell grid-x acym__listing__header">
						<div class="acym__listing__header__title cell medium-auto hide-for-small-only">
                            <?php echo acym_translation('ACYM_MAILS'); ?>
						</div>
						<div class="acym__listing__header__title cell large-3 hide-for-medium-only hide-for-small-only text-center">
                            <?php echo acym_translation('ACYM_RECIPIENTS'); ?>
						</div>
						<div class="acym__listing__header__title cell medium-4 hide-for-small-only text-center">
                            <?php echo acym_translation('ACYM_SENDING_DATE'); ?>
						</div>
						<div class="cell medium-2 hide-for-small-only"></div>
					</div>
                    <?php foreach ($data['allElements'] as $row) {
                        ?>
						<div data-acy-elementid="<?php echo acym_escape($row->id); ?>" class="cell grid-x acym__listing__row">
							<div class="cell medium-auto acym_vcenter">
								<div class="acym__listing__title">
                                    <?php
                                    $afterName = $row->language;
                                    if (!empty($row->sending_params['abtest'])) {
                                        $afterName = $row->subject;
                                    } elseif (!empty($data['languages'][$row->language])) {
                                        $afterName = $data['languages'][$row->language]->name;
                                    }
                                    $afterName = empty($afterName) ? '' : ' - '.$afterName
                                    ?>
									<h6 class="acym__listing__title__primary acym_text_ellipsis"><?php echo $row->name.$afterName; ?></h6>
								</div>
							</div>
							<div class="cell large-3 hide-for-medium-only hide-for-small-only text-center">
								<div class="queue_lists">
                                    <?php
                                    $i = 0;
                                    $class = 'acym_subscription acymicon-circle';
                                    foreach ($row->lists as $oneList) {
                                        if ($i === 6) {
                                            echo acym_tooltip(
                                                '<i data-campaign="'.$row->id.'" class="acym_subscription acymicon-add"></i>',
                                                acym_translation('ACYM_SHOW_ALL_LISTS')
                                            );
                                            $class .= ' is-hidden';
                                        }
                                        echo acym_tooltip('<i class="'.$class.'" style="color:'.$oneList->color.'"></i>', $oneList->name);
                                        $i++;
                                    }
                                    ?>
								</div>
							</div>
							<div class="cell medium-4 small-9">
                                <?php echo acym_date($row->sending_date, acym_getDateTimeFormat()); ?>
							</div>
							<div class="cell medium-2 small-3">
								<div class="acym_vcenter">
                                    <?php
                                    // Now display the action buttons
                                    echo '<div class="acym_action_buttons">';
                                    echo acym_tooltip(
                                        '<i class="acymicon-times-circle acym__queue__cancel__button" mailid="'.$row->id.'"></i>',
                                        acym_translation('ACYM_CANCEL_SCHEDULING')
                                    );
                                    echo '</div>';
                                    ?>
								</div>
							</div>
						</div>
                    <?php } ?>
				</div>
                <?php echo $data['pagination']->display('squeue'); ?>
            <?php } ?>
        <?php } ?>
        <?php acym_formOptions(true, 'scheduled'); ?>
		<input type="hidden" name="acym__queue__cancel__mail_id">
	</div>
</form>
