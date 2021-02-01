<form id="acym_form" action="<?php echo acym_completeLink(acym_getVar('cmd', 'ctrl')); ?>" method="post" name="acyForm">
    <?php $data['toolbar']->displayToolbar($data); ?>
	<div id="acym__fields" class="acym__content">
		<div class="cell acym__listing__actions margin-bottom-1 grid-x">
            <?php
            $actions = [
                'delete' => acym_translation('ACYM_DELETE'),
                'setActive' => acym_translation('ACYM_ENABLE'),
                'setInactive' => acym_translation('ACYM_DISABLE'),
            ];
            echo acym_listingActions($actions, acym_translation('ACYM_DELETE_CUSTOM_FIELDS_TYPE_DATE'));
            ?>
		</div>

		<div class="grid-x acym__listing">
			<div class="grid-x cell acym__listing__header">
				<div class="medium-shrink small-1 cell">
					<input id="checkbox_all" type="checkbox" name="checkbox_all">
				</div>
				<div class="medium-1 small-1 cell acym__listing__header__title text-center">

				</div>
				<div class="grid-x medium-auto small-8 cell">
					<div class="medium-4 small-7 cell acym__listing__header__title">
                        <?php echo acym_translation('ACYM_NAME'); ?>
					</div>
					<div class="medium-auto hide-for-small-only cell acym__listing__header__title ">
                        <?php echo acym_translation('ACYM_FIELD_TYPE'); ?>
					</div>
					<div class="medium-1 small-3 small-text-right text-center cell acym__listing__header__title">
                        <?php echo acym_translation('ACYM_REQUIRED'); ?>
					</div>
					<div class="medium-1 small-3 hide-for-small-only small-text-right text-center cell acym__listing__header__title">
                        <?php echo acym_translation('ACYM_ACTIVE'); ?>
					</div>
					<div class="medium-1 small-2 text-center cell acym__listing__header__title">
                        <?php echo acym_translationSprintf('ACYM_ID'); ?>
					</div>
				</div>
			</div>
			<div class="acym__sortable__listing cell grid-x" data-sort-ctrl="fields">
                <?php
                foreach ($data['allFields'] as $field) {
                    $generalClass = in_array($field->id, [1, 2, $data['languageFieldId']]) ? ' acym_opacity-5 ' : ' acym_toggleable cursor-pointer ';
                    ?>
					<div class="grid-x cell acym__listing__row" data-id-element="<?php echo acym_escape($field->id); ?>">
						<div class="medium-shrink small-1 cell">
							<input id="checkbox_<?php echo acym_escape($field->id); ?>" type="checkbox" name="elements_checked[]" value="<?php echo acym_escape($field->id); ?>">
						</div>
						<div class="medium-1 small-1 cell text-center">
							<div class="grabbable acym__sortable__listing__handle grid-x">
								<i class="acymicon-ellipsis-h cell acym__color__dark-gray"></i>
								<i class="acymicon-ellipsis-h cell acym__color__dark-gray"></i>
							</div>
						</div>
						<div class="grid-x medium-auto small-8 cell acym__field__listing">
							<div class="medium-4 small-7 cell acym__listing__title grid-x">
								<a href="<?php echo acym_completeLink('fields&task=edit&id='.$field->id); ?>" class="cell auto">
									<h6><?php echo acym_escape(acym_translation($field->name)); ?></h6>
								</a>
							</div>
							<div class="medium-auto hide-for-small-only cell acym__listing__title">
								<h6><?php echo acym_translation('ACYM_'.strtoupper(acym_escape($field->type))); ?></h6>
							</div>
							<div class="acym__listing__controls acym__field__controls medium-1 small-3 text-center cell">
                                <?php
                                $class = $field->required == 1 ? 'acymicon-check-circle acym__color__green" data-acy-newvalue="0' : 'acymicon-times-circle acym__color__red" data-acy-newvalue="1';
                                echo '<i data-acy-table="field" data-acy-field="required" data-acy-elementid="'.acym_escape(
                                        $field->id
                                    ).'" class="'.(in_array($field->id, [2, $data['languageFieldId']]) ? ' acym_opacity-5 ' : ' acym_toggleable cursor-pointer ').$class.'"></i>';
                                ?>
							</div>
							<div class="acym__listing__controls hide-for-small-only acym__field__controls medium-1 small-1 text-center cell">
                                <?php
                                $class = $field->active == 1 ? 'acymicon-check-circle acym__color__green" data-acy-newvalue="0' : 'acymicon-times-circle acym__color__red" data-acy-newvalue="1';
                                echo '<i data-acy-table="field" data-acy-field="active" data-acy-elementid="'.acym_escape($field->id).'" class="'.$generalClass.$class.'"></i>';
                                ?>
							</div>
							<h6 class="text-center medium-1 small-2 acym__listing__text"><?php echo acym_escape($field->id); ?></h6>
						</div>
					</div>
                <?php } ?>
			</div>
		</div>
	</div>
    <?php acym_formOptions(); ?>
</form>
