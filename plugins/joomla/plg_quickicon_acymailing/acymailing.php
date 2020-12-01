<?php
/**
 * @package          AcyMailing for Joomla
 * @author           acyba.com
 * @copyright    (C) 2009-2020 ACYBA SAS - All rights reserved.
 * @license          GNU/GPLv3 https://www.gnu.org/licenses/gpl-3.0.html
 */

defined('_JEXEC') or die('Restricted access');

class plgQuickiconAcymailing extends JPlugin
{
    public function onGetIcons($context)
    {
        if ($context != $this->params->get('context', 'mod_quickicon')) return [];

        $acymailingHelper = rtrim(
                JPATH_ADMINISTRATOR,
                DIRECTORY_SEPARATOR
            ).DIRECTORY_SEPARATOR.'components'.DIRECTORY_SEPARATOR.'com_acym'.DIRECTORY_SEPARATOR.'helpers'.DIRECTORY_SEPARATOR.'helper.php';
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
