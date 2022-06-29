<?php

use AcyMailing\Libraries\acymPlugin;
use AcyMailing\Helpers\TabHelper;

class plgAcymEventsmanager extends acymPlugin
{
    public function __construct()
    {
        parent::__construct();
        $this->cms = 'WordPress';
        $this->installed = acym_isExtensionActive('events-manager/events-manager.php');
        $this->rootCategoryId = 0;

        $this->pluginDescription->name = 'Events Manager';
        $this->pluginDescription->icon = ACYM_PLUGINS_URL.'/'.basename(__DIR__).'/icon.svg';
        $this->pluginDescription->category = 'Events management';
        $this->pluginDescription->features = '["content","automation"]';
        $this->pluginDescription->description = '- Insert events in your emails<br />- Filter users by event subscription';

        if ($this->installed) {
            $this->displayOptions = [
                'title' => ['ACYM_TITLE', true],
                'image' => ['ACYM_FEATURED_IMAGE', true],
                'intro' => ['ACYM_INTRO_TEXT', true],
                'fulltext' => ['ACYM_FULL_TEXT', false],
                'date' => ['ACYM_DATE', false],
                'location' => ['ACYM_LOCATION', true],
                'cutoff' => [__('Booking Cut-Off Date', 'events-manager'), false],
                'cats' => ['ACYM_CATEGORIES', false],
                'tags' => ['ACYM_TAGS', false],
                'author' => ['ACYM_AUTHOR', false],
                'attributes' => [__('Attributes', 'events-manager'), false],
                'customfields' => ['ACYM_CUSTOM_FIELDS', false],
                'readmore' => ['ACYM_READ_MORE', false],
            ];

            $this->initCustomView();

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
                    WHERE post.post_type = "event" 
                        AND post.post_status = "publish"';
        $element = acym_loadObject($query);
        if (empty($element)) return;
        foreach ($element as $key => $value) {
            $this->elementOptions[$key] = [$key];
        }

        $emAttributes = $this->getEMAttributes();
        if (!empty($emAttributes)) {
            foreach ($emAttributes as $oneAttribute) {
                $this->elementOptions[$oneAttribute] = [$oneAttribute];
            }
        }
    }

    private function getEMAttributes()
    {
        // Get the Events Manager custom attributes from its configuration
        if (!get_option('dbem_attributes_enabled')) return [];

        $emAttributes = explode("\n", get_option('dbem_placeholders_custom'));

        if (empty($emAttributes)) return [];

        $attributes = [];
        foreach ($emAttributes as $oneAttribute) {
            if (strpos($oneAttribute, '#_ATT{') !== 0) continue;

            $oneAttribute = substr($oneAttribute, '6', strpos($oneAttribute, '}') - 6);
            $attributes[] = $oneAttribute;
        }

        return $attributes;
    }

    public function getPossibleIntegrations()
    {
        return $this->pluginDescription;
    }

    public function insertionOptions($defaultValues = null)
    {
        $this->defaultValues = $defaultValues;
        $this->prepareWPCategories('event-categories');

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
                'default' => 'post_date',
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
        $this->filters[] = 'post.post_type = "event"';
        $this->filters[] = 'post.post_status = "publish"';
        $this->searchFields = ['post.ID', 'post.post_title'];
        $this->pageInfo->order = 'post.ID';
        $this->elementIdTable = 'post';
        $this->elementIdColumn = 'ID';

        if ($this->getParam('hidepast', '1') === '1') {
            $this->query .= 'JOIN #__postmeta AS startdate ON post.ID = startdate.post_id AND startdate.meta_key = "_event_start" ';
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
                    JOIN #__postmeta AS startdate ON post.ID = startdate.post_id AND startdate.meta_key = "_event_start" ';

            $where = [];

            $selectedArea = $this->getSelectedArea($parameter);
            if (!empty($selectedArea)) {
                $query .= 'JOIN #__term_relationships AS cat ON post.ID = cat.object_id ';
                $where[] = 'cat.term_taxonomy_id IN ('.implode(',', $selectedArea).')';
            }

            $where[] = 'post.post_type = "event"';
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
        $query = 'SELECT post.*, `user`.`user_nicename`, `user`.`display_name` 
                    FROM #__posts AS post
                    LEFT JOIN #__users AS `user` 
                        ON `user`.`ID` = `post`.`post_author` 
                    WHERE post.post_type = "event" 
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

        $varFields['{fulltext}'] = $this->cleanExtensionContent($element->post_content);
        $varFields['{intro}'] = $this->cleanExtensionContent($this->getIntro($element->post_content));
        if (in_array('fulltext', $tag->display)) {
            $contentText .= $varFields['{fulltext}'];
        } elseif (in_array('intro', $tag->display)) {
            $contentText .= $varFields['{intro}'];
        }

        $varFields['{startdate}'] = '';
        $varFields['{enddate}'] = '';
        $varFields['{simpleenddate}'] = '';
        $varFields['{simplestartdate}'] = '';
        $varFields['{date}'] = '';
        if (!empty($properties['_event_start']->value)) {
            $allDay = !empty($properties['_event_all_day']->value) && $properties['_event_all_day']->value === '1';

            $startDate = $properties['_event_start']->value;
            $endDate = empty($properties['_event_end']->value) ? '' : $properties['_event_end']->value;


            $varFields['{startdate}'] = acym_date($startDate, acym_translation('ACYM_DATE_FORMAT_LC2'));
            $varFields['{enddate}'] = acym_date($endDate, acym_translation('ACYM_DATE_FORMAT_LC2'));

            $varFields['{simplestartdate}'] = acym_date($startDate, acym_translation('ACYM_DATE_FORMAT_LC1'));
            $varFields['{simpleenddate}'] = acym_date($endDate, acym_translation('ACYM_DATE_FORMAT_LC1'));

            $varFields['{date}'] = $allDay ? $varFields['{simplestartdate}'] : $varFields['{startdate}'];
            if (!empty($endDate) && $startDate !== $endDate) {
                if ($allDay) {
                    $endDateDisplay = $varFields['{simpleenddate}'];
                } else {
                    if ($varFields['{simplestartdate}'] === $varFields['{simpleenddate}']) {
                        $endDateDisplay = acym_date($endDate, 'H:i');
                    } else {
                        $endDateDisplay = $varFields['{enddate}'];
                    }
                }

                if ($endDateDisplay !== $varFields['{date}']) {
                    $varFields['{date}'] .= ' - '.$endDateDisplay;
                }
            }
        }
        if (in_array('date', $tag->display) && !empty($properties['_event_start']->value)) {
            $customFields[] = [
                $varFields['{date}'],
                acym_translation('ACYM_DATE'),
            ];
        }

        $varFields['{location}'] = '';
        // When "No location" or "Physical location" is selected on the event, the value for _event_location_type is NULL for some reason
        if (!empty($properties['_event_location_type']->value) && $properties['_event_location_type']->value === 'url') {
            if (!empty($properties['_event_location_url']->value)) {
                $varFields['{location}'] = '<a href="'.$properties['_event_location_url']->value.'" target="_blank">'.$properties['_event_location_url_text']->value.'</a>';
            }
        } elseif (!empty($properties['_location_id']->value)) {
            $locationData = acym_loadObject('SELECT * FROM #__em_locations WHERE location_id = '.intval($properties['_location_id']->value));

            if (!empty($locationData)) {
                $locationLink = '';
                // wp-content / plugins / events-manager / classes / em-location.php line 740
                if (defined('EM_MS_GLOBAL') && EM_MS_GLOBAL) {
                    $blog_id = get_current_site()->blog_id;
                    if (get_site_option('dbem_ms_mainblog_locations')) {
                        $locationLink = get_blog_permalink($blog_id, $locationData->post_id);
                    } elseif ($blog_id != get_current_blog_id()) {
                        $dbemLocationsPage = get_option('dbem_locations_page');
                        if (!get_site_option('dbem_ms_global_locations_links') && is_main_site() && $dbemLocationsPage) {
                            $locationLink = get_permalink($dbemLocationsPage).get_site_option('dbem_ms_locations_slug', EM_LOCATION_SLUG);
                            $locationLink = trailingslashit($locationLink.'/'.$locationData->location_slug.'-'.$locationData->location_id);
                        } else {
                            $locationLink = get_blog_permalink($blog_id, $locationData->post_id);
                        }
                    }
                }

                if (empty($locationLink)) {
                    $locationLink = get_post_permalink($locationData->post_id);
                }

                $varFields['{location}'] = '<a href="'.esc_url($locationLink).'" target="_blank">'.$locationData->location_name.'</a>';
            }
        }

        if (in_array('location', $tag->display) && !empty($varFields['{location}'])) {
            $customFields[] = [
                $varFields['{location}'],
                acym_translation('ACYM_LOCATION'),
            ];
        }

        $varFields['{cutoff}'] = empty($properties['_event_rsvp_date']->value)
            ? ''
            : acym_date(
                $properties['_event_rsvp_date']->value.' '.$properties['_event_rsvp_time']->value,
                acym_translation('ACYM_DATE_FORMAT_LC2'),
                false
            );
        if (in_array('cutoff', $tag->display) && !empty($properties['_event_rsvp']->value) && !empty($properties['_event_rsvp_date']->value)) {
            $customFields[] = [
                $varFields['{cutoff}'],
                __('Booking Cut-Off Date', 'events-manager'),
            ];
        }

        $varFields['{author}'] = empty($element->display_name) ? $element->user_nicename : $element->display_name;
        if (in_array('author', $tag->display) && !empty($varFields['{author}'])) {
            $customFields[] = [
                $varFields['{author}'],
                acym_translation('ACYM_AUTHOR'),
            ];
        }

        $varFields['{cats}'] = get_the_term_list($tag->id, 'event-categories', '', ', ');
        if (in_array('cats', $tag->display) && !empty($varFields['{cats}'])) {
            $customFields[] = [
                $varFields['{cats}'],
                acym_translation('ACYM_CATEGORIES'),
            ];
        }

        $varFields['{tags}'] = get_the_term_list($tag->id, 'event-tags', '', ', ');
        if (in_array('tags', $tag->display) && !empty($varFields['{tags}'])) {
            $customFields[] = [
                $varFields['{tags}'],
                acym_translation('ACYM_TAGS'),
            ];
        }

        if (in_array('attributes', $tag->display)) {
            $emAttributes = $this->getEMAttributes();
            if (!empty($emAttributes)) {
                foreach ($emAttributes as $oneAttribute) {
                    if (empty($properties[$oneAttribute]->value)) {
                        $varFields['{'.$oneAttribute.'}'] = '';
                    } else {
                        $customFields[] = [
                            $varFields['{'.$oneAttribute.'}'],
                            $oneAttribute,
                        ];
                    }
                }
            }
        }

        if (in_array('customfields', $tag->display)) {
            foreach ($properties as $propName => $propVal) {
                if (strpos($propName, '_') === 0) continue;

                $customFields[] = [
                    $varFields['{'.$propName.'}'],
                    $propName,
                ];
            }
        }

        $varFields['{readmore}'] = '<a class="acymailing_readmore_link" style="text-decoration:none;" target="_blank" href="'.$link.'">';
        $varFields['{readmore}'] .= '<span class="acymailing_readmore">'.acym_escape(acym_translation('ACYM_READ_MORE')).'</span>';
        $varFields['{readmore}'] .= '</a>';
        if (in_array('readmore', $tag->display)) $afterArticle .= $varFields['{readmore}'];

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
            echo json_encode(
                [
                    [
                        'value' => $id,
                        'text' => $id.' - '.$subject,
                    ],
                ]
            );
            exit;
        }

        $return = [];
        $search = acym_getVar('string', 'search', '');
        $elements = acym_loadObjectList(
            'SELECT ID, post_title 
            FROM #__posts 
            WHERE post_title LIKE '.acym_escapeDB('%'.$search.'%').' AND post_type = "event" 
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
            'SELECT ticket.`ticket_id`, ticket.`ticket_name` 
            FROM `#__em_tickets` AS ticket 
            JOIN `#__em_events` AS event ON ticket.event_id = event.event_id 
            WHERE event.`post_id` = '.intval($id).' 
            ORDER BY ticket.`ticket_order` ASC'
        );

        $options = [];
        $options[0] = acym_translation('ACYM_ANY');
        foreach ($elements as $oneElement) {
            $options[$oneElement->ticket_id] = $oneElement->ticket_name;
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
        $conditions['user']['eventsmanager'] = new stdClass();
        $conditions['user']['eventsmanager']->name = 'Events Manager - Booking';
        $conditions['user']['eventsmanager']->option = '<div class="cell grid-x grid-margin-x">';

        $conditions['user']['eventsmanager']->option .= '<div class="intext_select_automation cell">';

        $conditions['user']['eventsmanager']->option .= acym_select(
            $this->getBookingStatuses(),
            'acym_condition[conditions][__numor__][__numand__][eventsmanager][status]',
            '1',
            ['class' => 'acym__select']
        );
        $conditions['user']['eventsmanager']->option .= '</div>';

        $conditions['user']['eventsmanager']->option .= '<div class="intext_select_automation cell">';

        $this->prepareWPCategories('event-categories');
        $categories = $this->categories;
        array_unshift($categories, (object)['id' => 0, 'title' => acym_translation('ACYM_ANY_CATEGORY')]);
        $conditions['user']['eventsmanager']->option .= acym_select(
            $categories,
            'acym_condition[conditions][__numor__][__numand__][eventsmanager][category]',
            null,
            ['class' => 'acym__select'],
            'id',
            'title'
        );
        $conditions['user']['eventsmanager']->option .= '</div>';

        $conditions['user']['eventsmanager']->option .= '<div class="intext_select_automation cell">';

        $selectOptions = [
            'class' => 'acym__select acym_select2_ajax',
            'data-placeholder' => acym_translation('ACYM_ANY_EVENT'),
            'data-params' => [
                'plugin' => __CLASS__,
                'trigger' => 'searchEvent',
            ],
        ];

        $selectOptions['acym-automation-reload'] = [
            'plugin' => __CLASS__,
            'trigger' => 'getTicketsSelection',
            'change' => '#em_tochange___numor_____numand__',
            'name' => 'acym_condition[conditions][__numor__][__numand__][eventsmanager][ticket]',
            'paramFields' => [
                'event' => 'acym_condition[conditions][__numor__][__numand__][eventsmanager][event]',
            ],
        ];

        $conditions['user']['eventsmanager']->option .= acym_select(
            [],
            'acym_condition[conditions][__numor__][__numand__][eventsmanager][event]',
            null,
            $selectOptions
        );
        $conditions['user']['eventsmanager']->option .= '</div>';

        $conditions['user']['eventsmanager']->option .= '<div class="intext_select_automation cell" id="em_tochange___numor_____numand__">';
        $conditions['user']['eventsmanager']->option .= '<input type="hidden" name="acym_condition[conditions][__numor__][__numand__][eventsmanager][ticket]" />';
        $conditions['user']['eventsmanager']->option .= '</div>';

        $conditions['user']['eventsmanager']->option .= '</div>';


        $conditions['user']['eventsmanager']->option .= '<div class="cell grid-x grid-margin-x">';
        $conditions['user']['eventsmanager']->option .= acym_dateField('acym_condition[conditions][__numor__][__numand__][eventsmanager][datemin]', '', 'cell shrink');
        $conditions['user']['eventsmanager']->option .= '<span class="acym__title acym__title__secondary acym_vcenter margin-bottom-0 cell shrink"><</span>';
        $conditions['user']['eventsmanager']->option .= '<span class="acym_vcenter">'.acym_translation('ACYM_REGISTRATION_DATE').'</span>';
        $conditions['user']['eventsmanager']->option .= '<span class="acym__title acym__title__secondary acym_vcenter margin-bottom-0 cell shrink"><</span>';
        $conditions['user']['eventsmanager']->option .= acym_dateField('acym_condition[conditions][__numor__][__numand__][eventsmanager][datemax]', '', 'cell shrink');
        $conditions['user']['eventsmanager']->option .= '</div>';
    }

    private function getBookingStatuses()
    {
        return [
            'all' => acym_translation('ACYM_STATUS'),
            '0' => __('Pending', 'events-manager'),
            '1' => __('Approved', 'events-manager'),
            '2' => __('Rejected', 'events-manager'),
            '3' => __('Cancelled', 'events-manager'),
            '4' => __('Awaiting Online Payment', 'events-manager'),
            '5' => __('Awaiting Payment', 'events-manager'),
        ];
    }

    public function onAcymProcessCondition_eventsmanager(&$query, $options, $num, &$conditionNotValid)
    {
        $this->processConditionFilter_eventsmanager($query, $options, $num);
        $affectedRows = $query->count();
        if (empty($affectedRows)) $conditionNotValid++;
    }

    public function onAcymProcessFilterCount_eventsmanager(&$query, $options, $num)
    {
        $this->onAcymProcessFilter_eventsmanager($query, $options, $num);

        return acym_translationSprintf('ACYM_SELECTED_USERS', $query->count());
    }

    public function onAcymProcessFilter_eventsmanager(&$query, $options, $num)
    {
        $this->processConditionFilter_eventsmanager($query, $options, $num);
    }

    private function processConditionFilter_eventsmanager(&$query, $options, $num)
    {
        $booking = 'booking'.$num;
        $ticket = 'ticketBooking'.$num;
        $event = 'event'.$num;
        $category = 'category'.$num;

        $query->join[$booking] = '#__em_bookings AS '.$booking.' ON '.$booking.'.person_id = user.cms_id ';

        if (!empty($options['ticket'])) {
            $query->join[$ticket] = '#__em_tickets_bookings AS '.$ticket.' ON '.$ticket.'.booking_id = '.$booking.'.booking_id ';
            $query->where[] = $ticket.'.ticket_id = '.intval($options['ticket']);
        } elseif (!empty($options['event'])) {
            $query->join[$event] = '#__em_events AS '.$event.' ON '.$event.'.event_id = '.$booking.'.event_id ';
            $query->where[] = $event.'.post_id = '.intval($options['event']);
        } elseif (!empty($options['category'])) {
            $query->join[$event] = '#__em_events AS '.$event.' ON '.$event.'.event_id = '.$booking.'.event_id ';
            $query->join[$category] = '#__term_relationships AS '.$category.' ON '.$event.'.post_id = '.$category.'.object_id ';
            $query->where[] = $category.'.term_taxonomy_id = '.intval($options['category']);
        }

        if (isset($options['status']) && $options['status'] !== 'all') {
            $query->where[] = $booking.'.booking_status = '.intval($options['status']);
        }

        if (!empty($options['datemin'])) {
            $options['datemin'] = acym_replaceDate($options['datemin']);
            if (!is_numeric($options['datemin'])) $options['datemin'] = strtotime($options['datemin']);
            if (!empty($options['datemin'])) {
                $query->where[] = $booking.'.booking_date > '.acym_escapeDB(date('Y-m-d H:i:s', $options['datemin']));
            }
        }

        if (!empty($options['datemax'])) {
            $options['datemax'] = acym_replaceDate($options['datemax']);
            if (!is_numeric($options['datemax'])) $options['datemax'] = strtotime($options['datemax']);
            if (!empty($options['datemax'])) {
                $query->where[] = $booking.'.booking_date < '.acym_escapeDB(date('Y-m-d H:i:s', $options['datemax']));
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
        if (empty($automation['eventsmanager'])) return;

        if (empty($automation['eventsmanager']['event'])) {
            if (empty($automation['eventsmanager']['category'])) {
                $event = '';
            } else {
                $event = acym_loadResult(
                        'SELECT term.name 
                        FROM #__term_taxonomy AS taxo
                        JOIN #__terms AS term ON taxo.term_id = term.term_id
                        WHERE taxo.term_taxonomy_id = '.intval($automation['eventsmanager']['category'])
                    ).' - ';
            }
            $event .= acym_translation('ACYM_ANY_EVENT');
        } else {
            $event = acym_loadResult(
                'SELECT post_title 
                    FROM #__posts 
                    WHERE ID = '.intval($automation['eventsmanager']['event'])
            );
        }

        if (!empty($automation['eventsmanager']['ticket'])) {
            $ticket = acym_loadResult(
                'SELECT ticket_name 
                    FROM #__em_tickets 
                    WHERE ticket_id = '.intval($automation['eventsmanager']['ticket'])
            );

            $event .= ' - '.$ticket;
        }

        $finalText = acym_translationSprintf('ACYM_REGISTERED_TO', $event);

        $dates = [];
        if (!empty($automation['eventsmanager']['datemin'])) {
            $dates[] = acym_translation('ACYM_AFTER').' '.acym_replaceDate($automation['eventsmanager']['datemin'], true);
        }

        if (!empty($automation['eventsmanager']['datemax'])) {
            $dates[] = acym_translation('ACYM_BEFORE').' '.acym_replaceDate($automation['eventsmanager']['datemax'], true);
        }

        if (!empty($dates)) {
            $finalText .= ' '.implode(' '.acym_translation('ACYM_AND').' ', $dates);
        }

        if (isset($automation['eventsmanager']['status']) && $automation['eventsmanager']['status'] !== 'all') {
            $statuses = $this->getBookingStatuses();
            $finalText .= acym_translation('ACYM_STATUS').': '.$statuses[$automation['eventsmanager']['status']];
        }

        $automation = $finalText;
    }
}
