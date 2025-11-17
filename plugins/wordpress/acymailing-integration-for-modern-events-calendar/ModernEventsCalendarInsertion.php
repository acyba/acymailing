<?php

use AcyMailing\Helpers\TabHelper;

trait ModernEventsCalendarInsertion
{
    public function insertionOptions(?object $defaultValues = null): void
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

        $this->displaySelectionZone($this->getCategoryListing());
        $this->pluginHelper->displayOptions($displayOptions, $identifier, 'grouped', $this->defaultValues);

        $tabHelper->endTab();

        $tabHelper->display('plugin');
    }

    public function prepareListing(): string
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
            $this->filters[] = 'startdate.`meta_value` >= '.acym_escapeDB(gmdate('Y-m-d'));
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

    public function replaceContent(object &$email): void
    {
        $this->replaceMultiple($email);
        $this->replaceOne($email);
    }

    public function replaceIndividualContent(object $tag): string
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

        $link = '';
        if (!empty($properties['mec_read_more'])) {
            $link = $properties['mec_read_more']->value;
        }
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

        $imageId = get_post_thumbnail_id($tag->id);
        if (!empty($imageId)) {
            $imagePath = get_the_post_thumbnail_url($tag->id);
        }
        $varFields['{image}'] = $imagePath;

        // phpcs:ignore PluginCheck.CodeAnalysis.ImageFunctions.NonEnqueuedImage
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
            $locationTerm = get_term($properties['mec_location_id']->value);
            if (!empty($locationTerm)) {
                $varFields['{locationname}'] = $locationTerm->name;
                $varFields['{location}'] = $varFields['{locationname}'];
            }

            $addressDetails = get_term_meta($properties['mec_location_id']->value);
            if (!empty($addressDetails['address'][0])) {
                $varFields['{locationaddress}'] = $addressDetails['address'][0];
            }
            if (!empty($addressDetails['latitude'][0]) || !empty($addressDetails['longitude'][0])) {
                $varFields['{locationlatlong}'] = $addressDetails['latitude'][0].','.$addressDetails['longitude'][0];
            }
            if (!empty($addressDetails['url'][0])) {
                $varFields['{locationurl}'] = $addressDetails['url'][0];
            }

            if (!empty($varFields['{locationurl}'])) {
                $varFields['{location}'] = '<a href="'.$varFields['{locationurl}'].'" target="_blank">'.$varFields['{location}'].'</a>';
            } elseif (!empty($varFields['{locationaddress}'])) {
                $varFields['{location}'] = '<a href="https://maps.google.com/?q='.urlencode($varFields['{locationaddress}']).'" target="_blank">'.$varFields['{location}'].'</a>';
            } elseif (!empty($varFields['{locationlatlong}'])) {
                $varFields['{location}'] = '<a href="https://maps.google.com/?q='.urlencode($varFields['{locationlatlong}']).'" target="_blank">'.$varFields['{location}'].'</a>';
            }

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
                // phpcs:ignore WordPress.WP.I18n.NonSingularStringLiteralDomain
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
                // phpcs:ignore WordPress.WP.I18n.NonSingularStringLiteralDomain
                __('Event Labels', $this->textDomain),
            ];
        }

        $varFields['{moreinfo}'] = empty($properties['mec_more_info']->value) ? ''
            : '<a target="_blank" href="'.$properties['mec_more_info']->value.'">'.$properties['mec_more_info']->value.'</a>';
        if (in_array('moreinfo', $tag->display) && !empty($properties['mec_more_info']->value)) {
            $customFields[] = [
                $varFields['{moreinfo}'],
                // phpcs:ignore WordPress.WP.I18n.NonSingularStringLiteralDomain
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
        $format->link = empty($tag->clickable) && empty($tag->clickableimg) ? '' : $link;
        $format->customFields = $customFields;
        $result = '<div class="acymailing_content">'.$this->pluginHelper->getStandardDisplay($format).'</div>';

        return $this->finalizeElementFormat($result, $tag, $varFields);
    }

    protected function replaceMultiple(&$email): void
    {
        $this->generateByCategory($email);
        if (empty($this->tags)) return;
        $this->pluginHelper->replaceTags($email, $this->tags, true);
    }

    public function generateByCategory(object &$email): object
    {
        $tags = $this->pluginHelper->extractTags($email, 'auto'.$this->name);
        $this->tags = [];

        if (empty($tags)) return $this->generateCampaignResult;

        $time = time();

        foreach ($tags as $oneTag => $parameter) {
            if (isset($this->tags[$oneTag])) continue;

            if (empty($parameter->from)) {
                $parameter->from = gmdate('Y-m-d H:i:s', $time);
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

            if (!empty($parameter->order)) {
                $ordering = explode(',', $parameter->order);
                if ($ordering[0] === 'rand') {
                    $query .= ' ORDER BY rand()';
                } else {
                    $table = 'post.';
                    $column = $ordering[0];

                    if (strpos($column, '.') !== false) {
                        $parts = explode('.', $column, 2);
                        $table = acym_secureDBColumn($parts[0]).'.';
                        $column = $parts[1];
                    }

                    if ($column === 'post_date') {
                        $query .= ' ORDER BY startdate.meta_value '.acym_secureDBColumn(trim($ordering[1])).', starthour.meta_value '.acym_secureDBColumn(trim($ordering[1]));
                    } else {
                        $query .= ' ORDER BY '.$table.'`'.acym_secureDBColumn(trim($column)).'` '.acym_secureDBColumn(trim($ordering[1]));
                    }
                }
            }

            $this->handleMax($query, $parameter);

            $elements = acym_loadResultArray($query);

            $this->tags[$oneTag] = $this->formatIndividualTags($elements, $parameter);
        }

        return $this->generateCampaignResult;
    }

    public function getStandardStructure(string &$customView): void
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

    public function initReplaceOptionsCustomView(): void
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

    public function initElementOptionsCustomView(): void
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
}
