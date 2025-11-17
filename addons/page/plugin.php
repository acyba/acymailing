<?php

use AcyMailing\Core\AcymPlugin;

class plgAcymPage extends AcymPlugin
{
    public function __construct()
    {
        parent::__construct();
        $this->cms = 'WordPress';

        $this->pluginDescription->name = acym_translation('ACYM_PAGE');
        $this->pluginDescription->icon = '<div class="wp-menu-image dashicons-before dashicons-admin-page"></div>';
        $this->pluginDescription->icontype = 'raw';

        if (ACYM_CMS === 'wordpress') {
            $this->displayOptions = [
                'title' => ['ACYM_TITLE', true],
                'image' => ['ACYM_FEATURED_IMAGE', true],
                'intro' => ['ACYM_INTRO_ONLY', true],
                'content' => ['ACYM_FULL_TEXT', false],
                'readmore' => ['ACYM_READ_MORE', false],
            ];

            $this->initCustomView();

            $this->settings = [
                'custom_view' => [
                    'type' => 'custom_view',
                    'tags' => array_merge($this->displayOptions, $this->replaceOptions, $this->elementOptions),
                ],
            ];
        }
    }

    public function getStandardStructure(string &$customView): void
    {
        $tag = new stdClass();
        $tag->id = 0;

        $format = new stdClass();
        $format->tag = $tag;
        $format->title = '{title}';
        $format->afterTitle = '';
        $format->afterArticle = '';
        $format->imagePath = '{image}';
        $format->description = '{intro}';
        $format->link = '{link}';
        $format->customFields = [];
        $customView = '<div class="acymailing_content">'.$this->pluginHelper->getStandardDisplay($format).'</div>';
    }

    public function initReplaceOptionsCustomView(): void
    {
        $this->replaceOptions = [
            'link' => ['ACYM_LINK'],
            'picthtml' => ['ACYM_IMAGE'],
            'readmore' => ['ACYM_READ_MORE'],
        ];
    }

    public function initElementOptionsCustomView(): void
    {
        $query = 'SELECT page.*
                    FROM #__posts AS page
                    WHERE page.post_type = "page" 
                        AND page.post_status = "publish"';
        $element = acym_loadObject($query);
        if (empty($element)) return;
        foreach ($element as $key => $value) {
            $this->elementOptions[$key] = [$key];
        }
    }

    public function getPossibleIntegrations(): ?object
    {
        return $this->pluginDescription;
    }

    public function insertionOptions(?object $defaultValues = null): void
    {
        $this->defaultValues = $defaultValues;

        $displayOptions = [
            [
                'title' => 'ACYM_DISPLAY',
                'type' => 'checkbox',
                'name' => 'display',
                'options' => $this->displayOptions,
            ],
            [
                'title' => 'ACYM_CLICKABLE_TITLE',
                'type' => 'boolean',
                'name' => 'clickable',
                'default' => true,
            ],
            [
                'title' => 'ACYM_CLICKABLE_IMAGE',
                'type' => 'boolean',
                'name' => 'clickableimg',
                'default' => false,
            ],
            [
                'title' => 'ACYM_REPLACE_SHORTCODES',
                'type' => 'boolean',
                'name' => 'replaceshortcode',
                'default' => false,
            ],
            [
                'title' => 'ACYM_TRUNCATE',
                'type' => 'intextfield',
                'isNumber' => 1,
                'name' => 'wrap',
                'text' => 'ACYM_TRUNCATE_AFTER',
                'default' => 0,
            ],
            [
                'title' => 'ACYM_DISPLAY_PICTURES',
                'type' => 'pictures',
                'name' => 'pictures',
            ],
        ];

        $zoneContent = $this->getFilteringZone(false).$this->prepareListing();
        $this->displaySelectionZone($zoneContent);
        $this->pluginHelper->displayOptions($displayOptions, $this->name, 'individual', $this->defaultValues);
    }

    public function prepareListing(): string
    {
        $this->querySelect = 'SELECT page.ID, page.post_title, page.post_date, page.post_content ';
        $this->query = 'FROM #__posts AS page ';
        $this->filters = [];
        $this->filters[] = 'page.post_type = "page"';
        $this->filters[] = 'page.post_status = "publish"';
        $this->searchFields = ['page.ID', 'page.post_title'];
        $this->pageInfo->order = 'page.ID';
        $this->elementIdTable = 'page';
        $this->elementIdColumn = 'ID';

        parent::prepareListing();

        $rows = $this->getElements();
        foreach ($rows as $i => $row) {
            if (str_replace(['wp:core-embed', 'wp:shortcode'], '', $row->post_content) !== $row->post_content) {
                $rows[$i]->post_title = acym_tooltip(
                        [
                            'hoveredText' => '<i class="acymicon-exclamation-triangle"></i>',
                            'textShownInTooltip' => acym_translation('ACYM_SPECIAL_CONTENT_WARNING'),
                        ]
                    ).$rows[$i]->post_title;
            }
        }

        $listingOptions = [
            'header' => [
                'post_title' => [
                    'label' => 'ACYM_TITLE',
                    'size' => '7',
                ],
                'post_date' => [
                    'label' => 'ACYM_DATE_CREATED',
                    'size' => '4',
                    'type' => 'date',
                ],
                'ID' => [
                    'label' => 'ACYM_ID',
                    'size' => '1',
                    'class' => 'text-center',
                ],
            ],
            'id' => 'ID',
            'rows' => $rows,
        ];

        return $this->getElementsListing($listingOptions);
    }

