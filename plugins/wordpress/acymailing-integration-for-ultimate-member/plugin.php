<?php

use AcyMailing\Libraries\acymPlugin;

class plgAcymUltimatemember extends acymPlugin
{
    public function __construct()
    {
        parent::__construct();
        $this->cms = 'WordPress';
        $this->installed = acym_isExtensionActive('ultimate-member/ultimate-member.php');

        $this->pluginDescription->name = 'Ultimate Member';
        $this->pluginDescription->category = 'Subscription system';
        $this->pluginDescription->features = '[]';
        $this->pluginDescription->description = '- Insert AcyMailing list on your Ultimate Member register form';
    }

    public function onRegacyUseExternalPlugins()
    {
        if (!is_plugin_active('ultimate-member/ultimate-member.php')) return;

        ?>
		<div class="cell grid-x grid-margin-x">
            <?php
            echo acym_switch(
                'config[regacy_use_ultimate_member]',
                $this->config->get('regacy_use_ultimate_member', 0),
                acym_translation('ACYM_DISPLAY_FORM_ON_ULTIMATE_MEMBER'),
                [],
                'xlarge-3 medium-5 small-9',
                'auto'
            );
            ?>
		</div>
        <?php
    }
}
