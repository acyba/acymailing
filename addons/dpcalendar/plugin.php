<?php

class plgAcymDpcalendar extends acymPlugin
{
    public function __construct()
    {
        parent::__construct();
        $this->cms = 'Joomla';
        $this->installed = acym_isExtensionActive('com_dpcalendar');

        $this->pluginDescription->name = 'DPCalendar';
        $this->pluginDescription->icon = ACYM_DYNAMICS_URL.basename(__DIR__).'/icon.png';

        if ($this->installed) {
            $this->displayOptions = [
                'title' => ['ACYM_TITLE', true],
                'image' => ['ACYM_IMAGE', true],
                'date' => ['ACYM_DATE', true],
                'venue' => ['ACYM_LOCATION', true],
                'desc' => ['ACYM_DESCRIPTION', true],
                'url' => ['ACYM_URL', false],
                'capacity' => ['COM_DPCALENDAR_FIELD_CAPACITY_LABEL', false],
                'closingdate' => ['COM_DPCALENDAR_FIELD_BOOKING_CLOSING_DATE_LABEL', true],
                'tags' => ['ACYM_TAGS', false],
            ];

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
                'hidepast' => [
                    'type' => 'switch',
                    'label' => 'ACYM_HIDE_PAST_EVENTS',
                    'value' => 1,
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
        $format->afterTitle = '';
        $format->afterArticle = 'Date: {date} <br/> Location: {venue} <br/> Booking closing date: {closingdate}';
        $format->imagePath = '{image}';
        $format->description = '{desc}';
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
            'startdate' => ['ACYM_START_DATE'],
            'enddate' => ['ACYM_END_DATE'],
        ];
    }

    public function initElementOptionsCustomView()
    {
        $query = 'SELECT event.*, category.title AS cattitle ';
        $query .= 'FROM `#__dpcalendar_events` AS event ';
        $query .= 'JOIN `#__categories` AS category ON event.`catid` = category.`id`';
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

        acym_loadLanguageFile('com_dpcalendar', JPATH_ADMINISTRATOR.DS.'components'.DS.'com_dpcalendar');
        $this->categories = acym_loadObjectList('SELECT `id`, `parent_id`, `title` FROM `#__categories` WHERE published = 1 AND extension = "com_dpcalendar"', 'id');

        $tabHelper = acym_get('helper.tab');
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

        $rawCustomFields = acym_loadObjectList(
            'SELECT `id`, `title` 
            FROM #__fields 
            WHERE `state` = 1 AND `context` = "com_dpcalendar.event" 
            ORDER BY `title` ASC'
        );
        if (!empty($rawCustomFields)) {
            $customFields = [];
            foreach ($rawCustomFields as $oneCustomField) {
                $customFields[$oneCustomField->id] = [$oneCustomField->title, false];
            }

            $displayOptions[] = [
                'title' => 'ACYM_CUSTOM_FIELDS',
                'type' => 'checkbox',
                'name' => 'custom',
                'options' => $customFields,
            ];
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
            [
                'title' => 'ACYM_FROM',
                'type' => 'date',
                'name' => 'from',
                'default' => time(),
            ],
            [
                'title' => 'ACYM_TO',
                'type' => 'date',
                'name' => 'to',
                'default' => '',
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
        $this->query = 'FROM `#__dpcalendar_events` AS event ';
        $this->filters = [];
        $this->filters[] = 'event.state = 1';
        $this->filters[] = 'event.access = 1';
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
            $this->filters[] = 'event.`catid` = '.intval($this->pageInfo->filter_cat);
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

    protected function loadLibraries($email)
    {
        acym_loadLanguageFile('com_dpcalendar', JPATH_ADMINISTRATOR.DS.'components'.DS.'com_dpcalendar');

        return true;
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

            $query = 'SELECT DISTINCT event.id FROM `#__dpcalendar_events` AS event ';

            $where = [];
            $where[] = 'event.`state` = 1';
            $where[] = 'event.`access` = 1';

            $selectedArea = $this->getSelectedArea($parameter);
            if (!empty($selectedArea)) {
                $where[] = 'event.catid IN ('.implode(',', $selectedArea).')';
            }

            // Not started events
            $where[] = 'event.`start_date` >= '.acym_escapeDB($parameter->from);

            if (!empty($parameter->to)) $where[] = 'event.start_date <= '.acym_escapeDB($parameter->to).' AND event.start_date != "0000-00-00 00:00:00"';

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

    public function replaceIndividualContent($tag)
    {
        $query = 'SELECT event.*, category.title AS cattitle ';
        $query .= 'FROM `#__dpcalendar_events` AS event ';
        $query .= 'JOIN `#__categories` AS category ON event.`catid` = category.`id`';
        $query .= 'WHERE event.`id` = '.intval($tag->id);

        $element = $this->initIndividualContent($tag, $query);

        if (empty($element)) return '';

        $varFields = $this->getCustomLayoutVars($element);
        $link = 'index.php?option=com_dpcalendar&view=event&id='.$tag->id.'&calid='.$element->catid;
        $link = $this->finalizeLink($link);

        $varFields['{link}'] = $link;

        $title = '';
        $afterTitle = '';
        $afterArticle = '';

        $imagePath = '';
        $contentText = '';
        $customFields = [];

        $varFields['{title}'] = $element->title;
        $varFields['{desc}'] = $element->description;
        if (in_array('title', $tag->display)) $title = $varFields['{title}'];
        if (in_array('desc', $tag->display)) $contentText .= $varFields['{desc}'];

        if (!empty($element->images)) {
            $element->images = json_decode($element->images, true);
            if (!empty($element->images['image_intro'])) {
                $imagePath = $element->images['image_intro'];
            } elseif (!empty($element->images['image_full'])) {
                $imagePath = $element->images['image_full'];
            }

            if (!empty($imagePath) && strpos($imagePath, 'http') !== 0) {
                $imagePath = acym_rootURI().$imagePath;
            }
        }
        $varFields['{image}'] = $imagePath;
        $varFields['{picthtml}'] = '<img alt="" src="'.$imagePath.'">';
        if (!in_array('image', $tag->display)) $imagePath = '';

        if (!empty($element->start_date) && !empty($element->end_date)) {
            $dateFormat = empty($element->all_day) ? 'ACYM_DATE_FORMAT_LC2' : 'ACYM_DATE_FORMAT_LC1';

            $varFields['{startdate}'] = acym_date($element->start_date, acym_translation($dateFormat));
            $varFields['{enddate}'] = acym_date($element->end_date, acym_translation($dateFormat));


            $varFields['{date}'] = $varFields['{startdate}'];
            if ($element->start_date !== $element->end_date) {
                $varFields['{date}'] .= ' - '.$varFields['{enddate}'];
            }
        }

        if (in_array('date', $tag->display) && !empty($element->start_date) && !empty($element->end_date)) {
            $customFields[] = [
                $varFields['{date}'],
                acym_translation('ACYM_DATE'),
            ];
        }

        $location = acym_loadObject(
            'SELECT location.* FROM #__dpcalendar_locations AS location 
                JOIN #__dpcalendar_events_location AS map ON location.id = map.location_id 
                WHERE map.event_id = '.intval($tag->id)
        );
        $varFields['{venue}'] = '';
        if (!empty($location)) {
            $googleMapsSearch = [];
            if (!empty($location->number)) $googleMapsSearch[] = $location->number;
            if (!empty($location->street)) $googleMapsSearch[] = $location->street;
            if (!empty($location->zip)) $googleMapsSearch[] = $location->zip;
            if (!empty($location->city)) $googleMapsSearch[] = $location->city;
            if (!empty($location->country)) $googleMapsSearch[] = $location->country;

            if (empty($googleMapsSearch)) {
                $gmapQuery = $location->latitude.','.$location->longitude;
            } else {
                $gmapQuery = implode(' ', $googleMapsSearch);
            }
            $varFields['{venue}'] = '<a href="https://maps.google.com/?q='.urlencode($gmapQuery).'" target="_blank">'.$location->title.'</a>';
        }

        if (in_array('venue', $tag->display)) {
            $customFields[] = [
                $varFields['{venue}'],
                acym_translation('ACYM_LOCATION'),
            ];
        }

        $varFields['{capacity}'] = '';
        if (!empty($element->capacity)) $varFields['{capacity}'] = $element->capacity;
        if (in_array('capacity', $tag->display) && !empty($element->capacity)) {
            $customFields[] = [
                $varFields['{capacity}'],
                acym_translation('COM_DPCALENDAR_FIELD_CAPACITY_LABEL'),
            ];
        }

        $varFields['{closingdate}'] = '';
        if (!empty($element->booking_closing_date)) $varFields['{closingdate}'] = acym_date($element->booking_closing_date, acym_translation('ACYM_DATE_FORMAT_LC2'));
        if (in_array('closingdate', $tag->display) && !empty($element->booking_closing_date)) {
            $customFields[] = [
                $varFields['{closingdate}'],
                acym_translation('COM_DPCALENDAR_FIELD_BOOKING_CLOSING_DATE_LABEL'),
            ];
        }

        $varFields['{url}'] = '';
        if (!empty($element->url)) $varFields['{url}'] = '<a target="_blank" href="'.$element->url.'">'.$element->url.'</a>';
        if (in_array('url', $tag->display) && !empty($element->url)) {
            $customFields[] = [
                $varFields['{url}'] = '',
                acym_translation('ACYM_URL'),
            ];
        }

        $tags = $this->getElementTags('com_dpcalendar.event', $tag->id);
        $varFields['{tags}'] = implode(', ', $tags);
        if (in_array('tags', $tag->display)) {

            if (!empty($varFields['{tags}'])) {
                $customFields[] = [
                    $varFields['{tags}'],
                    acym_translation('ACYM_TAGS'),
                ];
            }
        }

        $this->handleCustomFields($tag, $customFields);

        $varFields['{readmore}'] = '<a class="acymailing_readmore_link" style="text-decoration:none;" target="_blank" href="'.$link.'"><span class="acymailing_readmore">'.acym_translation('ACYM_READ_MORE').'</span></a>';
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
        $format->link = empty($tag->clickable) ? '' : $link;
        $format->customFields = $customFields;
        $result = '<div class="acymailing_content">'.$this->pluginHelper->getStandardDisplay($format).'</div>';

        return $this->finalizeElementFormat($result, $tag, $varFields);
    }
}
