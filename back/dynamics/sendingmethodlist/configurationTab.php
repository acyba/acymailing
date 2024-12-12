<input type="hidden" id="acym__configuration__sml__methods" value="<?php echo acym_escape(json_encode($sendingMethods)); ?>">
<input type="hidden" id="acym__configuration__sml__method__id" value="" name="sml[id]">
<?php if (!empty($sendingMethods)) { ?>
	<div class="acym__listing grid-x margin-top-2">
		<div class="cell grid-x acym__listing__header">
			<div class="grid-x medium-auto cell">
				<div class="cell medium-4 acym__listing__header__title">
                    <?php echo acym_translation('ACYM_NAME'); ?>
				</div>
				<div class="cell medium-3 acym__listing__header__title">
                    <?php echo acym_translation('ACYM_TYPE'); ?>
				</div>
				<div class="cell medium-3 acym__listing__header__title">
                    <?php echo acym_translation('ACYM_ACTION'); ?>
				</div>
			</div>
		</div>
        <?php foreach ($sendingMethods as $id => $sendingMethod) { ?>
			<div class="grid-x cell acym__listing__row">
				<div class="cell medium-4">
                    <?php echo acym_escape($sendingMethod['name']); ?>
				</div>
				<div class="cell medium-3">
                    <?php echo acym_escape($sendingMethod['mailer_method']); ?>
				</div>
				<div class="cell medium-3 acym__configuration__sml__actions" data-acym-method-id="<?php echo acym_escape($id); ?>">
					<i class="acymicon-pencil cursor-pointer acym__configuration__sml__edit"></i>
					<i class="acymicon-trash-o cursor-pointer margin-left-1 acym__configuration__sml__delete"></i>
				</div>
			</div>
        <?php } ?>
	</div>
<?php } ?>
<button type="button" id="acym__configuration__sml__toggle" class="button button-secondary margin-top-2">
    <?php echo acym_translation('ACYM_ADD_SENDING_METHOD'); ?>
</button>
<div id="acym__configuration__sml__form" class="margin-top-2" style="display: none;">
	<h5 class="acym__title acym__title__secondary cell">
        <?php echo acym_translation('ACYM_SENDING_METHOD'); ?>
	</h5>
	<div class="cell grid-x">
		<label for="acym__configuration__sml__name"><?php echo acym_translation('ACYM_SENDING_METHOD_NAME'); ?></label>
		<input type="text" id="acym__configuration__sml__name" name="sml[name]">
	</div>
    <?php include acym_getPartial('configuration', 'sending_methods'); ?>
	<div class="cell grid-x">
		<button
				type="button"
				class="button button-secondary margin-right-2 acym__button__cancel"
				id="acym__configuration__sml__cancel-edit"
		>
            <?php echo acym_translation('ACYM_CANCEL'); ?>
		</button>
		<button class="button acy_button_submit" data-task="addNewSml"><?php echo acym_translation('ACYM_SAVE_METHOD'); ?></button>
	</div>
</div>
