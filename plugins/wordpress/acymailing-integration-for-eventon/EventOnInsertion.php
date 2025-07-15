<?php

use AcyMailing\Helpers\TabHelper;

trait EventOnInsertion
{
    private $eventOnMeta;
    private $recurringEvents;

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
                    WHERE post.post_type = "ajde_events" 
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
        $this->prepareWPCategories('event_type');

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
        $this->displaySelectionZone($zoneContent);
        $this->pluginHelper->displayOptions($displayOptions, $identifier, 'individual', $this->defaultValues);

        $tabHelper->endTab();
        $identifier = 'auto'.$this->name;
        // phpcs:ignore WordPress.WP.I18n.TextDomainMismatch
        $tabHelper->startTab(__('Event Type', 'eventon'), !empty($this->defaultValues->defaultPluginTab) && $identifier === $this->defaultValues->defaultPluginTab);

        $catOptions = [
            [
                'title' => 'ACYM_ORDER_BY',
                'type' => 'select',
                'name' => 'order',
                'options' => [
                    'ID' => 'ACYM_DATE_CREATED',
                    'meta.meta_value' => 'ACYM_DATE',
                    'post_title' => 'ACYM_TITLE',
                    'menu_order' => 'ACYM_MENU_ORDER',
                    'rand' => 'ACYM_RANDOM',
                ],
                'default' => 'meta.meta_value',
                'defaultdir' => 'asc',
            ],
        ];
        $this->autoContentOptions($catOptions, 'event');

        $this->autoCampaignOptions($catOptions);

        $displayOptions = array_merge($displayOptions, $catOptions);

        $this->displaySelectionZone($this->getCategoryListing());
        $this->pluginHelper->displayOptions($displayOptions, $identifier, 'grouped', $this->defaultValues);

        $tabHelper->endTab();

        // Reload the categories for the listing
        $this->prepareWPCategories('event_type_2');
        $this->getCategoryFilter();

        $identifier = 'auto2'.$this->name;
        // phpcs:ignore WordPress.WP.I18n.TextDomainMismatch
        $tabHelper->startTab(__('Event Type 2', 'eventon'), !empty($this->defaultValues->defaultPluginTab) && $identifier === $this->defaultValues->defaultPluginTab);

        $this->displaySelectionZone($this->getCategoryListing());
        $this->pluginHelper->displayOptions($displayOptions, $identifier, 'grouped', $this->defaultValues);

        $tabHelper->endTab();

