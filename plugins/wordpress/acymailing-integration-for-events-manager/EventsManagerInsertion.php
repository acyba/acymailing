<?php

use AcyMailing\Helpers\TabHelper;

trait EventsManagerInsertion
{
    public function getStandardStructure(string &$customView): void
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

    public function initReplaceOptionsCustomView(): void
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

    public function initElementOptionsCustomView(): void
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

    public function insertionOptions(?object $defaultValues = null): void
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
        $this->filters[] = 'post.post_type = "event"';
        $this->filters[] = 'post.post_status = "publish"';
        $this->searchFields = ['post.ID', 'post.post_title'];
        $this->pageInfo->order = 'post.ID';
        $this->elementIdTable = 'post';
        $this->elementIdColumn = 'ID';

        if ($this->getParam('hidepast', '1') === '1') {
            $this->query .= 'JOIN #__postmeta AS startdate ON post.ID = startdate.post_id AND startdate.meta_key = "_event_start" ';
            $this->filters[] = 'startdate.`meta_value` >= '.acym_escapeDB(gmdate('Y-m-d H:i:s'));
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

    public function replaceContent(object &$email): void
    {
        $this->replaceMultiple($email);
        $this->replaceOne($email);
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

    public function replaceIndividualContent(object $tag): string
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
        // phpcs:ignore PluginCheck.CodeAnalysis.ImageFunctions.NonEnqueuedImage
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
                // phpcs:ignore WordPress.WP.I18n.TextDomainMismatch
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
}
