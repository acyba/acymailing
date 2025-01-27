<?php
$isPerformance = !empty($data['isPerformance']) && $data['isPerformance'] === true;
$classType = $isPerformance ? 'acym__scenario__edit__right__panel__performances' : 'acym__scenario__edit__right__panel__design'; ?>
<div id="acym__scenario__edit__right__panel" class="<?php echo $classType; ?>" style="display: none">
	<div id="acym__scenario__edit__right__panel__header">
		<h1 id="acym__scenario__edit__right__panel__title">Test title</h1>
		<i id="acym__scenario__edit__right__panel__close" class="acymicon-close"></i>
	</div>
	<div class="acym__scenario__edit__right__panel__separator"></div>
	<h3 class="acym__scenario__edit__right__panel__subtitle" <?php echo $isPerformance ? '' : 'style="display: none;"'; ?>><?php echo acym_translation('ACYM_STATISTICS'); ?></h3>
	<div id="acym__scenario__edit__right__panel__content"></div>
	<div id="acym__scenario__edit__right__panel__actions">
		<button type="button" id="acym__scenario__edit__right__panel__delete" class="button acym__button__cancel" style="display: none"><?php echo acym_translation(
                'ACYM_DELETE'
            ); ?></button>
		<button type="button" id="acym__scenario__edit__right__panel__cancel" class="button button-secondary"><?php echo acym_translation('ACYM_CANCEL'); ?></button>
		<button type="button" id="acym__scenario__edit__right__panel__save__flow" class="button"><?php echo acym_translation('ACYM_SAVE'); ?></button>
		<button id="acym__scenario__edit__right__panel__save__scenario" class="button" type="button"><?php echo acym_translation(
                'ACYM_SAVE'
            ); ?></button>
	</div>
</div>
