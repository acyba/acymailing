<?php

use AcyMailing\Helpers\TabHelper;

trait JcalproInsertion
{
    public function getStandardStructure(&$customView)
    {
        $tag = new stdClass();
        $tag->id = 0;

        $format = new stdClass();
        $format->tag = $tag;
        $format->title = '{title}';
        $format->afterTitle = '';
        $format->afterArticle = acym_translation('ACYM_DATE').': {date} <br/> '.acym_translation('ACYM_LOCATION').': {location_link}';
        $format->imagePath = '';
        $format->description = '{description}';
        $format->link = '{link}';
        $format->customFields = [];
        $customView = '<div class="acymailing_content">'.$this->pluginHelper->getStandardDisplay($format).'</div>';
    }

    public function initReplaceOptionsCustomView()
    {
        $this->replaceOptions = [
            'link' => ['ACYM_LINK'],
            'location_link' => ['ACYM_LOCATION'],
            'readmore' => ['ACYM_READ_MORE'],
            'startdate' => ['ACYM_START_DATE'],
            'enddate' => ['ACYM_END_DATE'],
        ];
    }

    public function initElementOptionsCustomView()
    {
        $query = 'SELECT event.*, category.title AS cattitle, location.title AS loctitle ';
        $query .= 'FROM `#__jcalpro_events` AS event ';
        $query .= 'JOIN `#__jcalpro_event_categories` AS catmap ON event.`id` = catmap.`event_id`';
        $query .= 'JOIN `#__categories` AS category ON catmap.`category_id` = category.`id`';
        $query .= 'JOIN `#__jcalpro_locations` AS location ON location.`id` = event.`location`';
        $element = acym_loadObject($query);
        if (empty($element)) return;
        foreach ($element as $key => $value) {
            $this->elementOptions[$key] = [$key];
        }
    }

    public function insertionOptions($defaultValues = null)
    {
        $this->defaultValues = $defaultValues;

        $this->categories = acym_loadObjectList('SELECT `id`, `parent_id`, `title` FROM `#__categories` WHERE published = 1 AND extension = "com_jcalpro"', 'id');

        $tabHelper = new TabHelper();
        $identifier = $this->name;
        $tabHelper->startTab(acym_translation('ACYM_ONE_BY_ONE'), !empty($this->defaultValues->defaultPluginTab) && $identifier === $this->defaultValues->defaultPluginTab);

        $displayOptions = [
            [
                'title' => 'ACYM_DISPLAY',
                'type' => 'checkbox',
                'name' => 'display',
                'options' => $this->displayOptions,
                'format' => false,
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
                'title' => 'ACYM_READ_MORE',
                'type' => 'boolean',
                'name' => 'readmore',
                'default' => true,
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
                    'start_date' => 'ACYM_DATE',
                    'title' => 'ACYM_TITLE',
                    'rand' => 'ACYM_RANDOM',
                ],
                'default' => 'start_date',
                'defaultdir' => 'asc',
            ],
        ];
        $this->autoContentOptions($catOptions, 'event');

        $this->autoCampaignOptions($catOptions);

        $displayOptions = array_merge($displayOptions, $catOptions);

        echo $this->displaySelectionZone($this->getCategoryListing());
        echo $this->pluginHelper->displayOptions($displayOptions, $identifier, 'grouped', $this->defaultValues);

        $tabHelper->endTab();

