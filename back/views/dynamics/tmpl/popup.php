<div id="acym_content" class="popup_size">
	<input type="hidden" name="mail_id" value="<?php echo $data['mail_id']; ?>">
	<div id="acym__dynamics__popup">
		<div class="acym__dynamics__popup__menu grid-x">
			<div id="acym__dynamics__popup__menu__insert__tag" class="cell grid-x">
				<div class="medium-auto hide-for-small-only"></div>
				<input title="<?php echo acym_translation('ACYM_DYNAMIC_TEXT'); ?>"
					   type="text"
					   class="cell medium-5 small-12 margin-right-1"
					   id="dtextcode"
					   name="dtextcode"
					   value=""
					   onclick="this.select();">
				<div class="medium-2 small-12">
					<button class="button expanded " id="insertButton"><?php echo acym_translation('ACYM_INSERT'); ?></button>
				</div>
				<div class="medium-auto hide-for-small-only"></div>
			</div>
			<div class="cell grid-x acym__content acym__content__tab">
                <?php
                foreach ($data['plugins'] as $id => $onePlugin) {
                    if (empty($onePlugin)) {
                        continue;
                    }
                    if ($data['automation']) $onePlugin->plugin = $onePlugin->plugin.'&automation=true';
                    $data['tab']->startTab($onePlugin->name, false, 'data-dynamics="'.$onePlugin->plugin.'"');
                    $data['tab']->endTab();
                }
                $data['tab']->display('popup__dynamics');
                ?>
			</div>
		</div>
	</div>
</div>
