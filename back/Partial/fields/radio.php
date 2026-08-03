<?php
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- View file, its variables are local to the include scope, not true globals.
// context verification
?>

<?php foreach ($data['values'] as $radioKey => $radioText) { ?>
	<label>
		<input
            <?php
            echo ' type="radio"';
            echo ' name="'.acym_escape($data['name']).'"';
            echo ' value="'.acym_escape($radioKey).'"';
            if (!empty($data['data-required'])) {
                echo ' data-required="'.acym_escape($data['data-required']).'"';
            }
            acym_checked($data['value'] == $radioKey);
            ?>
		> <?php echo acym_escapeHtml($radioText); ?>
	</label>
<?php } ?>
