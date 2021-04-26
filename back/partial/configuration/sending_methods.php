<div class="cell grid-x acym__sending__methods__choose acym__selection">
	<div class="cell grid-x grid-margin-x grid-margin-y acym__sending__methods__choose__selection <?php echo !empty($data['step']) && $data['step'] == 'phpmail' ? 'align-center' : ''; ?>">
        <?php
        uksort(
            $data['sendingMethods'],
            function ($first, $next) {
                if ($first === 'sendinblue') return -1;

                return $first !== 'phpmail' || $next === 'sendinblue' ? 1 : -1;
            }
        );

        foreach ($data['sendingMethods'] as $key => $sendingMethod) {
            ?>
            <?php $selected = isset($sendingMethod['selected']) && $sendingMethod['selected'];
            $class = !empty($sendingMethod['premium']) ? 'acym__sending__methods__one__premium' : '';
            $class .= empty($data['step']) ? ' acym__sending__methods__one__config' : '';
            $name = !empty($sendingMethod['premium']) ? $sendingMethod['name'].' <br>('.acym_translation('ACYM_OUR_PARTNER').')' : $sendingMethod['name'];
            ?>
			<div class="cell large-3 medium-6 grid-x align-center acym_vcenter acym__sending__methods__one <?php echo $class; ?>">
				<label for="acym__sending__methods-<?php echo $key; ?>" id="<?php echo $key; ?>"
					   class="acym__selection__card cell acym_vcenter align-center <?php echo $selected ? 'acym__selection__card-selected' : ''; ?>">
                    <?php
                    if (!empty($sendingMethod['premium'])) {
                        echo '<span class="acym__selection__card__recommended">'.acym_translation('ACYM_RECOMMENDED').'</span>';
                    }
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
					   name="config[mailer_method]"
                    <?php echo $selected ? 'checked' : ''; ?>
					   id="acym__sending__methods-<?php echo $key; ?>"
					   value="<?php echo $key; ?>"
					   style="display: none">
			</div>
        <?php } ?>
	</div>
    <?php
    $class = '';
    if (!empty($data['step'])) {
        $class = 'medium-8';
        echo '<div class="cell medium-2 hide-for-small-only"></div>';
    } ?>
	<div class="cell <?php echo $class; ?> grid-x margin-top-2 text-left">
        <?php foreach ($data['sendingMethodsHtmlSettings'] as $html) {
            echo $html;
        } ?>
	</div>
</div>
