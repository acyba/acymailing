<?php
// context verification
if (!empty($form->settings['image']['url'])) { ?>
	<div class="acym__subscription__form__image">
		<img src="<?php echo acym_escapeUrl($form->settings['image']['url']); ?>"
		     alt=""
		     width="<?php echo acym_escape($form->settings['image']['size']['width']); ?>"
		     height="<?php echo acym_escape($form->settings['image']['size']['height']); ?>">
	</div>
	<style>
		<?php echo '#acym_fulldiv_'.acym_escapeHtml($form->form_tag_name).' '; ?>.acym__subscription__form__image{
			text-align: center;
		}

		<?php echo '#acym_fulldiv_'.acym_escapeHtml($form->form_tag_name).' '; ?>.acym__subscription__form__image img{
			display: inline-block;
			width: <?php echo acym_escapeHtml($form->settings['image']['size']['width']); ?>px;
			height: <?php echo acym_escapeHtml($form->settings['image']['size']['height']); ?>px;
			margin: 0 1rem;
		}
	</style>
<?php } ?>
