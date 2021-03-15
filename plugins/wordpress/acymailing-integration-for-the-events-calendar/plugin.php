<?php

use AcyMailing\Libraries\acymPlugin;
use AcyMailing\Helpers\TabHelper;

class plgAcymTheeventscalendar extends acymPlugin
{
    var $rtecInstalled = false;
    var $eventTicketsInstalled = false;

    public function __construct()
    {
        parent::__construct();
        $this->cms = 'WordPress';
        $this->installed = acym_isExtensionActive('the-events-calendar/the-events-calendar.php');
        $this->rtecInstalled = acym_isExtensionActive('registrations-for-the-events-calendar/registrations-for-the-events-calendar.php');
        $this->eventTicketsInstalled = acym_isExtensionActive('event-tickets/event-tickets.php');
        $this->rootCategoryId = 0;

        $this->pluginDescription->name = 'The Events Calendar';
        $this->pluginDescription->icon = ACYM_PLUGINS_URL.'/'.basename(__DIR__).'/icon.png';
        $this->pluginDescription->category = 'Events management';
        $this->pluginDescription->features = '["content","automation"]';
        $this->pluginDescription->description = '- Insert events in your emails<br />- Filter users by event subscription';

        if ($this->installed) {
            $this->displayOptions = [
                'title' => ['ACYM_TITLE', true],
                'price' => ['ACYM_PRICE', true],
                'image' => ['ACYM_IMAGE', true],
                'intro' => ['ACYM_INTRO_ONLY', true],
                'full' => ['ACYM_FULL_TEXT', false],
                'date' => ['ACYM_DATE', true],
                'location' => ['ACYM_LOCATION', true],
                'website' => [__('Event Website', 'the-events-calendar'), false],
                'tags' => ['ACYM_TAGS', false],
                'cats' => ['ACYM_CATEGORIES', false],
            ];

            $this->initReplaceOptionsCustomView();
            $this->initElementOptionsCustomView();

            $this->settings = [
                'custom_view' => [
                    'type' => 'custom_view',
                    'tags' => array_merge($this->displayOptions, $this->replaceOptions, $this->elementOptions),
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
        $format->afterTitle = 'Price: {price}';
        $format->afterArticle = 'Date: {date} <br> Location: {location}';
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

    public function getPossibleIntegrations()
    {
        return $this->pluginDescription;
    }

    public function insertionOptions($defaultValues = null)
    {
        $this->defaultValues = $defaultValues;

        $this->categories = acym_loadObjectList(
            'SELECT cat.term_taxonomy_id AS id, cat.parent AS parent_id, catdetails.name AS title 
            FROM `#__term_taxonomy` AS cat 
            JOIN `#__terms` AS catdetails ON cat.term_id = catdetails.term_id
            WHERE cat.taxonomy = "tribe_events_cat"'
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
        ];

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
                'relativeDate' => '+',
            ],
            [
                'title' => 'ACYM_TO',
                'type' => 'date',
                'name' => 'to',
                'default' => '',
                'relativeDate' => '+',
            ],
            [
                'title' => 'ACYM_ORDER_BY',
                'type' => 'select',
                'name' => 'order',
                'options' => [
                    'ID' => 'ACYM_DATE_CREATED',
                    'post_date' => 'ACYM_DATE',
                    'post_title' => 'ACYM_TITLE',
                    'rand' => 'ACYM_RANDOM',
                ],
                'default' => 'post_date',
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
        if (!empty($properties['_EventStartDateUTC']->value)) {
            $allday = !empty($properties['_EventAllDay']->value) && $properties['_EventAllDay']->value === 'yes';

            $startDate = $properties['_EventStartDateUTC']->value;
            $endDate = empty($properties['_EventEndDateUTC']->value) ? '' : $properties['_EventEndDateUTC']->value;


            $varFields['{startdate}'] = acym_date($startDate, acym_translation('ACYM_DATE_FORMAT_LC2'));
            $varFields['{enddate}'] = acym_date($endDate, acym_translation('ACYM_DATE_FORMAT_LC2'));

            $varFields['{simplestartdate}'] = acym_date($startDate, acym_translation('ACYM_DATE_FORMAT_LC1'));
            $varFields['{simpleenddate}'] = acym_date($endDate, acym_translation('ACYM_DATE_FORMAT_LC1'));

            $varFields['{date}'] = $allday ? $varFields['{simplestartdate}'] : $varFields['{startdate}'];
            if (!empty($endDate) && $startDate !== $endDate) {
                if ($allday) {
                    $endDateDisplay = $varFields['{simpleenddate}'];
                } else {
                    if ($varFields['{simplestartdate}'] === $varFields['{simpleenddate}']) {
                        $endDateDisplay = acym_date($endDate, 'H:i');
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

        $varFields['{website}'] = empty($properties['_EventURL']->value) ? '' : '<a target="_blank" href="'.$properties['_EventURL']->value.'">'.$properties['_EventURL']->value.'</a>';
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
        $format->link = empty($tag->clickable) ? '' : $link;
        $format->customFields = $customFields;
        $result = '<div class="acymailing_content">'.$this->pluginHelper->getStandardDisplay($format).'</div>';

        return $this->finalizeElementFormat($result, $tag, $varFields);
    }

    /**
     * Function called with ajax to search in events
     */
    public function searchEvent()
    {
        $id = acym_getVar('int', 'id');
        if (!empty($id)) {
            $subject = acym_loadResult(
                'SELECT post_title 
                FROM #__posts 
                WHERE ID = '.intval($id)
            );
            if (empty($subject)) $subject = '';
            echo json_encode(['value' => $id.' - '.$subject]);
            exit;
        }

        $return = [];
        $search = acym_getVar('string', 'search', '');
        $elements = acym_loadObjectList(
            'SELECT ID, post_title 
            FROM #__posts 
            WHERE post_title LIKE '.acym_escapeDB('%'.$search.'%').' AND post_type = "tribe_events" 
            ORDER BY post_title ASC'
        );

        foreach ($elements as $oneElement) {
            $return[] = [$oneElement->ID, $oneElement->ID.' - '.$oneElement->post_title];
        }

        echo json_encode($return);
        exit;
    }

    public function getTicketsSelection()
    {
        $id = acym_getVar('int', 'event', 0);
        if (empty($id)) exit;

        $elements = acym_loadObjectList(
            'SELECT ticket.`ID`, ticket.`post_title` 
            FROM `#__posts` AS ticket 
            JOIN #__postmeta AS meta 
                ON meta.post_id = ticket.ID AND meta.meta_key = "_tribe_rsvp_for_event"
            WHERE ticket.`post_type` = "tribe_rsvp_tickets" 
                AND meta.`meta_value` = '.intval($id).' 
            ORDER BY ticket.`post_title` ASC'
        );

        $options = [];
        $options[0] = acym_translation('ACYM_ANY');
        foreach ($elements as $oneElement) {
            $options[$oneElement->ID] = $oneElement->post_title;
        }

        echo acym_select(
            $options,
            acym_getVar('string', 'name', ''),
            acym_getVar('int', 'value', 0),
            [
                'class' => 'acym__select',
            ]
        );
        exit;
    }

    public function onAcymDeclareFilters(&$filters)
    {
        $this->filtersFromConditions($filters);
    }

    public function onAcymDeclareConditions(&$conditions)
    {
        if (!$this->rtecInstalled && !$this->eventTicketsInstalled) return;

        $conditions['user']['eventscalendar'] = new stdClass();
        $conditions['user']['eventscalendar']->name = 'The Events Calendar - Registration';
        $conditions['user']['eventscalendar']->option = '<div class="cell grid-x grid-margin-x">';

        $conditions['user']['eventscalendar']->option .= '<div class="intext_select_automation cell">';

        $selectOptions = [
            'class' => 'acym__select acym_select2_ajax',
            'data-placeholder' => acym_translation('ACYM_ANY_EVENT'),
            'data-params' => [
                'plugin' => __CLASS__,
                'trigger' => 'searchEvent',
            ],
        ];

        // If Events tickets installed, add the ticket option
        if ($this->eventTicketsInstalled) {
            $selectOptions['acym-automation-reload'] = [
                'plugin' => __CLASS__,
                'trigger' => 'getTicketsSelection',
                'change' => '#ettec_tochange___numor_____numand__',
                'name' => 'acym_condition[conditions][__numor__][__numand__][eventscalendar][ticket]',
                'paramFields' => [
                    'event' => 'acym_condition[conditions][__numor__][__numand__][eventscalendar][event]',
                ],
            ];
        }

        $conditions['user']['eventscalendar']->option .= acym_select(
            [],
            'acym_condition[conditions][__numor__][__numand__][eventscalendar][event]',
            null,
            $selectOptions
        );
        $conditions['user']['eventscalendar']->option .= '</div>';

        $conditions['user']['eventscalendar']->option .= '<div class="intext_select_automation cell" id="ettec_tochange___numor_____numand__">';
        $conditions['user']['eventscalendar']->option .= '<input type="hidden" name="acym_condition[conditions][__numor__][__numand__][eventscalendar][ticket]" />';
        $conditions['user']['eventscalendar']->option .= '</div>';

        $conditions['user']['eventscalendar']->option .= '</div>';


        $conditions['user']['eventscalendar']->option .= '<div class="cell grid-x grid-margin-x">';
        $conditions['user']['eventscalendar']->option .= acym_dateField('acym_condition[conditions][__numor__][__numand__][eventscalendar][datemin]', '', 'cell shrink');
        $conditions['user']['eventscalendar']->option .= '<span class="acym__title acym__title__secondary acym_vcenter margin-bottom-0 cell shrink"><</span>';
        $conditions['user']['eventscalendar']->option .= '<span class="acym_vcenter">'.acym_translation('ACYM_REGISTRATION_DATE').'</span>';
        $conditions['user']['eventscalendar']->option .= '<span class="acym__title acym__title__secondary acym_vcenter margin-bottom-0 cell shrink"><</span>';
        $conditions['user']['eventscalendar']->option .= acym_dateField('acym_condition[conditions][__numor__][__numand__][eventscalendar][datemax]', '', 'cell shrink');
        $conditions['user']['eventscalendar']->option .= '</div>';
    }

    public function onAcymProcessCondition_eventscalendar(&$query, $options, $num, &$conditionNotValid)
    {
        $this->processConditionFilter_eventscalendar($query, $options, $num);
        $affectedRows = $query->count();
        if (empty($affectedRows)) $conditionNotValid++;
    }

    public function onAcymProcessFilterCount_eventscalendar(&$query, $options, $num)
    {
        $this->onAcymProcessFilter_eventscalendar($query, $options, $num);

        return acym_translationSprintf('ACYM_SELECTED_USERS', $query->count());
    }

    public function onAcymProcessFilter_eventscalendar(&$query, $options, $num)
    {
        $this->processConditionFilter_eventscalendar($query, $options, $num);
    }

    private function processConditionFilter_eventscalendar(&$query, $options, $num)
    {
        if ($this->eventTicketsInstalled) {
            // Event tickets integration, since the ticket field exists
            $userT = 'eventTicketsUser'.$num;
            $entryT = 'eventTickets'.$num;
            $elemT = 'eventTicketsElement'.$num;
            $dateField = $entryT.'.post_date_gmt';

            $query->join[$userT] = '#__postmeta AS '.$userT.' ON '.$userT.'.meta_value = user.cms_id AND '.$userT.'.meta_key = "_tribe_tickets_attendee_user_id"';
            $query->join[$entryT] = '#__posts AS '.$entryT.' ON '.$entryT.'.ID = '.$userT.'.post_id AND '.$entryT.'.post_type = "tribe_rsvp_attendees"';

            if (!empty($options['ticket'])) {
                $type = '_tribe_rsvp_product';
                $value = 'ticket';
            } elseif (!empty($options['event'])) {
                $type = '_tribe_rsvp_event';
                $value = 'event';
            }

            if (!empty($type) && !empty($value)) {
                $query->join[$elemT] = '#__postmeta AS '.$elemT.' 
                                            ON '.$entryT.'.ID = '.$elemT.'.post_id 
                                            AND '.$elemT.'.meta_key = "'.$type.'" 
                                            AND '.$elemT.'.meta_value = '.intval($options[$value]);
            }
        } elseif ($this->rtecInstalled) {
            // Registration the events calendar, since the ticket field doesn't exist
            $dateField = 'rtec'.$num.'.registration_date';

            $query->join['eventscalendar'.$num] = '#__rtec_registrations AS rtec'.$num.' ON rtec'.$num.'.email = user.email OR (user.cms_id != 0 AND rtec'.$num.'.user_id = user.cms_id)';
            if (!empty($options['event'])) $query->where[] = 'rtec'.$num.'.event_id = '.intval($options['event']);
        } else {
            return;
        }

        if (!empty($options['datemin'])) {
            $options['datemin'] = acym_replaceDate($options['datemin']);
            if (!is_numeric($options['datemin'])) $options['datemin'] = strtotime($options['datemin']);
            if (!empty($options['datemin'])) {
                $query->where[] = $dateField.' > '.acym_escapeDB(date('Y-m-d H:i:s', $options['datemin']));
            }
        }

        if (!empty($options['datemax'])) {
            $options['datemax'] = acym_replaceDate($options['datemax']);
            if (!is_numeric($options['datemax'])) $options['datemax'] = strtotime($options['datemax']);
            if (!empty($options['datemax'])) {
                $query->where[] = $dateField.' < '.acym_escapeDB(date('Y-m-d H:i:s', $options['datemax']));
            }
        }
    }

    public function onAcymDeclareSummary_conditions(&$automationCondition)
    {
        $this->summaryConditionFilters($automationCondition);
    }

    public function onAcymDeclareSummary_filters(&$automationFilter)
    {
        $this->summaryConditionFilters($automationFilter);
    }

    private function summaryConditionFilters(&$automation)
    {
        if (empty($automation['eventscalendar'])) return;

        if (empty($automation['eventscalendar']['ticket'])) {
            if (empty($automation['eventscalendar']['event'])) {
                $event = acym_translation('ACYM_ANY_EVENT');
            } else {
                $event = acym_loadResult(
                    'SELECT post_title 
                    FROM #__posts 
                    WHERE ID = '.intval($automation['eventscalendar']['event'])
                );
            }
        } else {
            $ticket = acym_loadResult(
                'SELECT post_title 
                    FROM #__posts 
                    WHERE ID = '.intval($automation['eventscalendar']['ticket'])
            );
            $event = acym_loadResult(
                'SELECT event.post_title 
                    FROM #__postmeta AS meta 
                    JOIN #__posts AS event ON event.ID = meta.meta_value 
                    WHERE meta.meta_key = "_tribe_rsvp_for_event" AND meta.post_id = '.intval($automation['eventscalendar']['ticket'])
            );

            $event .= ' - '.$ticket;
        }

        $finalText = acym_translationSprintf('ACYM_REGISTERED_TO', $event);

        $dates = [];
        if (!empty($automation['eventscalendar']['datemin'])) {
            $dates[] = acym_translation('ACYM_AFTER').' '.acym_replaceDate($automation['eventscalendar']['datemin'], true);
        }

        if (!empty($automation['eventscalendar']['datemax'])) {
            $dates[] = acym_translation('ACYM_BEFORE').' '.acym_replaceDate($automation['eventscalendar']['datemax'], true);
        }

        if (!empty($dates)) {
            $finalText .= ' '.implode(' '.acym_translation('ACYM_AND').' ', $dates);
        }

        $automation = $finalText;
    }
}
