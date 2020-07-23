<?php

class plgAcymK2 extends acymPlugin
{
    public function __construct()
    {
        parent::__construct();
        $this->cms = 'Joomla';
        $this->installed = acym_isExtensionActive('com_k2');

        $this->pluginDescription->name = 'K2';
        $this->rootCategoryId = 0;
        $this->pluginDescription->icon = ACYM_DYNAMICS_URL.basename(__DIR__).'/icon.png';

        if ($this->installed) {
            $this->initDisplayOptionsCustomView();
            $this->initElementOptionsCustomView();
            $this->initReplaceOptionsCustomView();

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

    public function initDisplayOptionsCustomView()
    {
        $extraFields = acym_loadObjectList('SELECT * FROM #__k2_extra_fields WHERE published = 1 AND type NOT IN ("csv", "header")');

        $this->displayOptions = [];

        $this->displayOptions['title'] = ['ACYM_TITLE', true];
        $this->displayOptions['intro'] = ['ACYM_INTRO_TEXT', true];
        $this->displayOptions['full'] = ['ACYM_FULL_TEXT', false];
        $this->displayOptions['cat'] = ['ACYM_CATEGORY', false];
        $this->displayOptions['readmore'] = ['ACYM_READ_MORE', false];

        if (!empty($extraFields)) {
            foreach ($extraFields as $field) {
                $this->displayOptions[$field->name] = [$field->name, false];
            }
        }
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
        $query = 'SELECT item.* FROM #__k2_items AS item WHERE item.published = 1';
        $element = acym_loadObject($query);
        if (empty($element)) return;
        foreach ($element as $key => $value) {
            $this->elementOptions[$key] = [$key];
        }
    }

    public function getPossibleIntegrations()
    {
        if (!acym_isAdmin() && $this->getParam('front', 'all') === 'hide') return null;

        return $this->pluginDescription;
    }

    public function insertionOptions($defaultValues = null)
    {
        $this->defaultValues = $defaultValues;

        // Get the categories, always with the columns "id", "parent_id" and "title". Use the MySQL "AS" if needed
        $this->categories = acym_loadObjectList(
            'SELECT id, parent AS parent_id, name AS title
            FROM `#__k2_categories`'
        );

        $extraFields = acym_loadObjectList('SELECT * FROM #__k2_extra_fields WHERE published = 1 AND type NOT IN ("csv", "header")');

        $options = [];

        $options['title'] = ['ACYM_TITLE', true];
        $options['intro'] = ['ACYM_INTRO_TEXT', true];
        $options['full'] = ['ACYM_FULL_TEXT', false];
        $options['cat'] = ['ACYM_CATEGORY', false];
        $options['readmore'] = ['ACYM_READ_MORE', false];

        if (!empty($extraFields)) {
            foreach ($extraFields as $field) {
                $options[$field->id] = [$field->name, false];
            }
        }


        $tabHelper = acym_get('helper.tab');
        $identifier = $this->name;
        $tabHelper->startTab(acym_translation('ACYM_ONE_BY_ONE'), !empty($this->defaultValues->defaultPluginTab) && $identifier === $this->defaultValues->defaultPluginTab);

        $displayOptions = [
            [
                'title' => 'ACYM_DISPLAY',
                'type' => 'checkbox',
                'name' => 'display',
                'options' => $options,
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
                    'id' => 'ACYM_ID',
                    'publish_up' => 'ACYM_PUBLISHING_DATE',
                    'modified' => 'ACYM_MODIFICATION_DATE',
                    'title' => 'ACYM_TITLE',
                    'rand' => 'ACYM_RANDOM',
                ],
            ],
            [
                'title' => 'ACYM_ONLY_FEATURED',
                'type' => 'boolean',
                'name' => 'featured',
                'default' => false,
            ],
            [
                'title' => 'ACYM_COLUMNS',
                'type' => 'number',
                'name' => 'cols',
                'default' => 1,
                'min' => 1,
                'max' => 10,
            ],
            [
                'title' => 'ACYM_MAX_NB_ELEMENTS',
                'type' => 'number',
                'name' => 'max',
                'default' => 20,
            ],
        ];

        $this->autoCampaignOptions($catOptions);

        $displayOptions = array_merge($displayOptions, $catOptions);

        echo $this->displaySelectionZone($this->getCategoryListing());
        echo $this->pluginHelper->displayOptions($displayOptions, $identifier, 'grouped', $this->defaultValues);

        $tabHelper->endTab();

        $tabHelper->display('plugin');
    }

    public function prepareListing()
    {
        //we load all elements with the categories
        $this->querySelect = 'SELECT item.id, item.title, item.publish_up ';
        $this->query = 'FROM #__k2_items AS item ';
        $this->filters = [];
        $this->filters[] = 'item.published = 1';
        $this->searchFields = ['item.id', 'item.title'];
        $this->pageInfo->order = 'item.id';
        $this->elementIdTable = 'item';
        $this->elementIdColumn = 'id';

        if (!acym_isAdmin() && $this->getParam('front', 'all') === 'author') {
            $this->filters[] = 'item.created_by = '.intval(acym_currentUserId());
        }

        parent::prepareListing();

        // If we filtered the listing for a specific category, we display only the elements of this category
        if (!empty($this->pageInfo->filter_cat)) {
            $this->filters[] = 'item.catid = '.intval($this->pageInfo->filter_cat);
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
        require_once JPATH_SITE.DS.'components'.DS.'com_k2'.DS.'helpers'.DS.'route.php';

        return true;
    }

    public function generateByCategory(&$email)
    {
        $tags = $this->pluginHelper->extractTags($email, 'auto'.$this->name);
        $this->tags = [];
        $time = time();

        if (empty($tags)) return $this->generateCampaignResult;

        foreach ($tags as $oneTag => $parameter) {
            if (isset($this->tags[$oneTag])) continue;

            $query = 'SELECT DISTINCT element.`id` FROM #__k2_items AS element ';

            $where = [];

            $selectedArea = $this->getSelectedArea($parameter);
            if (!empty($selectedArea)) {
                $where[] = 'element.catid IN ('.implode(',', $selectedArea).')';
            }

            $where[] = 'element.published = 1';
            $where[] = '`publish_up` < '.acym_escapeDB(date('Y-m-d H:i:s', $time - date('Z')));
            $where[] = '`publish_down` > '.acym_escapeDB(date('Y-m-d H:i:s', $time - date('Z'))).' OR `publish_down` = 0';

            if ($parameter->featured) $where[] = 'element.featured = 1';

            if (!empty($parameter->onlynew)) {
                $lastGenerated = $this->getLastGenerated($email->id);
                if (!empty($lastGenerated)) {
                    $where[] = 'element.publish_up > '.acym_escapeDB(acym_date($lastGenerated, 'Y-m-d H:i:s', false));
                }
            }

            $query .= ' WHERE ('.implode(') AND (', $where).')';

            $this->tags[$oneTag] = $this->finalizeCategoryFormat($query, $parameter, 'element');
        }

        return $this->generateCampaignResult;
    }

    public function replaceIndividualContent($tag)
    {
        $query = 'SELECT item.* FROM #__k2_items AS item WHERE item.published = 1 AND item.id = '.intval($tag->id);

        $element = $this->initIndividualContent($tag, $query);

        if (empty($element)) return '';

        $element->extra_fields = empty($element->extra_fields) ? [] : json_decode($element->extra_fields, true);

        $varFields = $this->getCustomLayoutVars($element);

        $link = K2HelperRoute::getItemRoute($element->id.':'.urlencode($element->alias), $element->catid);
        $link = $this->finalizeLink($link);
        $varFields['{link}'] = $link;

        $title = '';
        $varFields['{title}'] = $element->title;
        if (in_array('title', $tag->display)) $title = $varFields['{title}'];

        $afterTitle = '';
        $afterArticle = '';

        $imagePath = '';

        $md5picture = md5('Image'.$element->id);
        //When we have a specific size, we will use the larger picture, not the small one.
        if (file_exists(JPATH_SITE.DS.'media'.DS.'k2'.DS.'items'.DS.'cache'.DS.$md5picture.'_S.jpg') && $tag->pict != 'resized') {
            $imagePath = acym_rootURI().'media/k2/items/cache/'.$md5picture.'_S.jpg';
        } elseif (file_exists(JPATH_SITE.DS.'media'.DS.'k2'.DS.'items'.DS.'cache'.DS.$md5picture.'_L.jpg')) {
            $imagePath = acym_rootURI().'media/k2/items/cache/'.$md5picture.'_L.jpg';
        }
        $varFields['{picthtml}'] = '<img alt="" src="'.$imagePath.'">';
        if (empty($tag->pict)) $imagePath = '';

        $contentText = '';
        $varFields['{content}'] = $element->introtext.$element->fulltext;
        if (in_array('content', $tag->display)) $contentText .= $varFields['{content}'];
        $varFields['{intro}'] = $element->introtext;
        if (in_array('intro', $tag->display)) $contentText .= $varFields['{intro}'];
        $varFields['{full}'] = $element->fulltext;
        if (in_array('full', $tag->display)) $contentText .= $varFields['{full}'];


        $customFields = [];
        $category = acym_loadObject('SELECT * FROM #__k2_categories WHERE id = '.intval($element->catid));
        $linkCat = $this->finalizeLink('index.php?option=com_k2&view=itemlist&layout=category&task=category&id='.$category->id);
        $varFields['{cat}'] = '<a href="'.$linkCat.'" target="_blank">'.acym_escape($category->name).'</a>';
        if (in_array('cat', $tag->display)) {
            $customFields[] = [
                $varFields['{cat}'],
                acym_translation('ACYM_CATEGORY'),
            ];
        }

        $readMoreText = acym_translation('ACYM_READ_MORE');
        $varFields['{readmore}'] = '<a class="acymailing_readmore_link" style="text-decoration:none;" target="_blank" href="'.$link.'"><span class="acymailing_readmore">'.acym_escape($readMoreText).'</span></a>';
        if (in_array('readmore', $tag->display)) $afterArticle .= $varFields['{readmore}'];

        if (!empty(!empty($element->extra_fields))) {
            $k2Fields = acym_loadObjectList('SELECT * FROM #__k2_extra_fields WHERE published = 1', 'id');
            foreach ($element->extra_fields as $elementField) {
                if (in_array($elementField['id'], array_keys($k2Fields))) {
                    if ($k2Fields[$elementField['id']]->type == 'image') {
                        $value = '<img src="'.ltrim($elementField['value'], '/').'" alt="" />';
                    } elseif (in_array($k2Fields[$elementField['id']]->type, ['select', 'radio'])) {
                        $selectValues = json_decode($k2Fields[$elementField['id']]->value, true);
                        $value = '';
                        foreach ($selectValues as $selectValue) {
                            if ($selectValue['value'] == $elementField['value']) $value = $selectValue['name'];
                        }
                    } elseif ($k2Fields[$elementField['id']]->type == 'multipleSelect') {
                        $selectValues = json_decode($k2Fields[$elementField['id']]->value, true);
                        $values = [];
                        foreach ($selectValues as $selectValue) {
                            if (in_array($selectValue['value'], $elementField['value'])) $values[] = $selectValue['name'];
                        }
                        $value = implode(',', $values);
                    } elseif ($k2Fields[$elementField['id']]->type == 'link') {
                        $value = '<a target="_blank" href="'.$elementField['value'][1].'" >'.$elementField['value'][0].'</a>';
                    } elseif ($k2Fields[$elementField['id']]->type == 'date') {
                        $time = $elementField['value'];
                        $dateFormat = 'l, d F Y';
                        $value = acym_date($time, $dateFormat, false);
                    } else {
                        $value = $elementField['value'];
                    }

                    $varFields['{'.$k2Fields[$elementField['id']]->name.'}'] = $value;
                }
            }
        }

        $extraFields = [];
        foreach ($tag->display as $option) {
            if (is_numeric($option)) $extraFields[] = '{'.$option.'}';
        }

        if (!empty($extraFields) && !empty($element->extra_fields)) {
            foreach ($element->extra_fields as $elementField) {
                if (empty($varFields['{'.$k2Fields[$elementField['id']]->name.'}'])) continue;
                $customFields[] = [
                    $varFields['{'.$k2Fields[$elementField['id']]->name.'}'],
                    $k2Fields[$elementField['id']]->name,
                ];
            }
        }

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

        return $this->finalizeElementFormat($result, $tag, $varFields);
    }
}
