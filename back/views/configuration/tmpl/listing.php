<form id="acym_form" action="<?php echo acym_completeLink(acym_getVar('cmd', 'ctrl')); ?>" method="post" name="acyForm" novalidate data-abide>
	<div class="grid-x acym__content acym__content__tab">

        <?php
        $data['tab']->content[] = '
        <div class="cell grid-x align-right">
            <button acym-data-before="jQuery.acymConfigSave();" type="submit" data-task="test" class="cell medium-shrink button margin-1 acy_button_submit button-secondary">
                '.acym_translation('ACYM_SEND_TEST').'
            </button>
            <button acym-data-before="jQuery.acymConfigSave();" type="submit" data-task="save" class="cell medium-shrink button margin-1 acy_button_submit">
                '.acym_translation('ACYM_SAVE').'
            </button>
        </div>';

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

		<div class="cell grid-x align-right">
			<button type="submit" data-task="save" class="cell margin-1 shrink button acy_button_submit">
                <?php echo acym_translation('ACYM_SAVE'); ?>
			</button>
		</div>
	</div>

    <?php acym_formOptions(); ?>
</form>
