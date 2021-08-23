<?php

use AcyMailing\Libraries\acymParameter;
use AcyMailing\Libraries\acymPlugin;
use AcyMailing\Helpers\TabHelper;

class plgAcymJevents extends acymPlugin
{
    var $imgFolder = '';
    var $useStdTime;

    public function __construct()
    {
        parent::__construct();
        $this->cms = 'Joomla';
        $this->installed = acym_isExtensionActive('com_jevents');

        $this->pluginDescription->name = 'JEvents';
        $this->pluginDescription->icon = ACYM_DYNAMICS_URL.basename(__DIR__).'/icon.ico';

        if ($this->installed) {
            $this->initCustomView();

            $this->settings = [
                'custom_view' => [
                    'type' => 'custom_view',
                    'tags' => array_merge($this->displayOptions, $this->customOptions, $this->replaceOptions, $this->elementOptions),
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
        $format->title = '{summary}';
        $format->afterTitle = '';
        $format->afterArticle = '';
        $format->imagePath = '';
        $format->description = '{description}';
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

    public function getPossibleIntegrations()
    {
        if (!acym_isAdmin() && $this->getParam('front', 'all') === 'hide') return null;

        return $this->pluginDescription;
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
                'type' => 'radio',
                'name' => 'type',
                'options' => [
                    'title' => 'ACYM_TITLE_ONLY',
                    'full' => 'ACYM_FULL_TEXT',
                ],
                'default' => 'full',
            ],
            [
                'title' => 'ACYM_CLICKABLE_TITLE',
                'type' => 'boolean',
                'name' => 'clickable',
                'default' => true,
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
        ];

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
                }
            }

            if (!empty($jevCf)) {
                $customField = [
                    'title' => 'ACYM_FIELDS_TO_DISPLAY',
                    'type' => 'checkbox',
                    'name' => 'custom',
                    'separator' => ', ',
                    'options' => [],
                ];
                foreach ($jevCf as $oneParam) {
                    $name = $oneParam->attributes()->name;
                    $label = $oneParam->attributes()->label;
                    if (!empty($name) && !empty($label)) {
                        $customField['options'][$name] = [$label, false];
                    }
                }

                $displayOptions[] = $customField;
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
        $query = 'SELECT rpt.*, detail.*, cat.title AS category, ev.catid, ev.uid FROM `#__jevents_repetition` AS rpt ';
        $query .= ' JOIN `#__jevents_vevent` AS ev ON rpt.eventid = ev.ev_id ';
        $query .= ' JOIN `#__jevents_vevdetail` AS detail ON rpt.eventdetail_id = detail.evdet_id ';
        $query .= 'LEFT JOIN `#__categories` AS cat ON cat.id = ev.catid ';
        $query .= 'WHERE rpt.rp_id = '.intval($tag->id).' LIMIT 1';

        $element = $this->initIndividualContent($tag, $query);

        if (empty($element)) return '';

        $this->pluginHelper->translateItem($element, $tag, 'jevents_vevdetail', $element->evdet_id);

        //Do we need to load the location from somewhere else?
        if (file_exists(JPATH_ADMINISTRATOR.DS.'components'.DS.'com_jevlocations') && !empty($element->location) && is_numeric($element->location)) {
            $query = 'SELECT title, street, postcode, city, state, country FROM `#__jev_locations` WHERE loc_id = '.intval($element->location);
            $location = acym_loadObject($query);
            if (!empty($location)) {
                foreach ($location as $prop => $value) {
                    $element->$prop = $value;
                }
                $element->location = $location->title;
            }
        }

        $varFields = $this->getCustomLayoutVars($element);

        $startdate = acym_date($element->startrepeat, acym_translation('ACYM_DATE_FORMAT_LC1'), false);
        $enddate = acym_date($element->endrepeat, acym_translation('ACYM_DATE_FORMAT_LC1'), false);
        $starttime = substr($element->startrepeat, 11, 5);
        $endtime = substr($element->endrepeat, 11, 5);

        if ($starttime == '00:00') {
            $starttime = '';
            $endtime = '';
        } elseif ($element->noendtime) {
            $endtime = '';
        }

        if (!empty($this->useStdTime)) {
            if (!empty($starttime)) $starttime = strtolower(strftime("%#I:%M%p", strtotime($element->startrepeat)));
            if (!empty($endtime)) $endtime = strtolower(strftime("%#I:%M%p", strtotime($element->endrepeat)));
        }

        $date = $startdate;
        if (!empty($starttime)) $date .= ' '.$starttime;
        if ($startdate == $enddate) {
            if (!empty($endtime)) $date .= ' - '.$endtime;
        } else {
            $date .= ' - '.$enddate;
            if (!empty($endtime)) $date .= ' '.$endtime;
        }
        $varFields['{date}'] = $date;


        $link = 'index.php?option=com_jevents&task=icalrepeat.detail&evid='.intval($element->rp_id);
        if (empty($tag->itemid)) {
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
                    if ($menuId != '') break;
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
        } else {
            $link .= '&Itemid='.intval($tag->itemid);
        }

        $link = $this->finalizeLink($link);
        $varFields['{link}'] = $link;

        $title = $element->summary;

        $afterTitle = '';
        $afterArticle = '';

        $imagePath = '';
        $contentText = '';
        $customFields = [];


        //load values
        $customVDB = [];
        if (in_array(acym_getPrefix().'jev_customfields', acym_getTableList())) {
            $customVDB = acym_loadObjectList('SELECT name, value FROM #__jev_customfields WHERE evdet_id = '.intval($element->evdet_id));
        }
        foreach ($customVDB as $oneField) {
            $varFields['{'.$oneField->name.'}'] = $oneField->value;
        }

        if ($tag->type == 'full') {
            $contentText = $element->description;
            $customFields[] = [$date];

            if (!empty($element->location)) $customFields[] = [$element->location, acym_translation('ACYM_ADDRESS')];

            //handle custom fields
            if (!empty($tag->custom)) {
                //extract wanted fields
                $tag->custom = explode(',', $tag->custom);
                foreach ($tag->custom as $i => $oneField) {
                    $tag->custom[$i] = trim($oneField);
                }

                //first retrieve which template is selected in jevents plugin for custom fields
                $jevCFParams = acym_loadObject('SELECT params FROM #__extensions WHERE element = "jevcustomfields"');
                if (!empty($jevCFParams->params)) $template = json_decode($jevCFParams->params)->template;

                //load available custom fields
                if (!empty($template)) {
                    $xmlfile = JPATH_SITE.DS.'plugins'.DS.'jevents'.DS.'jevcustomfields'.DS.'customfields'.DS.'templates'.DS.$template;
                    if (file_exists($xmlfile)) {
                        $xml = simplexml_load_file($xmlfile);
                        $jevCf = $xml->xpath('//fields/fieldset/field');
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
                    }
                }

                $customValues = [];
                foreach ($customVDB as $oneCustomValue) {
                    $customValues[$oneCustomValue->name] = $oneCustomValue->value;
                }

                //display custom fields
                if (!empty($customValues)) {
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

                        if (empty($customValues[$oneCustom]) && in_array(
                                $jevCustomFields[$oneCustom]->type,
                                ['jevcfuser', 'jevcfyoutube', 'jevcfupdatable', 'jevcfdblist', 'jevcftext', 'jevcfimage', 'jevcffile', 'jevcfhtml', 'jevcfeventflag', 'jevcfnotes']
                            )) {
                            unset($customValues[$oneCustom]);
                        }

                        if (isset($customValues[$oneCustom])) $customFields[] = [$customValues[$oneCustom], $label];
                    }
                }
            }

            if (!empty($element->contact)) {
                $value = $element->contact;

                if (acym_isValidEmail($value)) $value = '<a href="mailto:'.$value.'">'.$value.'</a>';

                $customFields[] = [$value, acym_translation('JEV_EVENT_CONTACT')];
            }
            if (!empty($element->extra_info)) $customFields[] = [$element->extra_info];
        }

        // Handle the main picture
        if (file_exists(JPATH_SITE.DS.'plugins'.DS.'jevents'.DS.'jevfiles'.DS.'jevfiles.php')) {

            if (in_array(acym_getPrefix().'jev_files_combined', acym_getTableList())) {
                $filesRow = acym_loadObject(
                    'SELECT files.* 
                    FROM `#__jev_files_combined` AS files 
                    JOIN #__jevents_repetition AS rpt ON files.ev_id = rpt.eventid 
                    WHERE rpt.rp_id = '.intval($tag->id)
                );

                if (!empty($filesRow)) {
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
                            if (!empty($tag->pluginFields)) $files[] = '<a target="_blank" href="'.$varFields['{filepath'.$i.'}'].'">'.(empty($filesRow->{'filename'.$i}) ? : $filesRow->{'filetitle'.$i}).'</a>';
                        }
                    }
                    if (!empty($files)) $afterArticle .= implode('<br />', $files);
                }
            } else {
                $files = acym_loadObjectList(
                    'SELECT files.* 
                    FROM `#__jev_files` AS files 
                    JOIN #__jevents_repetition AS rpt ON files.ev_id = rpt.eventid 
                    WHERE rpt.rp_id = '.intval($tag->id).' 
                    ORDER BY filetype DESC'
                );

                if (!empty($files)) {
                    foreach ($files as $i => $oneFile) {
                        if (empty($oneFile->filename)) continue;

                        $varFields['{imgpath'.$i.'}'] = $this->imgFolder.$oneFile->filename;
                        if ($oneFile->filetype == 'file') {
                            if (!empty($tag->pluginFields)) $afterArticle .= '<br /><a target="_blank" href="'.$varFields['{imgpath'.$i.'}'].'">'.$oneFile->filetitle.'</a>';
                        } else {
                            if (empty($imagePath)) {
                                $imagePath = $varFields['{imgpath'.$i.'}'];
                                continue;
                            }
                            $afterArticle .= '<br /><a target="_blank" href="'.$varFields['{imgpath'.$i.'}'].'"><img src="'.$varFields['{imgpath'.$i.'}'].'" alt="" /></a>';
                        }
                    }
                }
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
        $result['categories'] = acym_loadObjectList('SELECT `id`, `title` FROM #__categories WHERE `extension` = "com_jevents"', 'id');

        foreach ($result['categories'] as $key => $category) {
            $result['categories'][$key] = $category->title;
        }

        $result['categories'] = ['' => acym_translation('ACYM_ANY_CATEGORY')] + $result['categories'];

        return $result;
    }

    public function onAcymDeclareTriggers(&$triggers, &$defaultValues)
    {
        $params = $this->getTriggerParams();

        $triggers['classic']['jevents_reminder'] = new stdClass();
        $triggers['classic']['jevents_reminder']->name = acym_translationSprintf('ACYM_COMBINED_TRANSLATIONS', 'JEvents', acym_translation('ACYM_REMINDER'));
        $triggers['classic']['jevents_reminder']->option = '<div class="grid-x cell acym_vcenter"><div class="grid-x cell grid-margin-x acym_vcenter margin-bottom-1">';
        $triggers['classic']['jevents_reminder']->option .= '<div class="cell medium-shrink">
                                                                <input 
                                                                    type="number" 
                                                                    name="[triggers][classic][jevents_reminder][number]" 
                                                                    class="intext_input" 
                                                                    value="'.(empty($defaultValues['jevents_reminder']) ? '1' : $defaultValues['jevents_reminder']['number']).'">
                                                            </div>';
        $triggers['classic']['jevents_reminder']->option .= '<div class="cell medium-shrink">'.acym_select(
                $params['every'],
                '[triggers][classic][jevents_reminder][time]',
                empty($defaultValues['jevents_reminder']) ? '86400' : $defaultValues['jevents_reminder']['time'],
                'data-class="intext_select acym__select"'
            ).'</div></div>';
        $triggers['classic']['jevents_reminder']->option .= '<div class="grid-x cell grid-margin-x acym_vcenter margin-bottom-1"><div class="cell medium-shrink">'.acym_select(
                $params['when'],
                '[triggers][classic][jevents_reminder][when]',
                empty($defaultValues['jevents_reminder']) ? 'before' : $defaultValues['jevents_reminder']['when'],
                'data-class="intext_select acym__select"'
            ).'</div>';
        $triggers['classic']['jevents_reminder']->option .= '<div class="cell medium-shrink">'.acym_translation('ACYM_AN_EVENT_IN').'</div>';
        $triggers['classic']['jevents_reminder']->option .= '<div class="cell medium-auto">'.acym_select(
                $params['categories'],
                '[triggers][classic][jevents_reminder][cat]',
                empty($defaultValues['jevents_reminder']) ? '' : $defaultValues['jevents_reminder']['cat'],
                'data-class="intext_select_larger intext_select acym__select"'
            ).'</div>';
        $triggers['classic']['jevents_reminder']->option .= '</div></div>';
    }

    public function onAcymExecuteTrigger(&$step, &$execute, &$data)
    {
        $time = $data['time'];
        $triggers = $step->triggers;

        if (!empty($triggers['jevents_reminder']['number'])) {
            $triggerReminder = $triggers['jevents_reminder'];

            $timestamp = ($triggerReminder['number'] * $triggerReminder['time']);

            if ($triggerReminder['when'] == 'before') {
                $timestamp += $time;
            } else {
                $timestamp -= $time;
            }


            $join = [];
            $where = [];

            if (!empty($triggerReminder['cat'])) {
                $multicat = JComponentHelper::getParams('com_jevents')->get('multicategory', 0);
                if ($multicat == 1) {
                    $join[] = 'JOIN #__jevents_catmap AS cats ON rpt.eventid = cats.evid ';
                    $where[] = 'cats.catid = '.intval($triggerReminder['cat']);
                } else {
                    $join[] = 'LEFT JOIN #__jevents_vevent AS event ON `rpt`.`eventid` = `event`.`ev_id`';
                    $where[] = '`event`.`catid` = '.intval($triggerReminder['cat']);
                }
            }
            $join[] = 'LEFT JOIN #__jevents_vevdetail AS eventd ON `rpt`.`eventdetail_id` = `eventd`.`evdet_id`';

            $where[] = '`rpt`.`startrepeat` >= '.acym_escapeDB(acym_date($timestamp, 'Y-m-d H:i:s'));
            $where[] = '`rpt`.`startrepeat` <= '.acym_escapeDB(acym_date($timestamp + $this->config->get('cron_frequency', '900'), 'Y-m-d H:i:s'));
            $where[] = '`eventd`.`state` = 1';

            $events = acym_loadObjectList('SELECT * FROM `#__jevents_repetition` AS rpt '.implode(' ', $join).' WHERE '.implode(' AND ', $where));
            if (!empty($events)) $execute = true;
        }
    }

    public function onAcymDeclareSummary_triggers(&$automation)
    {
        if (!empty($automation->triggers['jevents_reminder'])) {
            $params = $this->getTriggerParams();

            $final = $automation->triggers['jevents_reminder']['number'].' ';
            $final .= $params['every'][$automation->triggers['jevents_reminder']['time']].' ';
            $final .= $params['when'][$automation->triggers['jevents_reminder']['when']].' ';
            $final .= acym_translation('ACYM_AN_EVENT_IN').' '.strtolower($params['categories'][$automation->triggers['jevents_reminder']['cat']]);

            $automation->triggers['jevents_reminder'] = $final;
        }
    }

    /**
     * Function called with ajax to search in events
     */
    public function searchEvent()
    {
        $id = acym_getVar('int', 'id');
        if (!empty($id)) {
            $subject = acym_loadResult(
                'SELECT evdet.summary 
                FROM #__jev_attendance AS attendance 
                JOIN #__jevents_vevent AS ev ON ev.ev_id = attendance.ev_id 
                JOIN #__jevents_vevdetail AS evdet ON ev.detail_id = evdet.evdet_id 
                WHERE attendance.id = '.intval($id)
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
            'SELECT attendance.id, attendance.ev_id, evdet.summary 
            FROM #__jev_attendance AS attendance 
            JOIN #__jevents_vevent AS ev ON ev.ev_id = attendance.ev_id 
            JOIN #__jevents_vevdetail AS evdet ON ev.detail_id = evdet.evdet_id 
            WHERE evdet.summary LIKE '.acym_escapeDB('%'.$search.'%').' 
            ORDER BY evdet.summary ASC'
        );

        foreach ($elements as $oneElement) {
            $return[] = [$oneElement->id, $oneElement->ev_id.' - '.$oneElement->summary];
        }

        echo json_encode($return);
        exit;
    }

    public function onAcymDeclareFilters(&$filters)
    {
        $this->filtersFromConditions($filters);
    }

    public function onAcymDeclareConditions(&$conditions)
    {
        if (!file_exists(JPATH_SITE.DS.'components'.DS.'com_rsvppro')) return;
        acym_loadLanguageFile('com_rsvppro', JPATH_ADMINISTRATOR);

        $conditions['user']['jeventsregistration'] = new stdClass();
        $conditions['user']['jeventsregistration']->name = acym_translationSprintf('ACYM_COMBINED_TRANSLATIONS', 'JEvents', 'RSVP');
        $conditions['user']['jeventsregistration']->option = '<div class="cell grid-x grid-margin-x">';

        $conditions['user']['jeventsregistration']->option .= '<div class="intext_select_automation cell">';
        $ajaxParams = json_encode(
            [
                'plugin' => __CLASS__,
                'trigger' => 'searchEvent',
            ]
        );
        $conditions['user']['jeventsregistration']->option .= acym_select(
            [],
            'acym_condition[conditions][__numor__][__numand__][jeventsregistration][event]',
            null,
            'class="acym__select acym_select2_ajax" data-placeholder="'.acym_translation('ACYM_ANY_EVENT', true).'" data-params="'.acym_escape($ajaxParams).'"'
        );
        $conditions['user']['jeventsregistration']->option .= '</div>';

        $status = [];
        $status[] = acym_selectOption('-1', 'RSVP_ALL_REGISTERED_USERS');
        $status[] = acym_selectOption('1', 'RSVP_IS_CONFIRMED');
        $status[] = acym_selectOption('0', 'RSVP_PENDING');

        $conditions['user']['jeventsregistration']->option .= '<div class="intext_select_automation cell">';
        $conditions['user']['jeventsregistration']->option .= acym_select(
            $status,
            'acym_condition[conditions][__numor__][__numand__][jeventsregistration][status]',
            '-1',
            'class="acym__select"'
        );
        $conditions['user']['jeventsregistration']->option .= '</div>';

        $conditions['user']['jeventsregistration']->option .= '</div>';

        $conditions['user']['jeventsregistration']->option .= '<div class="cell grid-x grid-margin-x">';
        $conditions['user']['jeventsregistration']->option .= acym_dateField('acym_condition[conditions][__numor__][__numand__][jeventsregistration][datemin]', '', 'cell shrink');
        $conditions['user']['jeventsregistration']->option .= '<span class="acym__title acym__title__secondary acym_vcenter margin-bottom-0 cell shrink"><</span>';
        $conditions['user']['jeventsregistration']->option .= '<span class="acym_vcenter">'.acym_translation('RSVP_EVENT_REGISTRATION_DATE').'</span>';
        $conditions['user']['jeventsregistration']->option .= '<span class="acym__title acym__title__secondary acym_vcenter margin-bottom-0 cell shrink"><</span>';
        $conditions['user']['jeventsregistration']->option .= acym_dateField('acym_condition[conditions][__numor__][__numand__][jeventsregistration][datemax]', '', 'cell shrink');
        $conditions['user']['jeventsregistration']->option .= '</div>';
    }

    public function onAcymProcessCondition_jeventsregistration(&$query, $options, $num, &$conditionNotValid)
    {
        $this->processConditionFilter_jeventsregistration($query, $options, $num);
        $affectedRows = $query->count();
        if (empty($affectedRows)) $conditionNotValid++;
    }

    public function onAcymProcessFilterCount_jeventsregistration(&$query, $options, $num)
    {
        $this->onAcymProcessFilter_jeventsregistration($query, $options, $num);

        return acym_translationSprintf('ACYM_SELECTED_USERS', $query->count());
    }

    public function onAcymProcessFilter_jeventsregistration(&$query, $options, $num)
    {
        $this->processConditionFilter_jeventsregistration($query, $options, $num);
    }

    private function processConditionFilter_jeventsregistration(&$query, $options, $num)
    {
        if (!$this->installed) return;

        $query->join['jeventsregistration'.$num] = '#__jev_attendees AS jev_attendees'.$num.' ON jev_attendees'.$num.'.email_address = user.email OR (jev_attendees'.$num.'.user_id != 0 AND jev_attendees'.$num.'.user_id = user.cms_id)';
        if (!empty($options['event'])) $query->where[] = 'jev_attendees'.$num.'.at_id = '.intval($options['event']);
        if ($options['status'] != -1) {
            $query->where[] = 'jev_attendees'.$num.'.attendstate = '.intval($options['status']);
        }

        if (!empty($options['datemin'])) {
            $options['datemin'] = acym_replaceDate($options['datemin']);
            if (!is_numeric($options['datemin'])) $options['datemin'] = strtotime($options['datemin']);
            if (!empty($options['datemin'])) {
                $query->where[] = 'jev_attendees'.$num.'.created > '.acym_escapeDB(date('Y-m-d H:i:s', $options['datemin']));
            }
        }

        if (!empty($options['datemax'])) {
            $options['datemax'] = acym_replaceDate($options['datemax']);
            if (!is_numeric($options['datemax'])) $options['datemax'] = strtotime($options['datemax']);
            if (!empty($options['datemax'])) {
                $query->where[] = 'jev_attendees'.$num.'.created < '.acym_escapeDB(date('Y-m-d H:i:s', $options['datemax']));
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
        if (!empty($automation['jeventsregistration'])) {
            acym_loadLanguageFile('com_rsvppro', JPATH_ADMINISTRATOR);

            if (empty($automation['jeventsregistration']['event'])) {
                $event = acym_translation('ACYM_ANY_EVENT');
            } else {
                $event = acym_loadResult(
                    'SELECT evdet.summary 
                    FROM #__jev_attendance AS attendance 
                    JOIN #__jevents_vevent AS ev ON ev.ev_id = attendance.ev_id 
                    JOIN #__jevents_vevdetail AS evdet ON ev.detail_id = evdet.evdet_id 
                    WHERE attendance.id = '.intval($automation['jeventsregistration']['event'])
                );
            }

            $status = [
                '-1' => 'ACYM_ANY',
                '1' => 'RSVP_IS_CONFIRMED',
                '0' => 'RSVP_PENDING',
            ];

            $status = acym_translation($status[$automation['jeventsregistration']['status']]);

            $finalText = acym_translationSprintf('ACYM_REGISTERED', $event, $status);

            $dates = [];
            if (!empty($automation['jeventsregistration']['datemin'])) {
                $dates[] = acym_translation('ACYM_AFTER').' '.acym_replaceDate($automation['jeventsregistration']['datemin'], true);
            }

            if (!empty($automation['jeventsregistration']['datemax'])) {
                $dates[] = acym_translation('ACYM_BEFORE').' '.acym_replaceDate($automation['jeventsregistration']['datemax'], true);
            }

            if (!empty($dates)) {
                $finalText .= ' '.implode(' '.acym_translation('ACYM_AND').' ', $dates);
            }

            $automation = $finalText;
        }
    }
}