        $tabHelper->display('plugin');
    }

    public function prepareListing()
    {
        $this->querySelect = 'SELECT post.ID, post.post_title, post.post_date, meta.meta_value ';
        $this->query = 'FROM #__posts AS post ';
        $this->query .= 'JOIN #__postmeta AS meta ON post.ID = meta.post_id AND meta.meta_key = "evcal_srow" ';
        $this->filters = [];
        $this->filters[] = 'post.post_type = "ajde_events"';
        $this->filters[] = 'post.post_status = "publish"';
        $this->searchFields = ['post.ID', 'post.post_title'];
        $this->pageInfo->order = 'post.ID';
        $this->elementIdTable = 'post';
        $this->elementIdColumn = 'ID';

        if ($this->getParam('hidepast', '1') === '1') {
            $this->filters[] = 'meta.`meta_value` >= '.time();
        }

        parent::prepareListing();

        //if a category is selected
        if (!empty($this->pageInfo->filter_cat)) {
            $this->query .= 'JOIN #__term_relationships AS cat ON post.ID = cat.object_id';
            $this->filters[] = 'cat.term_taxonomy_id = '.intval($this->pageInfo->filter_cat);
        }

        $events = $this->getElements();
        $this->includeRecurringEventsForSelection($events);
        foreach ($events as &$oneEvent) {
            $oneEvent->ID .= '-'.$oneEvent->meta_value;
        }

        $listingOptions = [
            'header' => [
                'post_title' => [
                    'label' => 'ACYM_TITLE',
                    'size' => '7',
                ],
                // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
                'meta_value' => [
                    'label' => 'ACYM_DATE',
                    'size' => '4',
                    'type' => 'date',
                ],
                'ID' => [
                    'label' => 'ACYM_ID',
                    'size' => '1',
                    'type' => 'int',
                    'class' => 'text-center',
                ],
            ],
            'id' => 'ID',
            'rows' => $events,
        ];

        return $this->getElementsListing($listingOptions);
    }

    private function includeRecurringEventsForSelection(&$events)
    {
        $querySelect = 'SELECT post.ID, post.post_title, post.post_date, meta.meta_value ';
        $query = 'FROM #__posts AS post JOIN #__postmeta AS meta ON post.ID = meta.post_id AND meta.meta_key = "repeat_intervals" ';

        $where = [];
        $where[] = 'post.post_type = "ajde_events"';
        $where[] = 'post.post_status = "publish"';

        if (!empty($this->pageInfo->filter_cat)) {
            $query .= 'JOIN #__term_relationships AS cat ON post.ID = cat.object_id ';
            $where[] = 'cat.term_taxonomy_id = '.intval($this->pageInfo->filter_cat);
        }

        if (!empty($this->pageInfo->search) && !empty($this->searchFields)) {
            $searchVal = '%'.acym_getEscaped($this->pageInfo->search, true).'%';
            $where[] = implode(' LIKE '.acym_escapeDB($searchVal).' OR ', $this->searchFields).' LIKE '.acym_escapeDB($searchVal);
        }

        $conditions = ' WHERE ('.implode(') AND (', $where).')';

        $rows = acym_loadObjectList($querySelect.$query.$conditions, '', $this->pageInfo->start, $this->pageInfo->limit);
        if (empty($rows)) return;

        $hidePast = $this->getParam('hidepast', '1') === '1';
        $time = time();

        foreach ($rows as $oneRepeatingEvent) {
            $oneRepeatingEvent->meta_value = unserialize($oneRepeatingEvent->meta_value);
            foreach ($oneRepeatingEvent->meta_value as $oneOccurrence) {
                if ($hidePast && $oneOccurrence[0] < $time) {
                    continue;
                }

                $newEntry = (object)[
                    'ID' => $oneRepeatingEvent->ID,
                    'post_title' => $oneRepeatingEvent->post_title,
                    'post_date' => $oneRepeatingEvent->post_date,
                    // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
                    'meta_value' => $oneOccurrence[0],
                ];

                // Repeating events contain the main event, don't duplicate it
                if (!in_array($newEntry, $events)) {
                    $events[] = $newEntry;
                }
            }
        }

        usort(
            $events,
            function ($a, $b) {
                return $a->meta_value > $b->meta_value ? -1 : 1;
            }
        );
    }

    public function replaceContent(&$email)
    {
        $this->recurringEvents = [];
        $this->replaceMultiple($email);
        $this->eventOnMeta = acym_getCMSConfig('evo_tax_meta');
        $this->replaceOne($email);
    }

    public function generateByCategory(&$email)
    {
        $this->tags = [];
        $time = time();

        foreach (['auto', 'auto2'] as $categoryType) {
            $tags = $this->pluginHelper->extractTags($email, $categoryType.$this->name);
            if (empty($tags)) continue;

            foreach ($tags as $oneTag => $parameter) {
                if (isset($this->tags[$oneTag])) continue;

                $parameter->from = empty($parameter->from) ? $time : acym_replaceDate($parameter->from);
                if (!empty($parameter->to)) $parameter->to = acym_replaceDate($parameter->to);

                $query = 'SELECT DISTINCT post.`ID`, meta.meta_value, post.`post_title`, post.`menu_order` FROM #__posts AS post ';

                $where = [];

                $selectedArea = $this->getSelectedArea($parameter);
                if (!empty($selectedArea)) {
                    $query .= 'JOIN #__term_relationships AS cat ON post.ID = cat.object_id ';
                    $where[] = 'cat.term_taxonomy_id IN ('.implode(',', $selectedArea).')';
                }

                $where[] = 'post.post_type = "ajde_events"';
                $where[] = 'post.post_status = "publish"';

                $query .= ' JOIN #__postmeta AS meta ON post.ID = meta.post_id AND meta.meta_key =';
                $queryRecurring = $query.' "repeat_intervals" WHERE ('.implode(') AND (', $where).')';
                $query .= ' "evcal_srow" ';

                $where[] = 'meta.`meta_value` >= '.intval($parameter->from);

                if (!empty($parameter->to)) {
                    $where[] = 'meta.meta_value <= '.intval($parameter->to);
                }

                if (!empty($parameter->onlynew)) {
                    $lastGenerated = $this->getLastGenerated($email->id);
                    if (!empty($lastGenerated)) {
                        $where[] = 'meta.meta_value > '.intval($lastGenerated);
                    }
                }

                $query .= ' WHERE ('.implode(') AND (', $where).')';

                $this->handleOrderBy($query, $parameter, 'post');
                $this->handleMax($query, $parameter);
                $elements = acym_loadObjectList($query);
                $repeatingEvents = acym_loadObjectList($queryRecurring);

                if (!empty($repeatingEvents)) {
                    $elements = $this->includeRecurringEvents($elements, $repeatingEvents, $parameter);
                }

                $this->tags[$oneTag] = $this->formatIndividualTags($elements, $parameter);
            }
        }

        return $this->generateCampaignResult;
    }

    private function includeRecurringEvents($elements, $repeatingEvents, $parameter)
    {
        // Add matching repeating events into the selected events
        foreach ($repeatingEvents as &$oneRepeatingEvent) {
            $oneRepeatingEvent->meta_value = unserialize($oneRepeatingEvent->meta_value);
            foreach ($oneRepeatingEvent->meta_value as $oneOccurrence) {
                // Handle From limit
                if ($oneOccurrence[0] < $parameter->from) {
                    continue;
                }

                // Handle To limit
                if (!empty($parameter->to) && $oneOccurrence[0] > $parameter->to) {
                    continue;
                }

                // Handle auto-campaigns
                if (!empty($parameter->onlynew) && !empty($lastGenerated) && $oneOccurrence[0] <= $lastGenerated) {
                    continue;
                }

                $newEntry = (object)[
                    'ID' => $oneRepeatingEvent->ID,
                    // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
                    'meta_value' => $oneOccurrence[0],
                    'post_title' => $oneRepeatingEvent->post_title,
                    'menu_order' => $oneRepeatingEvent->menu_order,
                ];

                // Repeating events contain the main event, don't duplicate it
                if (!in_array($newEntry, $elements)) {
                    $elements[] = $newEntry;
                }
            }
        }

        // Handle ordering again with the additional entries
        if (!empty($parameter->order)) {
            $ordering = explode(',', $parameter->order);
            if ($ordering[0] === 'meta.meta_value') {
                $ordering[0] = 'meta_value';
            }

            usort(
                $elements,
                function ($a, $b) use ($ordering) {
                    $orderColumn = $ordering[0];

                    if ($orderColumn === 'rand') {
                        return wp_rand(0, 1) === 0 ? 1 : -1;
                    }

                    if (strtolower($ordering[1]) === 'asc') {
                        $greater = 1;
                        $lower = -1;
                    } else {
                        $greater = -1;
                        $lower = 1;
                    }

                    if ($a->$orderColumn > $b->$orderColumn) {
                        return $greater;
                    }

                    // If the ordering value is the same, order by date
                    if ($a->$orderColumn === $b->$orderColumn) {
                        return $a->meta_value > $b->meta_value ? $greater : $lower;
                    }

                    return $lower;
                }
            );
        }

        // We added new events then ordered them, make sure we don't exceed the required number of events
        $elements = array_slice($elements, 0, $parameter->max);

        return $elements;
    }

    /**
     * For this plugin this method will change a bit to include the dates of the recurring event entries in addition to the event ID
     *
     * @param $elements
     * @param $parameter
     *
     * @return array
     */
    protected function buildIndividualTags($elements, $parameter): array
    {
        $arrayElements = [];
        unset($parameter->id);

        $i = 0;
        foreach ($elements as $oneElement) {
            $args = [];
            $args[] = $this->name.':'.$oneElement->ID.'-'.$oneElement->meta_value;

            foreach ($parameter as $oneParam => $val) {
                if (is_bool($val)) {
                    $args[] = $oneParam;
                } else {
                    $args[] = $oneParam.':'.$val;
                }
            }

            if ($i % 2 === 1 && !empty($parameter->alternate)) {
                $args[] = 'invert';
            }

            $arrayElements[] = '{'.implode('| ', $args).'}';

            $i++;
        }

        return $arrayElements;
    }

    public function replaceIndividualContent($tag)
    {
        $dashPos = strpos($tag->id, '-');
        $startDate = substr($tag->id, $dashPos + 1);
        $tag->id = substr($tag->id, 0, $dashPos);

        $query = 'SELECT post.*
                    FROM #__posts AS post
                    WHERE post.post_type = "ajde_events" 
                        AND post.post_status = "publish"
                        AND post.ID = '.intval($tag->id);

        $element = $this->initIndividualContent($tag, $query);

        if (empty($element)) return '';
        $varFields = $this->getCustomLayoutVars($element);

        $properties = acym_loadObjectList('SELECT meta_key, meta_value AS `value` FROM #__postmeta WHERE post_id = '.intval($tag->id), 'meta_key');
        foreach ($properties as $name => $property) {
            $varFields['{'.$name.'}'] = $property->value;
        }

        $link = get_post_meta($tag->id, 'evcal_exlink', true);
        if (empty($link)) {
            $link = get_permalink($element->ID);
        }
        $varFields['{link}'] = $link;

        $title = '';
        $afterTitle = '';
        $afterArticle = '';
        $imagePath = '';
        $contentText = '';
        $customFields = [];

        $varFields['{title}'] = $element->post_title;
        if (in_array('title', $tag->display)) $title = $varFields['{title}'];

        if (in_array('subtitle', $tag->display) && !empty($properties['evcal_subtitle']->value)) {
            $afterTitle .= $properties['evcal_subtitle']->value;
        }

        $imageId = get_post_thumbnail_id($tag->id);
        if (!empty($imageId)) {
            $imagePath = get_the_post_thumbnail_url($tag->id);
        }
        $varFields['{image}'] = $imagePath;
        // phpcs:ignore PluginCheck.CodeAnalysis.ImageFunctions.NonEnqueuedImage
        $varFields['{picthtml}'] = '<img alt="" src="'.$imagePath.'">';
        if (!in_array('image', $tag->display)) $imagePath = '';

        $varFields['{full}'] = $this->cleanExtensionContent($element->post_content);
        $varFields['{intro}'] = $this->cleanExtensionContent($this->getIntro($element->post_content));
        if (in_array('full', $tag->display)) {
            $contentText .= $varFields['{full}'];
        } elseif (in_array('intro', $tag->display)) {
            $contentText .= $varFields['{intro}'];
        }

        $allday = !empty($properties['evcal_allday']->value) && $properties['evcal_allday']->value === 'yes';

        $properties['evcal_srow']->value = $startDate;
        if (!isset($this->recurringEvents[$tag->id])) {
            $this->recurringEvents[$tag->id] = get_post_meta($tag->id, 'repeat_intervals', true);
        }

        if (!empty($this->recurringEvents[$tag->id])) {
            foreach ($this->recurringEvents[$tag->id] as $oneOccurrence) {
                if (intval($oneOccurrence[0]) === intval($startDate)) {
                    $properties['evcal_erow']->value = $oneOccurrence[1];
                    break;
                }
            }
        }

        $startDate = $properties['evcal_srow']->value;
        $endDate = empty($properties['evcal_erow']->value) ? '' : $properties['evcal_erow']->value;


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

        if (in_array('date', $tag->display)) {
            $customFields[] = [
                $varFields['{date}'],
                acym_translation('ACYM_DATE'),
            ];
        }

        $location = get_the_terms($tag->id, 'event_location');
        $varFields['{location}'] = '';
        if (!empty($location[0]->term_id) && !empty($this->eventOnMeta['event_location'][$location[0]->term_id])) {
            $locationData = $this->eventOnMeta['event_location'][$location[0]->term_id];

            if (empty($locationData['evcal_location_link'])) {
                $googleMapsSearch = [];
                if (empty($locationData['location_address'])) {
                    $varFields['{location}'] = $location[0]->name;
                } else {
                    $googleMapsSearch[] = $locationData['location_address'];
                    if (!empty($locationData['location_state'])) $googleMapsSearch[] = $locationData['location_state'];
                    if (!empty($locationData['location_city'])) $googleMapsSearch[] = $locationData['location_city'];
                    if (!empty($locationData['location_country'])) $googleMapsSearch[] = $locationData['location_country'];

                    $url = 'https://maps.google.com/?q='.urlencode(implode(' ', $googleMapsSearch));
                    $varFields['{location}'] = '<a href="'.$url.'" target="_blank">'.$location[0]->name.'</a>';
                }
            } else {
                $varFields['{location}'] = '<a href="'.$locationData['evcal_location_link'].'" target="_blank">'.$location[0]->name.'</a>';
            }

            if (in_array('location', $tag->display)) {
                $customFields[] = [
                    $varFields['{location}'],
                    acym_translation('ACYM_LOCATION'),
                ];
            }
        }

        $organiser = get_the_terms($tag->id, 'event_organizer');
        $varFields['{organiser}'] = '';
        if (!empty($organiser[0]->name)) {
            $varFields['{organiser}'] = $organiser[0]->name;
            if (!empty($organiser[0]->term_id) && !empty($this->eventOnMeta['event_organizer'][$organiser[0]->term_id]['term_name'])) {
                $varFields['{organiser}'] = $this->eventOnMeta['event_organizer'][$organiser[0]->term_id]['term_name'];
            }
        }

        if (in_array('organiser', $tag->display) && !empty($varFields['{organiser}'])) {
            $customFields[] = [
                $varFields['{organiser}'],
                acym_translation('ACYM_ORGANIZER'),
            ];
        }

        $varFields['{price}'] = '';
        if (isset($properties['_seo_offer_price']->value) && strlen($properties['_seo_offer_price']->value) > 0) {
            $price = $properties['_seo_offer_price']->value;
            $symbol = empty($properties['_seo_offer_currency']->value) ? '' : $properties['_seo_offer_currency']->value;

            if ($this->getParam('currency_position', 'after') === 'after') {
                $price .= $symbol;
            } else {
                $price = $symbol.$price;
            }
            $varFields['{price}'] = $price;
        }
        if (in_array('price', $tag->display) && strlen($varFields['{price}']) > 0) {
            $customFields[] = [
                $varFields['{price}'],
                acym_translation('ACYM_PRICE'),
            ];
        }

        $varFields['{evtype}'] = get_the_term_list($tag->id, 'event_type', '', ', ');
        if (in_array('evtype', $tag->display) && !empty($varFields['{evtype}'])) {
            $customFields[] = [
                $varFields['{evtype}'],
                // phpcs:ignore WordPress.WP.I18n.TextDomainMismatch
                __('Event Type', 'eventon'),
            ];
        }

        $varFields['{evtype2}'] = get_the_term_list($tag->id, 'event_type_2', '', ', ');
        if (in_array('evtype2', $tag->display) && !empty($varFields['{evtype2}'])) {
            $customFields[] = [
                $varFields['{evtype2}'],
                // phpcs:ignore WordPress.WP.I18n.TextDomainMismatch
                __('Event Type 2', 'eventon'),
            ];
        }

        $varFields['{tags}'] = get_the_term_list($tag->id, 'post_tag', '', ', ');
        if (in_array('tags', $tag->display) && !empty($varFields['{tags}'])) {
            $customFields[] = [
                $varFields['{tags}'],
                acym_translation('ACYM_TAGS'),
            ];
        }

        $readmoreLink = get_post_meta($tag->id, 'evcal_lmlink', true);
        if (empty($readmoreLink)) {
            $readmoreLink = $link;
        }
        $varFields['{readmore}'] = '<a class="acymailing_readmore_link" style="text-decoration:none;" target="_blank" href="'.$readmoreLink.'">
                <span class="acymailing_readmore">'.acym_escape(acym_translation('ACYM_READ_MORE')).'</span>
            </a>';
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
