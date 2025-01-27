<div class="grid-x cell">
	<div id="acym__scenario__template__overlay">
		<h2><?php echo acym_translation('ACYM_COMING_SOON'); ?></h2>
	</div>
	<h2 class="cell"><?php echo acym_translation('ACYM_CHOOSE_TEMPLATE'); ?></h2>
	<div class="grid-x cell grid-margin-x grid-margin-y margin-top-1">
        <?php foreach ($data['defaultTemplates'] as $defaultTemplate) { ?>
			<div class="grid-x cell medium-4 acym__scenario__template__card cursor-pointer">
				<img src="<?php echo ACYM_IMAGES.'scenario/'.$defaultTemplate['image']; ?>" alt="" class="cell">
				<div class="cell grid-x margin-top-2">
					<h3 class="shrink acym__scenario__template__card__title"><?php echo $defaultTemplate['name']; ?></h3>
					<div class="cell auto"></div>
					<i class="acymicon-chevron-right"></i>
				</div>
			</div>
        <?php } ?>
	</div>
</div>
