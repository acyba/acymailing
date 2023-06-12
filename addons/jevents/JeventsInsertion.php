<?php

use AcyMailing\Libraries\acymParameter;
use AcyMailing\Helpers\TabHelper;

trait JeventsInsertion
{
    private $imgFolder = '';
    private $useStdTime;
    private $ignoredCustomFields = [
        'jevcfuser',
        'jevcfyoutube',
        'jevcfupdatable',
        'jevcfdblist',
        'jevcftext',
        'jevcfimage',
        'jevcffile',
        'jevcfhtml',
        'jevcfeventflag',
        'jevcfnotes',
    ];

    public function getStandardStructure(&$customView)
    {
        $tag = new stdClass();
        $tag->id = 0;

        $format = new stdClass();
        $format->tag = $tag;
        $format->title = '{title}';
        $format->afterTitle = '';
        $format->afterArticle = acym_translation('ACYM_DATE').': {date}<br/> '.acym_translation('ACYM_LOCATION').': {location}';
        $format->imagePath = '{imagePath}';
        $format->description = '{wrappedText}';
        $format->link = '{link}';
        $format->customFields = [];
        $customView = '<div class="acymailing_content">'.$this->pluginHelper->getStandardDisplay($format).'</div>';
    }

    public function initCustomOptionsCustomView()
    {
        $customVDB = acym_loadObjectList('SELECT name, value FROM #__jev_customfields');
        if (empty($customVDB)) return;
        foreach ($customVDB as $one) {
            $this->customOptions[$one->alias] = [$one->name];
        }
    }

    public function initReplaceOptionsCustomView()
    {
        $this->replaceOptions = [
            'title' => ['ACYM_TITLE'],
            'mainPicture' => ['ACYM_IMAGE'],
            'desc' => ['ACYM_DESCRIPTION'],
            'date' => ['ACYM_DATE'],
            'start_date' => ['ACYM_START_DATE'],
            'end_date' => ['ACYM_END_DATE'],
            'location' => ['ACYM_LOCATION'],
            'cat' => ['ACYM_CATEGORY'],
            'author' => ['ACYM_AUTHOR'],
            'contact' => ['JEV_EVENT_CONTACT'],
            'link' => ['ACYM_LINK'],
            'readmore' => ['ACYM_READ_MORE'],
        ];
    }

    public function initElementOptionsCustomView()
    {
        $query = 'SELECT rpt.*, detail.*, cat.title AS category, ev.catid, ev.uid FROM `#__jevents_repetition` AS rpt ';
        $query .= ' JOIN `#__jevents_vevent` AS ev ON rpt.eventid = ev.ev_id ';
        $query .= ' JOIN `#__jevents_vevdetail` AS detail ON rpt.eventdetail_id = detail.evdet_id ';
        $query .= 'LEFT JOIN `#__categories` AS cat ON cat.id = ev.catid ';
        $element = acym_loadObject($query);
        if (empty($element)) return;
        foreach ($element as $key => $value) {
            $this->elementOptions[$key] = [$key];
        }
    }

