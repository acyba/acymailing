<?php

use AcyMailing\Libraries\acymPlugin;
use AcyMailing\Helpers\TabHelper;

class plgAcymIcagenda extends acymPlugin
{
    public function __construct()
    {
        parent::__construct();
        $this->cms = 'Joomla';
        $this->installed = acym_isExtensionActive('com_icagenda');
        $this->rootCategoryId = 0;

        $this->pluginDescription->name = 'iCagenda';
        $this->pluginDescription->icon = ACYM_DYNAMICS_URL.basename(__DIR__).'/icon.png';

        if ($this->installed) {
            $this->displayOptions = [
                'title' => ['ACYM_TITLE', true],
                'image' => ['ACYM_IMAGE', true],
                'date' => ['COM_ICAGENDA_EVENT_DATE_FUTUR', true],
                'venue' => ['COM_ICAGENDA_EVENT_PLACE', true],
                'short' => ['ACYM_SHORT_DESCRIPTION', true],
                'desc' => ['ACYM_DESCRIPTION', false],
                'email' => ['COM_ICAGENDA_EVENT_MAIL', false],
                'phone' => ['COM_ICAGENDA_EVENT_PHONE', false],
                'availableseats' => ['COM_ICAGENDA_EVENT_NUMBER_OF_SEATS_AVAILABLE', true],
                'totalseats' => ['COM_ICAGENDA_EVENT_NUMBER_OF_SEATS', false],
                'website' => ['COM_ICAGENDA_EVENT_WEBSITE', false],
                'cat' => ['ACYM_CATEGORY', false],
            ];

            $this->initElementOptionsCustomView();
            $this->initReplaceOptionsCustomView();
            $this->initCustomOptionsCustomView();

            $this->settings = [
                'custom_view' => [
                    'type' => 'custom_view',
                    'tags' => array_merge($this->displayOptions, $this->replaceOptions, $this->customOptions, $this->elementOptions),
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
        $format->afterTitle = '';
        $format->afterArticle = 'Date: {date}<br/> Location: {venue}<br/> Avalaible seats: {availableseats}';
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
        ];
    }

    public function initElementOptionsCustomView()
    {
        $query = 'SELECT event.*, category.title AS cattitle ';
        $query .= 'FROM `#__icagenda_events` AS event ';
        $query .= 'JOIN `#__icagenda_category` AS category ON event.`catid` = category.`id`';
        $element = acym_loadObject($query);
        if (empty($element)) return;
        foreach ($element as $key => $value) {
            $this->elementOptions[$key] = [$key];
        }
    }

    public function initCustomOptionsCustomView()
    {
        $eventFields = acym_loadObjectList(
            'SELECT `field`.`alias`, `field`.`title` 
                FROM #__icagenda_customfields AS `field` 
                WHERE `field`.`state` = 1'
        );
        foreach ($eventFields as $value) {
            $this->customOptions[$value->alias] = [$value->title];
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

        acym_loadLanguageFile('com_icagenda', JPATH_SITE);
        $this->categories = acym_loadObjectList('SELECT `id`, 0 AS `parent_id`, `title` FROM `#__icagenda_category` WHERE state = 1', 'id');

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
                    'image' => ['ACYM_IMAGE', true],
                    'date' => ['COM_ICAGENDA_EVENT_DATE_FUTUR', true],
                    'venue' => ['COM_ICAGENDA_EVENT_PLACE', true],
                    'short' => ['ACYM_SHORT_DESCRIPTION', true],
                    'desc' => ['ACYM_DESCRIPTION', false],
                    'email' => ['COM_ICAGENDA_EVENT_MAIL', false],
                    'phone' => ['COM_ICAGENDA_EVENT_PHONE', false],
                    'availableseats' => ['COM_ICAGENDA_EVENT_NUMBER_OF_SEATS_AVAILABLE', true],
                    'totalseats' => ['COM_ICAGENDA_EVENT_NUMBER_OF_SEATS', false],
                    'website' => ['COM_ICAGENDA_EVENT_WEBSITE', false],
                    'cat' => ['ACYM_CATEGORY', false],
                ],
            ],
        ];

        $rawCustomFields = acym_loadObjectList(
            'SELECT `id`, `title` 
            FROM #__icagenda_customfields 
            WHERE `state` = 1 
                AND `parent_form` = 2 
                AND `type` NOT IN ("spacer_label", "spacer_description") 
            ORDER BY `title` ASC'
        );
        if (!empty($rawCustomFields)) {
            $customFields = [];
            foreach ($rawCustomFields as $oneCustomField) {
                $customFields[$oneCustomField->id] = [$oneCustomField->title, false];
            }

            $displayOptions[] = [
                'title' => 'ACYM_CUSTOM_FIELDS',
                'type' => 'checkbox',
                'name' => 'custom',
                'options' => $customFields,
            ];
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
                    'startdate' => 'ACYM_DATE',
                    'title' => 'ACYM_TITLE',
                    'rand' => 'ACYM_RANDOM',
                ],
                'default' => 'startdate',
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
        acym_loadLanguageFile('com_icagenda', JPATH_SITE);

        $this->querySelect = 'SELECT event.* ';
        $this->query = 'FROM `#__icagenda_events` AS event ';
        $this->filters = [];
        $this->filters[] = 'event.state = 1';
        $this->filters[] = 'event.approval = 0';
        $this->filters[] = 'event.access = 1';
        $this->searchFields = ['event.id', 'event.title'];
        $this->pageInfo->order = 'event.next';
        $this->elementIdTable = 'event';
        $this->elementIdColumn = 'id';

        if (!acym_isAdmin() && $this->getParam('front', 'all') === 'author') {
            $this->filters[] = 'event.created_by = '.intval(acym_currentUserId());
        }

        if ($this->getParam('hidepast', '1') === '1') {
            $this->filters[] = 'event.`next` >= '.acym_escapeDB(date('Y-m-d H:i:s'));
        }

        parent::prepareListing();

        if (!empty($this->pageInfo->filter_cat)) {
            $this->filters[] = 'event.`catid` = '.intval($this->pageInfo->filter_cat);
        }

        $listingOptions = [
            'header' => [
                'title' => [
                    'label' => 'ACYM_TITLE',
                    'size' => '8',
                ],
                'next' => [
                    'label' => 'COM_ICAGENDA_EVENT_DATE_FUTUR',
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
        acym_loadLanguageFile('com_icagenda', JPATH_SITE);

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

            $query = 'SELECT DISTINCT event.id FROM `#__icagenda_events` AS event ';

            $where = [];
            $where[] = 'event.`state` = 1';
            $where[] = 'event.`approval` = 0';
            $where[] = 'event.`access` = 1';

            $selectedArea = $this->getSelectedArea($parameter);
            if (!empty($selectedArea)) {
                $where[] = 'event.catid IN ('.implode(',', $selectedArea).')';
            }

            // Not started events
            $where[] = 'event.`startdate` >= '.acym_escapeDB($parameter->from).' OR event.next >= '.acym_escapeDB($parameter->from);

            if (!empty($parameter->to)) $where[] = '(event.startdate <= '.acym_escapeDB(
                    $parameter->to
                ).' AND event.startdate != "0000-00-00 00:00:00") OR event.next <= '.acym_escapeDB($parameter->to);

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
        $query = 'SELECT event.*, category.title AS cattitle ';
        $query .= 'FROM `#__icagenda_events` AS event ';
        $query .= 'JOIN `#__icagenda_category` AS category ON event.`catid` = category.`id`';
        $query .= 'WHERE event.`id` = '.intval($tag->id);

        $element = $this->initIndividualContent($tag, $query);

        if (empty($element)) return '';
        $element->params = json_decode($element->params);

        $varFields = $this->getCustomLayoutVars($element);

        $link = 'index.php?option=com_icagenda&view=list&layout=event&id='.$tag->id.':'.$element->alias.'&date='.str_replace([' ', ':'], '-', substr($element->next, 0, 16));
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
        $varFields['{short}'] = '<p>'.$element->shortdesc.'</p>';
        if (in_array('short', $tag->display)) $contentText .= $varFields['{short}'];
        $varFields['{desc}'] = $element->desc;
        if (in_array('desc', $tag->display)) $contentText .= $varFields['{desc}'];

        if (!empty($element->image)) {
            $imagePath = $element->image;
            if (strpos($imagePath, 'http') !== 0) $imagePath = acym_rootURI().$imagePath;
        }
        $varFields['{image}'] = $imagePath;
        $varFields['{picthtml}'] = '<img alt="", src="'.$imagePath.'">';
        if (!in_array('image', $tag->display) && !empty($element->image)) $imagePath = '';

        $varFields['{date}'] = '';
        if (!empty($element->next) && $element->next != '0000-00-00 00:00:00') {
            $varFields['{date}'] = acym_getDate(
                acym_getTime($element->next),
                acym_translation('ACYM_DATE_FORMAT_LC1')
            );
        }
        if (in_array('date', $tag->display) && !empty($element->next) && $element->next != '0000-00-00 00:00:00') {
            $customFields[] = [
                $varFields['{date}'],
                acym_translation('COM_ICAGENDA_EVENT_DATE_FUTUR'),
            ];
        }

        $varFields['{venue}'] = '';
        if (!(empty($element->place) && empty($element->address))) {
            $place = [];
            if (!empty($element->place)) {
                $place[] = $element->place;
            }
            if (!empty($element->address)) {
                $place[] = $element->address;
            }

            $varFields['{venue}'] = implode(' - ', $place);
        }
        if (in_array('venue', $tag->display) && !(empty($element->place) && empty($element->address))) {
            $customFields[] = [$varFields['{venue}'], acym_translation('COM_ICAGENDA_EVENT_PLACE')];
        }

        $varFields['availableseats'] = '';
        if (!empty($element->params->maxReg)) {
            $avseats = $element->params->maxReg - acym_loadResult('SELECT SUM(people) FROM #__icagenda_registration WHERE eventid = '.intval($tag->id));
            $varFields['{availableseats}'] = $avseats;
        }
        if (in_array('availableseats', $tag->display) && !empty($element->params->maxReg)) {
            $customFields[] = [$varFields['{availableseats}'], acym_translation('COM_ICAGENDA_EVENT_NUMBER_OF_SEATS_AVAILABLE')];
        }

        $varFields['{totalseats}'] = '';
        if (!empty($element->params->maxReg)) $varFields['{totalseats}'] = $element->params->maxReg;
        if (in_array('totalseats', $tag->display) && !empty($element->params->maxReg)) {
            $customFields[] = [$varFields['{totalseats}'], acym_translation('COM_ICAGENDA_EVENT_NUMBER_OF_SEATS')];
        }

        $varFields['{cat}'] = $element->cattitle;
        if (in_array('cat', $tag->display)) {
            $customFields[] = [
                $varFields['{cat}'],
                acym_translation('ACYM_CATEGORY'),
            ];
        }

        $varFields['{phone}'] = empty($element->phone) ? '' : '<a target="_blank" href="tel:'.$element->phone.'">'.$element->phone.'</a>';
        if (in_array('phone', $tag->display) && !empty($element->phone)) {
            $customFields[] = [
                $varFields['{phone}'],
                acym_translation('COM_ICAGENDA_EVENT_PHONE'),
            ];
        }

        $varFields['{email}'] = empty($element->email) ? '' : '<a target="_blank" href="mailto:'.$element->email.'">'.$element->email.'</a>';
        if (in_array('email', $tag->display) && !empty($element->email)) {
            $customFields[] = [
                $varFields['{email}'],
                acym_translation('COM_ICAGENDA_EVENT_MAIL'),
            ];
        }

        $varFields['{website}'] = empty($element->website) ? '' : '<a target="_blank" href="'.$element->website.'">'.$element->website.'</a>';
        if (in_array('website', $tag->display) && !empty($element->website)) {
            $customFields[] = [
                $varFields['{website}'],
                acym_translation('COM_ICAGENDA_EVENT_WEBSITE'),
            ];
        }

        $eventFields = acym_loadObjectList(
            'SELECT `field`.`id`, `field`.`title`, `field`.`type`, `field`.`options`, `data`.`value`, `field`.`alias` 
                FROM #__icagenda_customfields AS `field` 
                JOIN #__icagenda_customfields_data AS `data` ON `field`.`slug` = `data`.`slug` 
                WHERE `field`.`state` = 1 
                    AND `data`.`parent_id` = '.intval($tag->id),
            'id'
        );

        foreach ($eventFields as $oneCustom) {
            if (empty($oneCustom)) {
                $varFields['{'.$oneCustom->alias.'}'] = '';
                continue;
            }

            if (in_array($oneCustom->type, ['list', 'radio'])) {
                $options = [];
                $oneCustom->options = explode("\n", $oneCustom->options);
                foreach ($oneCustom->options as $oneOption) {
                    $preparation = explode('=', $oneOption, 2);
                    $options[$preparation[0]] = $preparation[1];
                }
                $value = isset($options[$oneCustom->value]) ? $options[$oneCustom->value] : '';
            } elseif ($oneCustom->type === 'url') {
                $value = '<a target="_blank" href="'.$oneCustom->value.'">'.$oneCustom->value.'</a>';
            } elseif ($oneCustom->type === 'email') {
                $value = '<a target="_blank" href="mailto:'.$oneCustom->value.'">'.$oneCustom->value.'</a>';
            } else {
                $value = $oneCustom->value;
            }

            $varFields['{'.$oneCustom->alias.'}'] = $value;
        }

        if (!empty($tag->custom)) {
            $tag->custom = explode(',', $tag->custom);

            foreach ($tag->custom as $oneCustomId) {
                if (empty($eventFields[$oneCustomId])) continue;

                $customFields[] = [
                    $varFields['{'.$eventFields[$oneCustomId]->alias.'}'],
                    $eventFields[$oneCustomId]->title,
                ];
            }
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

    /**
     * Function called with ajax to search in events
     */
    public function searchEvent()
    {
        $id = acym_getVar('int', 'id');
        if (!empty($id)) {
            $subject = acym_loadResult('SELECT `title` FROM #__icagenda_events WHERE `id` = '.intval($id));
            if (empty($subject)) $subject = '';
            echo json_encode(['value' => $id.' - '.$subject]);
            exit;
        }

        $return = [];
        $search = acym_getVar('cmd', 'search', '');
        $elements = acym_loadObjectList(
            'SELECT `id`, `title` FROM `#__icagenda_events` WHERE state != -2 AND `title` LIKE '.acym_escapeDB('%'.$search.'%').' ORDER BY `title` ASC'
        );

        foreach ($elements as $oneElement) {
            $return[] = [$oneElement->id, $oneElement->id.' - '.$oneElement->title];
        }

        echo json_encode($return);
        exit;
    }

    public function onAcymDeclareConditions(&$conditions)
    {
        $conditions['user']['icagenda'] = new stdClass();
        $conditions['user']['icagenda']->name = 'iCagenda';
        $conditions['user']['icagenda']->option = '<div class="cell grid-x grid-margin-x">';

        // Event
        $conditions['user']['icagenda']->option .= '<div class="intext_select_automation cell">';
        $conditions['user']['icagenda']->option .= acym_select(
            [],
            'acym_condition[conditions][__numor__][__numand__][icagenda][event]',
            null,
            [
                'class' => 'acym__select acym_select2_ajax',
                'data-placeholder' => acym_translation('ACYM_ANY_EVENT'),
                'data-params' => [
                    'plugin' => __CLASS__,
                    'trigger' => 'searchEvent',
                ],
            ]
        );
        $conditions['user']['icagenda']->option .= '</div>';

        $conditions['user']['icagenda']->option .= '</div>';

        $conditions['user']['icagenda']->option .= '<div class="cell grid-x grid-margin-x">';
        $conditions['user']['icagenda']->option .= acym_dateField(
            'acym_condition[conditions][__numor__][__numand__][icagenda][datemin]',
            '',
            'cell shrink'
        );
        $conditions['user']['icagenda']->option .= '<span class="acym__content__title__light-blue acym_vcenter margin-bottom-0 cell shrink"><</span>';
        $conditions['user']['icagenda']->option .= '<span class="acym_vcenter">'.acym_translation('ACYM_SUBSCRIPTION_DATE').'</span>';
        $conditions['user']['icagenda']->option .= '<span class="acym__content__title__light-blue acym_vcenter margin-bottom-0 cell shrink"><</span>';
        $conditions['user']['icagenda']->option .= acym_dateField(
            'acym_condition[conditions][__numor__][__numand__][icagenda][datemax]',
            '',
            'cell shrink'
        );
        $conditions['user']['icagenda']->option .= '</div>';
    }

    public function onAcymProcessCondition_icagenda(&$query, $options, $num, &$conditionNotValid)
    {
        $this->processConditionFilter_icagenda($query, $options, $num);
        $affectedRows = $query->count();
        if (empty($affectedRows)) $conditionNotValid++;
    }

    private function processConditionFilter_icagenda(&$query, $options, $num)
    {
        $query->join['icagenda'.$num] = '`#__icagenda_registration` AS icagenda'.$num.' ON icagenda'.$num.'.email = user.email';

        if (!empty($options['event'])) $query->where[] = 'icagenda'.$num.'.eventid = '.intval($options['event']);

        if (!empty($options['datemin'])) {
            $options['datemin'] = acym_replaceDate($options['datemin']);
            if (!is_numeric($options['datemin'])) $options['datemin'] = strtotime($options['datemin']);
            if (!empty($options['datemin'])) {
                $query->where[] = 'icagenda'.$num.'.modified > '.acym_escapeDB(date('Y-m-d H:i:s', $options['datemin']));
            }
        }

        if (!empty($options['datemax'])) {
            $options['datemax'] = acym_replaceDate($options['datemax']);
            if (!is_numeric($options['datemax'])) $options['datemax'] = strtotime($options['datemax']);
            if (!empty($options['datemax'])) {
                $query->where[] = 'icagenda'.$num.'.modified < '.acym_escapeDB(date('Y-m-d H:i:s', $options['datemax']));
            }
        }
    }

    public function onAcymDeclareSummary_conditions(&$automationCondition)
    {
        $this->summaryConditionFilters($automationCondition);
    }

    private function summaryConditionFilters(&$automationCondition)
    {
        if (!empty($automationCondition['icagenda'])) {
            acym_loadLanguageFile('com_icagenda', JPATH_SITE);

            if (empty($automationCondition['icagenda']['event'])) {
                $event = acym_translation('ACYM_ANY_EVENT');
            } else {
                $event = acym_loadResult('SELECT `title` FROM #__icagenda_events WHERE `id` = '.intval($automationCondition['icagenda']['event']));
            }

            $finalText = acym_translation_sprintf('ACYM_REGISTERED_TO', $event);

            $dates = [];
            if (!empty($automationCondition['icagenda']['datemin'])) {
                $dates[] = acym_translation('ACYM_AFTER').' '.acym_replaceDate($automationCondition['icagenda']['datemin'], true);
            }

            if (!empty($automationCondition['icagenda']['datemax'])) {
                $dates[] = acym_translation('ACYM_BEFORE').' '.acym_replaceDate($automationCondition['icagenda']['datemax'], true);
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

    public function onAcymProcessFilterCount_icagenda(&$query, $options, $num)
    {
        $this->onAcymProcessFilter_icagenda($query, $options, $num);

        return acym_translation_sprintf('ACYM_SELECTED_USERS', $query->count());
    }

    public function onAcymProcessFilter_icagenda(&$query, $options, $num)
    {
        $this->processConditionFilter_icagenda($query, $options, $num);
    }

    public function onAcymDeclareSummary_filters(&$automationFilter)
    {
        $this->summaryConditionFilters($automationFilter);
    }
}
