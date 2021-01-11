<form id="acym_form" action="<?php echo acym_completeLink(acym_getVar('cmd', 'ctrl')); ?>" method="post" name="acyForm" novalidate data-abide>
    <?php $data['toolbar']->displayToolbar($data); ?>
	<div class="grid-x acym__content acym__content__tab">

        <?php
        $tabs = [
            'mail' => 'ACYM_CONFIGURATION_MAIL',
            'queue' => 'ACYM_CONFIGURATION_QUEUE',
            'subscription' => 'ACYM_CONFIGURATION_SUBSCRIPTION',
            'license' => 'ACYM_LICENSE',
            'interfaces' => 'ACYM_CONFIGURATION_INTERFACE',
            'bounce' => 'ACYM_BOUNCE_HANDLING',
            'data' => 'ACYM_CONFIGURATION_DATA_COLLECTION',
            'security' => 'ACYM_CONFIGURATION_SECURITY',
            'languages' => 'ACYM_CONFIGURATION_LANGUAGES',
        ];

        foreach ($tabs as $oneTab => $title) {
            $data['tab']->startTab(acym_translation($title));
            echo '<div class="acym__configuration__content">';
            include dirname(__FILE__).DS.$oneTab.'.php';
            echo '</div>';
            $data['tab']->endTab();
        }

        $data['tab']->display('configuration');
        ?>
	</div>

    <?php acym_formOptions(); ?>
</form>