    public function insertionOptions($defaultValues = null)
    {
        $this->defaultValues = $defaultValues;

        acym_loadLanguageFile('com_jevents', JPATH_SITE);

        $this->categories = acym_loadObjectList('SELECT id, parent_id, title FROM `#__categories` WHERE extension = "com_jevents"', 'id');

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
                    'desc' => ['ACYM_DESCRIPTION', true],
                    'start_date' => ['ACYM_START_DATE', true],
                    'end_date' => ['ACYM_END_DATE', true],
                    'location' => ['ACYM_LOCATION', true],
                    'cat' => ['ACYM_CATEGORY', false],
                    'author' => ['ACYM_AUTHOR', false],
                    'contact' => ['JEV_EVENT_CONTACT', false],
                    'extra' => ['JEV_EVENT_EXTRA', false],
                ],
            ],
        ];

        $jevCf = $this->getCustomFields();
        if (!empty($jevCf)) {
            $customFields = [];
            foreach ($jevCf as $oneCustomField) {
                if (!empty($oneCustomField->attributes()->name) && !empty($oneCustomField->attributes()->label)) {
                    $fieldName = (string)$oneCustomField->attributes()->name;
                    $fieldLabel = (string)$oneCustomField->attributes()->label;

                    $customFields[$fieldName] = [$fieldLabel, false];
                }
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
                    'title' => 'ACYM_CLICKABLE_IMAGE',
                    'type' => 'boolean',
                    'name' => 'clickableimg',
                    'default' => false,
                ],
                [
                    'title' => 'ACYM_READ_MORE',
                    'type' => 'boolean',
                    'name' => 'readmore',
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
                    'title' => 'ACYM_DISPLAY_PICTURES',
                    'type' => 'pictures',
                    'name' => 'pictures',
                ],
            ]
        );

        if (file_exists(JPATH_SITE.DS.'plugins'.DS.'jevents'.DS.'jevfiles'.DS.'jevfiles.php')) {
            $displayOptions[] = [
                'title' => 'ACY_FILES',
                'type' => 'boolean',
                'name' => 'pluginFields',
                'default' => true,
            ];
        }

        //handle custom fields
        if (file_exists(JPATH_SITE.DS.'plugins'.DS.'jevents'.DS.'jevcustomfields')) {
            $jevCFParams = acym_loadObject('SELECT params FROM #__extensions WHERE element = "jevcustomfields"');
            if (!empty($jevCFParams->params)) {
                $template = json_decode($jevCFParams->params)->template;
            }

            if (!empty($template)) {
                $xmlfile = JPATH_SITE.DS.'plugins'.DS.'jevents'.DS.'jevcustomfields'.DS.'customfields'.DS.'templates'.DS.$template;
                if (file_exists($xmlfile)) {
                    $xml = simplexml_load_file($xmlfile);
                    $jevCf = $xml->xpath('//fields/fieldset/field');

                    $customField = [
                        'title' => 'ACYM_FIELDS_TO_DISPLAY',
                        'type' => 'checkbox',
                        'name' => 'custom',
                        'separator' => ', ',
                        'options' => [],
                    ];

                    foreach ($jevCf as $oneParam) {
                        $name = (string)$oneParam->attributes()->name;
                        $label = (string)$oneParam->attributes()->label;
                        if (!empty($name) && !empty($label)) {
                            $customField['options'][$name] = [$label, false];
                        }
                    }

                    $displayOptions[] = $customField;
                }
            }
        }

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
                    'startrepeat' => 'JEV_EVENT_STARTDATE',
                    'endrepeat' => 'JEV_EVENT_ENDDATE',
                    'summary' => 'ACYM_TITLE',
                    'rand' => 'ACYM_RANDOM',
                ],
                'default' => 'startrepeat',
                'defaultdir' => 'asc',
            ],
        ];
        $this->autoContentOptions($catOptions, 'event');

        $this->autoCampaignOptions($catOptions);

        // Handle location plugin
        if (file_exists(JPATH_ADMINISTRATOR.DS.'components'.DS.'com_jevlocations')) {
            $locs = acym_loadObjectList('SELECT loc_id, title, city, state, country FROM #__jev_locations');

            if (!empty($locs)) {
                $allCities = [0 => 'ACYM_ALL'];
                $allStates = [0 => 'ACYM_ALL'];
                $allCountries = [0 => 'ACYM_ALL'];
                $locations = [0 => 'ACYM_ALL'];
                foreach ($locs as $oneLoc) {
                    $locations[$oneLoc->loc_id] = $oneLoc->title;

                    if (!empty($oneLoc->city)) $allCities[$oneLoc->city] = $oneLoc->city;
                    if (!empty($oneLoc->state)) $allStates[$oneLoc->state] = $oneLoc->state;
                    if (!empty($oneLoc->country)) $allCountries[$oneLoc->country] = $oneLoc->country;
                }

                $catOptions[] = [
                    'title' => 'ACYM_LOCATION',
                    'type' => 'select',
                    'name' => 'location',
                    'options' => $locations,
                ];

                $catOptions[] = [
                    'title' => 'ACYM_COUNTRY',
                    'type' => 'select',
                    'name' => 'country',
                    'options' => $allCountries,
                ];

                $catOptions[] = [
                    'title' => 'ACYM_STATE',
                    'type' => 'select',
                    'name' => 'state',
                    'options' => $allStates,
                ];

                $catOptions[] = [
                    'title' => 'ACYM_CITY',
                    'type' => 'select',
                    'name' => 'city',
                    'options' => $allCities,
                ];
            }
        }

        $displayOptions = array_merge($displayOptions, $catOptions);

        echo $this->displaySelectionZone($this->getCategoryListing());
        echo $this->pluginHelper->displayOptions($displayOptions, $identifier, 'grouped', $this->defaultValues);

        $tabHelper->endTab();

        $tabHelper->display('plugin');
    }

    public function prepareListing()
    {
        $this->querySelect = 'SELECT rpt.*, detail.*, cat.title AS cattitle ';
        $this->query = 'FROM `#__jevents_repetition` AS rpt ';
        $this->query .= 'JOIN `#__jevents_vevent` AS ev ON rpt.eventid = ev.ev_id ';
        $this->query .= 'JOIN `#__categories` AS cat ON ev.catid = cat.id ';
        $this->query .= 'JOIN `#__jevents_vevdetail` AS detail ON ev.detail_id = detail.evdet_id ';
        $this->filters = [];
        $this->searchFields = ['rpt.rp_id', 'detail.evdet_id', 'detail.description', 'detail.summary', 'detail.contact', 'detail.location'];
        $this->pageInfo->order = 'rpt.startrepeat';
        $this->elementIdTable = 'rpt';
        $this->elementIdColumn = 'rp_id';

        if (!acym_isAdmin() && $this->getParam('front', 'all') === 'author') {
            $this->filters[] = 'ev.created_by = '.intval(acym_currentUserId());
        }

        if ($this->getParam('hidepast', '1') === '1') {
            $this->filters[] = 'rpt.`startrepeat` >= '.acym_escapeDB(date('Y-m-d H:i:s'));
        }

        parent::prepareListing();
        $this->pageInfo->orderdir = 'ASC';

        if (!empty($this->pageInfo->filter_cat)) {
            $this->filters[] = 'ev.catid = '.intval($this->pageInfo->filter_cat);
        }

        $listingOptions = [
            'header' => [
                'summary' => [
                    'label' => 'ACYM_TITLE',
                    'size' => '5',
                ],
                'startrepeat' => [
                    'label' => 'ACYM_DATE',
                    'size' => '3',
                    'type' => 'date',
                ],
                'cattitle' => [
                    'label' => 'ACYM_CATEGORY',
                    'size' => '3',
                ],
                'rp_id' => [
                    'label' => 'ACYM_ID',
                    'size' => '1',
                    'class' => 'text-center',
                ],
            ],
            'id' => 'rp_id',
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
        // Load the JEvents data
        acym_loadLanguageFile('com_jevents', JPATH_SITE);

        if (file_exists(JPATH_SITE.DS.'plugins'.DS.'jevents'.DS.'jevfiles'.DS.'jevfiles.php')) {
            $JEVplugin = JPluginHelper::getPlugin('jevents', 'jevfiles');
            $JEVparams = new acymParameter($JEVplugin->params);
            $imagesFolder = JComponentHelper::getParams('com_media')->get('image_path', 'images');
            $this->imgFolder = ACYM_LIVE.$imagesFolder.'/'.trim($JEVparams->get('folder', 'jevents'), '/').'/';
        }

        $this->useStdTime = JComponentHelper::getParams("com_jevents")->get('com_calUseStdTime');

        return true;
    }

    public function generateByCategory(&$email)
    {
        $time = time();

        //load the tags
        $tags = $this->pluginHelper->extractTags($email, 'auto'.$this->name);
        $this->tags = [];

        if (empty($tags)) return $this->generateCampaignResult;

        $multicat = JComponentHelper::getParams('com_jevents')->get('multicategory', 0);

        foreach ($tags as $oneTag => $parameter) {
            if (isset($this->tags[$oneTag])) continue;

            $where = [];
            $where[] = 'ev.`state` = 1';

            $query = 'SELECT DISTINCT rpt.rp_id FROM `#__jevents_repetition` AS rpt ';
            $query .= ' JOIN `#__jevents_vevent` AS ev ON rpt.eventid = ev.ev_id ';

            if (empty($parameter->order)) $parameter->order = 'startrepeat,ASC';
            if (empty($parameter->from)) {
                $parameter->from = date('Y-m-d H:i:s', $time);
            } else {
                $parameter->from = acym_date(acym_replaceDate($parameter->from), 'Y-m-d H:i:s');
            }
            if (!empty($parameter->to)) $parameter->to = acym_date(acym_replaceDate($parameter->to), 'Y-m-d H:i:s');

            if (!empty($parameter->id)) {
                $allCats = explode('-', $parameter->id);
                array_pop($allCats);
                if (!empty($allCats)) {
                    acym_arrayToInteger($allCats);
                    $catToSearch = implode(',', $allCats);
                    if ($multicat == 1) {
                        $query .= ' JOIN `#__jevents_catmap` AS cats ON ev.ev_id = cats.evid ';
                        $where[] = 'cats.catid IN ('.$catToSearch.')';
                    } else {
                        $where[] = 'ev.catid IN ('.$catToSearch.')';
                    }
                }
            }

            $locationColumn = '';
            if (empty($parameter->location)) {
                if (!empty($parameter->country)) $locationColumn = 'country';
                if (!empty($parameter->state)) $locationColumn = 'state';
                if (!empty($parameter->city)) $locationColumn = 'city';
            }

            if (isset($parameter->priority) || !empty($parameter->location) || !empty($locationColumn) || strpos($parameter->order, 'summary') !== false) {
                $query .= ' JOIN `#__jevents_vevdetail` AS evdet ON ev.detail_id = evdet.evdet_id ';
            }

            if (!empty($locationColumn)) {
                $query .= ' JOIN `#__jev_locations` AS evloc ON evdet.location = evloc.loc_id';
                $where[] = 'evloc.'.$locationColumn.' = '.acym_escapeDB($parameter->$locationColumn);
            }

            if (!empty($parameter->location)) {
                $where[] = 'evdet.location = '.intval($parameter->location);
            }

            if (isset($parameter->priority)) {
                //we check if the values are integer values
                $parameter->priority = explode(',', $parameter->priority);
                acym_arrayToInteger($parameter->priority);
                $where[] = 'evdet.priority IN ('.implode(',', $parameter->priority).')';
            }

            if ((empty($parameter->mindelay) || substr($parameter->mindelay, 0, 1) != '-') && (empty($parameter->delay) || substr($parameter->delay, 0, 1) != '-')) {
                if (!empty($parameter->addcurrent)) {
                    //not finished and next events
                    $where[] = 'rpt.`endrepeat` >= '.acym_escapeDB($parameter->from);
                } else {
                    //not started events
                    $where[] = 'rpt.`startrepeat` >= '.acym_escapeDB($parameter->from);
                }
            }

            //should we display only events starting in the sending day ?
            if (!empty($parameter->todaysevent)) {
                $where[] = 'rpt.`startrepeat` <= '.acym_escapeDB(date('Y-m-d 23:59:59', $time));
            }

            if (!empty($parameter->mindelay)) $where[] = 'rpt.`startrepeat` >= '.acym_escapeDB(date('Y-m-d H:i:s', $time + $parameter->mindelay));
            if (!empty($parameter->delay)) $where[] = 'rpt.`startrepeat` <= '.acym_escapeDB(date('Y-m-d H:i:s', $time + $parameter->delay));
            if (!empty($parameter->to)) $where[] = 'rpt.`startrepeat` <= '.acym_escapeDB($parameter->to);

            //This access level check should be done for J1.5 only
            if (isset($parameter->access)) {
                //We set it only if the access is defined in the tag
                $where[] = 'ev.`access` = '.intval($parameter->access);
            }

            if (!empty($parameter->onlynew)) {
                $lastGenerated = $this->getLastGenerated($email->id);
                if (!empty($lastGenerated)) {
                    $where[] = 'rpt.startrepeat > '.acym_escapeDB(acym_date($lastGenerated, 'Y-m-d H:i:s', false));
                }
            }

            $query .= ' WHERE ('.implode(') AND (', $where).')';

            $this->tags[$oneTag] = $this->finalizeCategoryFormat($query, $parameter);
        }

        return $this->generateCampaignResult;
    }

    public function replaceIndividualContent($tag)
    {
        //1 : Load the informations of the product...
        $query = 'SELECT rpt.*, detail.*, cat.title AS category, ev.catid, ev.uid, author.name AS authorname FROM `#__jevents_repetition` AS rpt ';
        $query .= ' JOIN `#__jevents_vevent` AS ev ON rpt.eventid = ev.ev_id ';
        $query .= ' JOIN `#__jevents_vevdetail` AS detail ON rpt.eventdetail_id = detail.evdet_id ';
        $query .= 'LEFT JOIN `#__categories` AS cat ON cat.id = ev.catid ';
        $query .= 'LEFT JOIN `#__users` AS author ON author.id = ev.created_by ';
        $query .= 'WHERE rpt.rp_id = '.intval($tag->id).' LIMIT 1';

        $element = $this->initIndividualContent($tag, $query);

        if (empty($element)) return '';

        $this->pluginHelper->translateItem($element, $tag, 'jevents_vevdetail', $element->evdet_id);

        $title = '';
        $afterTitle = '';
        $afterArticle = '';
        $imagePath = '';
        $contentText = '';
        $customFields = [];

        $this->handleLocationComponent($element);
        $varFields = $this->getCustomLayoutVars($element);
        $this->handleDates($element, $varFields);
        $this->handleLink($element, $varFields);

        // Needed transition for tags inserted before 8.4.0
        if (empty($tag->display)) {
            $tag->display = ['title'];
            if (!empty($tag->type) && $tag->type === 'full') {
                $tag->display = ['title', 'image', 'desc', 'date', 'location', 'contact', 'extra'];
                $tag->custom = [];
            }
        }

        $varFields['{title}'] = $element->summary;
        if (in_array('title', $tag->display)) {
            $title = $varFields['{title}'];
        }

        if (in_array('desc', $tag->display)) {
            $contentText = $varFields['{description}'];
        }

        if (in_array('start_date', $tag->display) && in_array('end_date', $tag->display)) {
            $customFields[] = [$varFields['{date}']];
        } else {
            if (in_array('start_date', $tag->display)) {
                $customFields[] = [$varFields['{start_date}'], acym_translation('ACYM_START_DATE')];
            }

            if (in_array('end_date', $tag->display)) {
                $customFields[] = [$varFields['{end_date}'], acym_translation('ACYM_END_DATE')];
            }
        }

        if (in_array('location', $tag->display) && !empty($varFields['{location}'])) {
            $customFields[] = [$varFields['{location}'], acym_translation('ACYM_ADDRESS')];
        }

        $this->handleCF($tag, $element, $varFields, $customFields);

        if (in_array('contact', $tag->display) && !empty($varFields['{contact}'])) {
            $value = $varFields['{contact}'];

            if (acym_isValidEmail($value)) {
                $value = '<a href="mailto:'.$value.'">'.$value.'</a>';
            }

            $customFields[] = [$value, acym_translation('JEV_EVENT_CONTACT')];
        }

        if (in_array('extra', $tag->display) && !empty($varFields['{extra_info}'])) {
            $customFields[] = [$varFields['{extra_info}']];
        }

        $this->handleImages($tag, $afterArticle, $imagePath);
        $varFields['{mainPicture}'] = '<img src="'.$imagePath.'" alt="'.$varFields['{title}'].'">';
        if (!in_array('image', $tag->display) || empty($imagePath)) {
            $imagePath = '';
        }

        $varFields['{cat}'] = $element->category;
        if (in_array('cat', $tag->display)) {
            $customFields[] = [$varFields['{cat}'], acym_translation('ACYM_CATEGORY')];
        }

        $varFields['{author}'] = $element->authorname;
        if (in_array('author', $tag->display) && !empty($varFields['{author}'])) {
            $customFields[] = [$varFields['{author}'], acym_translation('ACYM_AUTHOR')];
        }

        $varFields['{readmore}'] = '<a class="acymailing_readmore_link" style="text-decoration:none;" target="_blank" href="'.$varFields['{link}'].'">';
        $varFields['{readmore}'] .= '<span class="acymailing_readmore">'.acym_translation('ACYM_READ_MORE').'</span>';
        $varFields['{readmore}'] .= '</a>';
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
        $format->link = empty($tag->clickable) && empty($tag->clickableimg) ? '' : $varFields['{link}'];
        $format->customFields = $customFields;
        $result = '<div class="acymailing_content">'.$this->pluginHelper->getStandardDisplay($format).'</div>';

        return $this->finalizeElementFormat($result, $tag, $varFields);
    }

    private function handleLocationComponent(&$element)
    {
        //Do we need to load the location from somewhere else?
        if (file_exists(JPATH_ADMINISTRATOR.DS.'components'.DS.'com_jevlocations') && !empty($element->location) && is_numeric($element->location)) {
            $location = acym_loadObject('SELECT title, street, postcode, city, state, country FROM `#__jev_locations` WHERE loc_id = '.intval($element->location));
            if (!empty($location)) {
                foreach ($location as $prop => $value) {
                    $element->$prop = $value;
                }
                $element->location = $location->title;
            }
        }
    }

    private function handleDates($element, &$varFields)
    {
        $startDate = acym_date($element->startrepeat, acym_translation('ACYM_DATE_FORMAT_LC1'), false);
        $endDate = acym_date($element->endrepeat, acym_translation('ACYM_DATE_FORMAT_LC1'), false);
        $startTime = substr($element->startrepeat, 11, 5);
        $endTime = substr($element->endrepeat, 11, 5);

        if ($startTime == '00:00') {
            $startTime = '';
            $endTime = '';
        } elseif ($element->noendtime) {
            $endTime = '';
        }

        if (!empty($this->useStdTime)) {
            if (!empty($startTime)) {
                $startTime = strtolower(strftime("%#I:%M%p", strtotime($element->startrepeat)));
            }
            if (!empty($endTime)) {
                $endTime = strtolower(strftime("%#I:%M%p", strtotime($element->endrepeat)));
            }
        }

        $varFields['{start_date}'] = $startDate;
        if (!empty($startTime)) {
            $varFields['{start_date}'] .= ' '.$startTime;
        }
        $date = $varFields['{start_date}'];

        $varFields['{end_date}'] = $endDate;
        if ($startDate == $endDate) {
            if (!empty($endTime)) {
                $date .= ' - '.$endTime;
                $varFields['{end_date}'] .= ' '.$endTime;
            }
        } else {
            if (!empty($endTime)) {
                $varFields['{end_date}'] .= ' '.$endTime;
            }
            $date .= ' - '.$varFields['{end_date}'];
        }

        $varFields['{date}'] = $date;
    }

    private function handleLink($element, &$varFields)
    {
        $link = 'index.php?option=com_jevents&task=icalrepeat.detail&evid='.intval($element->rp_id);
        $areaCats = [];
        $areaCats[] = $element->catid;
        $cats = acym_loadObjectList('SELECT id, parent_id FROM #__categories', 'id');
        $position = $element->catid;

        while ($cats[$position]->parent_id != 0) {
            $areaCats[] = $cats[$position]->parent_id;
            $position = $cats[$position]->parent_id;
        }

        $menuId = '';
        $menus = acym_loadObjectList('SELECT id, params FROM #__menu WHERE link LIKE "index.php?option=com_jevents&view=cat&layout=listevents"');
        if (!empty($menus)) {
            foreach ($menus as $i => $menu) {
                $menus[$i]->params = json_decode($menus[$i]->params);
                if (empty($menus[$i]->params->catidnew)) continue;
                foreach ($menus[$i]->params->catidnew as $oneCatid) {
                    if (in_array($oneCatid, $areaCats)) {
                        $menuId = $menus[$i]->id;
                        break;
                    }
                }
                if ($menuId != '') {
                    break;
                }
            }
        }

        if (empty($menuId)) {
            $summary = str_replace('-', ' ', $element->summary);
            $summary = trim(strtolower($summary));
            $summary = preg_replace('/(\s|[^A-Za-z0-9\-])+/', '-', $summary);
            $summary = trim($summary, '-');
            $time = explode('-', substr($element->startrepeat, 0, strpos($element->startrepeat, ' ')));
            $link = 'index.php?option=com_jevents&task=icalrepeat.detail&evid='.intval($element->rp_id).'&year='.intval($time[0]).'&month='.intval($time[1]).'&day='.intval(
                    $time[2]
                ).'&title='.$summary.'&uid='.$element->uid;
        } else {
            $link .= '&Itemid='.intval($menuId);
        }

        $varFields['{link}'] = $this->finalizeLink($link);
    }

    private function handleImages($tag, &$afterArticle, &$imagePath)
    {
        if (!file_exists(JPATH_SITE.DS.'plugins'.DS.'jevents'.DS.'jevfiles'.DS.'jevfiles.php')) {
            return;
        }

        if (in_array(acym_getPrefix().'jev_files_combined', acym_getTableList())) {
            $this->handleCombinedImages($tag, $afterArticle, $imagePath);
        } else {
            $this->handleClassicImages($tag, $afterArticle, $imagePath);
        }
    }

    private function handleCombinedImages($tag, &$afterArticle, &$imagePath)
    {
        $filesRow = acym_loadObject(
            'SELECT files.* 
            FROM `#__jev_files_combined` AS files 
            JOIN #__jevents_repetition AS rpt ON files.ev_id = rpt.eventid 
            WHERE rpt.rp_id = '.intval($tag->id)
        );

        if (empty($filesRow)) {
            return;
        }

        for ($i = 1 ; $i < 30 ; $i++) {
            if (!empty($filesRow->{'imagename'.$i})) {
                $varFields['{imgpath'.$i.'}'] = $this->imgFolder.$filesRow->{'imagename'.$i};
                if (empty($imagePath)) {
                    $imagePath = $varFields['{imgpath'.$i.'}'];
                    continue;
                }
                $afterArticle .= '<br /><a target="_blank" href="'.$varFields['{imgpath'.$i.'}'].'"><img src="'.$varFields['{imgpath'.$i.'}'].'" alt="" /></a>';
            }

            if (!empty($filesRow->{'filename'.$i})) {
                $varFields['{filepath'.$i.'}'] = $this->imgFolder.$filesRow->{'filename'.$i};
                if (!empty($tag->pluginFields)) {
                    $files[] = '<a target="_blank" href="'.$varFields['{filepath'.$i.'}'].'">'.(empty($filesRow->{'filetitle'.$i})
                            ? : $filesRow->{'filetitle'.$i}).'</a>';
                }
            }
        }

        if (!empty($files)) {
            $afterArticle .= implode('<br />', $files);
        }
    }

    private function handleClassicImages($tag, &$afterArticle, &$imagePath)
    {
        $files = acym_loadObjectList(
            'SELECT files.* 
            FROM `#__jev_files` AS files 
            JOIN #__jevents_repetition AS rpt ON files.ev_id = rpt.eventid 
            WHERE rpt.rp_id = '.intval($tag->id).' 
            ORDER BY filetype DESC'
        );

        if (empty($files)) {
            return;
        }

        foreach ($files as $i => $oneFile) {
            if (empty($oneFile->filename)) continue;

            $varFields['{imgpath'.$i.'}'] = $this->imgFolder.$oneFile->filename;
            if ($oneFile->filetype === 'file') {
                if (!empty($tag->pluginFields)) {
                    $afterArticle .= '<br /><a target="_blank" href="'.$varFields['{imgpath'.$i.'}'].'">'.$oneFile->filetitle.'</a>';
                }
            } else {
                if (empty($imagePath)) {
                    $imagePath = $varFields['{imgpath'.$i.'}'];
                    continue;
                }
                $afterArticle .= '<br /><a target="_blank" href="'.$varFields['{imgpath'.$i.'}'].'"><img src="'.$varFields['{imgpath'.$i.'}'].'" alt="" /></a>';
            }
        }
    }

    private function handleCF($tag, $element, &$varFields, &$customFields)
    {
        if (empty($tag->custom)) {
            return;
        }

        //extract wanted fields
        $tag->custom = explode(',', $tag->custom);
        foreach ($tag->custom as $i => $oneField) {
            $tag->custom[$i] = trim($oneField);
        }

        //load available custom fields
        $jevCf = $this->getCustomFields();
        if (empty($jevCf)) {
            return;
        }

        $jevCustomFields = [];
        foreach ($jevCf as $i => $oneField) {
            $name = (string)$oneField->attributes()->name;
            $jevCustomFields[$name] = new stdClass();
            $jevCustomFields[$name]->label = (string)$oneField->attributes()->label;
            $jevCustomFields[$name]->type = (string)$oneField->attributes()->type;

            if (empty($oneField->option)) continue;

            $jevCustomFields[$name]->options = [];
            foreach ($oneField->option as $oneOption) {
                $jevCustomFields[$name]->options[] = $oneOption;
            }
        }

        $customValues = [];
        $customVDB = acym_loadObjectList('SELECT name, value FROM #__jev_customfields WHERE evdet_id = '.intval($element->evdet_id));
        foreach ($customVDB as $oneField) {
            $varFields['{'.$oneField->name.'}'] = $oneField->value;
            $customValues[$oneField->name] = $oneField->value;
        }

        if (empty($customValues)) {
            return;
        }

        foreach ($tag->custom as $oneCustom) {
            $label = (!empty($jevCustomFields[$oneCustom]->label)) ? $jevCustomFields[$oneCustom]->label : $oneCustom;
            //i.e. "if this fields have different possible values"
            if (!empty($jevCustomFields[$oneCustom]->options)) {
                $multipleValues = explode(',', $customValues[$oneCustom]);

                //all this block is made to replace the keys by the label in the field's values
                $orderedValues = [];
                foreach ($multipleValues as $oneValue) {
                    $orderedValues[$oneValue] = $oneValue;
                }

                $possibleValues = [];
                foreach ($jevCustomFields[$oneCustom]->options as $oneOption) {
                    $possibleValues[(string)$oneOption->attributes()->value] = (string)$oneOption;
                }

                foreach ($orderedValues as $key => $j) {
                    $orderedValues[$key] = $possibleValues[$key];
                }
                $customValues[$oneCustom] = implode(', ', $orderedValues);
            } elseif ($jevCustomFields[$oneCustom]->type == 'jevrurl') { //we want a link !
                $customValues[$oneCustom] = '<a href="'.$customValues[$oneCustom].'">'.$customValues[$oneCustom].'</a>';
            } elseif ($jevCustomFields[$oneCustom]->type == 'jevrcalendar') {//comprehensible display
                $customValues[$oneCustom] = acym_getDate(acym_getTime($customValues[$oneCustom]), acym_translation('ACYM_DATE_FORMAT_LC1'));
            } elseif ($jevCustomFields[$oneCustom]->type == 'jevruser') {//we do not want the user id but its name
                $user = acym_loadResultArray('SELECT name FROM #__users WHERE id = '.intval($customValues[$oneCustom]));
                $customValues[$oneCustom] = (empty($user[0])) ? $customValues[$oneCustom] : $user[0];
            } elseif ($jevCustomFields[$oneCustom]->type == 'jevcfboolean') {
                $customValues[$oneCustom] = empty($customValues[$oneCustom]) ? acym_translation('ACYM_NO') : acym_translation('ACYM_YES');
            }

            if (empty($customValues[$oneCustom]) && in_array($jevCustomFields[$oneCustom]->type, $this->ignoredCustomFields)) {
                unset($customValues[$oneCustom]);
            }

            if (isset($customValues[$oneCustom])) {
                $customFields[] = [$customValues[$oneCustom], $label];
            }
        }
    }

    private function getCustomFields()
    {
        if (!in_array(acym_getPrefix().'jev_customfields', acym_getTableList())) {
            return [];
        }

        $jevCFParams = acym_loadObject('SELECT params FROM #__extensions WHERE element = "jevcustomfields"');
        if (!empty($jevCFParams->params)) {
            $template = json_decode($jevCFParams->params)->template;
        }

        if (empty($template)) {
            return [];
        }

        $xmlfile = JPATH_SITE.DS.'plugins'.DS.'jevents'.DS.'jevcustomfields'.DS.'customfields'.DS.'templates'.DS.$template;
        if (!file_exists($xmlfile)) {
            return [];
        }

        $xml = simplexml_load_file($xmlfile);

        return $xml->xpath('//fields/fieldset/field');
    }
}
