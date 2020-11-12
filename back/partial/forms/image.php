<?php if (!empty($form->image_options['url'])) { ?>
	<div class="acym__subscription__form__image">
		<img src="<?php echo $form->image_options['url']; ?>"
			 alt=""
			 width="<?php echo $form->image_options['size']['width']; ?>"
			 height="<?php echo $form->image_options['size']['height']; ?>">
	</div>
	<style>
		<?php echo '#acym_fulldiv_'.$form->form_tag_name.' '; ?>.acym__subscription__form__image{
			text-align: center;
		}

		<?php echo '#acym_fulldiv_'.$form->form_tag_name.' '; ?>.acym__subscription__form__image img{
			display: inline-block;
			width: <?php echo $form->image_options['size']['width']; ?>px;
			height: <?php echo $form->image_options['size']['height']; ?>px;
		}
	</style>
<?php } ?>
