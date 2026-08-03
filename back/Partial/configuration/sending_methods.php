<?php
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- View file, its variables are local to the include scope, not true globals.
// context verification
?>
<div class="cell grid-x acym__sending__methods__choose acym__selection">
    <?php
    $services = $data['sendingMethods'];
    uksort($services, function ($first, $next) {
        if ($first === 'phpmail' || $first === 'acymailer') return -1;
        if ($next === 'sendinblue') return -1;

        return $first !== 'smtp' || $next === 'phpmail' ? 1 : -1;
    });
    ?>
    <?php if (empty($data['isSml'])) { ?>
		<div class="acym__title acym__title__secondary margin-top-1 medium-12 <?php echo !empty($data['step']) && $data['step'] == 'phpmail' ? 'text-center'
            : ''; ?>"><?php echo acym_escapeHtml(acym_translation('ACYM_SENDING_MEHTODS')); ?></div>
    <?php } ?>
	<div class="cell grid-x grid-margin-x grid-margin-y acym__sending__methods__choose__selection text-center <?php echo !empty($data['step']) && $data['step'] == 'phpmail'
        ? 'align-center'
        : ''; ?>">
        <?php
        foreach ($services as $key => $sendingMethod) {
            if (!empty($data['isSml']) && $key === 'acymailer') {
                continue;
            }

            $selected = isset($sendingMethod['selected']) && $sendingMethod['selected'];
            $class = !empty($sendingMethod['recommended']) ? 'acym__sending__methods__one__premium' : '';
            $class .= empty($data['step']) ? ' acym__sending__methods__one__config' : '';
            $idCheckbox = 'acym__sending__methods-'.(empty($data['isSml']) ? 'default' : 'sml').'-'.$key;
            ?>
			<div class="cell large-3 medium-6 grid-x align-center acym_vcenter acym__sending__methods__one <?php echo acym_escape($class); ?>">
				<label for="<?php echo acym_escape($idCheckbox); ?>" data-acym-method="<?php echo acym_escape($key); ?>"
				       class="acym__selection__card cell acym_vcenter align-center <?php echo $selected ? 'acym__selection__card-selected' : ''; ?>">
                    <?php
                    if (!empty($sendingMethod['icon'])) { ?>
						<i class="cell <?php echo acym_escape($sendingMethod['icon']); ?> text-center"></i>
                    <?php } else { ?>
						<img src="<?php echo acym_escapeUrl($sendingMethod['image']); ?>"
						     alt=""
						     class="cell <?php echo !empty($sendingMethod['image_class']) ? acym_escape($sendingMethod['image_class']) : ''; ?>">
                    <?php } ?>
				</label>
				<span class="cell acym__sending__methods__name">
					<?php
                    echo acym_escapeHtml($sendingMethod['name']);
                    if (!empty($sendingMethod['recommended'])) {
                        echo ' <br>('.acym_escapeHtml(acym_translation('ACYM_RECOMMENDED')).')';
                    }
                    ?>
				</span>
				<input type="radio"
				       name="<?php echo !empty($data['isSml']) ? 'sml' : 'config'; ?>[mailer_method]"
                    <?php acym_checked($selected); ?>
					   id="<?php echo acym_escape($idCheckbox); ?>"
					   value="<?php echo acym_escape($key); ?>"
					   style="display: none">
			</div>
        <?php } ?>
	</div>
    <?php
    $class = '';
    if (!empty($data['step'])) {
        $class = 'medium-10';
        echo '<div class="cell medium-1 hide-for-small-only"></div>';
    }
    ?>
	<div class="cell <?php echo acym_escape($class); ?> grid-x">
        <?php
        foreach ($data['sendingMethodsHtmlSettings'] as $html) {
            echo $html;
        }
        ?>
	</div>
</div>
