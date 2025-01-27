<div id="acym__scenario__performance__step">
    <?php include acym_getPartial('scenarios', 'performances_step_header'); ?>
	<div class="acym__scenario__edit__right__panel__separator"></div>
	<div id="acym__scenario__performance__listing">
        <?php if ($data['isEmpty']) {
            echo '<p>'.acym_translation('ACYM_NO_USER_TRIGGERED_SCENARIO').'</p>';
        } else { ?>
			<input
					type="text"
					name="search"
					placeholder="<?php echo acym_escape(acym_translation('ACYM_SEARCH')); ?>"
					id="acym__scenario__performance__step__search"
					value="<?php echo acym_escape($data['search']); ?>"
			>
			<div class="grid-x acym__listing acym__listing__no-checkbox">
				<div class="grid-x cell acym__listing__header">
					<div class="grid-x medium-auto small-11 cell acym__listing__header__title__container">
						<div class="cell medium-4 acym__listing__header__title">
                            <?php echo acym_translation('ACYM_EMAIL'); ?>
						</div>
						<div class="cell medium-4 acym__listing__header__title text-center">
                            <?php echo acym_translation('ACYM_START_DATE'); ?>
						</div>
						<div class="cell medium-4 acym__listing__header__title text-center">
                            <?php echo acym_translation('ACYM_END_DATE'); ?>
						</div>
					</div>
				</div>
                <?php
                if (!empty($data['usersProcess'])) {
                    foreach ($data['usersProcess'] as $userProcess) {
                        ?>
						<div class="grid-x cell align-middle acym__listing__row">
							<div class="grid-x medium-auto small-11 cell acym__listing__title__container">
								<div class="grid-x cell medium-4 acym__listing__title cursor-pointer acym__scenario__performance__trigger__user"
									 data-acym-user-id="<?php echo $userProcess->user_id; ?>"
									 data-acym-process-id="<?php echo $userProcess->id; ?>">
                                    <?php echo $userProcess->email; ?>
								</div>
								<div class="grid-x cell medium-4 acym__listing__title align-center">
                                    <?php echo acym_date($userProcess->start_at, 'Y-m-d H:i:s'); ?>
								</div>
								<div class="grid-x cell medium-4 acym__listing__title align-center">
                                    <?php echo empty($userProcess->end_at) ? '-' : acym_date($userProcess->end_at, 'Y-m-d H:i:s'); ?>
								</div>
							</div>
						</div>
                        <?php
                    }
                }
                ?>
			</div>
        <?php }
        echo $data['pagination']->display('scenario_performance_trigger_ajax', '', true);
        ?>
	</div>
</div>