        $tabHelper->display('plugin');
    }

    public function prepareListing()
    {
        $this->querySelect = 'SELECT event.* ';
        $this->query = 'FROM `#__jcalpro_events` AS event ';
        $this->filters = [];
        $this->filters[] = 'event.published = 1';
        $this->filters[] = 'event.approved = 1';
        $this->filters[] = 'event.private = 0';
        $this->searchFields = ['event.id', 'event.title'];
        $this->pageInfo->order = 'event.start_date';
        $this->elementIdTable = 'event';
        $this->elementIdColumn = 'id';

        if (!acym_isAdmin() && $this->getParam('front', 'all') === 'author') {
            $this->filters[] = 'event.created_by = '.intval(acym_currentUserId());
        }

        if ($this->getParam('hidepast', '1') === '1') {
            $this->filters[] = 'event.`start_date` >= '.acym_escapeDB(date('Y-m-d H:i:s'));
        }

        parent::prepareListing();

        if (!empty($this->pageInfo->filter_cat)) {
            $this->query .= 'JOIN `#__jcalpro_event_categories` AS catmap ON catmap.event_id = event.id ';
            $this->filters[] = 'catmap.`category_id` = '.intval($this->pageInfo->filter_cat);
        }

        $listingOptions = [
            'header' => [
                'title' => [
                    'label' => 'ACYM_TITLE',
                    'size' => '8',
                ],
                'start_date' => [
                    'label' => 'ACYM_DATE',
                    'size' => '3',
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

    public function generateByCategory(&$email)
    {
        $time = time();

        //load the tags
        $tags = $this->pluginHelper->extractTags($email, 'auto'.$this->name);
        $this->tags = [];

        if (empty($tags)) return $this->generateCampaignResult;

        foreach ($tags as $oneTag => $parameter) {
            if (isset($this->tags[$oneTag])) continue;

            if (empty($parameter->from)) {
                $parameter->from = date('Y-m-d H:i:s', $time);
            } else {
                $parameter->from = acym_date(acym_replaceDate($parameter->from), 'Y-m-d H:i:s');
            }
            if (!empty($parameter->to)) $parameter->to = acym_date(acym_replaceDate($parameter->to), 'Y-m-d H:i:s');

            $query = 'SELECT DISTINCT event.`id` FROM `#__jcalpro_events` AS event ';

            $where = [];
            $where[] = 'event.`published` = 1';
            $where[] = 'event.`approved` = 1';
            $where[] = 'event.`private` = 0';

            $selectedArea = $this->getSelectedArea($parameter);
            if (!empty($selectedArea)) {
                $query .= 'JOIN `#__jcalpro_event_categories` AS catmap ON catmap.event_id = event.id ';
                $where[] = 'catmap.category_id IN ('.implode(',', $selectedArea).')';
            }

            // Not started events
            $where[] = 'event.`start_date` >= '.acym_escapeDB($parameter->from);

            if (!empty($parameter->to)) {
                $where[] = 'event.start_date <= '.acym_escapeDB($parameter->to);
            }

            if (!empty($parameter->featured)) $where[] = 'event.featured = 1';

            if (!empty($parameter->onlynew)) {
                $lastGenerated = $this->getLastGenerated($email->id);
                if (!empty($lastGenerated)) {
                    $where[] = 'event.`created` > '.acym_escapeDB(acym_date($lastGenerated, 'Y-m-d H:i:s', false));
                }
            }

            $query .= ' WHERE ('.implode(') AND (', $where).')';

            $this->tags[$oneTag] = $this->finalizeCategoryFormat($query, $parameter, 'event');
        }

        return $this->generateCampaignResult;
    }

    protected function loadLibraries($email)
    {
        $jcalproUrlHelper = rtrim(JPATH_ADMINISTRATOR, DS).DS.'components'.DS.'com_jcalpro'.DS.'helpers'.DS.'url.php';
        if (file_exists($jcalproUrlHelper)) include_once $jcalproUrlHelper;

        return true;
    }

    public function replaceIndividualContent($tag)
    {
        $query = 'SELECT event.* ';
        $query .= 'FROM `#__jcalpro_events` AS event ';
        $query .= 'WHERE event.`id` = '.intval($tag->id);

        $element = $this->initIndividualContent($tag, $query);

        if (empty($element)) return '';

        $varFields = $this->getCustomLayoutVars($element);
        $link = 'index.php?option=com_jcalpro&view=event&id='.$tag->id;
        $link = $this->finalizeLink($link);
        $varFields['{link}'] = $link;

        $title = '';
        $afterTitle = '';
        $afterArticle = '';

        $imagePath = '';
        $contentText = '';
        $customFields = [];

        $varFields['{title}'] = $element->title;
        if (in_array('title', $tag->display)) $title = $varFields['{title}'];
        if (in_array('description', $tag->display)) $contentText .= $varFields['{description}'];
        $dateFormat = $element->duration_type == 2 ? 'ACYM_DATE_FORMAT_LC1' : 'ACYM_DATE_FORMAT_LC2';

        $varFields['{startdate}'] = new JDate($element->start_date);
        $varFields['{startdate}']->setTimezone(new DateTimeZone($element->timezone));
        $varFields['{startdate}'] = $varFields['{startdate}']->format(acym_translation($dateFormat), true);

        $varFields['{date}'] = $varFields['{startdate}'];

        $varFields['{enddate}'] = $element->end_date;
        // 0 => no end date
        // 1 => event with a length of for example 3 days and 2 hours
        // 2 => all day events
        // 3 => set end date
        if (in_array($element->duration_type, [1, 3])) {
            $varFields['{enddate}'] = new JDate($element->end_date);
            $varFields['{enddate}']->setTimezone(new DateTimeZone($element->timezone));
            $varFields['{enddate}'] = $varFields['{enddate}']->format(acym_translation('ACYM_DATE_FORMAT_LC2'), true);

            if ($varFields['{date}'] !== $varFields['{enddate}']) {
                $varFields['{date}'] .= ' - '.$varFields['{enddate}'];
            }
        }

        if (in_array('date', $tag->display)) {
            $customFields[] = [
                $varFields['{date}'],
                acym_translation('ACYM_DATE'),
            ];
        }

        $varFields['{loctitle}'] = acym_loadResult('SELECT `title` FROM #__jcalpro_locations WHERE `id` = '.intval($element->location));
        $varFields['{location_link}'] = '<a target="_blank" href="index.php?option=com_jcalpro&view=location&id='.$element->location.'">'.$varFields['{loctitle}'].'</a>';

        if (in_array('location', $tag->display)) {
            $customFields[] = [
                $varFields['{location_link}'],
                acym_translation('ACYM_LOCATION'),
            ];
        }

        $categories = acym_loadResultArray(
            'SELECT CONCAT("<a target=\\"_blank\\" href=\\"index.php?option=com_jcalpro&view=category&id=", cat.`id`, "\\">", cat.`title`, "</a>") 
            FROM #__categories AS cat 
            JOIN #__jcalpro_event_categories AS map 
                ON map.category_id = cat.id 
            WHERE map.event_id = '.intval($tag->id).' 
            ORDER BY title ASC'
        );
        $varFields['{categories}'] = implode(', ', $categories);
        if (in_array('categories', $tag->display)) {
            if (!empty($varFields['{categories}'])) {
                $customFields[] = [
                    $varFields['{categories}'],
                    acym_translation('ACYM_CATEGORIES'),
                ];
            }
        }

        $varFields['{readmore}'] = '<a class="acymailing_readmore_link" style="text-decoration:none;" target="_blank" href="'.$link.'">';
        $varFields['{readmore}'] .= '<span class="acymailing_readmore">'.acym_translation('ACYM_READ_MORE').'</span>';
        $varFields['{readmore}'] .= '</a>';
        if (!empty($tag->readmore)) {
            $afterArticle .= $varFields['{readmore}'];
        }

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
}
