<form id="acym_form" action="<?php echo acym_completeLink(acym_getVar('cmd', 'ctrl')); ?>" method="post" name="acyForm">
	<div id="acym_content" class="acym__language__modal popup_size">
		<div class="cell grid-x grid-margin-x text-right margin-bottom-0 margin-y">
			<h1 class="cell medium-auto medium-text-left text-center hide-for-small-only hide-for-medium-only acym__title acym__title__secondary">
                <?php echo acym_translation('ACYM_FILE').' : '.$data['file']->name; ?>
			</h1>
            <?php
            //__START__joomla_
            if (ACYM_CMS === 'joomla') {
                ?>
				<button data-task="share" class="acy_button_submit button button-secondary medium-6 large-shrink cell">
                    <?php echo acym_translation('ACYM_SHARE_TRANSLATION'); ?>
				</button>
                <?php
            }

            if (ACYM_CMS === 'joomla') {
                ?>
				<button
						id="load_latest_translation"
						class="cell medium-6 large-shrink button button-secondary"
						data-acym-code="<?php echo acym_escape($data['file']->name); ?>">
                    <?php echo acym_translation('ACYM_LOAD_LATEST_LANGUAGE'); ?>
					<i class="acymicon-download"></i>
				</button>
                <?php
            }
            //__END__joomla_
            ?>
			<a href="#customcontent" id="edit_translation" class="cell medium-6 large-shrink button button-secondary"><?php echo acym_translation('ACYM_EDIT'); ?></a>
			<button data-task="saveLanguage" class="acy_button_submit button medium-6 large-shrink cell">
                <?php echo acym_translation('ACYM_SAVE'); ?>
			</button>
		</div>
		<div class="cell acym__language__modal__existing acym__content<?php if (ACYM_CMS === 'wordpress') echo ' is-hidden'; ?>">
			<i id="load_latest_spinner" class="acymicon-circle-o-notch acymicon-spin" style="display: none;"></i>
			<textarea readonly rows="18" name="content" id="translation" class="acym__language__modal__existing__translation"><?php echo str_replace(
                    '&',
                    '&amp;',
                    $data['file']->content
                ); ?></textarea>
		</div>
		<div class="grid-x acym__content acym__language__modal__custom margin-top-2" id="customcontent">
			<h6 class="cell large-7 acym__title acym__title__secondary margin-bottom-0"><?php echo acym_translation('ACYM_CUSTOM_TRANS'); ?></h6>
			<div class="cell large-5 grid-x align-right">
				<button id="copy_translations" class="button"><?php echo acym_translation('ACYM_COPY_DEFAULT_TRANSLATIONS'); ?></button>
			</div>
			<div class="cell margin-top-1">
                <?php echo acym_translation('ACYM_CUSTOM_TRANS_DESC'); ?>
				<textarea rows="10" name="customcontent" class="acym__language__modal__body"><?php echo str_replace('&', '&amp;', $data['file']->customcontent); ?></textarea>
			</div>
		</div>
		<div class="clr"></div>
		<input type="hidden" name="code" value="<?php echo acym_escape($data['file']->name); ?>" />
        <?php acym_formOptions(); ?>
	</div>
</form>
