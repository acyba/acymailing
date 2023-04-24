<?php

use AcyMailing\Helpers\TabHelper;

trait EventBookingInsertion
{
    private $eventbookingconfig;

    public function getStandardStructure(&$customView)
    {
        $tag = new stdClass();
        $tag->id = 0;

        $format = new stdClass();
        $format->tag = $tag;
        $format->title = '{title}';
        $format->afterTitle = '{price}<br/>Start date: {sdate}<br/>End Date: {edate}';
        $format->afterArticle = '';
        $format->imagePath = '{image}';
        $format->description = '{short}';
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
            'individualregbutton' => [acym_translation('EB_REGISTER_INDIVIDUAL')],
            'groupregbutton' => [acym_translation('EB_REGISTER_GROUP')],
        ];
    }

    public function initElementOptionsCustomView()
    {
        $query = 'SELECT event.*, location.name AS location_name FROM `#__eb_events` AS event ';
        $query .= 'LEFT JOIN `#__eb_locations` AS location ON event.location_id = location.id ';
        $element = acym_loadObject($query);
        if (empty($element)) return;
        foreach ($element as $key => $value) {
            $this->elementOptions[$key] = [$key];
        }
    }

    public function insertionOptions($defaultValues = null)
    {
        $this->defaultValues = $defaultValues;

        acym_loadLanguageFile('com_eventbooking', JPATH_SITE);
        acym_loadLanguageFile('com_eventbookingcommon', JPATH_ADMINISTRATOR);
        $this->categories = acym_loadObjectList('SELECT `id`, `parent` AS `parent_id`, `name` AS `title` FROM `#__eb_categories` WHERE published = 1', 'id');

        $tabHelper = new TabHelper();
        $identifier = $this->name;
        $tabHelper->startTab(acym_translation('ACYM_ONE_BY_ONE'), !empty($this->defaultValues->defaultPluginTab) && $identifier === $this->defaultValues->defaultPluginTab);

        $displayOptions = [
            [
                'title' => 'ACYM_DISPLAY',
                'type' => 'checkbox',
                'name' => 'display',
                'options' => [
                    'title' => ['EB_TITLE', true],
                    'price' => ['EB_PRICE', true],
                    'price_text' => ['EB_PRICE_TEXT', false],
                    'sdate' => ['EB_EVENT_DATE', true],
                    'edate' => ['EB_EVENT_END_DATE', true],
                    'image' => ['EB_EVENT_IMAGE', true],
                    'short' => ['EB_SHORT_DESCRIPTION', true],
                    'desc' => ['EB_DESCRIPTION', false],
                    'cats' => ['EB_CATEGORIES', false],
                    'location' => ['ACYM_LOCATION', true],
                    'capacity' => ['EB_CAPACITY', false],
                    'regstart' => ['EB_REGISTRATION_START_DATE', false],
                    'cut' => ['EB_CUT_OFF_DATE', false],
                    'indiv' => ['EB_REGISTER_INDIVIDUAL', false],
                    'group' => ['EB_REGISTER_GROUP', false],
                ],
            ],
        ];

        if (file_exists(JPATH_ROOT.DS.'components'.DS.'com_eventbooking'.DS.'fields.xml')) {
            $xml = simplexml_load_file(JPATH_ROOT.'/components/com_eventbooking/fields.xml');
            if (!empty($xml->fields)) {
                $fields = $xml->fields->fieldset->children();
                $customFields = [];
                foreach ($fields as $oneCustomField) {
                    $name = $oneCustomField->attributes()->name;
                    $label = acym_translation($oneCustomField->attributes()->label);
                    $customFields["$name"] = [$label, false];
                }

                $displayOptions[] = [
                    'title' => 'ACYM_CUSTOM_FIELDS',
                    'type' => 'checkbox',
                    'name' => 'custom',
                    'options' => $customFields,
                ];
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
                'title' => 'ACYM_ORDER_BY',
                'type' => 'select',
                'name' => 'order',
                'options' => [
                    'id' => 'ACYM_ID',
                    'event_date' => 'ACYM_DATE',
                    'cut_off_date' => 'EB_CUT_OFF_DATE',
                    'title' => 'ACYM_TITLE',
                    'rand' => 'ACYM_RANDOM',
                ],
                'default' => 'event_date',
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
        $this->query = 'FROM `#__eb_events` AS event ';
        $this->filters = [];
        $this->filters[] = 'event.published = 1';
        $this->searchFields = ['event.id', 'event.title'];
        $this->pageInfo->order = 'event.event_date';
        $this->elementIdTable = 'event';
        $this->elementIdColumn = 'id';

        if (!acym_isAdmin() && $this->getParam('front', 'all') === 'author') {
            $this->filters[] = 'event.created_by = '.intval(acym_currentUserId());
        }

        if ($this->getParam('hidepast', '1') === '1') {
            $this->filters[] = 'event.`event_date` >= '.acym_escapeDB(date('Y-m-d H:i:s'));
        }

        parent::prepareListing();

        if (!empty($this->pageInfo->filter_cat)) {
            $this->query .= 'JOIN `#__eb_event_categories` AS cat ON event.id = cat.event_id ';
            $this->filters[] = 'cat.category_id = '.intval($this->pageInfo->filter_cat);
        }

        $listingOptions = [
            'header' => [
                'title' => [
                    'label' => 'ACYM_TITLE',
                    'size' => '8',
                ],
                'event_date' => [
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
        // Load the eventbooking data
        acym_loadLanguageFile('com_eventbooking', JPATH_SITE);
        acym_loadLanguageFile('com_eventbookingcommon', JPATH_ADMINISTRATOR);

        // We need the helper to format the price
        if (!include_once JPATH_ROOT.'/components/com_eventbooking/helper/helper.php') {
            if (acym_isAdmin()) acym_enqueueMessage('Could not load the Events Booking helper', 'notice');

            return false;
        }

        $this->eventbookingconfig = EventBookingHelper::getConfig();

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

            $query = 'SELECT DISTINCT event.id FROM `#__eb_events` AS event ';

            $where = [];
            $where[] = 'event.`published` = 1';

            $selectedArea = $this->getSelectedArea($parameter);
            if (!empty($selectedArea)) {
                $query .= 'JOIN `#__eb_event_categories` AS cat ON event.id = cat.event_id ';
                $where[] = 'cat.category_id IN ('.implode(',', $selectedArea).')';
            }

            if ((empty($parameter->mindelay) || substr($parameter->mindelay, 0, 1) != '-') && (empty($parameter->delay) || substr($parameter->delay, 0, 1) != '-')) {
                if (!empty($parameter->addcurrent)) {
                    //not finished and next events
                    $where[] = 'event.`event_end_date` >= '.acym_escapeDB($parameter->from);
                } else {
                    //not started events
                    $where[] = 'event.`event_date` >= '.acym_escapeDB($parameter->from);
                }
            }

            //should we display only events starting in the sending day ?
            if (!empty($parameter->todaysevent)) {
                $where[] = 'event.`event_date` <= '.acym_escapeDB(date('Y-m-d 23:59:59', $time));
            }

            if (!empty($parameter->mindelay)) $where[] = 'event.`event_date` >= '.acym_escapeDB(date('Y-m-d H:i:s', $time + $parameter->mindelay));
            if (!empty($parameter->delay)) $where[] = 'event.`event_date` <= '.acym_escapeDB(date('Y-m-d H:i:s', $time + $parameter->delay));
            if (!empty($parameter->to)) $where[] = 'event.`event_date` <= '.acym_escapeDB($parameter->to);

            if (!empty($parameter->onlynew)) {
                $lastGenerated = $this->getLastGenerated($email->id);
                if (!empty($lastGenerated)) {
                    $where[] = 'event.event_date > '.acym_escapeDB(acym_date($lastGenerated, 'Y-m-d H:i:s', false));
                }
            }

            $query .= ' WHERE ('.implode(') AND (', $where).')';

            $this->tags[$oneTag] = $this->finalizeCategoryFormat($query, $parameter, 'event');
        }

        return $this->generateCampaignResult;
    }

    public function replaceIndividualContent($tag)
    {
        acym_loadLanguageFile('com_eventbooking', JPATH_SITE, $this->emailLanguage);
        acym_loadLanguageFile('com_eventbookingcommon', JPATH_ADMINISTRATOR, $this->emailLanguage);

        $query = 'SELECT location.*, event.*, location.name AS location_name FROM `#__eb_events` AS event ';
        $query .= 'LEFT JOIN `#__eb_locations` AS location ON event.location_id = location.id ';
        $query .= 'WHERE event.id = '.intval($tag->id);

        $element = $this->initIndividualContent($tag, $query);

        if (empty($element)) return '';

        $varFields = $this->getCustomLayoutVars($element);

        $tag->custom = empty($tag->custom) ? [] : explode(',', $tag->custom);

        $link = 'index.php?option=com_eventbooking&view=event&id='.intval($tag->id);

        //Get the related menu item if specified
        $menuId = $this->getParam('itemid');
        if (!empty($menuId)) {
            $link .= '&Itemid='.intval($menuId);
        }

        $link = $this->finalizeLink($link);
        $varFields['{link}'] = $link;

        $title = '';
        $afterTitle = '';
        $afterArticle = '';

        $imagePath = '';
        $contentText = '';
        $customFields = [];

        $languageMethod = 'title_'.substr($this->emailLanguage, 0, 2);
        $varFields['{title}'] = !empty($element->$languageMethod) ? $element->$languageMethod : $element->title;
        if (in_array('title', $tag->display)) $title = $varFields['{title}'];

        $languageMethod = 'short_description_'.substr($this->emailLanguage, 0, 2);
        $varFields['{short}'] = !empty($element->$languageMethod) ? $element->$languageMethod : $element->short_description;
        if (in_array('short', $tag->display)) $contentText .= $varFields['{short}'];

        $languageMethod = 'description_'.substr($this->emailLanguage, 0, 2);
        $varFields['{desc}'] = !empty($element->$languageMethod) ? $element->$languageMethod : $element->description;
        if (in_array('desc', $tag->display)) $contentText .= $varFields['{desc}'];

        $varFields['{image}'] = acym_frontendLink($element->image, false);
        if (in_array('image', $tag->display) && !empty($element->image)) $imagePath = $varFields['{image}'];

        $varFields['{sdate}'] = '';
        if ($element->event_date > '0001-00-00') $varFields['{sdate}'] = acym_date($element->event_date, $this->eventbookingconfig->event_date_format, false);
        if (in_array('sdate', $tag->display) && $element->event_date > '0001-00-00') {
            $customFields[] = [$varFields['{sdate}'], acym_translation('EB_EVENT_DATE')];
        }

        $varFields['edate'] = '';
        if ($element->event_end_date > '0001-00-00') $varFields['edate'] = acym_date($element->event_end_date, $this->eventbookingconfig->event_date_format, false);
        if (in_array('edate', $tag->display) && $element->event_end_date > '0001-00-00') {
            $customFields[] = [$varFields['edate'], acym_translation('EB_EVENT_END_DATE')];
        }

        $varFields['{location}'] = '';
        $languageMethod = 'name_'.substr($this->emailLanguage, 0, 2);
        $languageName = !empty($element->$languageMethod) ? $element->$languageMethod : $element->location_name;
        if (!empty($element->location_id)) $varFields['{location}'] = '<a href="index.php?option=com_eventbooking&view=map&format=html&location_id='.$element->location_id.'">'.$languageName.'</a>';
        if (in_array('location', $tag->display) && !empty($element->location_id)) {
            $customFields[] = [$varFields['{location}'], acym_translation('ACYM_LOCATION')];
        }


        $categories = acym_loadObjectList(
            'SELECT cat.*
                FROM #__eb_categories AS cat 
                JOIN #__eb_event_categories AS eventcats ON cat.id = eventcats.category_id 
                WHERE eventcats.event_id = '.intval($tag->id).' 
                ORDER BY cat.name ASC'
        );

        foreach ($categories as $i => $oneCat) {
            $categories[$i] =
                '<a target="_blank" href="index.php?option=com_eventbooking&view=category&id='.$oneCat->id.'">'.acym_escape(
                    !empty($oneCat->$languageMethod) ? $oneCat->$languageMethod : $oneCat->name
                ).'</a>';
        }
        $varFields['{cats}'] = implode(', ', $categories);
        if (in_array('cats', $tag->display)) {
            $customFields[] = [$varFields['{cats}'], acym_translation('ACYM_CATEGORIES')];
        }


        $varFields['{capacity}'] = empty($element->event_capacity) ? acym_translation('EB_UNLIMITED') : $element->event_capacity;
        if (in_array('capacity', $tag->display)) {
            $customFields[] = [$varFields['{capacity}'], acym_translation('EB_CAPACTIY')];
        }

        $languageMethod = 'price_'.substr($this->emailLanguage, 0, 2);
        if (!empty($element->$languageMethod)) {
            $varFields['{price}'] = $element->$languageMethod;
        } elseif ($element->individual_price > 0) {
            $varFields['{price}'] = @EventBookingHelper::formatCurrency($element->individual_price, $this->eventbookingconfig, $element->currency_symbol);
        } else {
            $varFields['{price}'] = acym_translation('EB_FREE');
        }
        if (in_array('price', $tag->display)) {
            $customFields[] = [$varFields['{price}'], acym_translation('EB_PRICE')];
        }

        $languageMethod = 'price_text_'.substr($this->emailLanguage, 0, 2);
        if (!empty($element->$languageMethod)) {
            $varFields['{price_text}'] = $element->$languageMethod;
        } elseif (!empty($element->price_text)) {
            $varFields['{price_text}'] = $element->price_text;
        } else {
            $varFields['{price_text}'] = acym_translation('EB_FREE');
        }
        if (in_array('price_text', $tag->display)) {
            $customFields[] = [$varFields['{price_text}'], acym_translation('EB_PRICE_TEXT')];
        }

        if (!empty($tag->custom) && !empty($element->custom_fields)) {
            $customFields = array_merge($customFields, $this->_handleCustomFields($element->custom_fields, $tag->custom));
        }

        $varFields['{regstart}'] = '';
        if ($element->registration_start_date > '0001-00-00') {
            $varFields['{regstart}'] = acym_date(
                $element->registration_start_date,
                $this->eventbookingconfig->date_format,
                false
            );
        }
        if (in_array('regstart', $tag->display) && $element->registration_start_date > '0001-00-00') {
            $customFields[] = [$varFields['{regstart}'], acym_translation('EB_REGISTRATION_START_DATE')];
        }

        $varFields['{cut}'] = '';
        if ($element->cut_off_date > '0001-00-00') $varFields['{cut}'] = acym_date($element->cut_off_date, $this->eventbookingconfig->date_format, false);
        if (in_array('cut', $tag->display) && $element->cut_off_date > '0001-00-00') {
            $customFields[] = [$varFields['{cut}'], acym_translation('EB_CUT_OFF_DATE')];
        }

        $varFields['{indiv}'] = [];
        if (in_array('indiv', $tag->display)) {
            $reglink = acym_frontendLink('index.php?option=com_eventbooking&task=register.individual_registration&event_id='.$tag->id, false);
            $varFields['{individualregbutton}'] = '<a class="event_registration eb_indivreg" href="'.$reglink.'" target="_blank">'.acym_translation(
                    'EB_REGISTER_INDIVIDUAL'
                ).'</a> ';
            $customFields[] = [$varFields['{individualregbutton}']];
        }

        if (in_array('group', $tag->display)) {
            $reglink = acym_frontendLink('index.php?option=com_eventbooking&task=register.group_registration&event_id='.$tag->id, false);
            $varFields['{groupregbutton}'] = '<a class="event_registration eb_groupreg" href="'.$reglink.'" target="_blank">'.acym_translation('EB_REGISTER_GROUP').'</a> ';
            $customFields[] = [$varFields['{groupregbutton}']];
        }
        $varFields['{indiv}'] = implode(' ', $varFields['{indiv}']);
        if (in_array('indiv', $tag->display) || in_array('group', $tag->display)) {
            $customFields[] = [$varFields['{indiv}']];
        }

        $varFields['{readmore}'] = '<a class="acymailing_readmore_link" style="text-decoration:none;" target="_blank" href="'.$link.'"><span class="acymailing_readmore">'.acym_translation(
                'ACYM_READ_MORE'
            ).'</span></a>';
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

    private function _handleCustomFields($customFields, $selected)
    {
        $result = [];

        // Load the fields and their values
        if (!file_exists(JPATH_ROOT.DS.'components'.DS.'com_eventbooking'.DS.'fields.xml')) return $result;

        $xml = simplexml_load_file(JPATH_ROOT.'/components/com_eventbooking/fields.xml');
        $fields = $xml->fields->fieldset->children();
        $params = new JRegistry();
        $params->loadString($customFields, 'INI');
        $decodedFields = json_decode($customFields);

        foreach ($fields as $oneCustomField) {
            $name = $oneCustomField->attributes()->name;
            $label = acym_translation($oneCustomField->attributes()->label);
            $value = $params->get($name);
            $name = (string)$name;

            if ($value === null && !empty($decodedFields) && !empty($decodedFields->$name)) {
                $value = $decodedFields->$name;
            }

            if (empty($value) || !in_array($name, $selected)) continue;

            $result[] = [$value, $label];
        }

        return $result;
    }
}
