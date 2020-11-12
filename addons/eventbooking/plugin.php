<?php

use AcyMailing\Libraries\acymPlugin;
use AcyMailing\Helpers\TabHelper;

class plgAcymEventbooking extends acymPlugin
{
    var $eventbookingconfig;

    public function __construct()
    {
        parent::__construct();
        $this->cms = 'Joomla';
        $this->installed = acym_isExtensionActive('com_eventbooking');
        $this->rootCategoryId = 0;

        $this->pluginDescription->name = 'Event Booking';
        $this->pluginDescription->icon = ACYM_DYNAMICS_URL.basename(__DIR__).'/icon.png';

        if ($this->installed) {
            $this->displayOptions = [
                'title' => ['ACYM_TITLE', true],
                'price' => ['ACYM_PRICE', true],
                'sdate' => ['ACYM_DATE', true],
                'edate' => ['EB_EVENT_END_DATE', true],
                'image' => ['ACYM_IMAGE', true],
                'short' => ['ACYM_SHORT_DESCRIPTION', true],
                'desc' => ['ACYM_DESCRIPTION', false],
                'cats' => ['ACYM_CATEGORIES', false],
                'location' => ['ACYM_LOCATION', true],
                'capacity' => ['EB_CAPACTIY', false],
                'regstart' => ['EB_REGISTRATION_START_DATE', false],
                'cut' => ['EB_CUT_OFF_DATE', false],
                'indiv' => ['EB_REGISTER_INDIVIDUAL', false],
                'group' => ['EB_REGISTER_GROUP', false],
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

    public function getPossibleIntegrations()
    {
        return $this->pluginDescription;
    }

    public function insertionOptions($defaultValues = null)
    {
        $this->defaultValues = $defaultValues;

        acym_loadLanguageFile('com_eventbooking', JPATH_SITE);
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
                    'title' => ['ACYM_TITLE', true],
                    'price' => ['ACYM_PRICE', true],
                    'sdate' => ['ACYM_DATE', true],
                    'edate' => ['EB_EVENT_END_DATE', true],
                    'image' => ['ACYM_IMAGE', true],
                    'short' => ['ACYM_SHORT_DESCRIPTION', true],
                    'desc' => ['ACYM_DESCRIPTION', false],
                    'cats' => ['ACYM_CATEGORIES', false],
                    'location' => ['ACYM_LOCATION', true],
                    'capacity' => ['EB_CAPACTIY', false],
                    'regstart' => ['EB_REGISTRATION_START_DATE', false],
                    'cut' => ['EB_CUT_OFF_DATE', false],
                    'indiv' => ['EB_REGISTER_INDIVIDUAL', false],
                    'group' => ['EB_REGISTER_GROUP', false],
                ],
            ],
        ];

        if (file_exists(JPATH_ROOT.DS.'components'.DS.'com_eventbooking'.DS.'fields.xml')) {
            $xml = JFactory::getXML(JPATH_ROOT.'/components/com_eventbooking/fields.xml');
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
                    'event_date' => 'ACYM_DATE',
                    'cut_off_date' => 'EB_CUT_OFF_DATE',
                    'title' => 'ACYM_TITLE',
                    'rand' => 'ACYM_RANDOM',
                ],
                'default' => 'event_date',
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

        // We need the helper to format the price
        if (!include_once JPATH_ROOT.'/components/com_eventbooking/helper/helper.php') {
            if (acym_isAdmin()) acym_enqueueMessage('Could not load the Event Booking helper', 'notice');

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
        $query = 'SELECT event.*, location.name AS location_name FROM `#__eb_events` AS event ';
        $query .= 'LEFT JOIN `#__eb_locations` AS location ON event.location_id = location.id ';
        $query .= 'WHERE event.id = '.intval($tag->id);

        $element = $this->initIndividualContent($tag, $query);

        if (empty($element)) return '';

        $varFields = $this->getCustomLayoutVars($element);

        $tag->custom = empty($tag->custom) ? [] : explode(',', $tag->custom);

        $link = 'index.php?option=com_eventbooking&view=event&id='.intval($tag->id);
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
        $varFields['{short}'] = $element->short_description;
        if (in_array('short', $tag->display)) $contentText .= $varFields['{short}'];
        $varFields['{desc}'] = $element->description;
        if (in_array('desc', $tag->display)) $contentText .= $varFields['{desc}'];

        $varFields['{image}'] = acym_frontendLink($element->image, false);
        if (in_array('image', $tag->display) && !empty($element->image)) $imagePath = $varFields['{image}'];

        $varFields['{sdate}'] = '';
        if ($element->event_date > '0001-00-00') $varFields['{sdate}'] = acym_date($element->event_date, $this->eventbookingconfig->event_date_format, null);
        if (in_array('sdate', $tag->display) && $element->event_date > '0001-00-00') {
            $customFields[] = [$varFields['{sdate}'], acym_translation('EB_EVENT_DATE')];
        }

        $varFields['edate'] = '';
        if ($element->event_end_date > '0001-00-00') $varFields['edate'] = acym_date($element->event_end_date, $this->eventbookingconfig->event_date_format, null);
        if (in_array('edate', $tag->display) && $element->event_end_date > '0001-00-00') {
            $customFields[] = [$varFields['edate'], acym_translation('EB_EVENT_END_DATE')];
        }

        $varFields['{location}'] = '';
        if (!empty($element->location_id)) $varFields['{location}'] = '<a href="index.php?option=com_eventbooking&view=map&format=html&location_id='.$element->location_id.'">'.$element->location_name.'</a>';
        if (in_array('location', $tag->display) && !empty($element->location_id)) {
            $customFields[] = [$varFields['{location}'], acym_translation('EB_LOCATION')];
        }


        $categories = acym_loadObjectList(
            'SELECT cat.id, cat.name
                FROM #__eb_categories AS cat 
                JOIN #__eb_event_categories AS eventcats ON cat.id = eventcats.category_id 
                WHERE eventcats.event_id = '.intval($tag->id).' 
                ORDER BY cat.name ASC'
        );

        foreach ($categories as $i => $oneCat) {
            $categories[$i] = '<a href="index.php?option=com_eventbooking&view=category&id='.$oneCat->id.'">'.acym_escape($oneCat->name).'</a>';
        }
        $varFields['{cats}'] = implode(', ', $categories);
        if (in_array('cats', $tag->display)) {
            $customFields[] = [$varFields['{cats}'], acym_translation('ACYM_CATEGORIES')];
        }


        $varFields['{capacity}'] = empty($element->event_capacity) ? acym_translation('EB_UNLIMITED') : $element->event_capacity;
        if (in_array('capacity', $tag->display)) {
            $customFields[] = [$varFields['{capacity}'], acym_translation('EB_CAPACTIY')];
        }


        if (!empty($element->price_text)) {
            $varFields['{price}'] = $element->price_text;
        } elseif ($element->individual_price > 0) {
            $varFields['{price}'] = @EventBookingHelper::formatCurrency($element->individual_price, $this->eventbookingconfig, $element->currency_symbol);
        } else {
            $varFields['{price}'] = acym_translation('EB_FREE');
        }
        if (in_array('price', $tag->display)) {
            $customFields[] = [$varFields['{price}'], acym_translation('EB_PRICE')];
        }

        if (!empty($tag->custom) && !empty($element->custom_fields)) {
            $customFields = array_merge($customFields, $this->_handleCustomFields($element->custom_fields, $tag->custom));
        }

        $varFields['{regstart}'] = '';
        if ($element->registration_start_date > '0001-00-00') $varFields['{regstart}'] = acym_date($element->registration_start_date, $this->eventbookingconfig->date_format, null);
        if (in_array('regstart', $tag->display) && $element->registration_start_date > '0001-00-00') {
            $customFields[] = [$varFields['{regstart}'], acym_translation('EB_REGISTRATION_START_DATE')];
        }

        $varFields['{cut}'] = '';
        if ($element->cut_off_date > '0001-00-00') $varFields['{cut}'] = acym_date($element->cut_off_date, $this->eventbookingconfig->date_format, null);
        if (in_array('cut', $tag->display) && $element->cut_off_date > '0001-00-00') {
            $customFields[] = [$varFields['{cut}'], acym_translation('EB_CUT_OFF_DATE')];
        }

        $varFields['{indiv}'] = [];
        if (in_array('indiv', $tag->display)) {
            $reglink = acym_frontendLink('index.php?option=com_eventbooking&task=register.individual_registration&event_id='.$tag->id, false);
            $varFields['{individualregbutton}'] = '<a class="event_registration eb_indivreg" href="'.$reglink.'" target="_blank" >'.acym_translation(
                    'EB_REGISTER_INDIVIDUAL'
                ).'</a> ';
            $varFields['{indiv}'][] = $varFields['{individualregbutton}'];
        }

        if (in_array('group', $tag->display)) {
            $reglink = acym_frontendLink('index.php?option=com_eventbooking&task=register.group_registration&event_id='.$tag->id, false);
            $varFields['{groupregbutton}'] = '<a class="event_registration eb_groupreg" href="'.$reglink.'" target="_blank" >'.acym_translation('EB_REGISTER_GROUP').'</a> ';
            $varFields['{indiv}'][] = $varFields['{groupregbutton}'];
        }
        $varFields['{indiv}'] = implode(' ', $varFields['{indiv}']);
        if (in_array('indiv', $tag->display) || in_array('group', $tag->display)) {
            $customFields[] = [implode(' ', $varFields['{indiv}'])];
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
        $format->link = empty($tag->clickable) ? '' : $link;
        $format->customFields = $customFields;
        $result = '<div class="acymailing_content">'.$this->pluginHelper->getStandardDisplay($format).'</div>';

        return $this->finalizeElementFormat($result, $tag, $varFields);
    }

    private function _handleCustomFields($customFields, $selected)
    {
        $result = [];

        // Load the fields and their values
        if (!file_exists(JPATH_ROOT.DS.'components'.DS.'com_eventbooking'.DS.'fields.xml')) return $result;

        $xml = JFactory::getXML(JPATH_ROOT.'/components/com_eventbooking/fields.xml');
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

    /**
     * Function called with ajax to search in events
     */
    public function searchEvent()
    {
        $id = acym_getVar('int', 'id');
        if (!empty($id)) {
            $subject = acym_loadResult('SELECT `title` FROM #__eb_events WHERE `id` = '.intval($id));
            if (empty($subject)) $subject = '';
            echo json_encode(['value' => $id.' - '.$subject]);
            exit;
        }

        $return = [];
        $search = acym_getVar('string', 'search', '');
        $elements = acym_loadObjectList('SELECT `id`, `title` FROM `#__eb_events` WHERE `title` LIKE '.acym_escapeDB('%'.$search.'%').' ORDER BY `title` ASC');

        foreach ($elements as $oneElement) {
            $return[] = [$oneElement->id, $oneElement->id.' - '.$oneElement->title];
        }

        echo json_encode($return);
        exit;
    }

    public function onAcymDeclareConditions(&$conditions)
    {
        acym_loadLanguageFile('com_eventbooking', JPATH_SITE);

        $conditions['user']['ebregistration'] = new stdClass();
        $conditions['user']['ebregistration']->name = acym_translation_sprintf('ACYM_COMBINED_TRANSLATIONS', 'Event Booking', acym_translation('EB_REGISTRANTS'));
        $conditions['user']['ebregistration']->option = '<div class="cell grid-x grid-margin-x">';

        $conditions['user']['ebregistration']->option .= '<div class="intext_select_automation cell">';
        $ajaxParams = json_encode(
            [
                'plugin' => __CLASS__,
                'trigger' => 'searchEvent',
            ]
        );
        $conditions['user']['ebregistration']->option .= acym_select(
            [],
            'acym_condition[conditions][__numor__][__numand__][ebregistration][event]',
            null,
            'class="acym__select acym_select2_ajax" data-placeholder="'.acym_translation('ACYM_ANY_EVENT', true).'" data-params="'.acym_escape($ajaxParams).'"'
        );
        $conditions['user']['ebregistration']->option .= '</div>';

        $status = [];
        $status[] = acym_selectOption('-1', 'ACYM_STATUS');
        $status[] = acym_selectOption('0', 'EB_PENDING');
        $status[] = acym_selectOption('1', 'EB_PAID');
        $status[] = acym_selectOption('2', 'EB_CANCELLED');

        $conditions['user']['ebregistration']->option .= '<div class="intext_select_automation cell">';
        $conditions['user']['ebregistration']->option .= acym_select(
            $status,
            'acym_condition[conditions][__numor__][__numand__][ebregistration][status]',
            '-1',
            'class="acym__select"'
        );
        $conditions['user']['ebregistration']->option .= '</div>';

        $conditions['user']['ebregistration']->option .= '</div>';

        $conditions['user']['ebregistration']->option .= '<div class="cell grid-x grid-margin-x">';
        $conditions['user']['ebregistration']->option .= acym_dateField('acym_condition[conditions][__numor__][__numand__][ebregistration][datemin]', '', 'cell shrink');
        $conditions['user']['ebregistration']->option .= '<span class="acym__content__title__light-blue acym_vcenter margin-bottom-0 cell shrink"><</span>';
        $conditions['user']['ebregistration']->option .= '<span class="acym_vcenter">'.acym_translation('EB_REGISTRATION_DATE').'</span>';
        $conditions['user']['ebregistration']->option .= '<span class="acym__content__title__light-blue acym_vcenter margin-bottom-0 cell shrink"><</span>';
        $conditions['user']['ebregistration']->option .= acym_dateField('acym_condition[conditions][__numor__][__numand__][ebregistration][datemax]', '', 'cell shrink');
        $conditions['user']['ebregistration']->option .= '</div>';
    }

    public function onAcymProcessCondition_ebregistration(&$query, $options, $num, &$conditionNotValid)
    {
        $this->processConditionFilter_ebregistration($query, $options, $num);
        $affectedRows = $query->count();
        if (empty($affectedRows)) $conditionNotValid++;
    }

    private function processConditionFilter_ebregistration(&$query, $options, $num)
    {
        $query->join['ebregistration'.$num] = '`#__eb_registrants` AS eventbooking'.$num.' ON (
                                                    eventbooking'.$num.'.email = user.email 
                                                    OR (
                                                        eventbooking'.$num.'.user_id != 0 
                                                        AND eventbooking'.$num.'.user_id = user.cms_id
                                                    )
                                                )';

        if (!empty($options['event'])) $query->where[] = 'eventbooking'.$num.'.event_id = '.intval($options['event']);
        if (!empty($options['status']) && $options['status'] != -1) $query->where[] = 'eventbooking'.$num.'.published = '.intval($options['status']);

        if (!empty($options['datemin'])) {
            $options['datemin'] = acym_replaceDate($options['datemin']);
            if (!is_numeric($options['datemin'])) $options['datemin'] = strtotime($options['datemin']);
            if (!empty($options['datemin'])) {
                $query->where[] = 'eventbooking'.$num.'.register_date > '.acym_escapeDB(date('Y-m-d H:i:s', $options['datemin']));
            }
        }

        if (!empty($options['datemax'])) {
            $options['datemax'] = acym_replaceDate($options['datemax']);
            if (!is_numeric($options['datemax'])) $options['datemax'] = strtotime($options['datemax']);
            if (!empty($options['datemax'])) {
                $query->where[] = 'eventbooking'.$num.'.register_date < '.acym_escapeDB(date('Y-m-d H:i:s', $options['datemax']));
            }
        }
    }

    public function onAcymDeclareSummary_conditions(&$automationCondition)
    {
        $this->summaryConditionFilters($automationCondition);
    }

    private function summaryConditionFilters(&$automationCondition)
    {
        if (!empty($automationCondition['ebregistration'])) {
            acym_loadLanguageFile('com_eventbooking', JPATH_SITE);

            if (empty($automationCondition['ebregistration']['event'])) {
                $event = acym_translation('ACYM_ANY_EVENT');
            } else {
                $event = acym_loadResult('SELECT `title` FROM #__eb_events WHERE `id` = '.intval($automationCondition['ebregistration']['event']));
            }

            $status = [
                '-1' => 'ACYM_ANY',
                '0' => 'EB_PENDING',
                '1' => 'EB_PAID',
                '2' => 'EB_CANCELLED',
            ];

            $status = acym_translation($status[$automationCondition['ebregistration']['status']]);

            $finalText = acym_translation_sprintf('ACYM_REGISTERED', $event, $status);

            $dates = [];
            if (!empty($automationCondition['ebregistration']['datemin'])) {
                $dates[] = acym_translation('ACYM_AFTER').' '.acym_replaceDate($automationCondition['ebregistration']['datemin'], true);
            }

            if (!empty($automationCondition['ebregistration']['datemax'])) {
                $dates[] = acym_translation('ACYM_BEFORE').' '.acym_replaceDate($automationCondition['ebregistration']['datemax'], true);
            }

            if (!empty($dates)) {
                $finalText .= ' '.implode(' '.acym_translation('ACYM_AND').' ', $dates);
            }

            $automationCondition = $finalText;
        }
    }

    public function onAcymDeclareFilters(&$filters)
    {
        $this->filtersFromConditions($filters);
    }

    public function onAcymProcessFilterCount_ebregistration(&$query, $options, $num)
    {
        $this->onAcymProcessFilter_ebregistration($query, $options, $num);

        return acym_translation_sprintf('ACYM_SELECTED_USERS', $query->count());
    }

    public function onAcymProcessFilter_ebregistration(&$query, $options, $num)
    {
        $this->processConditionFilter_ebregistration($query, $options, $num);
    }

    public function onAcymDeclareSummary_filters(&$automationFilter)
    {
        $this->summaryConditionFilters($automationFilter);
    }

    private function getTriggerParams()
    {
        $result = [];

        $result['every'] = [
            '3600' => acym_translation('ACYM_HOURS'),
            '86400' => acym_translation('ACYM_DAYS'),
        ];

        $result['when'] = [
            'before' => acym_translation('ACYM_BEFORE'),
            'after' => acym_translation('ACYM_AFTER'),
        ];
        $result['categories'] = acym_loadObjectList('SELECT `id`, `name` FROM #__eb_categories', 'id');

        foreach ($result['categories'] as $key => $category) {
            $result['categories'][$key] = $category->name;
        }

        $result['categories'] = ['' => acym_translation('ACYM_ANY_CATEGORY')] + $result['categories'];

        return $result;
    }

    public function onAcymDeclareTriggers(&$triggers, &$defaultValues)
    {
        $params = $this->getTriggerParams();

        $triggers['classic']['eventbooking_reminder'] = new stdClass();
        $triggers['classic']['eventbooking_reminder']->name = acym_translation_sprintf('ACYM_COMBINED_TRANSLATIONS', 'EventBooking', acym_translation('ACYM_REMINDER'));
        $triggers['classic']['eventbooking_reminder']->option = '<div class="grid-x cell acym_vcenter"><div class="grid-x cell grid-margin-x acym_vcenter margin-bottom-1">';
        $triggers['classic']['eventbooking_reminder']->option .= '<div class="cell medium-shrink">
                                                                <input 
                                                                    type="number" 
                                                                    name="[triggers][classic][eventbooking_reminder][number]" 
                                                                    class="intext_input" 
                                                                    value="'.(empty($defaultValues['eventbooking_reminder']) ? '1' : $defaultValues['eventbooking_reminder']['number']).'">
                                                            </div>';
        $triggers['classic']['eventbooking_reminder']->option .= '<div class="cell medium-shrink">'.acym_select(
                $params['every'],
                '[triggers][classic][eventbooking_reminder][time]',
                empty($defaultValues['eventbooking_reminder']) ? '86400' : $defaultValues['eventbooking_reminder']['time'],
                'data-class="intext_select acym__select"'
            ).'</div></div>';
        $triggers['classic']['eventbooking_reminder']->option .= '<div class="grid-x cell grid-margin-x acym_vcenter margin-bottom-1"><div class="cell medium-shrink">'.acym_select(
                $params['when'],
                '[triggers][classic][eventbooking_reminder][when]',
                empty($defaultValues['eventbooking_reminder']) ? 'before' : $defaultValues['eventbooking_reminder']['when'],
                'data-class="intext_select acym__select"'
            ).'</div>';
        $triggers['classic']['eventbooking_reminder']->option .= '<div class="cell medium-shrink">'.acym_translation('ACYM_AN_EVENT_IN').'</div>';
        $triggers['classic']['eventbooking_reminder']->option .= '<div class="cell medium-auto">'.acym_select(
                $params['categories'],
                '[triggers][classic][eventbooking_reminder][cat]',
                empty($defaultValues['eventbooking_reminder']) ? '' : $defaultValues['eventbooking_reminder']['cat'],
                'data-class="intext_select_larger intext_select acym__select"'
            ).'</div>';
        $triggers['classic']['eventbooking_reminder']->option .= '</div></div>';
    }

    public function onAcymExecuteTrigger(&$step, &$execute, &$data)
    {
        $time = $data['time'];
        $triggers = $step->triggers;

        if (!empty($triggers['eventbooking_reminder']['number'])) {
            $triggerReminder = $triggers['eventbooking_reminder'];

            $timestamp = ($triggerReminder['number'] * $triggerReminder['time']);

            if ($triggerReminder['when'] == 'before') {
                $timestamp += $time;
            } else {
                $timestamp -= $time;
            }


            $join = [];
            $where = [];

            if (!empty($triggerReminder['cat'])) {
                $join[] = 'LEFT JOIN #__eb_event_categories as cat ON `event`.`id` = `cat`.`event_id`';
                $where[] = '`cat`.`category_id` = '.intval($triggerReminder['cat']);
            }

            $where[] = '`event`.`event_date` >= '.acym_escapeDB(acym_date($timestamp, 'Y-m-d H:i:s', true));
            $where[] = '`event`.`event_date` <= '.acym_escapeDB(acym_date($timestamp + $this->config->get('cron_frequency', '900'), 'Y-m-d H:i:s', true));
            $where[] = '`event`.`published` = 1';

            $events = acym_loadObjectList('SELECT * FROM `#__eb_events` as event '.implode(' ', $join).' WHERE '.implode(' AND ', $where));
            if (!empty($events)) $execute = true;
        }
    }

    public function onAcymDeclareSummary_triggers(&$automation)
    {
        if (!empty($automation->triggers['eventbooking_reminder'])) {
            $params = $this->getTriggerParams();

            $final = $automation->triggers['eventbooking_reminder']['number'].' ';
            $final .= $params['every'][$automation->triggers['eventbooking_reminder']['time']].' ';
            $final .= $params['when'][$automation->triggers['eventbooking_reminder']['when']].' ';
            $final .= acym_translation('ACYM_AN_EVENT_IN').' '.strtolower($params['categories'][$automation->triggers['eventbooking_reminder']['cat']]);

            $automation->triggers['eventbooking_reminder'] = $final;
        }
    }
}
