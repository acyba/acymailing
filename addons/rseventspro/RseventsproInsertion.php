<?php

use AcyMailing\Helpers\TabHelper;
use Joomla\Registry\Registry;

trait RseventsproInsertion
{
    private $rsconfig;

    public function getStandardStructure(&$customView)
    {
        $tag = new stdClass();
        $tag->id = 0;

        $format = new stdClass();
        $format->tag = $tag;
        $format->title = '{title}';
        $format->afterTitle = acym_translation('ACYM_DATE').': {date}';
        $format->afterArticle = '';
        $format->imagePath = '{icon}';
        $format->description = '{short} <br> '.acym_translation('ACYM_LOCATION').': {location}';
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
        ];
    }

    public function initElementOptionsCustomView()
    {
        $query = 'SELECT event.*, location.name AS location_name, location.id AS location_id ';
        $query .= 'FROM `#__rseventspro_events` AS event ';
        $query .= 'LEFT JOIN `#__rseventspro_locations` AS location ON event.`location` = location.`id` AND location.`published` = 1 ';
        $element = acym_loadObject($query);
        if (empty($element)) return;
        foreach ($element as $key => $value) {
            $this->elementOptions[$key] = [$key];
        }
    }

    public function insertionOptions($defaultValues = null)
    {
        $this->defaultValues = $defaultValues;

        acym_loadLanguageFile('com_rseventspro', JPATH_SITE);
        $this->categories = acym_loadObjectList('SELECT `id`, `parent_id`, `title` FROM `#__categories` WHERE published = 1 AND `extension` = "com_rseventspro"', 'id');

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
        ];

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
                [
                    'title' => 'ACYM_AUTO_LOGIN',
                    'tooltip' => 'ACYM_AUTO_LOGIN_DESCRIPTION',
                    'type' => 'boolean',
                    'name' => 'autologin',
                    'default' => false,
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
                'title' => 'ACYM_ORDER_BY',
                'type' => 'select',
                'name' => 'order',
                'options' => [
                    'id' => 'ACYM_ID',
                    'start' => 'ACYM_DATE',
                    'name' => 'ACYM_TITLE',
                    'rand' => 'ACYM_RANDOM',
                ],
                'default' => 'start',
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
        $this->query = 'FROM `#__rseventspro_events` AS event ';
        $this->filters = [];
        $this->filters[] = 'event.published = 1';
        $this->searchFields = ['event.id', 'event.name'];
        $this->pageInfo->order = 'event.start';
        $this->elementIdTable = 'event';
        $this->elementIdColumn = 'id';

        if (!acym_isAdmin() && $this->getParam('front', 'all') === 'author') {
            $this->filters[] = 'event.owner = '.intval(acym_currentUserId());
        }

        if ($this->getParam('hidepast', '1') === '1') {
            $this->filters[] = 'event.`start` >= '.acym_escapeDB(date('Y-m-d H:i:s'));
        }

        parent::prepareListing();

        if (!empty($this->pageInfo->filter_cat)) {
            $this->query .= 'JOIN `#_rseventspro_taxonomy` AS catmap ON event.`id` = catmap.`ide` ';
            $this->filters[] = 'catmap.`type` = "category" AND catmap.`id` = '.intval($this->pageInfo->filter_cat);
        }

        $listingOptions = [
            'header' => [
                'name' => [
                    'label' => 'ACYM_TITLE',
                    'size' => '8',
                ],
                'start' => [
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
        acym_loadLanguageFile('com_rseventspro', JPATH_SITE);

        // We need the helper to format the price
        if (!include_once JPATH_ROOT.'/components/com_rseventspro/helpers/rseventspro.php') {
            if (acym_isAdmin()) acym_enqueueMessage('Could not load the RSEvents!Pro helper', 'error');

            return false;
        }

        $this->rsconfig = rseventsproHelper::getConfig();

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

            if (!empty($parameter->to)) {
                $parameter->to = acym_date(acym_replaceDate($parameter->to), 'Y-m-d H:i:s');
            }

            $query = 'SELECT DISTINCT event.id FROM `#__rseventspro_events` AS event ';

            $where = [];
            $where[] = 'event.`published` = 1';

            if (!empty($parameter->featured)) {
                $where[] = 'event.`featured` = 1';
            }

            $selectedArea = $this->getSelectedArea($parameter);
            if (!empty($selectedArea)) {
                $query .= 'JOIN `#__rseventspro_taxonomy` AS cat ON event.id = cat.ide ';
                $where[] = 'cat.id IN ('.implode(',', $selectedArea).')';
                $where[] = 'cat.type = "category"';
            }

            if ((empty($parameter->mindelay) || substr($parameter->mindelay, 0, 1) != '-') && (empty($parameter->delay) || substr($parameter->delay, 0, 1) != '-')) {
                if (!empty($parameter->addcurrent)) {
                    //not finished and next events
                    $where[] = 'event.`end` >= '.acym_escapeDB($parameter->from);
                } else {
                    //not started events
                    $where[] = 'event.`start` >= '.acym_escapeDB($parameter->from);
                }
            }

            //should we display only events starting in the sending day ?
            if (!empty($parameter->todaysevent)) {
                $where[] = 'event.`start` <= '.acym_escapeDB(date('Y-m-d 23:59:59', $time));
            }

            if (!empty($parameter->mindelay)) $where[] = 'event.`start` >= '.acym_escapeDB(date('Y-m-d H:i:s', $time + $parameter->mindelay));
            if (!empty($parameter->delay)) $where[] = 'event.`start` <= '.acym_escapeDB(date('Y-m-d H:i:s', $time + $parameter->delay));
            if (!empty($parameter->to)) $where[] = 'event.`start` <= '.acym_escapeDB($parameter->to);

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
        $query = 'SELECT event.*, location.name AS location_name, location.id AS location_id ';
        $query .= 'FROM `#__rseventspro_events` AS event ';
        $query .= 'LEFT JOIN `#__rseventspro_locations` AS location ON event.`location` = location.`id` AND location.`published` = 1 ';
        $query .= 'WHERE event.`id` = '.intval($tag->id);

        $element = $this->initIndividualContent($tag, $query);

        if (empty($element)) return '';

        $varFields = $this->getCustomLayoutVars($element);

        $language = $this->getLanguage();

        $link = rseventsproHelper::route('index.php?option=com_rseventspro&layout=show&id='.rseventsproHelper::sef($element->id, $element->name).$language);
        $link = str_replace('/administrator/', '/', $link);
        if (!empty($tag->autologin)) {
            $link .= (strpos($link, '?') ? '&' : '?').'autoSubId={subscriber:id}&subKey={subscriber:key|urlencode}';
        }
        $varFields['{link}'] = $link;

        $title = '';
        $afterTitle = '';
        $afterArticle = '';

        $imagePath = '';
        $contentText = '';
        $customFields = [];

        $varFields['{title}'] = $element->name;
        if (in_array('title', $tag->display)) $title = $varFields['{title}'];
        $varFields['{short}'] = '<p>'.$element->small_description.'</p>';
        if (in_array('short', $tag->display)) $contentText .= $varFields['{short}'];
        $varFields['{desc}'] = $element->description;
        if (in_array('desc', $tag->display)) $contentText .= $varFields['{desc}'];


        if (empty($element->icon)) {
            $icon = 'default/'.(empty($this->rsconfig->default_image) ? 'blank.png' : $this->rsconfig->default_image);
        } else {
            $icon = 'events/'.$element->icon;
        }
        $imagePath = acym_rootURI().'components/com_rseventspro/assets/images/'.$icon;
        $varFields['{icon}'] = $imagePath;
        $varFields['{picthtml}'] = '<img alt="" src="'.$imagePath.'">';
        if (!in_array('icon', $tag->display)) $imagePath = '';

        if (!empty($element->allday)) {
            $date = acym_translation('COM_RSEVENTSPRO_GLOBAL_ON').' '.rseventsproHelper::date($element->start, $this->rsconfig->global_date, true);
        } else {
            $date = acym_translation('COM_RSEVENTSPRO_GLOBAL_FROM').' '.rseventsproHelper::date($element->start, null, true);
            if (empty($tag->noenddate)) $date .= ' '.acym_translation('COM_RSEVENTSPRO_TO_LOWERCASE').' '.rseventsproHelper::date($element->end, null, true);
        }
        $varFields['{date}'] = $date;
        if (in_array('date', $tag->display)) $customFields[] = [$varFields['{date}'], acym_translation('ACYM_DATE')];


        $varFields['{location}'] = '';
        if (!empty($element->location_id)) {
            $url = rseventsproHelper::route('index.php?option=com_rseventspro&layout=location&id='.rseventsproHelper::sef($element->location_id, $element->location_name));
            $url = str_replace('/administrator/', '/', $url);
            $varFields['{location}'] = '<a href="'.$url.'" target="_blank">'.$element->location_name.'</a>';
        }
        if (in_array('location', $tag->display) && !empty($element->location_id)) {
            $customFields[] = [$varFields['{location}'], acym_translation('COM_RSEVENTSPRO_GLOBAL_AT')];
        }

        $categories = [];

        $allcategories = acym_loadObjectList(
            'SELECT category.`id`, category.`title` 
                FROM `#__categories` AS category 
                JOIN #__rseventspro_taxonomy AS map ON category.`id` = map.`id` 
                WHERE category.`published` = 1 
                    AND category.`extension` = "com_rseventspro" 
                    AND map.`type` = "category" 
                    AND map.`ide` = '.intval($tag->id),
            'title'
        );

        if (!empty($allcategories)) {
            foreach ($allcategories as $cat) {
                $style = '';
                if ($this->rsconfig->color) {
                    $color = '';
                    if ($cat->params) {
                        $registry = new Registry();
                        $registry->loadString($cat->params);
                        $color = $registry->get('color');
                    }

                    $style = empty($color) ? '' : 'style="color: '.$color.'"';
                }
                $url = rseventsproHelper::route('index.php?option=com_rseventspro&category='.rseventsproHelper::sef($cat->id, $cat->title).$language);
                $url = str_replace('administrator/', '', $url);

                $categories[] = '<a href="'.$url.'" class="rs_cat_link" '.$style.' target="_blank">'.$cat->title.'</a>';
            }
        }
        $varFields['{cats}'] = implode(', ', $categories);
        if (in_array('cats', $tag->display) && !empty($allcategories)) $customFields[] = [$varFields['{cats}'], acym_translation('COM_RSEVENTSPRO_GLOBAL_CATEGORIES')];

        $tags = [];

        $alltags = acym_loadObjectList(
            'SELECT tag.id, tag.name 
                FROM #__rseventspro_tags AS tag 
                JOIN #__rseventspro_taxonomy AS map ON map.`id` = tag.`id` 
                WHERE map.`type` = "tag" 
                    AND tag.`published` = 1
                    AND map.`ide` = '.intval($tag->id),
            'name'
        );

        if (!empty($alltags)) {
            foreach ($alltags as $oneTag) {
                $url = rseventsproHelper::route('index.php?option=com_rseventspro&tag='.rseventsproHelper::sef($oneTag->id, $oneTag->name).$language);
                $url = str_replace('administrator/', '', $url);

                $tags[] = '<a href="'.$url.'" class="rs_tag_link" target="_blank">'.$oneTag->name.'</a>';
            }
        }
        $varFields['{tags}'] = implode(', ', $tags);
        if (in_array('tags', $tag->display) && !empty($alltags)) $customFields[] = [$varFields['{tags}'], acym_translation('COM_RSEVENTSPRO_GLOBAL_TAGS')];

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
}
