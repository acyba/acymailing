<?php

use AcyMailing\Libraries\acymPlugin;
use AcyMailing\Helpers\TabHelper;
use AcyMailing\Types\OperatorinType;
use AcyMailing\Types\OperatorType;

class plgAcymEasysocial extends acymPlugin
{
    private $esConfig;
    public $rootCategoryId = 0;

    public function __construct()
    {
        parent::__construct();
        $this->cms = 'Joomla';
        $this->installed = acym_isExtensionActive('com_easysocial');

        $this->pluginDescription->name = 'EasySocial';
        $this->pluginDescription->icon = ACYM_DYNAMICS_URL.basename(__DIR__).'/icon.png';

        if ($this->installed) {
            $this->displayOptions = [
                'title' => ['ACYM_TITLE', true],
                'image' => ['ACYM_IMAGE', true],
                'date' => ['ACYM_DATE', true],
                'location' => ['ACYM_LOCATION', true],
                'desc' => ['ACYM_DESCRIPTION', true],
                'url' => ['ACYM_URL', false],
                'capacity' => ['FIELDS_EVENT_GUESTLIMIT_DEFAULT_TITLE', false],
            ];

            $this->initCustomView();

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

    public function dynamicText($mailId)
    {
        return $this->pluginDescription;
    }

    public function textPopup()
    {
        acym_loadLanguageFile('com_easysocial', JPATH_SITE);
        acym_loadLanguageFile('com_easysocial', JPATH_ADMINISTRATOR);
        ?>
		<script type="text/javascript">
            var selectedESUserDText;

            function applyES(tagname, element) {
                if (!tagname) return;

                selectedESUserDText = tagname;
                let string = '{<?php echo $this->name; ?>field:' + tagname + '|info:' + jQuery('input[name="typeInfoES"]:checked').val() + '}';
                setTag(string, jQuery(element));
            }

            function updateESFields(profile) {
                jQuery('[data-acym-profile]').addClass('is-hidden');
                jQuery('[data-acym-profile="' + profile + '"]').removeClass('is-hidden');
            }
		</script>
        <?php

        $text = '<div class="grid-x acym__popup__listing">';

        $typeinfo = [];
        $typeinfo[] = acym_selectOption('receiver', 'ACYM_RECEIVER_INFORMATION');
        $typeinfo[] = acym_selectOption('sender', 'ACYM_SENDER_INFORMATION');
        $text .= acym_radio($typeinfo, 'typeInfoES', 'receiver', ['onclick' => 'applyES(selectedESUserDText, this)']);

        $profiles = acym_loadObjectList('SELECT id, title FROM #__social_profiles');
        $profilesList = [];
        $profilesList[] = acym_selectOption(0, acym_translation('COM_EASYSOCIAL_REGISTRATIONS_SELECT_PROFILE_TYPE_TITLE'));
        foreach ($profiles as $oneProfile) {
            $profilesList[] = acym_selectOption($oneProfile->id, $oneProfile->title);
        }
        $text .= acym_select(
            $profilesList,
            'userfields_profile',
            '',
            [
                'onchange' => 'updateESFields(this.value)',
                'style' => 'width: 220px;',
            ]
        );

        $fields = acym_loadObjectList(
            'SELECT field.unique_key, field.title, workflowMap.uid 
					FROM #__social_fields AS field 
					JOIN #__social_fields_steps AS fieldStep ON field.step_id = fieldStep.id 
					JOIN #__social_workflows_maps AS workflowMap ON workflowMap.workflow_id = fieldStep.workflow_id 
					WHERE fieldStep.type = "profiles" 
						AND field.unique_key NOT LIKE "'.implode(
                '%" AND field.unique_key NOT LIKE "',
                ["JOOMLA_", "HEADER", "SEPARATOR", "TERMS", "COVER", "AVATAR", "HTML", "TEXT-", "FILE", "CURRENCY"]
            ).'%"'
        );

        foreach ($fields as $field) {
            $text .= '<div data-acym-profile="'.$field->uid.'" class="cell acym__row__no-listing acym__listing__row__popup is-hidden" onclick="applyES(\''.$field->unique_key.'\', this);">';
            $text .= acym_translation($field->title);
            $text .= '</div>';
        }

        $text .= '</div>';

        echo $text;
    }

    public function replaceUserInformation(&$email, &$user, $send = true)
    {
        $extractedTags = $this->pluginHelper->extractTags($email, $this->name.'field');
        if (empty($extractedTags)) return;

        require_once JPATH_ADMINISTRATOR.'/components/com_easysocial/includes/foundry.php';
        acym_loadLanguageFile('com_easysocial', JPATH_SITE);
        $receiver = Foundry::user($user->cms_id);
        $sender = Foundry::user($email->creator_id);

        $tags = [];
        foreach ($extractedTags as $i => $oneTag) {
            if (isset($tags[$i])) continue;

            if (!empty($oneTag->info) && $oneTag->info === 'sender') {
                if (!empty($sender)) {
                    $fieldValue = $sender->getFieldValue($oneTag->id);
                } else {
                    $fieldValue = '';
                }
            } else {
                $fieldValue = $receiver->getFieldValue($oneTag->id);
            }

            if (empty($fieldValue)) {
                $tags[$i] = '';
                continue;
            }

            if (is_string($fieldValue)) {
                $tags[$i] = $fieldValue;
                continue;
            }

            if (is_string($fieldValue->value)) {
                if (strstr($fieldValue->unique_key, 'BOOLEAN')) {
                    $tags[$i] = acym_translation(empty($fieldValue->value) ? 'ACYM_NO' : 'ACYM_YES');
                } elseif (strstr($fieldValue->unique_key, 'RELATIONSHIP')) {
                    $tags[$i] = json_decode($fieldValue->value)->type;
                } elseif (strstr($fieldValue->unique_key, 'COUNTRY')) {
                    $tags[$i] = implode(', ', json_decode($fieldValue->value));
                } else {
                    $tags[$i] = $fieldValue->value;
                }
            } elseif (is_object($fieldValue->value)) {
                $arrayValue = get_object_vars($fieldValue->value);

                if (in_array('day', array_keys($arrayValue))) {
                    if (empty($fieldValue->raw['date'])) {
                        $tags[$i] = '';
                    } else {
                        $tags[$i] = acym_date(strtotime($fieldValue->raw['date']), acym_translation('ACYM_DATE_FORMAT_LC1'));
                    }
                } elseif (!empty($arrayValue['address1']) || !empty($arrayValue['address2'])) {
                    $address = trim($arrayValue['address1'].' '.$arrayValue['address2'], ' ').', '.$arrayValue['zip'].' '.$arrayValue['city'].', '.$arrayValue['country'];
                    $tags[$i] = trim($address, ', ');
                } elseif (strpos($fieldValue->unique_key, 'GENDER') !== -1) {
                    $tags[$i] = empty($fieldValue->raw) ? '' : acym_translation($arrayValue['text']);
                } elseif (!empty($arrayValue['text'])) {
                    $tags[$i] = acym_translation($arrayValue['text']);
                } else {
                    $tags[$i] = trim(implode(', ', $arrayValue), ', ');
                }
            } elseif (is_array($fieldValue->value)) {
                $tags[$i] = implode(', ', $fieldValue->value);
            } else {
                $tags[$i] = '';
            }

            $this->pluginHelper->formatString($tags[$i], $oneTag);
        }

        $this->pluginHelper->replaceTags($email, $tags);
    }

    public function getStandardStructure(&$customView)
    {
        $tag = new stdClass();
        $tag->id = 0;

        $format = new stdClass();
        $format->tag = $tag;
        $format->title = '{title}';
        $format->afterTitle = '';
        $format->afterArticle = acym_translation('ACYM_DATE').': {date} <br/> '.acym_translation('ACYM_LOCATION').': {location}';
        $format->imagePath = '{image}';
        $format->description = '{desc}';
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
            'startdate' => ['ACYM_START_DATE'],
            'enddate' => ['ACYM_END_DATE'],
        ];
    }

