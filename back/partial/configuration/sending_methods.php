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
            : ''; ?>"><?php echo acym_translation('ACYM_SENDING_MEHTODS'); ?></div>
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
            $name = !empty($sendingMethod['recommended']) ? $sendingMethod['name'].' <br>('.acym_translation('ACYM_RECOMMENDED').')' : $sendingMethod['name'];
            $idCheckbox = 'acym__sending__methods-'.(empty($data['isSml']) ? 'default' : 'sml').'-'.$key;
            ?>
			<div class="cell large-3 medium-6 grid-x align-center acym_vcenter acym__sending__methods__one <?php echo $class; ?>">
				<label for="<?php echo $idCheckbox ?>" data-acym-method="<?php echo $key; ?>"
					   class="acym__selection__card cell acym_vcenter align-center <?php echo $selected ? 'acym__selection__card-selected' : ''; ?>">
                    <?php
                    if (!empty($sendingMethod['icon'])) { ?>
						<i class="cell <?php echo $sendingMethod['icon']; ?> text-center"></i>
                    <?php } else { ?>
						<img src="<?php echo $sendingMethod['image']; ?>"
							 alt=""
							 class="cell <?php echo !empty($sendingMethod['image_class']) ? $sendingMethod['image_class'] : '' ?>">
                    <?php } ?>
				</label>
				<span class="cell acym__sending__methods__name"><?php echo $name; ?></span>
				<input type="radio"
					   name="<?php echo !empty($data['isSml']) ? 'sml' : 'config' ?>[mailer_method]"
                    <?php echo $selected ? 'checked' : ''; ?>
					   id="<?php echo $idCheckbox; ?>"
					   value="<?php echo $key; ?>"
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
	<div class="cell <?php echo $class; ?> grid-x text-left">
        <?php
        foreach ($data['sendingMethodsHtmlSettings'] as $html) {
            echo $html;
        }
        ?>
	</div>
</div>
