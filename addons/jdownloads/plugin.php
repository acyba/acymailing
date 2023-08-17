<?php

use AcyMailing\Libraries\acymPlugin;

require_once __DIR__.DIRECTORY_SEPARATOR.'JdownloadsInsertion.php';

class plgAcymJdownloads extends acymPlugin
{
    use JdownloadsInsertion;

    public function __construct()
    {
        parent::__construct();
        $this->cms = 'Joomla';
        $this->addonDefinition = [
            'name' => 'jDownloads',
            'description' => '- Insert file descriptions in your emails',
            'documentation' => 'https://docs.acymailing.com/addons/joomla-add-ons/jdownloads',
            'category' => 'Files management',
            'level' => 'essential',
        ];
        if (!defined('JPATH_ADMINISTRATOR') || !file_exists(rtrim(JPATH_ADMINISTRATOR, DS).DS.'components'.DS.'com_jdownloads'.DS)) {
            $this->installed = false;
        } else {
            $manifest = acym_loadResult('SELECT `manifest_cache` FROM #__extensions WHERE `element` = "com_jdownloads" AND `type` = "component"');

            if (!empty($manifest)) {
                try {
                    $decoded = json_decode($manifest);
                    if (!empty($decoded->version) && version_compare($decoded->version, '3.9.0', '<')) {
                        $this->installed = false;
                    }
                } catch (Exception $exception) {

                }
            }
        }

        $this->pluginDescription->name = 'jDownloads';
        $this->pluginDescription->icon = ACYM_DYNAMICS_URL.basename(__DIR__).'/icon.png';

        if ($this->installed) {
            $this->displayOptions = [
                'title' => ['ACYM_TITLE', true],
                'symbol' => ['COM_JDOWNLOADS_CATSLIST_PIC', true],
                'image' => ['ACYM_IMAGE', true],
                'shortdesc' => ['COM_JDOWNLOADS_BACKEND_FILESEDIT_DESCRIPTION_SHORT', true],
                'longdesc' => ['COM_JDOWNLOADS_BACKEND_FILESEDIT_DESCRIPTION_LONG', false],
                'tags' => ['COM_JDOWNLOADS_TAGS_LABEL', false],
                'license' => ['COM_JDOWNLOADS_ACTIONLOG_TYPE_LICENSE', false],
                'author' => ['COM_JDOWNLOADS_FE_SORT_ORDER_AUTHOR', false],
                'creation' => ['COM_JDOWNLOADS_CREATED_DATE', false],
                'cat' => ['ACYM_CATEGORY', false],
                'price' => ['COM_JDOWNLOADS_BACKEND_FILESEDIT_PRICE', true],
                'readmore' => ['ACYM_READ_MORE', true],
            ];

            $this->initCustomView();

            $this->settings = [
                'custom_view' => [
                    'type' => 'custom_view',
                    'tags' => array_merge($this->displayOptions, $this->replaceOptions, $this->elementOptions),
                ],
                'front' => [
                    'type' => 'select',
                    'label' => 'ACYM_FRONT_ACCESS',
                    'value' => 'all',
                    'data' => [
                        'all' => 'ACYM_ALL_ELEMENTS',
                        'author' => 'ACYM_ONLY_AUTHORS_ELEMENTS',
                        'hide' => 'ACYM_DONT_SHOW',
                    ],
                ],
            ];
        } else {
            $this->settings = [
                'not_installed' => '1',
            ];
        }
    }

    public function getPossibleIntegrations()
    {
        if (!acym_isAdmin() && $this->getParam('front', 'all') === 'hide') return null;

        return $this->pluginDescription;
    }
}
