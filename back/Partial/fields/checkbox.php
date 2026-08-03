<?php
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- View file, its variables are local to the include scope, not true globals.
// context verification
?>

<input type="hidden" name="<?php echo acym_escape($data['name']); ?>" value="">
<?php foreach ($data['values'] as $checkboxKey => $checkboxText) { ?>
    <?php if (!empty($data['displayFront'])) { ?>
		<label>
			<input
                <?php
                echo ' type="checkbox"';
                echo ' name="'.acym_escape($data['name'].'['.$checkboxKey.']').'"';
                echo ' value="'.acym_escape($checkboxKey).'"';
                if (!empty($data['data-required'])) {
                    echo ' data-required="'.acym_escape($data['data-required']).'"';
                }
                acym_checked(in_array($checkboxKey, $data['value']));
                ?>
			> <?php echo acym_escapeHtml($checkboxText); ?>
		</label>
    <?php } else { ?>
		<label<?php echo in_array($checkboxKey, $data['value']) ? '' : ' class="cell margin-top-1"'; ?>>
			<input
                <?php
                echo ' type="checkbox"';
                echo ' name="'.acym_escape($data['name'].'['.$checkboxKey.']').'"';
                echo ' class="acym__users__creation__fields__checkbox"';
                if (in_array($checkboxKey, $data['value'])) {
                    echo ' checked="checked"';
                    if (!empty($data['data-required'])) {
                        echo ' data-required="'.acym_escape($data['data-required']).'"';
                    }
                }
                ?>
			><?php echo acym_escapeHtml($checkboxText); ?>
		</label>
    <?php } ?>
<?php } ?>
