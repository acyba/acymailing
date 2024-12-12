<?php

use AcyMailing\Helpers\TabHelper;

trait EasysocialInsertion
{
    private $esConfig;

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
        $receiver = Foundry::user(empty($user->cms_id) ? 0 : $user->cms_id);
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
                'tooltip' => 'ACYM_AUTO_LOGIN_DESCRIPTION_WARNING',
                'type' => 'boolean',
                'name' => 'autologin',
                'default' => false,
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
        $link = $this->finalizeLink($link, $tag);

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
        $format->link = empty($tag->clickable) && empty($tag->clickableimg) ? '' : $link;
        $format->customFields = $customFields;
        $result = '<div class="acymailing_content">'.$this->pluginHelper->getStandardDisplay($format).'</div>';

        return $this->finalizeElementFormat($result, $tag, $varFields);
    }
}
