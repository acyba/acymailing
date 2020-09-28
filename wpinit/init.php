<?php

namespace AcyMailing\Init;

use AcyMailing\Helpers\RegacyHelper;

abstract class acyHook
{
    public function addRegistrationFields($externalPluginConfig = '')
    {
        $config = acym_config();

        $displayOnExternalPlugin = true;
        if (!empty($externalPluginConfig)) $displayOnExternalPlugin = $config->get($externalPluginConfig, 0) == 1;

        if (!$config->get('regacy', 0) || !$displayOnExternalPlugin) return;

        $regacyHelper = new RegacyHelper();
        if (!$regacyHelper->prepareLists(['formatted' => true])) return;

        ?>
		<div class="acym__regacy">
			<label class="acym__regacy__label"><?php echo $regacyHelper->label; ?></label>
			<div class="acym__regacy__values"><?php echo $regacyHelper->lists; ?></div>
		</div>
        <?php
    }
}
