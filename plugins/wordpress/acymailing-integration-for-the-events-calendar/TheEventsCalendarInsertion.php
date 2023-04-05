<?php

use AcyMailing\Helpers\TabHelper;

trait TheEventsCalendarInsertion
{
    public function getStandardStructure(&$customView)
    {
        $tag = new stdClass();
        $tag->id = 0;

        $format = new stdClass();
        $format->tag = $tag;
        $format->title = '{title}';
        $format->afterTitle = acym_translation('ACYM_PRICE').': {price}';
        $format->afterArticle = acym_translation('ACYM_DATE').': {date} <br> '.acym_translation('ACYM_LOCATION').': {location}';
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
            'startdate' => ['ACYM_START_DATE'],
            'enddate' => ['ACYM_END_DATE'],
            'simplestartdate' => ['ACYM_START_DATE_SIMPLE'],
            'simpleenddate' => ['ACYM_END_DATE_SIMPLE'],
        ];
    }

    public function initElementOptionsCustomView()
    {
        $query = 'SELECT post.*
                    FROM #__posts AS post
                    WHERE post.post_type = "tribe_events" 
                        AND post.post_status = "publish"';
        $element = acym_loadObject($query);
        if (empty($element)) return;
        foreach ($element as $key => $value) {
            $this->elementOptions[$key] = [$key];
        }
    }

    public function insertionOptions($defaultValues = null)
    {
        $this->defaultValues = $defaultValues;
        $this->prepareWPCategories('tribe_events_cat');

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
                'title' => 'ACYM_ORDER_BY',
                'type' => 'select',
                'name' => 'order',
                'options' => [
                    'ID' => 'ACYM_DATE_CREATED',
                    'startdate.meta_value' => 'ACYM_DATE',
                    'post_title' => 'ACYM_TITLE',
                    'menu_order' => 'ACYM_MENU_ORDER',
                    'rand' => 'ACYM_RANDOM',
                ],
                'default' => 'startdate.meta_value',
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
        $this->querySelect = 'SELECT post.ID, post.post_title, post.post_date, post.post_content ';
        $this->query = 'FROM #__posts AS post ';
        $this->filters = [];
        $this->filters[] = 'post.post_type = "tribe_events"';
        $this->filters[] = 'post.post_status = "publish"';
        $this->searchFields = ['post.ID', 'post.post_title'];
        $this->pageInfo->order = 'post.ID';
        $this->elementIdTable = 'post';
        $this->elementIdColumn = 'ID';

        if ($this->getParam('hidepast', '1') === '1') {
            $this->query .= 'JOIN #__postmeta AS startdate ON post.ID = startdate.post_id AND startdate.meta_key = "_EventStartDateUTC" ';
            $this->filters[] = 'startdate.`meta_value` >= '.acym_escapeDB(date('Y-m-d H:i:s'));
        }

        parent::prepareListing();

        //if a category is selected
        if (!empty($this->pageInfo->filter_cat)) {
            $this->query .= 'JOIN #__term_relationships AS cat ON post.ID = cat.object_id';
            $this->filters[] = 'cat.term_taxonomy_id = '.intval($this->pageInfo->filter_cat);
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
        $tags = $this->pluginHelper->extractTags($email, 'auto'.$this->name);
        $this->tags = [];

        if (empty($tags)) return $this->generateCampaignResult;

        $time = time();

        foreach ($tags as $oneTag => $parameter) {
            if (isset($this->tags[$oneTag])) continue;

            if (empty($parameter->from)) {
                $parameter->from = date('Y-m-d H:i:s', $time);
            } else {
                $parameter->from = acym_date(acym_replaceDate($parameter->from), 'Y-m-d H:i:s');
            }
            if (!empty($parameter->to)) $parameter->to = acym_date(acym_replaceDate($parameter->to), 'Y-m-d H:i:s');

            $query = 'SELECT DISTINCT post.`ID` 
                    FROM #__posts AS post 
                    JOIN #__postmeta AS startdate ON post.ID = startdate.post_id AND startdate.meta_key = "_EventStartDateUTC" ';

            $where = [];

            $selectedArea = $this->getSelectedArea($parameter);
            if (!empty($selectedArea)) {
                $query .= 'JOIN #__term_relationships AS cat ON post.ID = cat.object_id ';
                $where[] = 'cat.term_taxonomy_id IN ('.implode(',', $selectedArea).')';
            }

            $where[] = 'post.post_type = "tribe_events"';
            $where[] = 'post.post_status = "publish"';
            $where[] = 'startdate.`meta_value` >= '.acym_escapeDB($parameter->from);

            if (!empty($parameter->to)) $where[] = 'startdate.meta_value <= '.acym_escapeDB($parameter->to).' AND startdate.meta_value != "0000-00-00 00:00:00"';

            if (!empty($parameter->onlynew)) {
                $lastGenerated = $this->getLastGenerated($email->id);
                if (!empty($lastGenerated)) {
                    $where[] = 'startdate.meta_value > '.acym_escapeDB(acym_date($lastGenerated, 'Y-m-d H:i:s', false));
                }
            }

            $query .= ' WHERE ('.implode(') AND (', $where).')';

            $this->tags[$oneTag] = $this->finalizeCategoryFormat($query, $parameter, 'post');
        }

        return $this->generateCampaignResult;
    }

    public function replaceIndividualContent($tag)
    {
        $query = 'SELECT post.*
                    FROM #__posts AS post
                    WHERE post.post_type = "tribe_events" 
                        AND post.post_status = "publish"
                        AND post.ID = '.intval($tag->id);

        $element = $this->initIndividualContent($tag, $query);

        if (empty($element)) return '';
        $varFields = $this->getCustomLayoutVars($element);

        $properties = acym_loadObjectList('SELECT meta_key, meta_value AS `value` FROM #__postmeta WHERE post_id = '.intval($tag->id), 'meta_key');
        foreach ($properties as $name => $property) {
            $varFields['{'.$name.'}'] = $property->value;
        }

        $link = get_permalink($element->ID);
        $varFields['{link}'] = $link;

        $title = '';
        $afterTitle = '';
        $afterArticle = '';
        $imagePath = '';
        $contentText = '';
        $customFields = [];

        $varFields['{title}'] = $element->post_title;
        if (in_array('title', $tag->display)) $title = $varFields['{title}'];


        $imageId = get_post_thumbnail_id($tag->id);
        if (!empty($imageId)) {
            $imagePath = get_the_post_thumbnail_url($tag->id);
        }
        $varFields['{image}'] = $imagePath;
        $varFields['{picthtml}'] = '<img alt="" src="'.$imagePath.'">';
        if (!in_array('image', $tag->display)) $imagePath = '';

        $varFields['{full}'] = $this->cleanExtensionContent($element->post_content);
        $varFields['{intro}'] = $this->cleanExtensionContent($this->getIntro($element->post_content));
        if (in_array('full', $tag->display)) {
            $contentText .= $varFields['{full}'];
        } elseif (in_array('intro', $tag->display)) {
            $contentText .= $varFields['{intro}'];
        }

        $varFields['{startdate}'] = '';
        $varFields['{enddate}'] = '';
        $varFields['{simpleenddate}'] = '';
        $varFields['{simplestartdate}'] = '';
        $varFields['{date}'] = '';

        if (!empty($properties['_EventStartDate']->value)) {
            $allday = !empty($properties['_EventAllDay']->value) && $properties['_EventAllDay']->value === 'yes';
            $startDate = $properties['_EventStartDate']->value;
            $endDate = empty($properties['_EventEndDate']->value) ? '' : $properties['_EventEndDate']->value;


            $varFields['{startdate}'] = acym_date($startDate, acym_translation('ACYM_DATE_FORMAT_LC2'), false);
            $varFields['{enddate}'] = acym_date($endDate, acym_translation('ACYM_DATE_FORMAT_LC2'), false);

            $varFields['{simplestartdate}'] = acym_date($startDate, acym_translation('ACYM_DATE_FORMAT_LC1'), false);
            $varFields['{simpleenddate}'] = acym_date($endDate, acym_translation('ACYM_DATE_FORMAT_LC1'), false);

            $varFields['{date}'] = $allday ? $varFields['{simplestartdate}'] : $varFields['{startdate}'];
            if (!empty($endDate) && $startDate !== $endDate) {
                if ($allday) {
                    $endDateDisplay = $varFields['{simpleenddate}'];
                } else {
                    if ($varFields['{simplestartdate}'] === $varFields['{simpleenddate}']) {
                        $endDateDisplay = acym_date($endDate, 'H:i', false);
                    } else {
                        $endDateDisplay = $varFields['{enddate}'];
                    }
                }

                $varFields['{date}'] .= ' - '.$endDateDisplay;
            }
        }
        if (in_array('date', $tag->display) && !empty($properties['_EventStartDateUTC']->value)) {
            $customFields[] = [
                $varFields['{date}'],
                acym_translation('ACYM_DATE'),
            ];
        }

        $varFields['{location}'] = '';
        if (!empty($properties['_EventVenueID']->value)) {
            $locationData = acym_loadObjectList('SELECT meta_key, meta_value AS `value` FROM #__postmeta WHERE post_id = '.intval($properties['_EventVenueID']->value), 'meta_key');

            if (!empty($locationData)) {
                $locationName = acym_loadResult('SELECT post_title FROM #__posts WHERE ID = '.intval($properties['_EventVenueID']->value));

                $googleMapsSearch = [];
                if (!empty($locationData['_VenueAddress']->value)) $googleMapsSearch[] = $locationData['_VenueAddress']->value;
                if (!empty($locationData['_VenueZip']->value)) $googleMapsSearch[] = $locationData['_VenueZip']->value;
                if (!empty($locationData['_VenueCity']->value)) $googleMapsSearch[] = $locationData['_VenueCity']->value;
                if (!empty($locationData['_VenueCountry']->value)) $googleMapsSearch[] = $locationData['_VenueCountry']->value;

                $gmapQuery = implode(' ', $googleMapsSearch);
                $varFields['{location}'] = '<a href="https://maps.google.com/?q='.urlencode($gmapQuery).'" target="_blank">'.$locationName.'</a>';
            }
            if (in_array('location', $tag->display) && !empty($properties['_EventVenueID']->value)) {
                $customFields[] = [
                    $varFields['{location}'],
                    acym_translation('ACYM_LOCATION'),
                ];
            }
        }


        $price = empty($properties['_EventCost']->value) ? 0 : $properties['_EventCost']->value;
        $symbol = empty($properties['_EventCurrencySymbol']->value) ? '' : $properties['_EventCurrencySymbol']->value;

        if (empty($properties['_EventCurrencyPosition']->value) || $properties['_EventCurrencyPosition']->value === 'suffix') {
            $price .= $symbol;
        } else {
            $price = $symbol.$price;
        }
        $varFields['{price}'] = $price;
        if (in_array('price', $tag->display)) {
            $customFields[] = [
                $varFields['{price}'],
                acym_translation('ACYM_PRICE'),
            ];
        }

        $varFields['{website}'] = empty($properties['_EventURL']->value) ? ''
            : '<a target="_blank" href="'.$properties['_EventURL']->value.'">'.$properties['_EventURL']->value.'</a>';
        if (in_array('website', $tag->display) && !empty($properties['_EventURL']->value)) {
            $customFields[] = [
                $varFields['{website}'],
                __('Event Website', 'the-events-calendar'),
            ];
        }

        $varFields['{cats}'] = get_the_term_list($tag->id, 'tribe_events_cat', '', ', ');
        if (in_array('cats', $tag->display) && !empty($varFields['{cats}'])) {
            $customFields[] = [
                $varFields['{cats}'],
                acym_translation('ACYM_CATEGORIES'),
            ];
        }

        $varFields['{tags}'] = get_the_term_list($tag->id, 'post_tag', '', ', ');
        if (in_array('tags', $tag->display) && !empty($varFields['{tags}'])) {
            $customFields[] = [
                $varFields['{tags}'],
                acym_translation('ACYM_TAGS'),
            ];
        }

        $varFields['{readmore}'] = '<a class="acymailing_readmore_link" style="text-decoration:none;" target="_blank" href="'.$link.'"><span class="acymailing_readmore">'.acym_escape(
                acym_translation('ACYM_READ_MORE')
            ).'</span></a>';
        if ($tag->readmore === '1') $afterArticle .= $varFields['{readmore}'];

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
