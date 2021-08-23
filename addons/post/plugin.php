<?php

use AcyMailing\Libraries\acymPlugin;
use AcyMailing\Helpers\TabHelper;

class plgAcymPost extends acymPlugin
{
    private $groupedByCategory = false;
    private $currentCategory = null;

    public function __construct()
    {
        parent::__construct();
        $this->cms = 'WordPress';
        $this->rootCategoryId = 0;

        $this->pluginDescription->name = acym_translation('ACYM_ARTICLE');
        $this->pluginDescription->icon = '<i class="cell acymicon-wordpress"></i>';
        $this->pluginDescription->icontype = 'raw';

        if ($this->installed && ACYM_CMS == 'wordpress') {
            $this->displayOptions = [
                'title' => ['ACYM_TITLE', true],
                'image' => ['ACYM_FEATURED_IMAGE', true],
                'intro' => ['ACYM_INTRO_ONLY', true],
                'content' => ['ACYM_FULL_TEXT', false],
                'cats' => ['ACYM_CATEGORIES', false],
                'author' => ['ACYM_AUTHOR', false],
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

    public function getStandardStructure(&$customView)
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

    public function initReplaceOptionsCustomView()
    {
        $this->replaceOptions = [
            'link' => ['ACYM_LINK'],
            'picthtml' => ['ACYM_IMAGE'],
            'readmore' => ['ACYM_READ_MORE'],
        ];
    }

    public function initElementOptionsCustomView()
    {
        $query = 'SELECT post.*
                    FROM #__posts AS post
                    WHERE post.post_type = "post" 
                        AND post.post_status = "publish"';
        $element = acym_loadObject($query);
        if (empty($element)) return;
        foreach ($element as $key => $value) {
            $this->elementOptions[$key] = [$key];
        }
    }

    public function getPossibleIntegrations()
    {
        return $this->pluginDescription;
    }

    public function insertionOptions($defaultValues = null)
    {
        $this->defaultValues = $defaultValues;
        $this->prepareWPCategories('category');

        $tabHelper = new TabHelper();
        $identifier = $this->name;
        $tabHelper->startTab(acym_translation('ACYM_ONE_BY_ONE'), !empty($this->defaultValues->defaultPluginTab) && $identifier === $this->defaultValues->defaultPluginTab);

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

        $zoneContent = $this->getFilteringZone().$this->prepareListing();
        echo $this->displaySelectionZone($zoneContent);
        echo $this->pluginHelper->displayOptions($displayOptions, $identifier, 'individual', $this->defaultValues);

        $tabHelper->endTab();
        $identifier = 'auto'.$this->name;
        $tabHelper->startTab(acym_translation('ACYM_BY_CATEGORY'), !empty($this->defaultValues->defaultPluginTab) && $identifier === $this->defaultValues->defaultPluginTab);

        $catOptions = [
            [
                'title' => 'ACYM_ORDER_BY',
                'type' => 'select',
                'name' => 'order',
                'options' => [
                    'ID' => 'ACYM_ID',
                    'post_date' => 'ACYM_PUBLISHING_DATE',
                    'post_modified' => 'ACYM_MODIFICATION_DATE',
                    'post_title' => 'ACYM_TITLE',
                    'rand' => 'ACYM_RANDOM',
                ],
            ],
            [
                'title' => 'ACYM_GROUP_BY_CATEGORY',
                'type' => 'boolean',
                'name' => 'groupbycat',
                'default' => false,
            ],
        ];
        $this->autoContentOptions($catOptions);

        $this->autoCampaignOptions($catOptions);

        $displayOptions = array_merge($displayOptions, $catOptions);

        ob_start();
        acym_display(acym_translation('ACYM_SPECIAL_CONTENT_WARNING'), 'warning', false);
        $warningMessage = ob_get_clean();
        echo $this->displaySelectionZone($warningMessage.$this->getCategoryListing());
        echo $this->pluginHelper->displayOptions($displayOptions, $identifier, 'grouped', $this->defaultValues);

        $tabHelper->endTab();

        $tabHelper->display('plugin');
    }

    public function prepareListing()
    {
        $this->querySelect = 'SELECT post.ID, post.post_title, post.post_date, post.post_content ';
        $this->query = 'FROM #__posts AS post ';
        $this->filters = [];
        $this->filters[] = 'post.post_type = "post"';
        $this->filters[] = 'post.post_status = "publish"';
        $this->searchFields = ['post.ID', 'post.post_title'];
        $this->pageInfo->order = 'post.ID';
        $this->elementIdTable = 'post';
        $this->elementIdColumn = 'ID';

        parent::prepareListing();

        //if a category is selected
        if (!empty($this->pageInfo->filter_cat)) {
            $this->query .= 'JOIN #__term_relationships AS cat ON post.ID = cat.object_id';
            $this->filters[] = 'cat.term_taxonomy_id = '.intval($this->pageInfo->filter_cat);
        }

        $rows = $this->getElements();
        foreach ($rows as $i => $row) {
            if (str_replace(['wp:core-embed', 'wp:shortcode'], '', $row->post_content) !== $row->post_content) {
                $rows[$i]->post_title = acym_tooltip('<i class="acymicon-exclamation-triangle"></i>', acym_translation('ACYM_SPECIAL_CONTENT_WARNING')).$rows[$i]->post_title;
            }
        }

        $listingOptions = [
            'header' => [
                'post_title' => [
                    'label' => 'ACYM_TITLE',
                    'size' => '7',
                ],
                'post_date' => [
                    'label' => 'ACYM_PUBLISHING_DATE',
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

    public function replaceContent(&$email)
    {
        $this->replaceMultiple($email);
        $this->replaceOne($email);
    }

    public function generateByCategory(&$email)
    {
        $tags = $this->pluginHelper->extractTags($email, 'auto'.$this->name);
        $this->tags = [];

        if (empty($tags)) return $this->generateCampaignResult;

        foreach ($tags as $oneTag => $parameter) {
            if (isset($this->tags[$oneTag])) continue;

            $query = 'SELECT DISTINCT post.`ID` 
                    FROM #__posts AS post 
                    LEFT JOIN #__term_relationships AS cat ON post.ID = cat.object_id';

            $where = [];

            $selectedArea = $this->getSelectedArea($parameter);
            if (!empty($selectedArea)) {
                $where[] = 'cat.term_taxonomy_id IN ('.implode(',', $selectedArea).')';
            }

            $where[] = 'post.post_type = "post"';
            $where[] = 'post.post_status = "publish"';
            if (!empty($parameter->min_publish)) {
                $parameter->min_publish = acym_date(acym_replaceDate($parameter->min_publish), 'Y-m-d H:i:s', false);
                $where[] = 'post.post_date_gmt >= '.acym_escapeDB($parameter->min_publish);
            }

            if (!empty($parameter->onlynew)) {
                $lastGenerated = $this->getLastGenerated($email->id);
                if (!empty($lastGenerated)) {
                    $where[] = 'post.post_date_gmt > '.acym_escapeDB(acym_date($lastGenerated, 'Y-m-d H:i:s', false));
                }
            }

            $query .= ' WHERE ('.implode(') AND (', $where).')';

            $this->groupedByCategory = !empty($parameter->groupbycat);
            $this->tags[$oneTag] = $this->finalizeCategoryFormat($query, $parameter, 'post');
        }

        return $this->generateCampaignResult;
    }

    protected function groupByCategory($elements)
    {
        if (!$this->groupedByCategory || empty($elements)) return $elements;

        acym_arrayToInteger($elements);
        $idsWithCatids = acym_loadObjectList(
            'SELECT map.`object_id`, taxonomy.`term_id` 
            FROM #__term_relationships AS map 
            JOIN #__term_taxonomy AS taxonomy 
                ON map.`term_taxonomy_id` = taxonomy.`term_taxonomy_id`
            WHERE taxonomy.`taxonomy` = "category" 
                AND map.`object_id` IN ('.implode(', ', $elements).')'
        );
        usort(
            $idsWithCatids,
            function ($a, $b) {
                return strtolower($a->term_id) > strtolower($b->term_id) ? 1 : -1;
            }
        );
        $elements = [];
        foreach ($idsWithCatids as $oneArticle) {
            if (in_array($oneArticle->object_id, $elements)) continue;
            $elements[] = $oneArticle->object_id;
        }

        return $elements;
    }

    public function replaceIndividualContent($tag)
    {
        $query = 'SELECT post.*, `user`.`user_nicename`, `user`.`display_name` 
                    FROM #__posts AS post 
                    LEFT JOIN #__users AS `user` 
                        ON `user`.`ID` = `post`.`post_author` 
                    WHERE post.post_type = "post" 
                        AND post.post_status = "publish"
                        AND post.ID = '.intval($tag->id);

        $element = $this->initIndividualContent($tag, $query);

        if (empty($element)) return '';

        $varFields = $this->getCustomLayoutVars($element);

        $link = get_permalink($element->ID);
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
        if (in_array('content', $tag->display)) {
            $contentText .= $varFields['{content}'];
        } elseif (in_array('intro', $tag->display)) {
            $contentText .= $varFields['{intro}'];
        }

        $customFields = [];
        $varFields['{author}'] = empty($element->display_name) ? $element->user_nicename : $element->display_name;
        if (in_array('author', $tag->display) && !empty($varFields['{author}'])) {
            $customFields[] = [
                $varFields['{author}'],
                acym_translation('ACYM_AUTHOR'),
            ];
        }

        $varFields['{cats}'] = get_the_term_list($tag->id, 'category', '', ', ');
        if (in_array('cats', $tag->display)) {
            $customFields[] = [
                $varFields['{cats}'],
                acym_translation('ACYM_CATEGORIES'),
            ];
        }

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
        $format->link = empty($tag->clickable) ? '' : $link;
        $format->customFields = $customFields;
        $result = '<div class="acymailing_content">'.$this->pluginHelper->getStandardDisplay($format).'</div>';

        $categoryTitle = '';
        $postCategories = acym_loadObjectList(
            'SELECT terms.`term_id`, terms.`name` 
            FROM #__terms AS terms 
            JOIN #__term_taxonomy AS taxonomy 
                ON terms.`term_id` = taxonomy.`term_id` 
            JOIN #__term_relationships AS map 
                ON map.`term_taxonomy_id` = taxonomy.`term_taxonomy_id` 
            WHERE map.`object_id` = '.intval($element->ID),
            'term_id'
        );
        $catIds = array_keys($postCategories);
        if (!empty($tag->groupbycat) && !in_array($this->currentCategory, $catIds)) {
            $this->currentCategory = min($catIds);

            $categoryTitle = '<h1 class="acymailing_category_title">'.$postCategories[$this->currentCategory]->name.'</h1>';
            $categoryTitle = '<a target="_blank" href="'.get_category_link($this->currentCategory).'">'.$categoryTitle.'</a>';
        }

        return $categoryTitle.$this->finalizeElementFormat($result, $tag, $varFields);
    }

    public function getPosts($ajax = true, $postPerPage = 20)
    {
        $return = $ajax ? [] : [0 => [0, acym_translation('ACYM_SELECT_AN_ARTICLE')]];

        $search = acym_getVar('string', 'searchedterm', '');
        if (!empty($search)) {
            $search = acym_escapeDB('%'.$search.'%');
            $search = 'post_title LIKE '.$search.' OR post_name LIKE '.$search.' OR post_content LIKE '.$search;
            $search = ' AND ('.$search.')';
        }

        $limit = '';
        if (!empty($postPerPage)) {
            $limit = 'LIMIT '.$postPerPage;
        }

        $query = 'SELECT ID, post_title FROM #__posts WHERE post_status = "publish" AND post_type IN ("post", "page") '.$search.' '.$limit;
        $posts = acym_loadObjectList($query);
        foreach ($posts as $post) {
            $return[] = [$post->ID, $post->post_title];
        }

        if ($ajax) {
            echo json_encode($return);
            exit;
        } else {
            return $return;
        }
    }

    protected function getTranslationId($elementId, $translationTool, $defaultLanguage = false)
    {
        $elementId = intval($elementId);
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
