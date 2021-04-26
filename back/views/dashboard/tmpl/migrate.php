<form id="acym_form" action="<?php echo acym_completeLink(acym_getVar('cmd', 'ctrl')); ?>" method="post" name="acyForm" class="acym__form__migrate">
	<div id="acym_migrate" class="cell grid-x">
		<div class="text-center cell acym__migrate__titles">
			<h1 class="acym__title text-center"><?php echo acym_translation('ACYM_THANKS_FOR_INSTALLING_ACYM'); ?></h1>
			<h2 class="acym__migrate__subtitle"><?php echo acym_translation('ACYM_DO_YOU_WANT_TO_MIGRATE'); ?></h2>
		</div>
		<div class="cell large-3"></div>

		<div class="acym__content acym__content__reduced cell large-6 grid-x">
			<div class="text-center cell acym__migrate__content__titles">
				<h1 class="acym__title acym__title__secondary"><?php echo acym_translation('ACYM_WHICH_DATA_TO_MIGRATE'); ?></h1>
				<p class="acym__color__red"><b><?php echo acym_translation('ACYM_MIGRATE_WARNING_DATA_OVERWRITE_MESSAGE'); ?></b></p>
			</div>
			<div class="cell acym__migrate__input__element">
				<input type="checkbox" id="acym__migrate__config" name="migrate[config]" class="acym__migrate__option" />
				<label for="acym__migrate__config"><?php echo acym_translation('ACYM_CONFIGURATION'); ?></label>
				<span id="acym__migrate__result__config__check"></span>
			</div>
            <?php if (acym_level(ACYM_ENTERPRISE)) { ?>
				<div class="cell acym__migrate__input__element">
					<input type="checkbox" id="acym__migrate__bounce" name="migrate[bounce]" class="acym__migrate__option" />
					<label for="acym__migrate__bounce"><?php echo acym_translation('ACYM_BOUNCE_HANDLING'); ?></label>
					<span id="acym__migrate__result__bounce__check"></span>
				</div>
            <?php } ?>
			<div class="cell acym__migrate__input__element">
				<input type="checkbox" id="acym__migrate__lists" name="migrate[lists]" class="acym__migrate__option" />
				<label for="acym__migrate__lists"><?php echo acym_translation('ACYM_LISTS'); ?></label>
				<span id="acym__migrate__result__lists__check"></span>
			</div>
			<div class="cell acym__migrate__input__element">
				<input type="checkbox" id="acym__migrate__mails" name="migrate[mails]" class="acym__migrate__option" />
				<label for="acym__migrate__mails"><?php echo acym_translation('ACYM_NEWSLETTERS'); ?></label>
				<span id="acym__migrate__result__mails__check"></span>
			</div>
			<div class="cell acym__migrate__input__element margin-left-2" id="acym__migrate__input__global_stats">
				<input type="checkbox" id="acym__migrate__mailstats" name="migrate[mailStats]" class="acym__migrate__option" />
				<label for="acym__migrate__mailstats"><?php echo acym_translation('ACYM_GLOBAL_STATS'); ?></label>
				<span id="acym__migrate__result__mailstats__check"></span>
			</div>
			<div class="cell acym__migrate__input__element">
				<input type="checkbox" id="acym__migrate__templates" name="migrate[templates]" class="acym__migrate__option" />
				<label for="acym__migrate__templates"><?php echo acym_translation('ACYM_TEMPLATES'); ?></label>
				<span id="acym__migrate__result__templates__check"></span>
			</div>
            <?php if (acym_level(ACYM_ENTERPRISE)) { ?>
				<div class="cell acym__migrate__input__element">
					<input type="checkbox" id="acym__migrate__fields" name="migrate[fields]" class="acym__migrate__option" />
					<label for="acym__migrate__fields"><?php echo acym_translation('ACYM_CUSTOM_FIELDS'); ?></label>
					<span id="acym__migrate__result__fields__check"></span>
				</div>
            <?php } ?>
			<div class="cell acym__migrate__input__element">
				<input type="checkbox" id="acym__migrate__users" name="migrate[users]" class="acym__migrate__option" />
				<label for="acym__migrate__users"><?php echo acym_translation('ACYM_SUBSCRIBERS'); ?></label>
				<span id="acym__migrate__result__users__check"></span>
			</div>
			<div class="cell grid-x text-center align-right grid-margin-x">
				<button type="button"
						data-task="migrationDone"
						class="button button-secondary acy_button_submit margin-right-1 cell medium-shrink small-12"
						id="acym__migrate__no__button"><?php echo acym_translation('ACYM_NO_DONT_WANT_TO_MIGRATE_MY_DATA'); ?></button>
				<button type="button" class="button primary cell medium-shrink small-12" id="acym__migrate__button"><?php echo acym_translation('ACYM_MIGRATE'); ?></button>
				<h1 class="acym__title acym__title__secondary cell acym__migration__need__display" id="acym__migration__percentage" style="display: none">0%</h1>
				<div class="success progress cell acym__migration__need__display" id="acym__migration__progress__bar" style="display: none">
					<div class="progress-meter" id="acym__migration__progress__bar__inner" style="width: 0"></div>
				</div>
				<div class="cell grid-x" style="display: none">
					<h1 class="acym__title acym__title__secondary cell"><?php echo acym_translation('ACYM_DISPLAY_GIF'); ?></h1>
					<img src="" alt="gif to wait" id="acym__migration__gif" class="cell">
				</div>
			</div>
			<div class="cell grid-x text-center" id="acym__migrate__result__ok">
				<h2 class="cell acym__listing__empty__subtitle"><?php echo acym_translation('ACYM_MIGRATION_DONE'); ?></h2>
				<button type="submit" data-task="migrationDone" class="button acy_button_submit cell medium-shrink"><?php echo acym_translation('ACYM_CONTINUE'); ?></button>
			</div>
			<div class="cell acym__color__red grid-x" id="acym__migrate__result__error">
				<p class="acym__color__red margin-bottom-1" id="acym__migrate__result__error__message"></p>
				<button type="button" id="acym__migrate__restart_from_error__button" class="button cell margin-right-1 medium-shrink"><?php echo acym_translation(
                        'ACYM_RESTART_FROM_ERROR'
                    ); ?></button>
				<button type="button" data-task="migrationDone" class="button button-secondary cell acy_button_submit medium-shrink"><?php echo acym_translation(
                        'ACYM_IGNORE_ERRORS_AND_CONTINUE'
                    ); ?></button>
			</div>
		</div>
		<div class="cell large-3"></div>
	</div>
    <?php acym_formOptions(true, '', null, 'dashboard'); ?>
</form>

