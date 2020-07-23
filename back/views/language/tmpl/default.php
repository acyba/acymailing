<form id="acym_form" action="<?php echo acym_completeLink(acym_getVar('cmd', 'ctrl')); ?>" method="post" name="acyForm">
	<div id="acym_content" class="acym__language__modal popup_size">
		<div class="acym__language__modal__header cell grid-x">
			<h1 class="shrink acym__language__modal__title acym__color__blue"><?php echo acym_translation('ACYM_FILE').' : '.$data['file']->name; ?></h1>
			<div class="auto cell"></div>
			<button data-task="share" class="acy_button_submit button margin-right-1 button-secondary shrink cell acym__language__modal__header__share"><?php echo acym_translation('ACYM_SHARE_TRANSLATION'); ?></button>
			<button data-task="saveLanguage" class="acy_button_submit button shrink cell acym__language__modal__header__save"><?php echo acym_translation('ACYM_SAVE'); ?></button>
		</div>
		<div class="cell grid-x acym__language__modal__existing acym__content">
			<div class="cell grid-x">
				<h6 class="cell shrink acym__language__modal__title acym__language__modal__existing__name-file"><?php echo acym_translation('ACYM_FILE').' : '.$data['file']->name; ?></h6>
				<div class="cell auto text-right">
                    <?php if (!empty($data['showLatest'])) { ?>
						<button data-task="latest" id="acym__button__load__latest__language" class="button small-shrink margin-left-1 acy_button_submit"> <?php echo acym_translation('ACYM_LOAD_LATEST_LANGUAGE'); ?> <i class="acymicon-file_download"></i></button>
                    <?php } ?>
					<a href="#customcontent" id="edit_translation" class="button margin-left-1"><?php echo acym_translation('ACYM_EDIT'); ?></a>
				</div>
			</div>
			<textarea readonly rows="18" name="content" id="translation" class="acym__language__modal__existing__translation acym__blue"><?php echo str_replace('&', '&amp;', $data['file']->content); ?></textarea>
		</div>
		<div class="acym__content acym__language__modal__custom margin-top-2" id="customcontent">
			<h6 class="acym__language__modal__title"><?php echo acym_translation('ACYM_CUSTOM_TRANS'); ?></h6>
            <?php echo acym_translation('ACYM_CUSTOM_TRANS_DESC'); ?>
			<textarea rows="10" name="customcontent" class="acym__language__modal__body acym__blue"><?php echo str_replace('&', '&amp;', $data['file']->customcontent); ?></textarea>

			<div class="cell grid-x align-right">
				<button id="copy_translations" class="button"><?php echo acym_translation('ACYM_COPY_DEFAULT_TRANSLATIONS'); ?></button>
			</div>
		</div>
		<div class="clr"></div>
		<input type="hidden" name="code" value="<?php echo acym_escape($data['file']->name); ?>" />
        <?php acym_formOptions(); ?>
	</div>
</form>
