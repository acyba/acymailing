<?php

use AcyMailing\Libraries\acymPlugin;
use AcyMailing\Helpers\TabHelper;

class plgAcymRseventspro extends acymPlugin
{
    var $rsconfig;

    public function __construct()
    {
        parent::__construct();
        $this->cms = 'Joomla';
        $this->installed = acym_isExtensionActive('com_rseventspro');

        $this->pluginDescription->name = 'RSEvents!Pro';
        $this->pluginDescription->icon = ACYM_DYNAMICS_URL.basename(__DIR__).'/icon.png';

        if ($this->installed) {
            $this->displayOptions = [
                'title' => ['ACYM_TITLE', true],
                'icon' => ['ACYM_IMAGE', true],
                'date' => ['ACYM_DATE', true],
                'short' => ['ACYM_SHORT_DESCRIPTION', true],
                'desc' => ['ACYM_DESCRIPTION', false],
                'location' => ['ACYM_LOCATION', true],
                'cats' => ['COM_RSEVENTSPRO_GLOBAL_CATEGORIES', false],
                'tags' => ['COM_RSEVENTSPRO_GLOBAL_TAGS', false],
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

    public function getPossibleIntegrations()
    {
        if (!acym_isAdmin() && $this->getParam('front', 'all') === 'hide') return null;

        return $this->pluginDescription;
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
                'title' => 'ACYM_ONLY_FEATURED',
                'type' => 'boolean',
                'name' => 'featured',
                'default' => false,
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
                    'id' => 'ACYM_ID',
                    'start' => 'ACYM_DATE',
                    'name' => 'ACYM_TITLE',
                    'rand' => 'ACYM_RANDOM',
                ],
                'default' => 'start',
                'defaultdir' => 'asc',
            ],
        ];
        $this->autoContentOptions($catOptions);

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
            if (!empty($parameter->to)) $parameter->to = acym_date(acym_replaceDate($parameter->to), 'Y-m-d H:i:s');

            $query = 'SELECT DISTINCT event.id FROM `#__rseventspro_events` AS event ';

            $where = [];
            $where[] = 'event.`published` = 1';

            $selectedArea = $this->getSelectedArea($parameter);
            if (!empty($selectedArea)) {
                $query .= 'JOIN `#__rseventspro_taxonomy` AS cat ON event.id = cat.ide ';
                $where[] = 'cat.id IN ('.implode(',', $selectedArea).')';
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
                    $where[] = 'event.`start` > '.acym_escapeDB(acym_date($lastGenerated, 'Y-m-d H:i:s', false));
                }
            }

            if (!empty($parameter->featured)) $where[] = 'event.`featured` = 1';

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
                        $registry = new JRegistry();
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
            $subject = acym_loadResult('SELECT `name` FROM #__rseventspro_events WHERE `id` = '.intval($id));
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
        $elements = acym_loadObjectList('SELECT `id`, `name` FROM `#__rseventspro_events` WHERE `name` LIKE '.acym_escapeDB('%'.$search.'%').' ORDER BY `name` ASC');

        foreach ($elements as $oneElement) {
            $return[] = [$oneElement->id, $oneElement->id.' - '.$oneElement->name];
        }

