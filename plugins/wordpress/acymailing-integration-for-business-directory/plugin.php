<?php

use AcyMailing\Core\AcymPlugin;

if (!defined('ABSPATH')) {
    exit;
}

require_once __DIR__.DIRECTORY_SEPARATOR.'BusinessDirectoryInsertion.php';

class plgAcymBusinessdirectory extends AcymPlugin
{
    use BusinessDirectoryInsertion;

    private $wpbdpFields;
    private $currentHeader = '';

    // The content is stored in the WordPress post table
    private string $wpPostType = 'wpbdp_listing';
    private string $wpCategoryType = 'wpbdp_category';
    private string $wpTagType = 'wpbdp_tag';

    public function __construct()
    {
        parent::__construct();
        $this->cms = 'WordPress';
        $this->installed = acym_isExtensionActive('business-directory-plugin/business-directory-plugin.php');

        $this->rootCategoryId = 0;

        $this->pluginDescription->name = 'Business Directory';
        $this->pluginDescription->icon = ACYM_PLUGINS_URL.'/'.basename(__DIR__).'/icon.png';
        $this->pluginDescription->category = 'Content management';
        $this->pluginDescription->description = '- Insert individual listings in your emails<br />- Insert listings by category';

        if ($this->installed) {
            $this->displayOptions = [
                'title' => ['ACYM_TITLE', true],
                'image' => ['ACYM_FEATURED_IMAGE', true],
                'short_desc' => ['ACYM_SHORT_DESCRIPTION', false],
                'desc' => ['ACYM_DESCRIPTION', true],
                'cats' => ['ACYM_CATEGORIES', false],
                'tags' => ['ACYM_TAGS', false],
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
