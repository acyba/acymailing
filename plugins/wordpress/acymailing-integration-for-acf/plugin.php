<?php

use AcyMailing\Core\AcymPlugin;

if (!defined('ABSPATH')) {
    exit;
}

require_once __DIR__.DIRECTORY_SEPARATOR.'AcfInsertion.php';

class plgAcymAcf extends AcymPlugin
{
    use AcymAcfInsertion;

    public function __construct()
    {
        parent::__construct();
        $this->cms = 'WordPress';
        $this->installed = acym_isExtensionActive('advanced-custom-fields/acf.php') || acym_isExtensionActive('advanced-custom-fields-pro/acf.php');
        $this->pluginDescription->name = 'ACF';
        $this->pluginDescription->icon = ACYM_PLUGINS_URL.'/'.basename(__DIR__).'/icon.svg';
        $this->pluginDescription->category = 'Content management';
        $this->pluginDescription->description = '- Insert custom post types in your emails<br />- Insert them by category';

        if ($this->installed) {
            $postType = acym_loadResult('SELECT ID FROM #__posts WHERE post_type = "acf-post-type" AND post_status = "publish"');
            $this->installed = !empty($postType);
        }

        if ($this->installed) {
            $this->displayOptions = [
                'title' => ['ACYM_TITLE', true],
                'image' => ['ACYM_FEATURED_IMAGE', true],
                'excerpt' => ['ACYM_EXCERPT', false],
                'intro' => ['ACYM_INTRO_ONLY', true],
                'content' => ['ACYM_FULL_TEXT', false],
                'author' => ['ACYM_AUTHOR', false],
                'readmore' => ['ACYM_READ_MORE', false],
            ];

            $this->initCustomView(true);

            $this->settings = [
                'custom_view' => [
                    'type' => 'custom_view',
                    'tags' => array_merge($this->displayOptions, $this->replaceOptions, $this->customOptions, $this->elementOptions),
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
        return $this->pluginDescription;
    }
}
