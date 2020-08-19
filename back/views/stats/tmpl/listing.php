<form id="acym_form" action="<?php echo acym_completeLink(acym_getVar('cmd', 'ctrl')); ?>" method="post" name="acyForm">
	<div class="acym__content acym__content__tab" id="acym_stats">
		<div class="cell grid-x acym_vcenter" id="acym_stats__select">
			<div class="cell grid-x acym_vcenter">
				<h2 class="cell medium-6 text-right acym_stats__title__choose"><?php echo acym_translation('ACYM_SELECT_AN_EMAIL'); ?></h2>
				<div class="cell large-2 medium-4 margin-left-1"><?php echo $data['mail_filter']; ?></div>
			</div>
            <?php if (!empty($data['emailTranslationsFilters']) && !empty($data['emailTranslations'])) { ?>
				<div class="cell grid-x margin-top-1 acym_vcenter">
					<h2 class="cell medium-6 text-right acym_stats__title__choose-smaller"><?php echo acym_translation('ACYM_SPECIFY_LANGUAGE'); ?></h2>
					<div class="cell large-2 medium-4 margin-left-1"><?php echo $data['emailTranslationsFilters']; ?></div>
				</div>
            <?php } ?>
		</div>
        <?php

        $textFirstTab = acym_translation(empty($data['selectedMailid']) ? 'ACYM_GLOBAL_STATISTICS' : 'ACYM_OVERVIEW');

        $data['tab']->startTab($textFirstTab);
        include dirname(__FILE__).DS.'global_stats.php';
        $data['tab']->endTab();

        $data['tab']->startTab(acym_translation('ACYM_DETAILED_STATS'));

        if (!acym_level(1)) {
            $data['version'] = 'essential';
            include ACYM_VIEW.'dashboard'.DS.'tmpl'.DS.'upgrade.php';
        }
        $data['tab']->endTab();

        if (!empty($data['selectedMailid'])) {
            $data['tab']->startTab(acym_translation('ACYM_CLICK_MAP'));

            if (!acym_level(1)) {
                $data['version'] = 'essential';
                include ACYM_VIEW.'dashboard'.DS.'tmpl'.DS.'upgrade.php';
            }
            $data['tab']->endTab();
        }

        $data['tab']->display('stats');
        ?>
	</div>
    <?php acym_formOptions(); ?>
</form>
