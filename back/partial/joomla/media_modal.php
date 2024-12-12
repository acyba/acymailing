<div id="acym__upload__modal__joomla-image">
	<div id="acym__upload__modal__joomla-image__bg" class="acym__upload__modal__joomla-image--close"></div>
	<div id="acym__upload__modal__joomla-image__ui" class="float-center cell">
        <?php
        $mediaUrl = 'index.php?option=com_media&asset=com_acym&author=acymailing&tmpl=component';
        $mediaUrlImage = $mediaUrl;
        if (!ACYM_J40) {
            $mediaUrlImage .= '&view=images';
        } elseif (!acym_isAdmin()) {
            $mediaUrlImage .= '&view=media';
        }
        ?>
		<iframe
				id="acym__upload__modal__joomla-image__ui__iframe"
				data-acym-src="<?php echo $mediaUrl; ?>"
				data-acym-src-image="<?php echo $mediaUrlImage; ?>"
				data-acym-is-j4="<?php echo ACYM_J40 ? '1' : '0'; ?>"
				src="<?php echo $mediaUrl; ?>"
				frameborder="0">
		</iframe>
		<div id="acym__upload__modal__joomla-image__ui__actions" class="cell grid-x grid-margin-x align-right" style="display: none">
			<button id="acym__upload__modal__joomla-image__ui__actions__cancel" type="button" class="button button-secondary cell shrink margin-bottom-0">
                <?php echo acym_translation('ACYM_CANCEL'); ?>
			</button>
			<button id="acym__upload__modal__joomla-image__ui__actions__select" type="button" class="button button-secondary cell shrink margin-bottom-0">
                <?php echo acym_translation('ACYM_SELECT'); ?>
			</button>
		</div>
	</div>
</div>
