<?php

use AcyMailing\Core\AcymPlugin;
use AcyMailing\Helpers\TabHelper;
use Joomla\Component\Content\Site\Helper\RouteHelper;
use Joomla\CMS\HTML\HTMLHelper;

class plgAcymArticle extends AcymPlugin
{
    private bool $groupedByCategory = false;
    private int $currentCategory = 0;

    public function __construct()
    {
        parent::__construct();
        $this->cms = 'Joomla';

        $this->pluginDescription->name = acym_translation('ACYM_ARTICLE');
        $this->pluginDescription->icon = '<i class="cell acymicon-joomla"></i>';
        $this->pluginDescription->icontype = 'raw';

        if (ACYM_CMS === 'joomla') {
            $this->displayOptions = [
                'title' => ['ACYM_TITLE', true],
                'intro' => ['ACYM_INTRO_TEXT', true],
                'full' => ['ACYM_FULL_TEXT', false],
                'cat' => ['ACYM_CATEGORY', false],
                'tags' => ['ACYM_TAGS', false],
                'author' => ['ACYM_AUTHOR', false],
                'publishing' => ['ACYM_PUBLISHING_DATE', false],
                'readmore' => ['ACYM_READ_MORE', false],
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
                'itemid' => [
                    'type' => 'text',
                    'label' => 'ACYM_MENU_ID',
                    'value' => '',
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
        $format->afterTitle = '{picthtml}';
        $format->afterArticle = '';
        $format->imagePath = '';
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
        $element = acym_getColumns('content', false);
        if (empty($element)) return;
        foreach ($element as $value) {
            $this->elementOptions[$value] = [$value];
        }
        $this->elementOptions['image_intro_caption'] = ['image_intro_caption'];
        $this->elementOptions['image_fulltext_caption'] = ['image_fulltext_caption'];
    }

    public function getPossibleIntegrations()
    {
        if (!acym_isAdmin() && $this->getParam('front', 'all') === 'hide') {
            return null;
        }

        return $this->pluginDescription;
    }

    public function insertionOptions($defaultValues = null)
    {
        $this->defaultValues = $defaultValues;

        // Get the categories, always with the columns "id", "parent_id" and "title". Use the MySQL "AS" if needed
        $this->categories = acym_loadObjectList(
            'SELECT id, parent_id, title
            FROM `#__categories` 
            WHERE extension = "com_content"'
        );

        $this->tagvalues = acym_loadObjectList(
            'SELECT `id` AS `term_id`, `title` AS `name`
			FROM #__tags 
			WHERE `level` > 0
			ORDER BY `name`'
        );

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
        ];

        if (!acym_isMultilingual() && acym_isExtensionActive('com_falang')) {
            $languageSelection = [
                'default' => 'ACYM_DEFAULT',
            ];
            $languages = acym_loadObjectList('SELECT `lang_id`, `title_native` FROM #__languages ORDER BY `title_native`');
            foreach ($languages as $oneLanguage) {
                $languageSelection[$oneLanguage->lang_id] = $oneLanguage->title_native;
            }

            $displayOptions = array_merge(
                $displayOptions,
                [
                    [
                        'title' => 'ACYM_LANGUAGE',
                        'type' => 'select',
                        'name' => 'falang',
                        'options' => $languageSelection,
                    ],
                ]
            );
        }

        // Handle joomla custom fields
        if (ACYM_J37) {
            $customFields = acym_loadObjectList(
                'SELECT id, title 
                FROM #__fields 
                WHERE context = "com_content.article" 
                    AND state = 1 
                ORDER BY ordering ASC, title ASC'
            );

            if (!empty($customFields)) {
                $customFieldsOption = [
                    'title' => 'ACYM_FIELDS_TO_DISPLAY',
                    'type' => 'checkbox',
                    'name' => 'custom',
                    'separator' => ', ',
                    'options' => [],
                ];
                foreach ($customFields as $oneCustomField) {
                    $customFieldsOption['options'][$oneCustomField->id] = [$oneCustomField->title, false];
                }

                $displayOptions[] = $customFieldsOption;
            }
        }

        $displayOptions = array_merge(
            $displayOptions,
            [
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
                    'caption' => true,
                ],
                [
                    'title' => 'ACYM_AUTO_LOGIN',
                    'tooltip' => 'ACYM_AUTO_LOGIN_DESCRIPTION_WARNING',
                    'type' => 'boolean',
                    'name' => 'autologin',
                    'default' => false,
                ],
            ]
        );

        $zoneContent = $this->getFilteringZone().$this->prepareListing();
        echo $this->displaySelectionZone($zoneContent);
        echo $this->pluginHelper->displayOptions($displayOptions, $identifier, 'individual', $this->defaultValues);

        $tabHelper->endTab();
        $identifier = 'auto'.$this->name;
        $tabHelper->startTab(acym_translation('ACYM_BY_CATEGORY'), !empty($this->defaultValues->defaultPluginTab) && $identifier === $this->defaultValues->defaultPluginTab);

        $catOptions = [
            [
                'title' => 'ACYM_LANGUAGE',
                'type' => 'language',
                'name' => 'language',
            ],
            [
                'title' => 'ACYM_ONLY_FEATURED',
                'type' => 'boolean',
                'name' => 'featured',
                'default' => false,
            ],
            [
                'title' => 'ACYM_ORDER_BY',
                'type' => 'select',
                'name' => 'order',
                'options' => [
                    'id' => 'ACYM_ID',
                    'publish_up' => 'ACYM_PUBLISHING_DATE',
                    'modified' => 'ACYM_MODIFICATION_DATE',
                    'ordering' => 'ACYM_ORDERING',
                    'title' => 'ACYM_TITLE',
                    'rand' => 'ACYM_RANDOM',
                ],
            ],
            [
                'title' => 'ACYM_GROUP_BY_CATEGORY',
                'type' => 'boolean',
                'name' => 'groupbycat',
                'default' => false,
            ],
            [
                'title' => 'ACYM_META_KEYWORDS',
                'tooltip' => 'ACYM_META_KEYWORDS_DESC',
                'type' => 'text',
                'name' => 'keywords',
                'class' => ' ',
            ],
        ];
        $this->autoContentOptions($catOptions);

        $this->autoCampaignOptions($catOptions, true);

        $displayOptions = array_merge($displayOptions, $catOptions);

        echo $this->displaySelectionZone($this->getCategoryListing());
        echo $this->pluginHelper->displayOptions($displayOptions, $identifier, 'grouped', $this->defaultValues);

        $tabHelper->endTab();
        $identifier = $this->name.'_tags';
        $tabHelper->startTab(acym_translation('ACYM_BY_TAG'), !empty($this->defaultValues->defaultPluginTab) && $identifier === $this->defaultValues->defaultPluginTab);

        echo $this->displaySelectionZone($this->getTagListing());
        echo $this->pluginHelper->displayOptions($displayOptions, $identifier, 'grouped', $this->defaultValues);

        $tabHelper->endTab();

        $tabHelper->display('plugin');
    }

    public function prepareListing()
    {
        //we load all elements with the categories
        $this->querySelect = 'SELECT element.id, element.title, element.publish_up ';
        $this->query = 'FROM #__content AS element ';
        $this->filters = [];
        $this->filters[] = 'element.state = 1';
        $this->searchFields = ['element.id', 'element.title'];
        $this->pageInfo->order = 'element.id';
        $this->elementIdTable = 'element';
        $this->elementIdColumn = 'id';

        if (!acym_isAdmin() && $this->getParam('front', 'all') === 'author') {
            $this->filters[] = 'element.created_by = '.intval(acym_currentUserId());
        }

        parent::prepareListing();

        // If we filtered the listing for a specific category, we display only the elements of this category
        if (!empty($this->pageInfo->filter_cat)) {
            $this->filters[] = 'element.catid = '.intval($this->pageInfo->filter_cat);
        }

        $listingOptions = [
            'header' => [
                'title' => [
                    'label' => 'ACYM_TITLE',
                    'size' => '7',
                ],
                'publish_up' => [
                    'label' => 'ACYM_PUBLISHING_DATE',
                    'size' => '4',
                    'type' => 'date',
                ],
                'id' => [
                    'label' => 'ACYM_ID',
                    'size' => '1',
                    'class' => 'text-center',
                ],
            ],
            'id' => 'id',
            'rows' => $this->getElements(),
        ];

        return $this->getElementsListing($listingOptions);
    }

    public function replaceContent(&$email)
    {
        $this->replaceMultiple($email);
        $this->replaceOne($email);
    }

    protected function loadLibraries($email)
    {
        if (!ACYM_J40) {
            require_once JPATH_SITE.DS.'components'.DS.'com_content'.DS.'helpers'.DS.'route.php';
        }

        return true;
    }

    public function generateByCategory(&$email)
    {
        $tags = $this->pluginHelper->extractTags($email, 'auto'.$this->name);
        $tags = array_merge($tags, $this->pluginHelper->extractTags($email, $this->name.'_tags'));

        $this->tags = [];
        $time = time();

        if (empty($tags)) return $this->generateCampaignResult;

        foreach ($tags as $oneTag => $parameter) {
            if (isset($this->tags[$oneTag])) continue;

            $query = 'SELECT DISTINCT element.`id` 
                    FROM #__content AS element 
                    LEFT JOIN #__categories AS category ON element.catid = category.id ';

            $where = [];

            $selectedArea = $this->getSelectedArea($parameter);
            if (!empty($selectedArea)) {
                if (strpos($oneTag, '{'.$this->name.'_tags') === 0) {
                    $query .= 'JOIN #__contentitem_tag_map AS tags ON element.id = tags.content_item_id AND tags.type_alias = "com_content.article"';
                    $where[] = 'tags.tag_id IN ('.implode(',', $selectedArea).')';
                } else {
                    $where[] = 'element.catid IN ('.implode(',', $selectedArea).')';
                }
            }

            $where[] = 'element.state = 1';
            $where[] = 'element.`publish_up` < '.acym_escapeDB(date('Y-m-d H:i:s', $time - date('Z')));
            $where[] = 'element.`publish_down` > '.acym_escapeDB(date('Y-m-d H:i:s', $time - date('Z'))).' OR element.`publish_down` = 0 OR element.`publish_down` IS NULL';
            if (!empty($parameter->min_publish)) {
                $parameter->min_publish = acym_date(acym_replaceDate($parameter->min_publish), 'Y-m-d H:i:s', false);
                $where[] = 'element.`publish_up` >= '.acym_escapeDB($parameter->min_publish);
            }

            if (!empty($parameter->featured)) {
                $where[] = 'element.featured = 1';
            }

            if (!empty($parameter->onlynew)) {
                $parameter->datefilter = 'onlynew';
            }
            if (!empty($parameter->datefilter)) {
                $lastGenerated = $this->getLastGenerated($email->id);
                if (!empty($lastGenerated)) {
                    $condition = 'element.publish_up > '.acym_escapeDB(acym_date($lastGenerated, 'Y-m-d H:i:s', false));
                    if ($parameter->datefilter === 'onlymodified') {
                        $condition .= ' OR element.modified > '.acym_escapeDB(acym_date($lastGenerated, 'Y-m-d H:i:s', false));
                    }
                    $where[] = $condition;
                }
            }

            if (!empty($parameter->language) && $parameter->language !== 'any') {
                $where[] = 'element.language IN ("*", '.acym_escapeDB($parameter->language).')';
            }

            if (!empty($parameter->keywords)) {
                $keywords = explode(',', $parameter->keywords);
                $conditionsMetaKeywords = [];
                foreach ($keywords as $oneKeyword) {
                    $conditionsMetaKeywords[] = 'element.metakey LIKE '.acym_escapeDB('%'.$oneKeyword.'%');
                }
                $where[] = '('.implode(' OR ', $conditionsMetaKeywords).')';
            }

            $query .= ' WHERE ('.implode(') AND (', $where).')';

            $this->groupedByCategory = !empty($parameter->groupbycat);
            $this->tags[$oneTag] = $this->finalizeCategoryFormat($query, $parameter, 'element');
        }

        return $this->generateCampaignResult;
    }

    protected function handleOrderBy(&$query, $parameter, $table = null)
    {
        if (empty($parameter->order)) return;

        $ordering = explode(',', $parameter->order);
        if ($ordering[0] === 'rand') {
            $query .= ' ORDER BY rand()';
        } elseif ($ordering[0] === 'ordering') {
            $query .= ' ORDER BY category.`title` '.acym_secureDBColumn(trim($ordering[1])).', element.`ordering` '.acym_secureDBColumn(trim($ordering[1]));
        } else {
            $table = null === $table ? '' : $table.'.';
            $column = $ordering[0];

            if (strpos($column, '.') !== false) {
                $parts = explode('.', $column, 2);
                $table = acym_secureDBColumn($parts[0]).'.';
                $column = $parts[1];
            }

            $query .= ' ORDER BY '.$table.'`'.acym_secureDBColumn(trim($column)).'` '.acym_secureDBColumn(trim($ordering[1]));
        }
    }

    protected function groupByCategory($elements)
    {
        if (!$this->groupedByCategory || empty($elements)) return $elements;

        acym_arrayToInteger($elements);
        $idsWithCatids = acym_loadObjectList('SELECT `id`, `catid` FROM #__content WHERE `id` IN ('.implode(', ', $elements).')');
        usort(
            $idsWithCatids,
            function ($a, $b) {
                return strtolower($a->catid) > strtolower($b->catid) ? 1 : -1;
            }
        );
        $elements = [];
        foreach ($idsWithCatids as $oneArticle) {
            $elements[] = $oneArticle->id;
        }

        return $elements;
    }

    public function replaceIndividualContent($tag)
    {
        $query = 'SELECT element.*, `user`.`name` AS authorname, cat.`title` AS category_title, cat.`access` AS category_access
                    FROM #__content AS element 
                    JOIN #__categories AS cat 
                        ON element.`catid` = cat.`id`
                    LEFT JOIN #__users AS `user` 
                        ON `user`.`id` = `element`.`created_by` 
                    WHERE element.state = 1
                        AND element.id = '.intval($tag->id);

        $element = $this->initIndividualContent($tag, $query);
        if (empty($element)) {
            return '';
        }

        $this->pluginHelper->contextLanguage = $this->getLanguage(null, true);
        $this->pluginHelper->translateItem($element, $tag, 'content');
        $varFields = $this->getCustomLayoutVars($element);

        $completeId = $element->id;
        if (!empty($element->alias)) $completeId .= ':'.$element->alias;

        if (defined('SH404SEF_IS_RUNNING') && SH404SEF_IS_RUNNING == 1) {
            $link = 'index.php?option=com_content&view=article&id='.$completeId.'&catid='.$element->catid.$this->getLanguage($element->language);
        } else {
            if (ACYM_J40) {
                $link = RouteHelper::getArticleRoute($completeId, $element->catid, $this->getLanguage($element->language, true));
            } else {
                $link = ContentHelperRoute::getArticleRoute($completeId, $element->catid, $this->getLanguage($element->language, true));
            }
        }

        //Get the related menu item if specified
        $menuId = $this->getParam('itemid');
        if (!empty($menuId)) {
            $link .= (strpos($link, '?') ? '&' : '?').'Itemid='.intval($menuId);
        }

        $link = $this->finalizeLink($link, $tag, acym_isAdmin() || (intval($element->access) === 1 && intval($element->category_access) === 1));

        $varFields['{link}'] = $link;

        $title = '';
        $afterTitle = '';
        $afterArticle = '';
        $imagePath = '';
        $imageCaption = '';
        $contentText = '';
        $customFields = [];
        $altImage = '';

        $varFields['{title}'] = $element->title;
        if (in_array('title', $tag->display)) {
            $title = $varFields['{title}'];
        }

        $varFields['{picthtml}'] = '';
        $varFields['{image_intro_caption}'] = '';
        $varFields['{image_fulltext_caption}'] = '';
        if (!empty($element->images)) {
            $images = json_decode($element->images, true);
            if (!empty($images['image_intro_caption'])) $varFields['{image_intro_caption}'] = $images['image_intro_caption'];
            if (!empty($images['image_fulltext_caption'])) $varFields['{image_fulltext_caption}'] = $images['image_fulltext_caption'];

            $pictVar = in_array('intro', $tag->display) || empty($images['image_fulltext']) ? 'image_intro' : 'image_fulltext';
            if (empty($images[$pictVar])) {
                $pictVar = $pictVar === 'image_intro' ? 'image_fulltext' : 'image_intro';
            }

            if (!empty($images[$pictVar])) {
                if (ACYM_J40 && class_exists(HTMLHelper::class)) {
                    $cleanedVar = HTMLHelper::_('cleanImageURL', $images[$pictVar]);
                    if (!empty($cleanedVar->url)) {
                        $images[$pictVar] = $cleanedVar->url;
                    }
                }
                $imagePath = acym_rootURI().$images[$pictVar];
                $altImage = empty($images[$pictVar.'_alt']) ? 'image' : $images[$pictVar.'_alt'];
                $varFields['{picthtml}'] = '<img alt="'.acym_escape($altImage).'" class="content_main_image" src="'.acym_escape($imagePath).'" />';

                if (!empty($tag->caption)) {
                    $imageCaption = $varFields['{'.$pictVar.'_caption}'];
                }
            }
        }

        if (empty($tag->pict)) {
            $imagePath = '';
            $imageCaption = '';
        }

        $varFields['{content}'] = $element->introtext.$element->fulltext;
        if (in_array('content', $tag->display)) {
            $contentText .= $varFields['{content}'];
        }

        $varFields['{intro}'] = $this->fixDivStructure($element->introtext);
        if (in_array('intro', $tag->display)) {
            $contentText .= $varFields['{intro}'];
        }

        $varFields['{full}'] = $this->fixDivStructure($element->fulltext);
        if (in_array('full', $tag->display)) {
            $contentText .= $varFields['{full}'];
        }

        $contentText = $this->cleanExtensionContent($contentText);

        if (empty($element->created_by_alias) && empty($element->authorname)) {
            $varFields['{author}'] = '';
        } else {
            $varFields['{author}'] = empty($element->created_by_alias) ? $element->authorname : $element->created_by_alias;
        }
        if (in_array('author', $tag->display) && !empty($varFields['{author}'])) {
            $customFields[] = [
                $varFields['{author}'],
                acym_translation('ACYM_AUTHOR'),
            ];
        }

        $varFields['{publishing}'] = acym_date($element->publish_up);
        if (in_array('publishing', $tag->display)) {
            $customFields[] = [
                $varFields['{publishing}'],
                acym_translation('ACYM_PUBLISHING_DATE'),
            ];
        }

        $category = acym_loadResult('SELECT title FROM #__categories WHERE id = '.intval($element->catid));
        $varFields['{cat}'] = '<a href="'.$this->finalizeLink('index.php?option=com_content&view=category&id='.$element->catid, $tag).'" target="_blank">'.acym_escape(
                $category
            ).'</a>';
        if (in_array('cat', $tag->display)) {
            $customFields[] = [
                $varFields['{cat}'],
                acym_translation('ACYM_CATEGORY'),
            ];
        }

        $tags = acym_loadObjectList(
            'SELECT tags.id, tags.title, tags.alias 
            FROM #__tags AS tags 
            JOIN #__contentitem_tag_map AS map ON tags.id = map.tag_id  
            WHERE map.type_alias = "com_content.article"
                  AND map.content_item_id = '.intval($tag->id)
        );
        foreach ($tags as $i => $oneTag) {
            $tags[$i] = '<a href="'.$this->finalizeLink('index.php?option=com_tags&view=tag&id='.$oneTag->id.':'.$oneTag->alias, $tag).'" target="_blank">'.acym_escape(
                    $oneTag->title
                ).'</a>';
        }
        $varFields['{tags}'] = implode(', ', $tags);

        if (in_array('tags', $tag->display) && !empty($varFields['{tags}'])) {
            $customFields[] = [
                $varFields['{tags}'],
                acym_translation('ACYM_TAGS'),
            ];
        }

        $this->handleCustomFields($tag, $customFields);

        $readMoreText = empty($tag->readmore) ? acym_translation('ACYM_READ_MORE') : $tag->readmore;
        $varFields['{readmore}'] = '<a class="acymailing_readmore_link" style="text-decoration:none;" target="_blank" href="'.$link.'"><span class="acymailing_readmore">'.acym_escape(
                $readMoreText
            ).'</span></a>';
        if (in_array('readmore', $tag->display)) $afterArticle .= $varFields['{readmore}'];

        $format = new stdClass();
        $format->tag = $tag;
        $format->title = $title;
        $format->afterTitle = $afterTitle;
        $format->afterArticle = $afterArticle;
        $format->imagePath = $imagePath;
        $format->imageCaption = $imageCaption;
        $format->description = $contentText;
        $format->link = empty($tag->clickable) && empty($tag->clickableimg) ? '' : $link;
        $format->customFields = $customFields;
        $format->altImage = $altImage;
        $result = '<div class="acymailing_content">'.$this->pluginHelper->getStandardDisplay($format).'</div>';

        $categoryTitle = '';
        if (!empty($tag->groupbycat) && $this->currentCategory !== intval($element->catid)) {
            $this->currentCategory = intval($element->catid);

            $categoryTitle = '<h1 class="acymailing_category_title">'.$element->category_title.'</h1>';
            $categoryLink = $this->finalizeLink('index.php?option=com_content&view=category&id='.$element->catid, $tag);
            $categoryTitle = '<a target="_blank" href="'.$categoryLink.'">'.$categoryTitle.'</a>';
        }

        return $categoryTitle.$this->finalizeElementFormat($result, $tag, $varFields);
    }
}
