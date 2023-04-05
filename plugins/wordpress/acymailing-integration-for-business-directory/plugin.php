<?php

use AcyMailing\Libraries\acymPlugin;

require_once __DIR__.DIRECTORY_SEPARATOR.'BusinessDirectoryInsertion.php';

class plgAcymBusinessdirectory extends acymPlugin
{
    use BusinessDirectoryInsertion;

    private $wpbdpFields;
    private $currentHeader = '';

    public function __construct()
    {
        parent::__construct();
        $this->cms = 'WordPress';
        $this->installed = acym_isExtensionActive('business-directory-plugin/business-directory-plugin.php');

        $this->rootCategoryId = 0;
        $this->wpPostType = 'wpbdp_listing';
        $this->wpCategoryType = 'wpbdp_category';
        $this->wpTagType = 'wpbdp_tag';

        $this->pluginDescription->name = 'Business Directory';
        $this->pluginDescription->icon = ACYM_PLUGINS_URL.'/'.basename(__DIR__).'/icon.png';
        $this->pluginDescription->category = 'Content management';
        $this->pluginDescription->features = '["content"]';
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
