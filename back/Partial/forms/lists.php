<?php
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- View file, its variables are local to the include scope, not true globals.
// context verification
?>
<div class="acym__subscription__form__lists">
    <?php
    foreach ($form->settings['lists']['displayed'] as $listId) {
        if (!empty($form->settings['lists']['automatic_subscribe']) && in_array($listId, $form->settings['lists']['automatic_subscribe'])) continue;
        if (empty($form->lists[$listId])) continue;
        $label = $form->lists[$listId];
        ?>
		<label>
			<input type="checkbox"
			       value="<?php echo acym_escape($listId); ?>"
			       name="subscription[]"
                <?php acym_checked(!empty($form->settings['lists']['checked']) && in_array($listId, $form->settings['lists']['checked'])); ?>>
            <?php echo acym_escapeHtml($label); ?>
		</label>
        <?php
    }

    $hiddenLists = empty($form->settings['lists']['automatic_subscribe']) ? '' : implode(',', $form->settings['lists']['automatic_subscribe']);
    echo '<input type="hidden" name="hiddenlists" value="'.acym_escape($hiddenLists).'">';
    ?>
	<style>
		<?php echo '#acym_fulldiv_'.acym_escapeHtml($form->form_tag_name).' '; ?>.acym__subscription__form__fields .acym__subscription__form__lists{
			display: inline-block;
			width: auto;
			margin: 0 20px;
			text-align: left;
		}

		<?php echo '#acym_fulldiv_'.acym_escapeHtml($form->form_tag_name).' '; ?>.acym__subscription__form__fields .acym__subscription__form__lists label{
			display: inline-block;
			margin-right: 10px;
			width: auto;
		}

		<?php echo '#acym_fulldiv_'.acym_escapeHtml($form->form_tag_name).' '; ?>.acym__subscription__form__fields .acym__subscription__form__lists input[type="checkbox"]{
			margin-top: 0 !important;
			margin-right: 5px;
		}
	</style>
</div>
