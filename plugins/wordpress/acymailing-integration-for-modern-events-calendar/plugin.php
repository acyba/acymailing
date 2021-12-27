<?php

use AcyMailing\Libraries\acymPlugin;
use AcyMailing\Helpers\TabHelper;

class plgAcymModerneventscalendar extends acymPlugin
{
    const MEC_LITE = 'modern-events-calendar-lite';
    const MEC_FULL = 'mec';

    protected $fullInstalled = false;
    protected $liteInstalled = false;
    protected $textDomain;

    public function __construct()
    {
        parent::__construct();
        $this->cms = 'WordPress';
        $this->liteInstalled = acym_isExtensionActive('modern-events-calendar-lite/modern-events-calendar-lite.php');
        $this->fullInstalled = acym_isExtensionActive('modern-events-calendar/mec.php');
        $this->installed = $this->liteInstalled || $this->fullInstalled;
        $this->textDomain = self::MEC_LITE;
        if ($this->fullInstalled) $this->textDomain = self::MEC_FULL;

        $this->pluginDescription->name = 'M.E. Calendar';
        $this->pluginDescription->icon = ACYM_PLUGINS_URL.'/'.basename(__DIR__).'/icon.png';
        $this->pluginDescription->category = 'Events management';
        $this->pluginDescription->features = '["content","automation"]';
        $this->pluginDescription->description = '- Insert events in your emails<br />- Filter users by event subscription';
        $this->rootCategoryId = 0;

        if ($this->installed) {
            $diplayBookingOption = [
                'bookingsLimit' => ['ACYM_BOOKING_LIMIT', false],
                'attendees' => ['ACYM_ATTENDEES', false],
            ];
            $this->displayOptions = [
                'title' => ['ACYM_TITLE', true],
                'price' => ['ACYM_PRICE', true],
                'image' => ['ACYM_IMAGE', true],
                'intro' => ['ACYM_INTRO_ONLY', true],
                'full' => ['ACYM_FULL_TEXT', false],
                'date' => ['ACYM_DATE', true],
                'location' => ['ACYM_LOCATION', true],
                'moreinfo' => [__('More Info', $this->textDomain), false],
                'tags' => ['ACYM_TAGS', false],
                'cats' => ['ACYM_CATEGORIES', false],
                'labels' => [__('Event Labels', $this->textDomain), false],
                'organizer' => ['ACYM_ORGANIZER', false],
                'otherOrganizer' => ['ACYM_OTHER_ORGANIZER', false],
                'otherLocation' => ['ACYM_OTHER_LOCATION', false],
                'eventNextOccurrences' => ['ACYM_NEXT_OCCURRENCES', false],
            ];

            if ($this->fullInstalled) {
                $bookingIsOn = get_option('mec_options', []);
                if ($bookingIsOn["settings"]["booking_status"]) {
                    $this->displayOptions = array_merge($this->displayOptions, $diplayBookingOption);
                }
            }
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

    public function getPossibleIntegrations()
    {
        return $this->pluginDescription;
    }

    public function insertionOptions($defaultValues = null)
    {
        $this->defaultValues = $defaultValues;
        $this->prepareWPCategories('mec_category');

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
                'title' => 'ACYM_ORDER_BY',
                'type' => 'select',
                'name' => 'order',
                'options' => [
                    'ID' => 'ACYM_DATE_CREATED',
                    'post_date' => 'ACYM_DATE',
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
        $this->filters[] = 'post.post_type = "mec-events"';
        $this->filters[] = 'post.post_status = "publish"';
        $this->searchFields = ['post.ID', 'post.post_title'];
        $this->pageInfo->order = 'post.ID';
        $this->elementIdTable = 'post';
        $this->elementIdColumn = 'ID';

        if ($this->getParam('hidepast', '1') === '1') {
            $this->query .= 'JOIN #__postmeta AS startdate ON post.ID = startdate.post_id AND startdate.meta_key = "mec_start_date" ';
            $this->filters[] = 'startdate.`meta_value` >= '.acym_escapeDB(date('Y-m-d'));
        }

        parent::prepareListing();

        //if a category is selected
        if (!empty($this->pageInfo->filter_cat)) {
            $this->query .= 'JOIN #__term_relationships AS cat ON post.ID = cat.object_id';
            $this->filters[] = 'cat.term_taxonomy_id = '.(int)$this->pageInfo->filter_cat;
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

    public function replaceIndividualContent($tag)
    {
        $query = 'SELECT post.*
                    FROM #__posts AS post
                    WHERE post.post_type = "mec-events" 
                        AND post.post_status = "publish"
                        AND post.ID = '.intval($tag->id);

        $element = $this->initIndividualContent($tag, $query);
        if (empty($element)) return '';

        $varFields = $this->getCustomLayoutVars($element);

        $properties = acym_loadObjectList('SELECT meta_key, meta_value AS `value` FROM #__postmeta WHERE post_id = '.intval($tag->id), 'meta_key');
        foreach ($properties as $name => $property) {
            $varFields['{'.$name.'}'] = $property->value;
        }

        if (!empty($properties['mec_read_more'])) {
            $link = $properties['mec_read_more']->value;
        } else {
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

        $imageId = get_post_thumbnail_id($tag->id);
        if (!empty($imageId)) {
            $imagePath = get_the_post_thumbnail_url($tag->id);
        }
        $varFields['{image}'] = $imagePath;
        $varFields['{picthtml}'] = '<img alt="" src="'.$imagePath.'">';
        if (!in_array('image', $tag->display)) $imagePath = '';

        $varFields['{full}'] = $this->cleanExtensionContent($element->post_content);
        $varFields['{shortdesc}'] = $this->cleanExtensionContent($element->post_excerpt);
        $varFields['{intro}'] = $this->cleanExtensionContent($this->getIntro($element->post_content));
        if (in_array('full', $tag->display)) {
            $contentText .= $varFields['{full}'];
        } elseif (in_array('intro', $tag->display)) {
            $contentText .= $varFields['{intro}'];
        }

        // Dates
        $varFields['{startdate}'] = '';
        $varFields['{simpleenddate}'] = '';
        $varFields['{starthour}'] = '';
        $varFields['{startminutes}'] = '';
        $varFields['{startampm}'] = '';
        $varFields['{startdaytime}'] = '';
        $varFields['{enddate}'] = '';
        $varFields['{simplestartdate}'] = '';
        $varFields['{endhour}'] = '';
        $varFields['{endminutes}'] = '';
        $varFields['{endampm}'] = '';
        $varFields['{enddaytime}'] = '';
        $varFields['{date}'] = '';
        if (!empty($properties['mec_start_date']->value)) {
            $varFields['{starthour}'] = $properties['mec_start_time_hour']->value;
            $varFields['{startminutes}'] = $properties['mec_start_time_minutes']->value;
            $varFields['{startampm}'] = $properties['mec_start_time_ampm']->value;
            $varFields['{startdaytime}'] = $properties['mec_start_day_seconds']->value;

            $varFields['{enddate}'] = $properties['mec_end_date']->value;
            $varFields['{endhour}'] = $properties['mec_end_time_hour']->value;
            $varFields['{endminutes}'] = $properties['mec_end_time_minutes']->value;
            $varFields['{endampm}'] = $properties['mec_end_time_ampm']->value;
            $varFields['{enddaytime}'] = $properties['mec_end_day_seconds']->value;

            $tmpStartDate = acym_getTime($properties['mec_start_date']->value);
            $varFields['{simplestartdate}'] = acym_date($tmpStartDate, acym_translation('ACYM_DATE_FORMAT_LC1'));
            if ($properties['mec_allday']->value) {
                $startDate = $varFields['{simplestartdate}'];
            } else {
                $tmpStartDateTime = acym_getTime($properties['mec_start_date']->value) + $properties['mec_start_day_seconds']->value;
                $startDate = acym_date($tmpStartDateTime, acym_translation('ACYM_DATE_FORMAT_LC2'));
            }
            $varFields['{startdate}'] = $startDate;

            $tmpEndDate = acym_getTime($properties['mec_end_date']->value);
            $varFields['{simpleenddate}'] = acym_date($tmpEndDate, acym_translation('ACYM_DATE_FORMAT_LC1'));
            if ($properties['mec_allday']->value) {
                $endDate = $varFields['{simpleenddate}'];
            } else {
                $tmpEndDateTime = acym_getTime($properties['mec_end_date']->value) + $properties['mec_end_day_seconds']->value;
                $endDate = acym_date($tmpEndDateTime, acym_translation('ACYM_DATE_FORMAT_LC2'));
            }
            $varFields['{enddate}'] = $endDate;

            $varFields['{date}'] = $startDate.' - '.$endDate;
        }
        if (in_array('date', $tag->display) && !empty($properties['mec_start_date']->value)) {
            $customFields[] = [
                $varFields['{date}'],
                acym_translation('ACYM_DATE'),
            ];
        }

        $varFields['{location}'] = '';
        $varFields['{locationaddress}'] = '';
        $varFields['{locationlatlong}'] = '';
        $varFields['{locationurl}'] = '';
        if (!empty($properties['mec_location_id']->value)) {
            $addressDetails = get_term_meta($properties['mec_location_id']->value);
            $location = [];
            if (!empty($addressDetails['address'][0])) {
                $varFields['{locationaddress}'] = $addressDetails['address'][0];
                $location[] = $addressDetails['address'][0];
            }
            if (!empty($addressDetails['latitude'][0]) || !empty($addressDetails['longitude'][0])) {
                $varFields['{locationlatlong}'] = $addressDetails['latitude'][0].','.$addressDetails['longitude'][0];
                $location[] = $addressDetails['latitude'][0].','.$addressDetails['longitude'][0];
            }
            if (!empty($addressDetails['url'][0])) {
                $varFields['{locationurl}'] = $addressDetails['url'][0];
                $location[] = '<a href="'.$addressDetails['url'][0].'" target="_blank">'.$addressDetails['url'][0].'</a>';
            }
            $varFields['{location}'] = implode('<br />', $location);
            if (in_array('location', $tag->display) && !empty($varFields['{location}'])) {
                $customFields[] = [
                    $varFields['{location}'],
                    acym_translation('ACYM_LOCATION'),
                ];
            }
        }

        $mecMain = new MEC_main();
        $price = empty($properties['mec_cost']->value) ? 0 : $properties['mec_cost']->value;
        $price = $mecMain->render_price($price);
        $varFields['{price}'] = $price;
        if (in_array('price', $tag->display)) {
            $customFields[] = [
                $varFields['{price}'],
                __('Cost', $this->textDomain),
            ];
        }

        $varFields['{cats}'] = get_the_term_list($tag->id, 'mec_category', '', ', ');
        if (in_array('cats', $tag->display) && !empty($varFields['{cats}'])) {
            $customFields[] = [
                $varFields['{cats}'],
                acym_translation('ACYM_CATEGORIES'),
            ];
        }

        $varFields['{organizer}'] = '';
        $organizer_id = get_post_meta($tag->id, 'mec_organizer_id', true);
        $organizerUrl = get_term_meta($organizer_id, 'url', true);
        $organizerTel = get_term_meta($organizer_id, 'tel', true);
        $organizerEmail = get_term_meta($organizer_id, 'email', true);
        // $organizer_id = 1 : hide organizer
        if ($organizer_id != 1) {
            $organizer = [];
            $organizerValue = get_term($organizer_id, 'mec_organizer');
            if (!empty($organizerValue)) {
                $organizer[] = $organizerValue->name;
            }
            if (!empty($organizerTel)) {
                $organizer[] = $organizerTel;
            }
            if (!empty($organizerEmail)) {
                $organizer[] = $organizerEmail;
            }
            if (!empty($organizerUrl)) {
                $organizer[] = '<a href="'.$organizerUrl.'" target="_blank">'.$organizerUrl.'</a>';
            }
            $varFields['{organizer}'] = implode('<br />', $organizer);
            if (in_array('organizer', $tag->display) && !empty($varFields['{organizer}'])) {
                $customFields[] = [
                    $varFields['{organizer}'],
                    acym_translation('ACYM_ORGANIZER'),
                ];
            }
        }

        $otherOrganizersIds = get_post_meta($tag->id, 'mec_additional_organizer_ids', true);
        if (!is_array($otherOrganizersIds)) $otherOrganizersIds = [$otherOrganizersIds];
        $otherOrganizers = [];
        if (!empty($otherOrganizersIds)) {
            foreach ($otherOrganizersIds as $otherOrganizersId) {
                $otherOrganizer = get_term($otherOrganizersId, 'mec_organizer');
                $otherOrganizerUrl = get_term_meta($otherOrganizersId, 'url', true);
                if (isset($otherOrganizer->name)) {
                    $otherOrganizers[] = $otherOrganizer->name;
                    $otherOrganizerTel = get_term_meta($otherOrganizersId, 'tel', true);
                    $otherOrganizerEmail = get_term_meta($otherOrganizersId, 'email', true);
                    if (!empty($otherOrganizerTel)) {
                        $otherOrganizers[] = $otherOrganizerTel;
                    }
                    if (!empty($otherOrganizerEmail)) {
                        $otherOrganizers[] = $otherOrganizerEmail;
                    }
                    if (!empty($otherOrganizerUrl)) {
                        $otherOrganizers[] = '<a href="'.$otherOrganizerUrl.'" target="_blank">'.$otherOrganizerUrl.'</a>';
                    }
                }
            }
        }
        $varFields['{otherOrganizer}'] = implode('<br />', $otherOrganizers);
        if (in_array('otherOrganizer', $tag->display) && !empty($varFields['{otherOrganizer}'])) {
            $customFields[] = [
                $varFields['{otherOrganizer}'],
                acym_translation('ACYM_OTHER_ORGANIZER'),
            ];
        }

        $varFields['{otherLocation}'] = '';
        $varFields['{otherLocationAddress}'] = '';
        $varFields['{otherLocationLatLong}'] = '';
        $varFields['{otherLocationUrl}'] = '';
        $additionalLocationsIds = get_post_meta($tag->id, 'mec_additional_location_ids', true);
        $otherLocation = [];
        if (!empty($additionalLocationsIds)) {
            foreach ($additionalLocationsIds as $additionalLocationsId) {
                $addressDetails = get_term_meta($additionalLocationsId, 'address', true);
                if (!empty($addressDetails)) {
                    $varFields['{otherLocationAddress}'] = $addressDetails;
                    $otherLocation[] = $addressDetails;
                }
                $longitude = get_term_meta($additionalLocationsId, 'longitude', true);
                $latitude = get_term_meta($additionalLocationsId, 'latitude', true);
                if (!empty($latitude) || !empty($longitude)) {
                    $varFields['{otherLocationLatLong}'] = $latitude.','.$longitude;
                    $otherLocation[] = $latitude.','.$longitude;
                }
                $url = get_term_meta($additionalLocationsId, 'url', true);
                if (!empty($url)) {
                    $varFields['{otherLocationUrl}'] = $url;
                    $otherLocation[] = '<a href="'.$url.'" target="_blank">'.$url.'</a>';
                }
            }
            $varFields['{otherLocation}'] = implode('<br />', $otherLocation);
            if (in_array('otherLocation', $tag->display) && !empty($varFields['{otherLocation}'])) {
                $customFields[] = [
                    $varFields['{otherLocation}'],
                    acym_translation('ACYM_OTHER_LOCATION'),
                ];
            }
        }

        $varFields['{eventNextOccurrences}'] = '';
        $eventOccurrences = acym_loadObjectList('SELECT `post_id`, `tstart`, `tend` FROM `#__mec_dates` WHERE `post_id`='.intval($tag->id).' LIMIT 20');
        $occurrences = '';
        if (!empty($eventOccurrences)) {
            foreach ($eventOccurrences as $occurrence) {
                $occurrences .= acym_date($occurrence->tstart, 'ACYM_DATE_FORMAT_LC4').' - '.acym_date($occurrence->tend, 'ACYM_DATE_FORMAT_LC4').'<br />';
            }

            $varFields['{eventNextOccurrences}'] = $occurrences;
            if (in_array('eventNextOccurrences', $tag->display) && !empty($varFields['{eventNextOccurrences}'])) {
                $customFields[] = [
                    $varFields['{eventNextOccurrences}'],
                    acym_translation('ACYM_NEXT_OCCURRENCES'),
                ];
            }
        }

        if ($this->fullInstalled) {
            $bookingIsOn = get_option('mec_options', []);
            if ($bookingIsOn['settings']['booking_status']) {
                $properties = acym_loadObjectList(
                    'SELECT post_id, meta_key, meta_value AS `value` FROM #__postmeta WHERE meta_key = "mec_event_id" AND meta_value ='.intval($tag->id)
                );
                $attendeesIds = [];
                if (!empty($properties)) {
                    foreach ($properties as $property) {
                        $attendeesIds[] = acym_escapeDB($property->post_id);
                    }
                }
                $booked = 0;
                $attendeesIdsString = implode(',', $attendeesIds);
                $mecTicketIds = acym_loadObjectList(
                    'SELECT post_id, meta_key, meta_value AS `value` FROM #__postmeta WHERE meta_key = "mec_ticket_id" AND post_id IN('.$attendeesIdsString.')'
                );
                if (!empty($mecTicketIds)) {
                    foreach ($mecTicketIds as $mecTicketId) {
                        $booked += count(array_filter(explode(',', trim($mecTicketId->value, ','))));
                    }
                }
                $varFields['{attendees}'] = acym_translationSprintf('ACYM_N_ATTENDEES', $booked);
                if (in_array('attendees', $tag->display) && !empty($varFields['{attendees}'])) {
                    $customFields[] = [
                        $varFields['{attendees}'],
                        acym_translation('ACYM_ATTENDEES'),
                    ];
                }

                $tab = get_post_meta($tag->id, 'mec_booking', true);
                if (array_key_exists('bookings_limit', $tab)) {
                    $varFields['{bookingsLimit}'] = $tab['bookings_limit'];
                    if (in_array('bookingsLimit', $tag->display) && !empty($varFields['{bookingsLimit}'])) {
                        $customFields[] = [
                            $varFields['{bookingsLimit}'],
                            acym_translation('ACYM_BOOKING_LIMIT'),
                        ];
                    }
                }
            }
        }

        $varFields['{tags}'] = get_the_term_list($tag->id, 'post_tag', '', ', ');
        if (in_array('tags', $tag->display) && !empty($varFields['{tags}'])) {
            $customFields[] = [
                $varFields['{tags}'],
                acym_translation('ACYM_TAGS'),
            ];
        }

        $varFields['{labels}'] = get_the_term_list($tag->id, 'mec_label', '', ', ');
        if (in_array('labels', $tag->display) && !empty($varFields['{labels}'])) {
            $customFields[] = [
                $varFields['{labels}'],
                __('Event Labels', $this->textDomain),
            ];
        }

        $varFields['{moreinfo}'] = empty($properties['mec_more_info']->value) ? ''
            : '<a target="_blank" href="'.$properties['mec_more_info']->value.'">'.$properties['mec_more_info']->value.'</a>';
        if (in_array('moreinfo', $tag->display) && !empty($properties['mec_more_info']->value)) {
            $customFields[] = [
                $varFields['{moreinfo}'],
                __('More Info', $this->textDomain),
            ];
        }

        $varFields['{readmore}'] = '<a class="acymailing_readmore_link" style="text-decoration:none;" target="_blank" href="'.$link.'"><span class="acymailing_readmore">'.acym_escape(
                acym_translation('ACYM_READ_MORE')
            ).'</span></a>';
        if ($tag->readmore === '1') {
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

    protected function replaceMultiple(&$email)
    {
        $this->generateByCategory($email);
        if (empty($this->tags)) return;
        $this->pluginHelper->replaceTags($email, $this->tags, true);
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
                    JOIN #__postmeta AS startdate ON post.ID = startdate.post_id AND startdate.meta_key = "mec_start_date" 
                    JOIN #__postmeta AS starthour ON post.ID = starthour.post_id AND starthour.meta_key = "mec_start_day_seconds" ';

            $where = [];

            $selectedArea = $this->getSelectedArea($parameter);
            if (!empty($selectedArea)) {
                $query .= 'JOIN #__term_relationships AS cat ON post.ID = cat.object_id ';
                $where[] = 'cat.term_taxonomy_id IN ('.implode(',', $selectedArea).')';
            }

            $where[] = 'post.post_type = "mec-events"';
            $where[] = 'post.post_status = "publish"';
            $where[] = 'CAST(CONCAT(startdate.meta_value, " ", (starthour.meta_value/3600), ":", ((starthour.meta_value%3600)/60), ":00") AS datetime) >= '.acym_escapeDB(
                    $parameter->from
                );

            if (!empty($parameter->to)) {
                $where[] = 'CAST(CONCAT(startdate.meta_value, " ", (starthour.meta_value/3600), ":", ((starthour.meta_value%3600)/60), ":00") AS datetime) <= '.acym_escapeDB(
                        $parameter->to
                    );
            }

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

    /**************** Custom view ****************/
    public function getStandardStructure(&$customView)
    {
        $tag = new stdClass();
        $tag->id = 0;

        $format = new stdClass();
        $format->tag = $tag;
        $format->title = '{title}';
        $format->afterTitle = 'Cost: {price}';
        $format->afterArticle = acym_translation('ACYM_DATE').': {date} <br /> '.acym_translation('ACYM_LOCATION').': {location}';
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
            'starthour' => ['ACYM_START_HOUR'],
            'endhour' => ['ACYM_END_HOUR'],
            'startminutes' => ['ACYM_START_MINUTES'],
            'endminutes' => ['ACYM_END_MINUTES'],
            'startampm' => ['ACYM_START_AM_PM'],
            'endampm' => ['ACYM_END_AM_PM'],
            'startdaytime' => ['ACYM_START_DAY_TIME'],
            'enddaytime' => ['ACYM_END_DAY_TIME'],
            'locationaddress' => ['ACYM_ADDRESS'],
            'locationlatlong' => ['ACYM_LATITUDE_LONGITUDE'],
            'locationurl' => ['ACYM_LOCATION_URL'],
        ];
    }

    public function initElementOptionsCustomView()
    {
        $query = 'SELECT post.*
                    FROM #__posts AS post
                    WHERE post.post_type = "mec_events" 
                        AND post.post_status = "publish"';
        $element = acym_loadObject($query);
        if (empty($element)) return;
        foreach ($element as $key => $value) {
            $this->elementOptions[$key] = [$key];
        }
    }

    /**************** Filters ****************/
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
            echo json_encode([
                [
                    'value' => $id,
                    'text' => $id.' - '.$subject,
                ],
            ]);
            exit;
        }

        $return = [];
        $search = acym_getVar('string', 'search', '');
        $elements = acym_loadObjectList(
            'SELECT ID, post_title 
            FROM #__posts 
            WHERE post_title LIKE '.acym_escapeDB('%'.$search.'%').' AND post_type = "mec-events" 
            ORDER BY post_title ASC'
        );

        foreach ($elements as $oneElement) {
            $return[] = [$oneElement->ID, $oneElement->ID.' - '.$oneElement->post_title];
        }

        echo json_encode($return);
        exit;
    }

    public function onAcymDeclareFilters(&$filters)
    {
        if (!$this->fullInstalled) return;

        $this->filtersFromConditions($filters);
    }

    public function onAcymDeclareConditions(&$conditions)
    {
        if (!$this->fullInstalled) return;

        $conditions['user']['moderneventscalendar'] = new stdClass();
        $conditions['user']['moderneventscalendar']->name = 'Modern Events Calendar';
        $conditions['user']['moderneventscalendar']->option = '<div class="cell grid-x grid-margin-x">';
        $conditions['user']['moderneventscalendar']->option .= '<div class="intext_select_automation cell">';
        $selectOptions = [
            'class' => 'acym__select acym_select2_ajax',
            'data-placeholder' => acym_translation('ACYM_ANY_EVENT'),
            'data-params' => [
                'plugin' => __CLASS__,
                'trigger' => 'searchEvent',
            ],
        ];
        $conditions['user']['moderneventscalendar']->option .= acym_select(
            [],
            'acym_condition[conditions][__numor__][__numand__][moderneventscalendar][event]',
            null,
            $selectOptions
        );
        $conditions['user']['moderneventscalendar']->option .= '</div>';
        $conditions['user']['moderneventscalendar']->option .= '</div>';

        $conditions['user']['moderneventscalendar']->option .= '<div class="cell grid-x grid-margin-x">';
        $conditions['user']['moderneventscalendar']->option .= acym_dateField(
            'acym_condition[conditions][__numor__][__numand__][moderneventscalendar][datemin]',
            '',
            'cell shrink'
        );
        $conditions['user']['moderneventscalendar']->option .= '<span class="acym__title acym__title__secondary acym_vcenter margin-bottom-0 cell shrink"><</span>';
        $conditions['user']['moderneventscalendar']->option .= '<span class="acym_vcenter">'.acym_translation('ACYM_REGISTRATION_DATE').'</span>';
        $conditions['user']['moderneventscalendar']->option .= '<span class="acym__title acym__title__secondary acym_vcenter margin-bottom-0 cell shrink"><</span>';
        $conditions['user']['moderneventscalendar']->option .= acym_dateField(
            'acym_condition[conditions][__numor__][__numand__][moderneventscalendar][datemax]',
            '',
            'cell shrink'
        );
        $conditions['user']['moderneventscalendar']->option .= '</div>';
    }

    public function onAcymProcessCondition_moderneventscalendar(&$query, $options, $num, &$conditionNotValid)
    {
        if (!$this->fullInstalled) return;

        $this->processConditionFilter_moderneventscalendar($query, $options, $num);
        $affectedRows = $query->count();
        if (empty($affectedRows)) $conditionNotValid++;
    }

    public function onAcymProcessFilterCount_moderneventscalendar(&$query, $options, $num)
    {
        if (!$this->fullInstalled) return '';
        $this->onAcymProcessFilter_moderneventscalendar($query, $options, $num);

        return acym_translationSprintf('ACYM_SELECTED_USERS', $query->count());
    }

    public function onAcymProcessFilter_moderneventscalendar(&$query, $options, $num)
    {
        $this->processConditionFilter_moderneventscalendar($query, $options, $num);
    }

    private function processConditionFilter_moderneventscalendar(&$query, $options, $num)
    {
        if (!$this->fullInstalled) return;

        $mecPost = 'mecP'.$num;
        $mecUser = 'mecU'.$num;
        $query->join[$mecUser] = '#__users AS '.$mecUser.' ON '.$mecUser.'.user_email = user.email';
        $query->join[$mecPost] = '#__posts AS '.$mecPost.' ON '.$mecPost.'.post_author = '.$mecUser.'.ID AND '.$mecPost.'.post_type = "mec-books"';

        if (!empty($options['event'])) {
            $mecPmEvent = 'mecPmEvent'.$num;
            $query->join[$mecPmEvent] = '#__postmeta AS '.$mecPmEvent.' 
                ON '.$mecPmEvent.'.post_id = '.$mecPost.'.ID 
                AND '.$mecPmEvent.'.meta_key = "mec_event_id" 
                AND '.$mecPmEvent.'.meta_value = '.(int)$options['event'];
        }

        if (!empty($options['datemin']) || !empty($options['datemax'])) {
            $mecPmDate = 'mecPmDate'.$num;
            $mecDateMin = '';
            $mecDateMax = '';

            if (!empty($options['datemin'])) {
                $options['datemin'] = acym_replaceDate($options['datemin']);
                if (!is_numeric($options['datemin'])) $options['datemin'] = strtotime($options['datemin']);
                if (!empty($options['datemin'])) {
                    $mecDateMin = ' AND '.$mecPmDate.'.meta_value > '.acym_escapeDB(acym_date($options['datemin'], 'Y-m-d H:i:s'));
                }
            }

            if (!empty($options['datemax'])) {
                $options['datemax'] = acym_replaceDate($options['datemax']);
                if (!is_numeric($options['datemax'])) $options['datemax'] = strtotime($options['datemax']);
                if (!empty($options['datemax'])) {
                    $mecDateMax = ' AND '.$mecPmDate.'.meta_value < '.acym_escapeDB(acym_date($options['datemax'], 'Y-m-d H:i:s'));
                }
            }

            $query->join[$mecPmDate] = '#__postmeta AS '.$mecPmDate.' 
                ON '.$mecPmDate.'.post_id = '.$mecPost.'.ID 
                AND '.$mecPmDate.'.meta_key = "mec_booking_time" 
                '.$mecDateMin.$mecDateMax;
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
        if (!$this->fullInstalled) return;

        if (empty($automation['moderneventscalendar'])) return;

        if (empty($automation['moderneventscalendar']['event'])) {
            $event = acym_translation('ACYM_ANY_EVENT');
        } else {
            $event = acym_loadResult(
                'SELECT post_title 
                    FROM #__posts 
                    WHERE ID = '.intval($automation['moderneventscalendar']['event'])
            );
        }

        $finalText = acym_translationSprintf('ACYM_REGISTERED_TO', $event);

        $dates = [];
        if (!empty($automation['moderneventscalendar']['datemin'])) {
            $dates[] = acym_translation('ACYM_AFTER').' '.acym_replaceDate($automation['moderneventscalendar']['datemin'], true);
        }

        if (!empty($automation['moderneventscalendar']['datemax'])) {
            $dates[] = acym_translation('ACYM_BEFORE').' '.acym_replaceDate($automation['moderneventscalendar']['datemax'], true);
        }

        if (!empty($dates)) {
            $finalText .= ' '.implode(' '.acym_translation('ACYM_AND').' ', $dates);
        }

        $automation = $finalText;
    }
}