    public function replaceContent(object &$email): void
    {
        $this->replaceOne($email);
    }

    public function replaceIndividualContent(object $tag): string
    {
        $query = 'SELECT page.*
                    FROM #__posts AS page
                    WHERE page.post_type = "page" 
                        AND page.post_status = "publish"
                        AND page.ID = '.intval($tag->id);

        $element = $this->initIndividualContent($tag, $query);

        if (empty($element)) return '';

        $varFields = $this->getCustomLayoutVars($element);

        $link = get_permalink($element->ID);
        $link = $this->getLinkTranslated($link);
        $varFields['{link}'] = $link;

        $title = '';
        $varFields['{title}'] = $element->post_title;
        if (in_array('title', $tag->display)) $title = $varFields['{title}'];

        $afterTitle = '';
        $afterArticle = '';

        $imagePath = '';
        $imageId = get_post_thumbnail_id($tag->id);
        if (!empty($imageId)) {
            $imagePath = get_the_post_thumbnail_url($tag->id);
        }
        $varFields['{image}'] = $imagePath;
        $varFields['{picthtml}'] = '<img alt="" src="'.$imagePath.'">';
        if (!in_array('image', $tag->display)) $imagePath = '';

        $contentText = '';
        $varFields['{content}'] = $this->cleanExtensionContent($element->post_content);
        $varFields['{intro}'] = $this->cleanExtensionContent($this->getIntro($element->post_content));
        $varFields['{content}'] = !empty($tag->replaceshortcode) ? $this->replaceShortcode($varFields['{content}']) : $varFields['{content}'];
        $varFields['{intro}'] = !empty($tag->replaceshortcode) ? $this->replaceShortcode($varFields['{intro}']) : $varFields['{intro}'];
        if (in_array('content', $tag->display)) {
            $contentText .= $varFields['{content}'];
        } elseif (in_array('intro', $tag->display)) {
            $contentText .= $varFields['{intro}'];
        }

        $customFields = [];

        $varFields['{readmore}'] = '<a class="acymailing_readmore_link" style="text-decoration:none;" target="_blank" href="'.$link.'"><span class="acymailing_readmore">'.acym_escape(
                acym_translation('ACYM_READ_MORE')
            ).'</span></a>';
        if (in_array('readmore', $tag->display)) $afterArticle .= $varFields['{readmore}'];

        $format = new stdClass();
        $format->tag = $tag;
        $format->title = $title;
        $format->afterTitle = $afterTitle;
        $format->afterArticle = $afterArticle;
        $format->imagePath = $imagePath;
        $format->description = $contentText;
        $format->link = empty($tag->clickable) && empty($tag->clickableimg) ? '' : $link;
        $format->customFields = $customFields;
        $result = '<div class="acymailing_content">'.$this->pluginHelper->getStandardDisplay($format).'</div>';

        return $this->finalizeElementFormat($result, $tag, $varFields);
    }

    protected function getTranslationId(int $elementId, string $translationTool, bool $defaultLanguage = false): int
    {
        $languageCode = $this->emailLanguage;

        if ($defaultLanguage) {
            $languageCode = $this->config->get('multilingual_default', ACYM_DEFAULT_LANGUAGE);
        } else {
            $idDefaultLanguage = $this->getTranslationId($elementId, $translationTool, true);

            // We only translate inserted articles of the default language
            if ($idDefaultLanguage !== $elementId) {
                return $elementId;
            }
        }

        $languageCode = substr($languageCode, 0, 2);

        if ($translationTool === 'polylang') {
            if (acym_isExtensionActive('polylang/polylang.php') && function_exists('pll_get_post')) {
                $translationId = pll_get_post($elementId, $languageCode);
                if (!empty($translationId)) $elementId = $translationId;
            }
        } elseif ($translationTool === 'wpml') {
            if (acym_isExtensionActive('sitepress-multilingual-cms/sitepress.php')) {
                $elementId = apply_filters('wpml_object_id', $elementId, 'post', true, $languageCode);
            }
        }

        return intval($elementId);
    }
}