        echo json_encode($return);
        exit;
    }

    public function getTicketsSelection()
    {
        acym_loadLanguageFile('com_rseventspro', JPATH_SITE);

        $id = acym_getVar('int', 'event', 0);
        if (empty($id)) exit;

        $elements = acym_loadObjectList('SELECT `id`, `name` FROM `#__rseventspro_tickets` WHERE `ide` = '.intval($id).' ORDER BY `name` ASC');

        $options = [];
        $options[0] = acym_translation('COM_RSEVENTSPRO_SUBSCRIBER_SELECT_TICKETS');
        foreach ($elements as $oneElement) {
            $options[$oneElement->id] = $oneElement->name;
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

    public function onAcymDeclareConditions(&$conditions)
    {
        acym_loadLanguageFile('com_rseventspro', JPATH_SITE);

        $conditions['user']['rseventspro'] = new stdClass();
        $conditions['user']['rseventspro']->name = 'RSEvents!Pro';
        $conditions['user']['rseventspro']->option = '<div class="cell grid-x grid-margin-x">';

        // Status
        $status = [];
        $status[] = acym_selectOption('0', 'ACYM_ANY_STATUS');
        $status[] = acym_selectOption('1', 'COM_RSEVENTSPRO_RULE_STATUS_INCOMPLETE');
        $status[] = acym_selectOption('2', 'COM_RSEVENTSPRO_RULE_STATUS_COMPLETE');
        $status[] = acym_selectOption('3', 'COM_RSEVENTSPRO_RULE_STATUS_DENIED');

        $conditions['user']['rseventspro']->option .= '<div class="intext_select_automation cell">';
        $conditions['user']['rseventspro']->option .= acym_select(
            $status,
            'acym_condition[conditions][__numor__][__numand__][rseventspro][status]',
            '0',
            'class="acym__select"'
        );
        $conditions['user']['rseventspro']->option .= '</div>';

        // Event
        $conditions['user']['rseventspro']->option .= '<div class="intext_select_automation cell">';
        $conditions['user']['rseventspro']->option .= acym_select(
            [],
            'acym_condition[conditions][__numor__][__numand__][rseventspro][event]',
            null,
            [
                'class' => 'acym__select acym_select2_ajax',
                'data-placeholder' => acym_translation('ACYM_ANY_EVENT'),
                'data-params' => [
                    'plugin' => __CLASS__,
                    'trigger' => 'searchEvent',
                ],
                'acym-automation-reload' => [
                    'plugin' => __CLASS__,
                    'trigger' => 'getTicketsSelection',
                    'change' => '#rseventspro_tochange___numor_____numand__',
                    'name' => 'acym_condition[conditions][__numor__][__numand__][rseventspro][ticket]',
                    'paramFields' => [
                        'event' => 'acym_condition[conditions][__numor__][__numand__][rseventspro][event]',
                    ],
                ],
            ]
        );
        $conditions['user']['rseventspro']->option .= '</div>';

        // Ticket
        $conditions['user']['rseventspro']->option .= '<div class="intext_select_automation cell" id="rseventspro_tochange___numor_____numand__">';
        $conditions['user']['rseventspro']->option .= '<input type="hidden" name="acym_condition[conditions][__numor__][__numand__][rseventspro][ticket]" />';
        $conditions['user']['rseventspro']->option .= '</div>';

        $conditions['user']['rseventspro']->option .= '</div>';

        $conditions['user']['rseventspro']->option .= '<div class="cell grid-x grid-margin-x">';
        $conditions['user']['rseventspro']->option .= acym_dateField('acym_condition[conditions][__numor__][__numand__][rseventspro][datemin]', '', 'cell shrink');
        $conditions['user']['rseventspro']->option .= '<span class="acym__title acym__title__secondary acym_vcenter margin-bottom-0 cell shrink"><</span>';
        $conditions['user']['rseventspro']->option .= '<span class="acym_vcenter">'.acym_translation('COM_RSEVENTSPRO_MY_SUBSCRIPTION_DATE').'</span>';
        $conditions['user']['rseventspro']->option .= '<span class="acym__title acym__title__secondary acym_vcenter margin-bottom-0 cell shrink"><</span>';
        $conditions['user']['rseventspro']->option .= acym_dateField('acym_condition[conditions][__numor__][__numand__][rseventspro][datemax]', '', 'cell shrink');
        $conditions['user']['rseventspro']->option .= '</div>';
    }

    public function onAcymProcessCondition_rseventspro(&$query, $options, $num, &$conditionNotValid)
    {
        $this->processConditionFilter_rseventspro($query, $options, $num);
        $affectedRows = $query->count();
        if (empty($affectedRows)) $conditionNotValid++;
    }

    private function processConditionFilter_rseventspro(&$query, $options, $num)
    {
        $query->join['rseventspro'.$num] = '`#__rseventspro_users` AS rseventspro'.$num.' ON rseventspro'.$num.'.email = user.email';

        if (!empty($options['status'])) $query->where[] = 'rseventspro'.$num.'.state = '.(intval($options['status']) - 1);
        if (!empty($options['event'])) $query->where[] = 'rseventspro'.$num.'.ide = '.intval($options['event']);
        if (!empty($options['ticket'])) {
            $query->join['rsticket'.$num] = '`#__rseventspro_user_tickets` AS rsticket'.$num.' ON rseventspro'.$num.'.id = rsticket'.$num.'.ids';
            $query->where[] = 'rsticket'.$num.'.idt = '.intval($options['ticket']);
        }

        if (!empty($options['datemin'])) {
            $options['datemin'] = acym_replaceDate($options['datemin']);
            if (!is_numeric($options['datemin'])) $options['datemin'] = strtotime($options['datemin']);
            if (!empty($options['datemin'])) {
                $query->where[] = 'rseventspro'.$num.'.date > '.acym_escapeDB(date('Y-m-d H:i:s', $options['datemin']));
            }
        }

        if (!empty($options['datemax'])) {
            $options['datemax'] = acym_replaceDate($options['datemax']);
            if (!is_numeric($options['datemax'])) $options['datemax'] = strtotime($options['datemax']);
            if (!empty($options['datemax'])) {
                $query->where[] = 'rseventspro'.$num.'.date < '.acym_escapeDB(date('Y-m-d H:i:s', $options['datemax']));
            }
        }
    }

    public function onAcymDeclareSummary_conditions(&$automationCondition)
    {
        $this->summaryConditionFilters($automationCondition);
    }

    private function summaryConditionFilters(&$automationCondition)
    {
        if (!empty($automationCondition['rseventspro'])) {
            acym_loadLanguageFile('com_rseventspro', JPATH_SITE);

            if (empty($automationCondition['rseventspro']['event'])) {
                $event = acym_translation('ACYM_ANY_EVENT');
            } else {
                $event = acym_loadResult('SELECT `name` FROM #__rseventspro_events WHERE `id` = '.intval($automationCondition['rseventspro']['event']));
            }

            $status = [
                '0' => 'ACYM_ANY',
                '1' => 'COM_RSEVENTSPRO_RULE_STATUS_INCOMPLETE',
                '2' => 'COM_RSEVENTSPRO_RULE_STATUS_COMPLETE',
                '3' => 'COM_RSEVENTSPRO_RULE_STATUS_DENIED',
            ];

            $status = acym_translation($status[$automationCondition['rseventspro']['status']]);

            $finalText = acym_translationSprintf('ACYM_REGISTERED', $event, $status);

            $dates = [];
            if (!empty($automationCondition['rseventspro']['datemin'])) {
                $dates[] = acym_translation('ACYM_AFTER').' '.acym_replaceDate($automationCondition['rseventspro']['datemin'], true);
            }

            if (!empty($automationCondition['rseventspro']['datemax'])) {
                $dates[] = acym_translation('ACYM_BEFORE').' '.acym_replaceDate($automationCondition['rseventspro']['datemax'], true);
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

    public function onAcymProcessFilterCount_rseventspro(&$query, $options, $num)
    {
        $this->onAcymProcessFilter_rseventspro($query, $options, $num);

        return acym_translationSprintf('ACYM_SELECTED_USERS', $query->count());
    }

    public function onAcymProcessFilter_rseventspro(&$query, $options, $num)
    {
        $this->processConditionFilter_rseventspro($query, $options, $num);
    }

    public function onAcymDeclareSummary_filters(&$automationFilter)
    {
        $this->summaryConditionFilters($automationFilter);
    }
}
