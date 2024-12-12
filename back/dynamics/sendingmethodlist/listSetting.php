<div class="cell grid-x acym__list__settings__sml acym__content margin-bottom-1" id="acym__list__settings__sml">
	<h5 class="cell acym__title acym__title__secondary"><?php echo acym_translation('ACYM_SENDING_METHOD'); ?></h5>
	<div class="cell medium-4 small-12">
        <?php echo acym_select($sendingMethodsFormatted, 'sml_sending_method', $sendingMethodSelected, ['class' => 'acym__select']); ?>
	</div>
</div>
