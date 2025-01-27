<?php

use Joomla\CMS\Plugin\CMSPlugin;

defined('_JEXEC') or die('Restricted access');

class plgQuickiconAcymailing extends CMSPlugin
{
    public function onGetIcons($context)
    {
        if ($context != $this->params->get('context', 'mod_quickicon')) return [];

        $acymailingHelper = rtrim(
                JPATH_ADMINISTRATOR,
                DIRECTORY_SEPARATOR
            ).DIRECTORY_SEPARATOR.'components'.DIRECTORY_SEPARATOR.'com_acym'.DIRECTORY_SEPARATOR.'Core'.DIRECTORY_SEPARATOR.'init.php';
        if (!file_exists($acymailingHelper)) return [];

        return [
            [
                'link' => 'index.php?option=com_acym',
                'image' => 'mail',
                'text' => $this->params->get('displayedtext', 'AcyMailing'),
                'access' => ['core.manage', 'com_acym'],
                'id' => 'plg_quickicon_acymailing',
            ],
        ];
    }
}
