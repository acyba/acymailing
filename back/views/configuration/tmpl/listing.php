<form id="acym_form" action="<?php echo acym_completeLink(acym_getVar('cmd', 'ctrl')); ?>" method="post" name="acyForm" novalidate data-abide-ignore>
    <?php $data['toolbar']->displayToolbar($data); ?>
	<div class="grid-x acym__content acym__content__tab">

        <?php
        $tabs = [
            'license' => 'ACYM_LICENSE',
            'mail' => 'ACYM_CONFIGURATION_MAIL',
            'queue' => 'ACYM_CONFIGURATION_QUEUE',
            'subscription' => 'ACYM_CONFIGURATION_SUBSCRIPTION',
            'bounce' => 'ACYM_BOUNCE_HANDLING',
            'data' => 'ACYM_CONFIGURATION_DATA_COLLECTION',
            'security' => 'ACYM_CONFIGURATION_SECURITY',
            'languages' => 'ACYM_CONFIGURATION_LANGUAGES',
        ];

        acym_trigger('onConfigurationAddTabs', [&$tabs]);

        foreach ($tabs as $oneTab => $title) {
            $data['tab']->startTab(acym_translation($title));
            echo '<div class="acym__configuration__content">';
            $filename = dirname(__FILE__).DS.$oneTab.'.php';
            if (file_exists($filename)) {
                include dirname(__FILE__).DS.$oneTab.'.php';
            } else {
                acym_trigger('onConfigurationTab_'.$oneTab, [$data]);
            }
            echo '</div>';
            $data['tab']->endTab();
        }

        $data['tab']->display('configuration');
        ?>
	</div>

    <?php acym_formOptions(); ?>
</form>
