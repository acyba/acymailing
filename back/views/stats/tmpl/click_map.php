<div id="acym__stats__click-map" class="acym__content">
	<input type="hidden" id="acym__stats_click__map__all-links__click" value="<?php echo empty($data['url_click']) ? '' : acym_escape($data['url_click']); ?>">
	<input type="hidden" class="acym__hidden__mail__content" value="<?php echo acym_escape(acym_absoluteURL($data['mailInformation']->body)); ?>">
	<div style="display: none" class="acym__hidden__mail__stylesheet"><?php echo $data['mailInformation']->stylesheet; ?></div>
	<div class="cell grid-x">
		<div class="cell grid-x align-right">
			<button type="button" class="cell shrink button primary  acym__stats__export__click-map__charts"><?php echo acym_translation('ACYM_EXPORT'); ?></button>
		</div>
		<div id="acym__stats__add_style_export__click-map">
			<style>
				<?php
				echo acym_fileGetContent($data['url_foundation_email']);
				echo acym_fileGetContent($data['url_click_map_email']);
				?>
			</style>
		</div>
		<div id="acym__wysid__email__preview" class="acym__email__preview grid-x cell margin-top-1"></div>
	</div>
</div>
