<?php

use AcyMailing\Libraries\acymPlugin;
use AcyMailing\Helpers\TabHelper;
use Joomla\Component\Content\Site\Helper\RouteHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\HTML\HTMLHelper;


class plgAcymShika extends acymPlugin
{
    private $groupedByCategory = false;
    private $currentCategory = null;

    public function __construct()
    {
        parent::__construct();
        $this->cms = 'Joomla';
        $this->addonDefinition = [
            'name' => 'Shika',
            'description' => '- Insert courses in your emails',
            'documentation' => 'https://docs.acymailing.com/addons/joomla-add-ons/shika',
            'category' => 'Content management',
            'level' => 'starter',
        ];
        $this->installed = acym_isExtensionActive('com_tjlms');

        acym_loadLanguageFile('com_tjlms', JPATH_ADMINISTRATOR);
        $this->pluginDescription->name = acym_translation('COM_TJLMS');
        $this->pluginDescription->icon = ACYM_DYNAMICS_URL.basename(__DIR__).'/icon.svg';

        if ($this->installed) {
            $this->displayOptions = [
                'title' => ['ACYM_TITLE', true],
                'intro' => ['ACYM_INTRO_TEXT', true],
                'full' => ['ACYM_FULL_TEXT', true],
                'cat' => ['ACYM_CATEGORY', false],
                'tags' => ['ACYM_TAGS', false],
                'author' => ['ACYM_AUTHOR', false],
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
        } else {
            $this->settings = [
                'not_installed' => '1',
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
        if (empty($element)) {
            return;
        }

        foreach ($element as $value) {
            $this->elementOptions[$value] = [$value];
        }
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

        $this->categories = acym_loadObjectList(
            'SELECT id, parent_id, title
            FROM `#__categories` 
            WHERE extension = "com_tjlms"'
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

        if (ACYM_J37) {
            $customFields = acym_loadObjectList(
                'SELECT id, title 
                FROM #__fields 
                WHERE context = "com_tjlms.course" 
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
                'title' => 'Min. start date',
                'tooltip' => 'Only the elements starting after the specified date will be inserted in your email.',
                'type' => 'date',
                'name' => 'min_startdate',
                'default' => '',
                'relativeDate' => '-',
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
        $this->querySelect = 'SELECT element.id, element.title ';
        $this->query = 'FROM #__tjlms_courses AS element ';
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

        if (!empty($this->pageInfo->filter_cat)) {
            $this->filters[] = 'element.catid = '.intval($this->pageInfo->filter_cat);
        }

        $listingOptions = [
            'header' => [
                'title' => [
                    'label' => 'ACYM_TITLE',
                    'size' => '10',
                ],
                'id' => [
                    'label' => 'ACYM_ID',
                    'size' => '2',
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
        if (!ACYM_J40 && !require_once JPATH_SITE.DS.'components'.DS.'com_tjlms'.DS.'helpers'.DS.'route.php') {
            return false;
        }

        return true;
    }

    public function generateByCategory(&$email)
    {
        $tags = $this->pluginHelper->extractTags($email, 'auto'.$this->name);
        $tags = array_merge($tags, $this->pluginHelper->extractTags($email, $this->name.'_tags'));

        $this->tags = [];

        if (empty($tags)) {
            return $this->generateCampaignResult;
        }

        foreach ($tags as $oneTag => $parameter) {
            if (isset($this->tags[$oneTag])) continue;

            $query = 'SELECT DISTINCT element.`id` 
                    FROM #__tjlms_courses AS element 
                    LEFT JOIN #__categories AS category ON element.catid = category.id ';

            $where = [];

            $selectedArea = $this->getSelectedArea($parameter);
            if (!empty($selectedArea)) {
                if (strpos($oneTag, '{'.$this->name.'_tags') === 0) {
                    $query .= 'JOIN #__contentitem_tag_map AS tags ON element.id = tags.content_item_id AND tags.type_alias = "com_tjlms.course"';
                    $where[] = 'tags.tag_id IN ('.implode(',', $selectedArea).')';
                } else {
                    $where[] = 'element.catid IN ('.implode(',', $selectedArea).')';
                }
            }

            $where[] = 'element.state = 1';
            if (!empty($parameter->min_publish)) {
                $parameter->min_publish = acym_date(acym_replaceDate($parameter->min_publish), 'Y-m-d H:i:s', false);
                $where[] = 'element.`start_date` >= '.acym_escapeDB($parameter->min_publish);
            }
            if (!empty($parameter->min_startdate)) {
                $parameter->min_startdate = acym_date(acym_replaceDate($parameter->min_startdate), 'Y-m-d H:i:s', false);
                $where[] = 'element.`start_date` >= '.acym_escapeDB($parameter->min_startdate);
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
                    $condition = 'element.created > '.acym_escapeDB(acym_date($lastGenerated, 'Y-m-d H:i:s', false));
                    if ($parameter->datefilter === 'onlymodified') {
                        $condition .= ' OR element.modified > '.acym_escapeDB(acym_date($lastGenerated, 'Y-m-d H:i:s', false));
                    }
                    $where[] = $condition;
                }
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
        $idsWithCatids = acym_loadObjectList('SELECT `id`, `catid` FROM #__tjlms_courses WHERE `id` IN ('.implode(', ', $elements).')');
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
                    FROM #__tjlms_courses AS element 
                    JOIN #__categories AS cat 
                        ON element.`catid` = cat.`id`
                    LEFT JOIN #__users AS `user` 
                        ON `user`.`id` = `element`.`created_by` 
                    WHERE element.state = 1
                        AND element.id = '.intval($tag->id);

        $element = $this->initIndividualContent($tag, $query);
        if (empty($element)) return '';

        $varFields = $this->getCustomLayoutVars($element);
        $link = 'index.php?option=com_tjlms&view=course&id='.$element->id;

        $menuId = $this->getParam('itemid');
        if (empty($menuId)) {
            $app = Factory::getApplication();
            $menu = $app->getMenu();
            $courseMenu = $menu->getItems('link', 'index.php?option=com_tjlms&view=courses&courses_to_show=all', true);
            if (!empty($courseMenu->id)) {
                $menuId = $courseMenu->id;
            }
        }

        if (!empty($menuId)) {
            $link .= '&Itemid='.intval($menuId);
        }

        $link = $this->finalizeLink($link, $tag, intval($element->access) === 1 && intval($element->category_access) === 1);

        $varFields['{link}'] = $link;

        $title = '';
        $afterTitle = '';
        $afterArticle = '';
        $imageCaption = '';
        $contentText = '';
        $customFields = [];
        $altImage = '';

        $varFields['{title}'] = $element->title;

        if (in_array('title', $tag->display)) {
            $title = $varFields['{title}'];
        }

        if ($element->image) {
            $imagePath = 'media/com_tjlms/images/courses/'.$element->image;
        } else {
            $imagePath = 'media/com_tjlms/images/default/course.png';
        }

        $varFields['{picthtml}'] = '<img class="content_main_image" src="'.acym_escape($imagePath).'" />';

        $varFields['{content}'] = $element->long_description;
        if (in_array('content', $tag->display)) {
            $contentText .= $varFields['{content}'];
        }

        $varFields['{intro}'] = $this->fixDivStructure($element->short_desc);
        if (in_array('intro', $tag->display)) {
            $contentText .= $varFields['{intro}'];
        }

        $varFields['{full}'] = $this->fixDivStructure($element->description);
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

        $category = acym_loadResult('SELECT title FROM #__categories WHERE id = '.intval($element->catid));
        $varFields['{cat}'] = '<a href="'.$this->finalizeLink('index.php?option=com_tjlms&view=category&id='.$element->catid, $tag).'" target="_blank">'.acym_escape(
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
            WHERE map.type_alias = "com_tjlms.course"
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
        if (in_array('readmore', $tag->display)) {
            $afterArticle .= $varFields['{readmore}'];
        }

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
        if (!empty($tag->groupbycat) && $this->currentCategory !== $element->catid) {
            $this->currentCategory = $element->catid;

            $categoryTitle = '<h1 class="acymailing_category_title">'.$element->category_title.'</h1>';
            $categoryLink = $this->finalizeLink('index.php?option=com_tjlms&view=category&id='.$element->catid, $tag);
            $categoryTitle = '<a target="_blank" href="'.$categoryLink.'">'.$categoryTitle.'</a>';
        }

        return $categoryTitle.$this->finalizeElementFormat($result, $tag, $varFields);
    }
}