    public function initElementOptionsCustomView()
    {
        $query = 'SELECT `event`.*, `eventdata`.start_gmt, `eventdata`.end_gmt, `eventdata`.all_day, avatars.large AS avatar 
					FROM #__social_clusters AS `event` 
					JOIN #__social_events_meta AS `eventdata` ON `event`.`id` = `eventdata`.`cluster_id` 
					LEFT JOIN #__social_avatars AS avatars ON avatars.type = "event" AND avatars.uid = `event`.id 
					WHERE `event`.cluster_type = "event"';

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
        acym_loadLanguageFile('com_easysocial', JPATH_SITE);
        $this->defaultValues = $defaultValues;

        $this->categories = acym_loadObjectList('SELECT `id`, `parent_id`, `title` FROM `#__social_clusters_categories` WHERE state = 1 AND type = "event"', 'id');

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
                    'eventdata.start_gmt' => 'ACYM_DATE',
                    'title' => 'ACYM_TITLE',
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
        $this->querySelect = 'SELECT event.*, `eventdata`.start_gmt ';
        $this->query = 'FROM `#__social_clusters` AS event ';
        $this->query .= 'JOIN #__social_events_meta AS `eventdata` ON `event`.`id` = `eventdata`.`cluster_id` ';
        $this->filters = [];
        $this->filters[] = '`event`.cluster_type = "event"';
        $this->filters[] = 'event.state = 1';
        $this->filters[] = 'event.type = 1';
        $this->searchFields = ['event.id', 'event.title'];
        $this->pageInfo->order = 'eventdata.start_gmt';
        $this->elementIdTable = 'event';
        $this->elementIdColumn = 'id';

        if (!acym_isAdmin() && $this->getParam('front', 'all') === 'author') {
            $this->filters[] = 'event.creator_uid = '.intval(acym_currentUserId());
        }

        if ($this->getParam('hidepast', '1') === '1') {
            $this->filters[] = 'eventdata.start_gmt >= '.acym_escapeDB(date('Y-m-d H:i:s'));
        }

        parent::prepareListing();

        if (!empty($this->pageInfo->filter_cat)) {
            $this->filters[] = 'event.`category_id` = '.intval($this->pageInfo->filter_cat);
        }

        $listingOptions = [
            'header' => [
                'title' => [
                    'label' => 'ACYM_TITLE',
                    'size' => '8',
                ],
                'start_gmt' => [
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
        acym_loadLanguageFile('com_easysocial', JPATH_SITE);
        require_once JPATH_ADMINISTRATOR.'/components/com_easysocial/includes/foundry.php';
        require_once JPATH_ADMINISTRATOR.'/components/com_easysocial/includes/storage/storage.php';
        $this->esConfig = FD::config();

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

            $query = 'SELECT DISTINCT event.id 
						FROM `#__social_clusters` AS event 
						JOIN #__social_events_meta AS `eventdata` ON `event`.`id` = `eventdata`.`cluster_id` ';

            $where = [];
            $where[] = 'event.`cluster_type` = "event"';
            $where[] = 'event.`state` = 1';
            $where[] = 'event.`type` = 1';

            $selectedArea = $this->getSelectedArea($parameter);
            if (!empty($selectedArea)) {
                $where[] = 'event.category_id IN ('.implode(',', $selectedArea).')';
            }

            // Not started events
            $where[] = 'eventdata.`start_gmt` >= '.acym_escapeDB($parameter->from);

            if (!empty($parameter->to)) $where[] = 'eventdata.`start_gmt` <= '.acym_escapeDB($parameter->to).' AND eventdata.`start_gmt` != "0000-00-00 00:00:00"';

            if (!empty($parameter->featured)) $where[] = 'event.featured = 1';

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
        $query = 'SELECT `event`.*, `eventdata`.start_gmt, `eventdata`.end_gmt, `eventdata`.all_day, avatars.large AS avatar, avatars.storage  
					FROM #__social_clusters AS `event` 
					JOIN #__social_events_meta AS `eventdata` ON `event`.`id` = `eventdata`.`cluster_id` 
					LEFT JOIN #__social_avatars AS avatars ON avatars.type = "event" AND avatars.uid = `event`.id 
					WHERE `event`.id = '.intval($tag->id);

        $element = $this->initIndividualContent($tag, $query);

        if (empty($element)) return '';

        $varFields = $this->getCustomLayoutVars($element);
        $link = 'index.php?option=com_easysocial&view=events&layout=item&id='.$tag->id.':'.$element->alias;
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

        $varFields['{desc}'] = $element->description;

        if (in_array('desc', $tag->display)) {
            $contentText .= $varFields['{desc}'];
        }

        if (!empty($element->avatar)) {
            $storage = new SocialStorage($this->esConfig->get('storage.avatars', 'joomla'));

            $relativePath = FD::cleanPath($this->esConfig->get('avatars.storage.container')).'/'.FD::cleanPath(
                    $this->esConfig->get('avatars.storage.event')
                ).'/'.$element->id.'/'.$element->avatar;
            if (file_exists(JPATH_SITE.DS.$relativePath)) {
                $element->avatar = $storage->getPermalink($relativePath);
            }
            $imagePath = $element->avatar;
        }
        $varFields['{image}'] = $imagePath;
        $varFields['{picthtml}'] = '<img alt="" src="'.$imagePath.'">';
        if (!in_array('image', $tag->display)) $imagePath = '';

        if (empty($element->all_day)) {
            $varFields['{startdate}'] = acym_date($element->start_gmt, acym_translation('ACYM_DATE_FORMAT_LC2'));
            if ($element->end_gmt === '0000-00-00 00:00:00') {
                $varFields['{enddate}'] = $varFields['{startdate}'];
            } else {
                $varFields['{enddate}'] = acym_date($element->end_gmt, acym_translation('ACYM_DATE_FORMAT_LC2'));
            }
        } else {
            $varFields['{startdate}'] = acym_date($element->start_gmt, acym_translation('ACYM_DATE_FORMAT_LC1'));
            if ($element->end_gmt === '0000-00-00 00:00:00') {
                $varFields['{enddate}'] = '';
            } else {
                $varFields['{enddate}'] = acym_date($element->end_gmt, acym_translation('ACYM_DATE_FORMAT_LC1'));
            }
        }

        $varFields['{date}'] = $varFields['{startdate}'];
        if ($varFields['{startdate}'] !== $varFields['{enddate}'] && !empty($varFields['{enddate}'])) {
            $varFields['{date}'] .= ' - '.$varFields['{enddate}'];
        }

        if (in_array('date', $tag->display) && !empty($varFields['{startdate}']) && !empty($varFields['{enddate}'])) {
            $customFields[] = [
                $varFields['{date}'],
                acym_translation('ACYM_DATE'),
            ];
        }

        $varFields['{location}'] = '';
        $gmapQuery = '';
        if (!empty($element->latitude) && !empty($element->longitude)) {
            $gmapQuery = $element->latitude.','.$element->longitude;
        } elseif (!empty($element->address)) {
            $gmapQuery = $element->address;
        }
        if (!empty($gmapQuery)) {
            $varFields['{location}'] = '<a href="https://maps.google.com/?q='.urlencode($gmapQuery).'" target="_blank">';
            $varFields['{location}'] .= acym_translation('FIELDS_USER_ADDRESS_VIEW_IN_MAPS');
            $varFields['{location}'] .= '</a>';
        }

        if (in_array('location', $tag->display)) {
            $customFields[] = [
                $varFields['{location}'],
                acym_translation('ACYM_LOCATION'),
            ];
        }

        $varFields['{capacity}'] = '';
        if (!empty($element->params)) {
            $element->params = json_decode($element->params, true);
            if (!empty($element->params['guestlimit'])) {
                $varFields['{capacity}'] = $element->params['guestlimit'];
            }
        }
        if (in_array('capacity', $tag->display) && !empty($varFields['{capacity}'])) {
            $customFields[] = [
                $varFields['{capacity}'],
                acym_translation('FIELDS_EVENT_GUESTLIMIT_DEFAULT_TITLE'),
            ];
        }

        $varFields['{url}'] = '';
        $url = acym_loadResult(
            'SELECT fielddata.raw 
			FROM #__social_fields_data AS fielddata 
			JOIN #__social_fields AS field ON field.id = fielddata.field_id 
			WHERE field.unique_key LIKE "URL%"
				AND fielddata.uid = '.intval($tag->id)
        );
        if (!empty($url)) $varFields['{url}'] = '<a target="_blank" href="'.$url.'">'.$url.'</a>';
        if (in_array('url', $tag->display) && !empty($varFields['{url}'])) {
            $customFields[] = [
                $varFields['{url}'],
                acym_translation('ACYM_URL'),
            ];
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

    public function onAcymDeclareConditions(&$conditions)
    {
        acym_loadLanguageFile('com_easysocial', JPATH_SITE);
        $operatorIn = new OperatorinType();
        $operator = new OperatorType();

        // Groups filter
        $conditions['user']['easysocialgroups'] = new stdClass();
        $conditions['user']['easysocialgroups']->name = acym_translationSprintf('ACYM_COMBINED_TRANSLATIONS', 'EasySocial', acym_translation('ACYM_GROUP'));
        $conditions['user']['easysocialgroups']->option = '<div class="cell grid-x grid-margin-x">';

        $conditions['user']['easysocialgroups']->option .= '<div class="intext_select_automation cell">';
        $conditions['user']['easysocialgroups']->option .= $operatorIn->display('acym_condition[conditions][__numor__][__numand__][easysocialgroups][in]');
        $conditions['user']['easysocialgroups']->option .= '</div>';

        $conditions['user']['easysocialgroups']->option .= '<div class="intext_select_automation cell">';
        $ajaxParams = json_encode([
            'plugin' => __CLASS__,
            'trigger' => 'searchGroup',
        ]);
        $conditions['user']['easysocialgroups']->option .= acym_select(
            [],
            'acym_condition[conditions][__numor__][__numand__][easysocialgroups][group]',
            null,
            'class="acym__select acym_select2_ajax" data-placeholder="'.acym_translation('ACYM_ANY_GROUP', true).'" data-params="'.acym_escape($ajaxParams).'"'
        );
        $conditions['user']['easysocialgroups']->option .= '</div>';

        $conditions['user']['easysocialgroups']->option .= '</div>';

        $conditions['user']['easysocialgroups']->option .= '<div class="cell grid-x grid-margin-x">';
        $conditions['user']['easysocialgroups']->option .= acym_dateField('acym_condition[conditions][__numor__][__numand__][easysocialgroups][datemin]', '', 'cell shrink');
        $conditions['user']['easysocialgroups']->option .= '<span class="acym__title acym__title__secondary acym_vcenter margin-bottom-0 cell shrink"><</span>';
        $conditions['user']['easysocialgroups']->option .= '<span class="acym_vcenter">'.acym_translation('ACYM_SUBSCRIPTION_DATE').'</span>';
        $conditions['user']['easysocialgroups']->option .= '<span class="acym__title acym__title__secondary acym_vcenter margin-bottom-0 cell shrink"><</span>';
        $conditions['user']['easysocialgroups']->option .= acym_dateField('acym_condition[conditions][__numor__][__numand__][easysocialgroups][datemax]', '', 'cell shrink');
        $conditions['user']['easysocialgroups']->option .= '</div>';


        // Profile type filter
        $conditions['user']['easysocialprofiles'] = new stdClass();
        $conditions['user']['easysocialprofiles']->name = acym_translationSprintf('ACYM_COMBINED_TRANSLATIONS', 'EasySocial', acym_translation('ACYM_MENU_PROFILE'));
        $conditions['user']['easysocialprofiles']->option = '<div class="cell grid-x grid-margin-x">';

        $conditions['user']['easysocialprofiles']->option .= '<div class="intext_select_automation cell">';
        $conditions['user']['easysocialprofiles']->option .= $operatorIn->display('acym_condition[conditions][__numor__][__numand__][easysocialprofiles][in]');
        $conditions['user']['easysocialprofiles']->option .= '</div>';

        $conditions['user']['easysocialprofiles']->option .= '<div class="intext_select_automation cell">';
        $ajaxParams = json_encode([
            'plugin' => __CLASS__,
            'trigger' => 'searchProfile',
        ]);
        $conditions['user']['easysocialprofiles']->option .= acym_select(
            [],
            'acym_condition[conditions][__numor__][__numand__][easysocialprofiles][profile]',
            null,
            'class="acym__select acym_select2_ajax" data-placeholder="'.acym_translation('ACYM_ANY_PROFILE', true).'" data-params="'.acym_escape($ajaxParams).'"'
        );
        $conditions['user']['easysocialprofiles']->option .= '</div>';

        $conditions['user']['easysocialprofiles']->option .= '</div>';

        $conditions['user']['easysocialprofiles']->option .= '<div class="cell grid-x grid-margin-x">';
        $conditions['user']['easysocialprofiles']->option .= acym_dateField('acym_condition[conditions][__numor__][__numand__][easysocialprofiles][datemin]', '', 'cell shrink');
        $conditions['user']['easysocialprofiles']->option .= '<span class="acym__title acym__title__secondary acym_vcenter margin-bottom-0 cell shrink"><</span>';
        $conditions['user']['easysocialprofiles']->option .= '<span class="acym_vcenter">'.acym_translation('ACYM_SUBSCRIPTION_DATE').'</span>';
        $conditions['user']['easysocialprofiles']->option .= '<span class="acym__title acym__title__secondary acym_vcenter margin-bottom-0 cell shrink"><</span>';
        $conditions['user']['easysocialprofiles']->option .= acym_dateField('acym_condition[conditions][__numor__][__numand__][easysocialprofiles][datemax]', '', 'cell shrink');
        $conditions['user']['easysocialprofiles']->option .= '</div>';


        // Badge filter
        $conditions['user']['easysocialbadge'] = new stdClass();
        $conditions['user']['easysocialbadge']->name = acym_translationSprintf('ACYM_COMBINED_TRANSLATIONS', 'EasySocial', acym_translation('COM_EASYSOCIAL_BADGES'));
        $conditions['user']['easysocialbadge']->option = '<div class="cell grid-x grid-margin-x">';

        $conditions['user']['easysocialbadge']->option .= '<div class="intext_select_automation cell">';
        $conditions['user']['easysocialbadge']->option .= $operatorIn->display('acym_condition[conditions][__numor__][__numand__][easysocialbadge][in]');
        $conditions['user']['easysocialbadge']->option .= '</div>';

        $conditions['user']['easysocialbadge']->option .= '<div class="intext_select_automation cell">';
        $allBadges = acym_loadObjectList('SELECT id, title FROM #__social_badges');
        foreach ($allBadges as $i => $oneBadge) {
            $allBadges[$i]->title = acym_translation($oneBadge->title);
        }
        usort($allBadges, function ($a, $b) {
            return strtolower($a->title) > strtolower($b->title) ? 1 : -1;
        });
        $conditions['user']['easysocialbadge']->option .= acym_select(
            $allBadges,
            'acym_condition[conditions][__numor__][__numand__][easysocialbadge][badge]',
            null,
            'class="acym__select" data-placeholder="'.acym_translation('ACYM_ANY_BADGE', true).'"',
            'id',
            'title'
        );
        $conditions['user']['easysocialbadge']->option .= '</div>';

        $conditions['user']['easysocialbadge']->option .= '</div>';

        $conditions['user']['easysocialbadge']->option .= '<div class="cell grid-x grid-margin-x">';
        $conditions['user']['easysocialbadge']->option .= acym_dateField('acym_condition[conditions][__numor__][__numand__][easysocialbadge][datemin]', '', 'cell shrink');
        $conditions['user']['easysocialbadge']->option .= '<span class="acym__title acym__title__secondary acym_vcenter margin-bottom-0 cell shrink"><</span>';
        $conditions['user']['easysocialbadge']->option .= '<span class="acym_vcenter">'.acym_translation('ACYM_DATE_CREATED').'</span>';
        $conditions['user']['easysocialbadge']->option .= '<span class="acym__title acym__title__secondary acym_vcenter margin-bottom-0 cell shrink"><</span>';
        $conditions['user']['easysocialbadge']->option .= acym_dateField('acym_condition[conditions][__numor__][__numand__][easysocialbadge][datemax]', '', 'cell shrink');
        $conditions['user']['easysocialbadge']->option .= '</div>';


        // Field filter
        $conditions['user']['easysocialfield'] = new stdClass();
        $conditions['user']['easysocialfield']->name = acym_translationSprintf('ACYM_COMBINED_TRANSLATIONS', 'EasySocial', acym_translation('ACYM_FIELD'));
        $conditions['user']['easysocialfield']->option = '<div class="cell grid-x grid-margin-x">';

        // profile
        $conditions['user']['easysocialfield']->option .= '<div class="intext_select_automation cell">';
        $conditions['user']['easysocialfield']->option .= acym_select(
            [],
            'acym_condition[conditions][__numor__][__numand__][easysocialfield][profile]',
            null,
            [
                'class' => 'acym__select acym_select2_ajax',
                'data-placeholder' => acym_translation('COM_EASYSOCIAL_PROFILE'),
                'data-params' => [
                    'plugin' => __CLASS__,
                    'trigger' => 'searchProfile',
                ],
                'acym-automation-reload' => [
                    'plugin' => __CLASS__,
                    'trigger' => 'searchFields',
                    'change' => '#easysocialfield_tochange___numor_____numand__',
                    'name' => 'acym_condition[conditions][__numor__][__numand__][easysocialfield][field]',
                    'paramFields' => [
                        'profile' => 'acym_condition[conditions][__numor__][__numand__][easysocialfield][profile]',
                    ],
                ],
            ]
        );
        $conditions['user']['easysocialfield']->option .= '</div>';

        // field
        $conditions['user']['easysocialfield']->option .= '<div class="intext_select_automation cell" id="easysocialfield_tochange___numor_____numand__">';
        $conditions['user']['easysocialfield']->option .= '<input type="text" name="acym_condition[conditions][__numor__][__numand__][easysocialfield][field]" disabled="disabled"/>';
        $conditions['user']['easysocialfield']->option .= '</div>';

        $conditions['user']['easysocialfield']->option .= '<div class="intext_select_automation cell">';
        $conditions['user']['easysocialfield']->option .= $operator->display(
            'acym_condition[conditions][__numor__][__numand__][easysocialfield][operator]',
            '',
            'acym__automation__conditions__operator__dropdown'
        );
        $conditions['user']['easysocialfield']->option .= '</div>';
        $conditions['user']['easysocialfield']->option .= '<input 
            class="acym__automation__one-field intext_input_automation cell acym__automation__condition__regular-field" 
            type="text" 
            name="acym_condition[conditions][__numor__][__numand__][easysocialfield][value]">';

        $conditions['user']['easysocialfield']->option .= '</div>';


        // Attending event filter
        $conditions['user']['easysocialevent'] = new stdClass();
        $conditions['user']['easysocialevent']->name = acym_translationSprintf('ACYM_COMBINED_TRANSLATIONS', 'EasySocial', acym_translation('COM_ES_EVENTS'));
        $conditions['user']['easysocialevent']->option = '<div class="cell grid-x grid-margin-x">';

        $conditions['user']['easysocialevent']->option .= '<div class="intext_select_automation cell">';
        $ajaxParams = json_encode(['plugin' => __CLASS__, 'trigger' => 'searchEvent',]);
        $conditions['user']['easysocialevent']->option .= acym_select(
            [],
            'acym_condition[conditions][__numor__][__numand__][easysocialevent][event]',
            null,
            'class="acym__select acym_select2_ajax" data-placeholder="'.acym_translation('ACYM_ANY_EVENT', true).'" data-params="'.acym_escape($ajaxParams).'"'
        );
        $conditions['user']['easysocialevent']->option .= '</div>';

        $conditions['user']['easysocialevent']->option .= '<div class="intext_select_automation cell">';
        $allCats = acym_loadObjectList('SELECT id, title FROM #__social_clusters_categories WHERE type LIKE "event" ORDER BY title');
        $cats = [acym_selectOption(0, acym_translation('ACYM_ANY_CATEGORY'))];
        foreach ($allCats as $oneCat) {
            $cats[] = acym_selectOption($oneCat->id, acym_translation($oneCat->title));
        }
        $conditions['user']['easysocialevent']->option .= acym_select(
            $cats,
            'acym_condition[conditions][__numor__][__numand__][easysocialevent][category]',
            null,
            'class="acym__select"'
        );
        $conditions['user']['easysocialevent']->option .= '</div>';

        $conditions['user']['easysocialevent']->option .= '<div class="intext_select_automation cell">';
        $state = [
            '0' => acym_translation('ACYM_ANY_STATUS'),
            '1' => acym_translation('COM_EASYSOCIAL_EVENTS_GUEST_GOING'),
            '3' => acym_translation('COM_EASYSOCIAL_EVENTS_GUEST_MAYBE'),
            '4' => acym_translation('COM_EASYSOCIAL_EVENTS_GUEST_NOTGOING'),
        ];
        $conditions['user']['easysocialevent']->option .= acym_select(
            $state,
            'acym_condition[conditions][__numor__][__numand__][easysocialevent][status]',
            null,
            'class="acym__select"'
        );
        $conditions['user']['easysocialevent']->option .= '</div>';

        $conditions['user']['easysocialevent']->option .= '<div class="cell grid-x grid-margin-x margin-top-1 margin-left-0">';
        $conditions['user']['easysocialevent']->option .= acym_dateField('acym_condition[conditions][__numor__][__numand__][easysocialevent][datemin]', '', 'cell shrink');
        $conditions['user']['easysocialevent']->option .= '<span class="acym__title acym__title__secondary acym_vcenter margin-bottom-0 cell shrink"><</span>';
        $conditions['user']['easysocialevent']->option .= '<span class="acym_vcenter">'.acym_translation('ACYM_DATE_CREATED').'</span>';
        $conditions['user']['easysocialevent']->option .= '<span class="acym__title acym__title__secondary acym_vcenter margin-bottom-0 cell shrink"><</span>';
        $conditions['user']['easysocialevent']->option .= acym_dateField('acym_condition[conditions][__numor__][__numand__][easysocialevent][datemax]', '', 'cell shrink');
        $conditions['user']['easysocialevent']->option .= '</div>';

        $conditions['user']['easysocialevent']->option .= '</div>';
    }

    public function onAcymProcessCondition_easysocialgroups(&$query, $options, $num, &$conditionNotValid)
    {
        $this->processConditionFilter_easysocialgroups($query, $options, $num);
        $affectedRows = $query->count();
        if (empty($affectedRows)) $conditionNotValid++;
    }

    private function processConditionFilter_easysocialgroups(&$query, $options, $num)
    {
        $groupsTable = 'easysocialgroups'.$num;
        $membersTable = 'easysocialmembers'.$num;
        $query->leftjoin[$membersTable] = '#__social_clusters_nodes AS '.$membersTable.' ON '.$membersTable.'.uid = user.cms_id AND '.$membersTable.'.type = "user"';

        $query->leftjoin[$groupsTable] = '#__social_clusters AS '.$groupsTable.' ON '.$groupsTable.'.id = '.$membersTable.'.cluster_id';
        $query->leftjoin[$groupsTable] .= ' AND '.$groupsTable.'.cluster_type = "group"';
        $query->leftjoin[$groupsTable] .= ' AND '.$groupsTable.'.state = 1';

        if (!empty($options['group'])) {
            $query->leftjoin[$membersTable] .= ' AND '.$membersTable.'.cluster_id = '.intval($options['group']);
        }

        if (!empty($options['datemin'])) {
            $options['datemin'] = acym_replaceDate($options['datemin']);
            if (!is_numeric($options['datemin'])) $options['datemin'] = strtotime($options['datemin']);
            if (!empty($options['datemin'])) {
                $query->leftjoin[$groupsTable] .= ' AND '.$groupsTable.'.created > '.acym_escapeDB(acym_date($options['datemin'], 'Y-m-d H:i:s', false));
            }
        }

        if (!empty($options['datemax'])) {
            $options['datemax'] = acym_replaceDate($options['datemax']);
            if (!is_numeric($options['datemax'])) $options['datemax'] = strtotime($options['datemax']);
            if (!empty($options['datemax'])) {
                $query->leftjoin[$groupsTable] .= ' AND '.$groupsTable.'.created < '.acym_escapeDB(acym_date($options['datemax'], 'Y-m-d H:i:s', false));
            }
        }

        if (empty($options['in']) || $options['in'] === 'in') {
            $query->where[] = $groupsTable.'.id IS NOT NULL';
        } else {
            $query->where[] = $groupsTable.'.id IS NULL';
        }
    }

    public function onAcymProcessCondition_easysocialprofiles(&$query, $options, $num, &$conditionNotValid)
    {
        $this->processConditionFilter_easysocialprofiles($query, $options, $num);
        $affectedRows = $query->count();
        if (empty($affectedRows)) $conditionNotValid++;
    }

    private function processConditionFilter_easysocialprofiles(&$query, $options, $num)
    {
        $profilesTable = 'easysocialprofiles'.$num;
        $join = '#__social_profiles_maps AS '.$profilesTable.' ON '.$profilesTable.'.user_id = user.cms_id AND '.$profilesTable.'.state = 1';

        if (!empty($options['profile'])) {
            $join .= ' AND '.$profilesTable.'.profile_id = '.intval($options['profile']);
        }

        if (!empty($options['datemin'])) {
            $options['datemin'] = acym_replaceDate($options['datemin']);
            if (!is_numeric($options['datemin'])) $options['datemin'] = strtotime($options['datemin']);
            if (!empty($options['datemin'])) {
                $join .= ' AND '.$profilesTable.'.created > '.acym_escapeDB(acym_date($options['datemin'], 'Y-m-d H:i:s', false));
            }
        }

        if (!empty($options['datemax'])) {
            $options['datemax'] = acym_replaceDate($options['datemax']);
            if (!is_numeric($options['datemax'])) $options['datemax'] = strtotime($options['datemax']);
            if (!empty($options['datemax'])) {
                $join .= ' AND '.$profilesTable.'.created < '.acym_escapeDB(acym_date($options['datemax'], 'Y-m-d H:i:s', false));
            }
        }

        if (empty($options['in']) || $options['in'] === 'in') {
            $query->join[] = $join;
        } else {
            $query->leftjoin[] = $join;
            $query->where[] = $profilesTable.'.id IS NULL';
        }
    }

    public function onAcymProcessCondition_easysocialbadge(&$query, $options, $num, &$conditionNotValid)
    {
        $this->processConditionFilter_easysocialbadge($query, $options, $num);
        $affectedRows = $query->count();
        if (empty($affectedRows)) $conditionNotValid++;
    }

    private function processConditionFilter_easysocialbadge(&$query, $options, $num)
    {
        $badgeTable = 'easysocialbadge'.$num;
        $join = '#__social_badges_maps AS '.$badgeTable.' ON '.$badgeTable.'.user_id = user.cms_id';

        if (!empty($options['badge'])) {
            $join .= ' AND '.$badgeTable.'.badge_id = '.intval($options['badge']);
        }

        if (!empty($options['datemin'])) {
            $options['datemin'] = acym_replaceDate($options['datemin']);
            if (!is_numeric($options['datemin'])) $options['datemin'] = strtotime($options['datemin']);
            if (!empty($options['datemin'])) {
                $join .= ' AND '.$badgeTable.'.created > '.acym_escapeDB(acym_date($options['datemin'], 'Y-m-d H:i:s', false));
            }
        }

        if (!empty($options['datemax'])) {
            $options['datemax'] = acym_replaceDate($options['datemax']);
            if (!is_numeric($options['datemax'])) $options['datemax'] = strtotime($options['datemax']);
            if (!empty($options['datemax'])) {
                $join .= ' AND '.$badgeTable.'.created < '.acym_escapeDB(acym_date($options['datemax'], 'Y-m-d H:i:s', false));
            }
        }

        if (empty($options['in']) || $options['in'] === 'in') {
            $query->join[] = $join;
        } else {
            $query->leftjoin[] = $join;
            $query->where[] = $badgeTable.'.id IS NULL';
        }
    }

    public function onAcymProcessCondition_easysocialfield(&$query, $options, $num, &$conditionNotValid)
    {
        $this->processConditionFilter_easysocialfield($query, $options, $num);
        $affectedRows = $query->count();
        if (empty($affectedRows)) $conditionNotValid++;
    }

    private function processConditionFilter_easysocialfield(&$query, $options, $num)
    {
        $fieldTable = 'easysocialfield'.$num;
        $query->join[$fieldTable] = '#__social_fields_data AS '.$fieldTable.' ON user.cms_id = '.$fieldTable.'.uid';
        $query->where[] = $fieldTable.'.field_id = '.intval($options['field']);
        $query->where[] = $query->convertQuery($fieldTable, 'raw', $options['operator'], $options['value']);
    }

    public function onAcymProcessCondition_easysocialevent(&$query, $options, $num, &$conditionNotValid)
    {
        $this->processConditionFilter_easysocialevent($query, $options, $num);
        $affectedRows = $query->count();
        if (empty($affectedRows)) $conditionNotValid++;
    }

    private function processConditionFilter_easysocialevent(&$query, $options, $num)
    {
        $eventTable = 'easysocialcat'.$num;
        $attendeeTable = 'easysocialevent'.$num;
        $query->join[$attendeeTable] = '#__social_clusters_nodes AS '.$attendeeTable.' ON '.$attendeeTable.'.uid = user.cms_id AND '.$attendeeTable.'.type = "user"';
        $query->join[$eventTable] = '#__social_clusters AS '.$eventTable.' ON '.$eventTable.'.id = '.$attendeeTable.'.cluster_id';

        $query->where[] = $eventTable.'.cluster_type = "event"';
        $query->where[] = $eventTable.'.state = 1';

        if (!empty($options['status'])) {
            $query->join[$attendeeTable] .= ' AND '.$attendeeTable.'.state = '.intval($options['status']);
        }

        if (!empty($options['event'])) {
            $query->where[] = $attendeeTable.'.cluster_id = '.intval($options['event']);
        } elseif (!empty($options['category'])) {
            $query->where[] = $eventTable.'.category_id = '.intval($options['category']);
        }

        if (!empty($options['datemin'])) {
            $options['datemin'] = acym_replaceDate($options['datemin']);
            if (!is_numeric($options['datemin'])) $options['datemin'] = strtotime($options['datemin']);
            if (!empty($options['datemin'])) {
                $query->where[] = $attendeeTable.'.created > '.acym_escapeDB(acym_date($options['datemin'], 'Y-m-d H:i:s', false));
            }
        }

        if (!empty($options['datemax'])) {
            $options['datemax'] = acym_replaceDate($options['datemax']);
            if (!is_numeric($options['datemax'])) $options['datemax'] = strtotime($options['datemax']);
            if (!empty($options['datemax'])) {
                $query->where[] = $attendeeTable.'.created < '.acym_escapeDB(acym_date($options['datemax'], 'Y-m-d H:i:s', false));
            }
        }
    }

    public function onAcymDeclareSummary_conditions(&$automationCondition)
    {
        $this->summaryConditionFilters($automationCondition);
    }

    private function summaryConditionFilters(&$automationCondition)
    {
        acym_loadLanguageFile('com_easysocial', JPATH_SITE);

        if (!empty($automationCondition['easysocialgroups'])) {
            if (empty($automationCondition['easysocialgroups']['group'])) {
                $group = acym_translation('ACYM_ANY_GROUP');
            } else {
                $group = acym_loadResult('SELECT `title` FROM #__social_clusters WHERE `id` = '.intval($automationCondition['easysocialgroups']['group']));
            }

            $inOperator = acym_translation($automationCondition['easysocialgroups']['in'] === 'in' ? 'ACYM_IN' : 'ACYM_NOT_IN');
            $finalText = acym_translationSprintf('ACYM_FILTER_ACY_GROUP_SUMMARY', $inOperator, $group);

            $dates = [];
            if (!empty($automationCondition['easysocialgroups']['datemin'])) {
                $dates[] = acym_translation('ACYM_AFTER').' '.acym_replaceDate($automationCondition['easysocialgroups']['datemin'], true);
            }

            if (!empty($automationCondition['easysocialgroups']['datemax'])) {
                $dates[] = acym_translation('ACYM_BEFORE').' '.acym_replaceDate($automationCondition['easysocialgroups']['datemax'], true);
            }

            if (!empty($dates)) {
                $finalText .= ' '.implode(' '.acym_translation('ACYM_AND').' ', $dates);
            }

            $automationCondition = $finalText;
        }

        if (!empty($automationCondition['easysocialprofiles'])) {
            if (empty($automationCondition['easysocialprofiles']['profile'])) {
                $profile = acym_translation('ACYM_ANY_PROFILE');
            } else {
                $profile = acym_loadResult('SELECT `title` FROM #__social_profiles WHERE `id` = '.intval($automationCondition['easysocialprofiles']['profile']));
            }

            $inOperator = acym_translation($automationCondition['easysocialprofiles']['in'] === 'in' ? 'ACYM_IN' : 'ACYM_NOT_IN');
            $finalText = acym_translationSprintf('ACYM_FILTER_IN_PROFILE_SUMMARY', $inOperator, $profile);

            $dates = [];
            if (!empty($automationCondition['easysocialprofiles']['datemin'])) {
                $dates[] = acym_translation('ACYM_AFTER').' '.acym_replaceDate($automationCondition['easysocialprofiles']['datemin'], true);
            }

            if (!empty($automationCondition['easysocialprofiles']['datemax'])) {
                $dates[] = acym_translation('ACYM_BEFORE').' '.acym_replaceDate($automationCondition['easysocialprofiles']['datemax'], true);
            }

            if (!empty($dates)) {
                $finalText .= ' '.implode(' '.acym_translation('ACYM_AND').' ', $dates);
            }

            $automationCondition = $finalText;
        }

        if (!empty($automationCondition['easysocialbadge'])) {
            if (empty($automationCondition['easysocialbadge']['badge'])) {
                $badge = 'ACYM_ANY_BADGE';
            } else {
                $badge = acym_loadResult('SELECT `title` FROM #__social_badges WHERE `id` = '.intval($automationCondition['easysocialbadge']['badge']));
            }

            $inOperator = acym_translation($automationCondition['easysocialbadge']['in'] === 'in' ? 'ACYM_IN' : 'ACYM_NOT_IN');
            $finalText = acym_translationSprintf('ACYM_FILTER_IN_PROFILE_SUMMARY', $inOperator, acym_translation($badge));

            $dates = [];
            if (!empty($automationCondition['easysocialbadge']['datemin'])) {
                $dates[] = acym_translation('ACYM_AFTER').' '.acym_replaceDate($automationCondition['easysocialbadge']['datemin'], true);
            }

            if (!empty($automationCondition['easysocialbadge']['datemax'])) {
                $dates[] = acym_translation('ACYM_BEFORE').' '.acym_replaceDate($automationCondition['easysocialbadge']['datemax'], true);
            }

            if (!empty($dates)) {
                $finalText .= ' '.implode(' '.acym_translation('ACYM_AND').' ', $dates);
            }

            $automationCondition = $finalText;
        }

        if (!empty($automationCondition['easysocialfield'])) {
            $field = acym_loadResult('SELECT title FROM #__social_fields WHERE id = '.intval($automationCondition['easysocialfield']['field']));

            $automationCondition = acym_translationSprintf(
                'ACYM_FILTER_ACY_FIELD_SUMMARY',
                acym_translation(empty($field) ? 'ACYM_FIELD' : $field),
                $automationCondition['easysocialfield']['operator'],
                $automationCondition['easysocialfield']['value']
            );
        }

        if (!empty($automationCondition['easysocialevent'])) {
            if (empty($automationCondition['easysocialevent']['event'])) {
                if (empty($automationCondition['easysocialevent']['category'])) {
                    $category = acym_translation('ACYM_ANY_CATEGORY');
                } else {
                    $category = acym_loadResult('SELECT `title` FROM #__social_clusters_categories WHERE `id` = '.intval($automationCondition['easysocialevent']['category']));
                }
                $finalText = acym_translationSprintf('ACYM_EVENT_FILTER_CATEGORY_SUMMARY', $category);
            } else {
                $event = acym_loadResult('SELECT `title` FROM #__social_clusters WHERE `id` = '.intval($automationCondition['easysocialevent']['event']));
                $finalText = acym_translationSprintf('ACYM_EVENT_FILTER_SUMMARY', $event);
            }

            $dates = [];
            if (!empty($automationCondition['easysocialevent']['datemin'])) {
                $dates[] = acym_translation('ACYM_AFTER').' '.acym_replaceDate($automationCondition['easysocialevent']['datemin'], true);
            }

            if (!empty($automationCondition['easysocialevent']['datemax'])) {
                $dates[] = acym_translation('ACYM_BEFORE').' '.acym_replaceDate($automationCondition['easysocialevent']['datemax'], true);
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

    public function onAcymProcessFilterCount_easysocialgroups(&$query, $options, $num)
    {
        $this->onAcymProcessFilter_easysocialgroups($query, $options, $num);

        return acym_translationSprintf('ACYM_SELECTED_USERS', $query->count());
    }

    public function onAcymProcessFilter_easysocialgroups(&$query, $options, $num)
    {
        $this->processConditionFilter_easysocialgroups($query, $options, $num);
    }

    public function onAcymProcessFilterCount_easysocialprofiles(&$query, $options, $num)
    {
        $this->onAcymProcessFilter_easysocialprofiles($query, $options, $num);

        return acym_translationSprintf('ACYM_SELECTED_USERS', $query->count());
    }

    public function onAcymProcessFilter_easysocialprofiles(&$query, $options, $num)
    {
        $this->processConditionFilter_easysocialprofiles($query, $options, $num);
    }

    public function onAcymProcessFilterCount_easysocialbadge(&$query, $options, $num)
    {
        $this->onAcymProcessFilter_easysocialbadge($query, $options, $num);

        return acym_translationSprintf('ACYM_SELECTED_USERS', $query->count());
    }

    public function onAcymProcessFilter_easysocialbadge(&$query, $options, $num)
    {
        $this->processConditionFilter_easysocialbadge($query, $options, $num);
    }

    public function onAcymProcessFilterCount_easysocialfield(&$query, $options, $num)
    {
        $this->onAcymProcessFilter_easysocialfield($query, $options, $num);

        return acym_translationSprintf('ACYM_SELECTED_USERS', $query->count());
    }

    public function onAcymProcessFilter_easysocialfield(&$query, $options, $num)
    {
        $this->processConditionFilter_easysocialfield($query, $options, $num);
    }

    public function onAcymProcessFilterCount_easysocialevent(&$query, $options, $num)
    {
        $this->onAcymProcessFilter_easysocialevent($query, $options, $num);

        return acym_translationSprintf('ACYM_SELECTED_USERS', $query->count());
    }

    public function onAcymProcessFilter_easysocialevent(&$query, $options, $num)
    {
        $this->processConditionFilter_easysocialevent($query, $options, $num);
    }

    public function onAcymDeclareSummary_filters(&$automationFilter)
    {
        $this->summaryConditionFilters($automationFilter);
    }

    /**
     * Function called with ajax to search in groups
     */
    public function searchGroup()
    {
        $ids = $this->getIdsSelectAjax();

        if (!empty($ids)) {
            $elements = acym_loadObjectList('SELECT `title` AS name, `id` FROM #__social_clusters WHERE cluster_type = "group" AND `id` IN ("'.implode('","', $ids).'")');

            $value = [];
            if (!empty($elements)) {
                foreach ($elements as $element) {
                    $value[] = [
                        'text' => $element->name,
                        'value' => $element->id,
                    ];
                }
            }
            echo json_encode($value);
            exit;
        }

        $return = [];
        $search = acym_getVar('string', 'search', '');
        $elements = acym_loadObjectList(
            'SELECT `id`, `title` FROM `#__social_clusters` WHERE cluster_type = "group" AND `title` LIKE '.acym_escapeDB('%'.$search.'%').' ORDER BY `title`'
        );

        foreach ($elements as $oneElement) {
            $return[] = [$oneElement->id, $oneElement->title];
        }

        echo json_encode($return);
        exit;
    }

    /**
     * Function called with ajax to search in profiles
     */
    public function searchProfile()
    {
        $ids = $this->getIdsSelectAjax();

        if (!empty($ids)) {
            $elements = acym_loadObjectList('SELECT `title` AS name, `id` FROM #__social_profiles WHERE `id` IN ("'.implode('","', $ids).'")');

            $value = [];
            if (!empty($elements)) {
                foreach ($elements as $element) {
                    $value[] = [
                        'text' => $element->name,
                        'value' => $element->id,
                    ];
                }
            }
            echo json_encode($value);
            exit;
        }

        $return = [];
        $search = acym_getVar('string', 'search', '');
        $elements = acym_loadObjectList(
            'SELECT `id`, `title` FROM `#__social_profiles` WHERE `title` LIKE '.acym_escapeDB('%'.$search.'%').' ORDER BY `title`'
        );

        foreach ($elements as $oneElement) {
            $return[] = [$oneElement->id, $oneElement->title];
        }

        echo json_encode($return);
        exit;
    }

    public function searchFields()
    {
        acym_loadLanguageFile('com_easysocial', JPATH_SITE);
        acym_loadLanguageFile('com_easysocial', JPATH_ADMINISTRATOR);

        $id = acym_getVar('int', 'profile', 0);
        if (empty($id)) exit;

        $elements = acym_loadObjectList(
            'SELECT field.id, field.title 
			FROM #__social_fields AS field 
			JOIN #__social_fields_steps AS fieldStep ON field.step_id = fieldStep.id 
			JOIN #__social_workflows_maps AS workflowMap ON workflowMap.workflow_id = fieldStep.workflow_id 
			WHERE fieldStep.type = "profiles" 
				AND workflowMap.uid = '.intval($id).' 
				AND field.unique_key NOT LIKE "'.implode(
                '%" AND field.unique_key NOT LIKE "',
                ['JOOMLA_', 'HEADER', 'SEPARATOR', 'TERMS', 'COVER', 'AVATAR', 'HTML', 'TEXT-', 'FILE', 'CURRENCY']
            ).'%"'
        );

        $options = [];
        $options[0] = acym_translation('ACYM_SELECT_FIELD');
        foreach ($elements as $oneElement) {
            $options[$oneElement->id] = acym_translation($oneElement->title);
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

    /**
     * Function called with ajax to search in profiles
     */
    public function searchEvent()
    {
        $ids = $this->getIdsSelectAjax();

        if (!empty($ids)) {
            $elements = acym_loadObjectList(
                'SELECT clusters.`title` AS name, clusters.`id` 
                FROM #__social_clusters AS clusters 
                WHERE clusters.`id` IN ("'.implode('","', $ids).'") 
                    AND clusters.cluster_type = "event" 
                    AND clusters.state = 1 
                ORDER BY clusters.title'
            );

            $value = [];
            if (!empty($elements)) {
                foreach ($elements as $element) {
                    $value[] = [
                        'text' => acym_translation($element->name),
                        'value' => $element->id,
                    ];
                }
            }
            echo json_encode($value);
            exit;
        }

        $return = [];
        $search = acym_getVar('string', 'search', '');
        $elements = acym_loadObjectList(
            'SELECT clusters.`title`, clusters.`id` 
            FROM #__social_clusters AS clusters 
            JOIN #__social_events_meta AS meta ON clusters.id = meta.cluster_id
            WHERE clusters.cluster_type = "event" 
                AND clusters.state = 1 
                AND clusters.`title` LIKE '.acym_escapeDB('%'.$search.'%').' 
                AND meta.start > '.acym_escapeDB(date('Y-m-d H:i:s', time() - 5184000)).' 
            ORDER BY clusters.title'
        );

        foreach ($elements as $oneElement) {
            $return[] = [$oneElement->id, acym_translation($oneElement->title)];
        }

        echo json_encode($return);
        exit;
    }
}
