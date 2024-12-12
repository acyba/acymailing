<?php

use AcyMailing\Helpers\TabHelper;

trait PhocaInsertion
{
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
        $format->description = '{description}';
        $format->link = '{link}';
        $format->customFields = [];
        $customView = '<div class="acymailing_content">'.$this->pluginHelper->getStandardDisplay($format).'</div>';
    }

    public function initElementOptionsCustomView()
    {
        $element = acym_loadObject('SELECT * FROM #__phocadownload WHERE published = 1');
        if (empty($element)) {
            return;
        }

        foreach ($element as $key => $value) {
            $this->elementOptions[$key] = [$key];
        }
    }

    public function initReplaceOptionsCustomView()
    {
        $this->replaceOptions = [
            'link' => ['ACYM_LINK'],
            'picthtml' => ['ACYM_IMAGE'],
            'cat' => ['ACYM_CATEGORY'],
            'readmore' => ['ACYM_READ_MORE'],
        ];
    }

    public function insertionOptions($defaultValues = null)
    {
        $this->defaultValues = $defaultValues;
        $this->categories = acym_loadObjectList('SELECT id, parent_id, title FROM `#__phocadownload_categories`');

        $tabHelper = new TabHelper();
        $identifier = $this->name;
        $tabHelper->startTab(acym_translation('ACYM_ONE_BY_ONE'), !empty($this->defaultValues->defaultPluginTab) && $identifier === $this->defaultValues->defaultPluginTab);

        $displayOptions = [
            [
                'title' => 'ACYM_DISPLAY',
                'type' => 'checkbox',
                'name' => 'display',
                'options' => [
                    'title' => ['ACYM_TITLE', true],
                    'description' => ['ACYM_DESCRIPTION', true],
                    'features' => ['ACYM_DETAILS', false],
                    'author' => ['ACYM_AUTHOR', false],
                    'version' => ['ACYM_VERSION_LABEL', false],
                    'cat' => ['ACYM_CATEGORY', false],
                    'readmore' => ['ACYM_READ_MORE', false],
                ],
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

        echo $this->displaySelectionZone($this->getFilteringZone().$this->prepareListing());
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
                    'title' => 'ACYM_TITLE',
                    'rand' => 'ACYM_RANDOM',
                ],
            ],
        ];

        $this->autoContentOptions($catOptions);
        $this->autoCampaignOptions($catOptions);
        $displayOptions = array_merge($displayOptions, $catOptions);

        echo $this->displaySelectionZone($this->getCategoryListing());
        echo $this->pluginHelper->displayOptions($displayOptions, $identifier, 'grouped', $this->defaultValues);

        $tabHelper->endTab();
        $tabHelper->display('plugin');
    }

    public function prepareListing()
    {
        $this->querySelect = 'SELECT item.id, item.title, item.publish_up ';
        $this->query = 'FROM #__phocadownload AS item ';
        $this->filters = [];
        $this->filters[] = 'item.published = 1';
        $this->searchFields = ['item.id', 'item.title'];
        $this->pageInfo->order = 'item.id';
        $this->elementIdTable = 'item';
        $this->elementIdColumn = 'id';

        parent::prepareListing();

        if (!empty($this->pageInfo->filter_cat)) {
            $this->filters[] = 'item.catid = '.intval($this->pageInfo->filter_cat);
        }

        return $this->getElementsListing(
            [
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
            ]
        );
    }

    public function replaceContent(&$email)
    {
        $this->replaceMultiple($email);
        $this->replaceOne($email);
    }

    public function replaceIndividualContent($tag)
    {
        $query = 'SELECT * FROM #__phocadownload WHERE `published` = 1 AND `id` = '.intval($tag->id);
        $element = $this->initIndividualContent($tag, $query);

        if (empty($element)) {
            return '';
        }

        $varFields = $this->getCustomLayoutVars($element);

        $link = 'index.php?option=com_phocadownload&view=file&Itemid=1140&id='.$element->id;
        $link = $this->finalizeLink($link, $tag);
        $varFields['{link}'] = $link;

        $title = '';
        $afterArticle = '';
        $imagePath = '';
        $contentText = '';
        $customFields = [];

        if (in_array('title', $tag->display)) {
            $title = $element->title;
        }

        $varFields['{picthtml}'] = '';
        if (!empty($tag->pict) && !empty($element->image_download)) {
            $imagePath = 'images/phocadownload/'.$element->image_download;
            $varFields['{picthtml}'] = '<img alt="" src="'.$imagePath.'">';
        }

        if (in_array('description', $tag->display)) {
            $contentText .= $element->description;
        }

        if (in_array('features', $tag->display) && !empty($varFields['{features}'])) {
            $contentText .= $element->features;
        }

        if (in_array('author', $tag->display) && !empty($varFields['{author}'])) {
            $author = $varFields['{author}'];
            if (!empty($element->author_url)) {
                $author = '<a href="'.$element->author_url.'" target="_blank">'.$author.'</a>';
            }
            $customFields[] = [
                $author,
                acym_translation('ACYM_AUTHOR'),
            ];
        }

        if (in_array('version', $tag->display) && !empty($varFields['{version}'])) {
            $customFields[] = [
                $varFields['{version}'],
                acym_translation('ACYM_VERSION_LABEL'),
            ];
        }

        $category = acym_loadObject('SELECT alias, title FROM #__phocadownload_categories WHERE id = '.intval($element->catid));
        $linkCat = $this->finalizeLink('index.php?option=com_phocadownload&view=category&id='.$element->catid.':'.$category->alias, $tag);
        $varFields['{cat}'] = '<a href="'.$linkCat.'" target="_blank">'.acym_escape($category->title).'</a>';
        if (in_array('cat', $tag->display)) {
            $customFields[] = [
                $varFields['{cat}'],
                acym_translation('ACYM_CATEGORY'),
            ];
        }

        if (in_array('readmore', $tag->display)) {
            $afterArticle .= '<a class="acymailing_readmore_link" style="text-decoration:none;" target="_blank" href="'.$link.'">
                <span class="acymailing_readmore">'.acym_escape(acym_translation('ACYM_READ_MORE')).'</span>
            </a>';
        }

        $format = new stdClass();
        $format->tag = $tag;
        $format->title = $title;
        $format->afterArticle = $afterArticle;
        $format->imagePath = $imagePath;
        $format->altImage = $title;
        $format->description = $contentText;
        $format->link = empty($tag->clickable) && empty($tag->clickableimg) ? '' : $link;
        $format->customFields = $customFields;
        $result = '<div class="acymailing_content">'.$this->pluginHelper->getStandardDisplay($format).'</div>';

        return $this->finalizeElementFormat($result, $tag, $varFields);
    }

    public function generateByCategory(&$email)
    {
        $tags = $this->pluginHelper->extractTags($email, 'auto'.$this->name);
        $this->tags = [];
        $time = time();

        if (empty($tags)) {
            return $this->generateCampaignResult;
        }

        foreach ($tags as $oneTag => $parameter) {
            if (isset($this->tags[$oneTag])) {
                continue;
            }

            $query = 'SELECT DISTINCT element.`id` FROM #__phocadownload AS element ';

            $where = [];

            $selectedArea = $this->getSelectedArea($parameter);
            if (!empty($selectedArea)) {
                $where[] = 'element.catid IN ('.implode(',', $selectedArea).')';
            }

            $where[] = 'element.published = 1';
            $where[] = 'element.`publish_up` < '.acym_escapeDB(date('Y-m-d H:i:s', $time - date('Z')));
            $where[] = 'element.`publish_down` > '.acym_escapeDB(date('Y-m-d H:i:s', $time - date('Z'))).' OR element.`publish_down` = 0';

            if (!empty($parameter->min_publish)) {
                $parameter->min_publish = acym_date(acym_replaceDate($parameter->min_publish), 'Y-m-d H:i:s', false);
                $where[] = 'element.`publish_up` >= '.acym_escapeDB($parameter->min_publish);
            }

            if (!empty($parameter->onlynew)) {
                $lastGenerated = $this->getLastGenerated($email->id);
                if (!empty($lastGenerated)) {
                    $where[] = 'element.`publish_up` > '.acym_escapeDB(acym_date($lastGenerated, 'Y-m-d H:i:s', false));
                }
            }

            $query .= ' WHERE ('.implode(') AND (', $where).')';

            $this->tags[$oneTag] = $this->finalizeCategoryFormat($query, $parameter, 'element');
        }

        return $this->generateCampaignResult;
    }
}
