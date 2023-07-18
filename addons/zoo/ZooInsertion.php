<?php

use AcyMailing\Helpers\TabHelper;

trait ZooInsertion
{
    private $contentTypes = [];
    private $countryNames = [];

    public function getStandardStructure(&$customView)
    {
        $tag = new stdClass();
        $tag->id = 0;

        $format = new stdClass();
        $format->tag = $tag;
        $format->title = '{title}';
        $format->afterTitle = '';
        $format->afterArticle = '';
        $format->imagePath = '{teaser_image}';
        $format->description = '{teaser_desc}';
        $format->link = '{link}';
        $format->customFields = [];
        $customView = '<div class="acymailing_content">'.$this->pluginHelper->getStandardDisplay($format).'</div>';
    }

    public function initReplaceOptionsCustomView()
    {
        $this->replaceOptions = [
            'link' => ['ACYM_LINK'],
            'teaser_image' => ['Teaser Image'],
            'teaser_desc' => ['ACYM_SHORT_DESCRIPTION'],
            'picthtml' => ['ACYM_IMAGE'],
            'readmore' => ['ACYM_READ_MORE'],
        ];
    }

    public function initElementOptionsCustomView()
    {
        $query = 'SELECT element.*, category.name AS cattitle ';
        $query .= 'FROM `#__zoo_item` AS element ';
        $query .= 'JOIN `#__zoo_category_item` AS map ON element.`id` = map.`item_id`';
        $query .= 'JOIN `#__zoo_category` AS category ON map.`category_id` = category.`id`';
        $element = acym_loadObject($query);
        if (empty($element)) return;
        foreach ($element as $key => $value) {
            $this->elementOptions[$key] = [$key];
        }
    }

    protected function getFilteringZone($categoryFilter = true): string
    {
        $result = '<div class="grid-x margin-y" id="plugin_listing_filters">
                    <div class="cell">
                        <input type="text" name="plugin_search" placeholder="'.acym_escape(acym_translation('ACYM_SEARCH')).'"/>
                    </div>
                    <div class="cell grid-x">
                        <div class="cell medium-shrink">';

        $filterType = acym_getVar('string', 'plugin_zootype', '');
        $allTypes = acym_loadResultArray('SELECT DISTINCT type FROM #__zoo_item');
        sort($allTypes);
        $zooTypes = [0 => acym_translation('ACYM_ANY')];
        foreach ($allTypes as $oneType) {
            $zooTypes[$oneType] = ucfirst($oneType);
        }

        $result .= acym_select($zooTypes, 'plugin_zootype', $filterType, ['class' => 'plugin_type_select']);
        $result .= '</div><div class="cell hide-for-small-only medium-auto"></div><div class="cell medium-shrink">';
        $result .= $this->getCategoryFilter();

        $result .= '</div>
                    </div>
                </div>';

        return $result;
    }

    public function insertionOptions($defaultValues = null)
    {
        $this->defaultValues = $defaultValues;

        acym_loadLanguageFile('com_zoo', JPATH_ADMINISTRATOR);
        $this->categories = acym_loadObjectList(
            'SELECT `id`, `parent` AS `parent_id`, `name` AS `title` 
            FROM `#__zoo_category` 
            WHERE published = 1',
            'id'
        );

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
                'tooltip' => 'ACYM_AUTO_LOGIN_DESCRIPTION',
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

        $allTypes = acym_loadResultArray('SELECT DISTINCT type FROM #__zoo_item');
        sort($allTypes);
        $zooTypes = ['' => acym_translation('ACYM_ANY')];
        foreach ($allTypes as $oneType) {
            $zooTypes[$oneType] = ucfirst($oneType);
        }

        $catOptions = [
            [
                'title' => 'ACYM_TYPE',
                'type' => 'select',
                'name' => 'type',
                'options' => $zooTypes,
                'default' => '',
            ],
            [
                'title' => 'ACYM_ORDER_BY',
                'type' => 'select',
                'name' => 'order',
                'options' => [
                    'id' => 'ACYM_ID',
                    'publish_up' => 'ACYM_PUBLISHING_DATE',
                    'name' => 'ACYM_TITLE',
                    'rand' => 'ACYM_RANDOM',
                ],
                'default' => 'id',
                'defaultdir' => 'desc',
            ],
        ];

        $this->autoContentOptions($catOptions);
        $this->autoCampaignOptions($catOptions, true);

        $displayOptions = array_merge($displayOptions, $catOptions);

        echo $this->displaySelectionZone($this->getCategoryListing());
        echo $this->pluginHelper->displayOptions($displayOptions, $identifier, 'grouped', $this->defaultValues);

        $tabHelper->endTab();
        $tabHelper->display('plugin');
    }

    public function prepareListing(): string
    {
        $this->querySelect = 'SELECT DISTINCT element.id, element.name, element.publish_up ';
        $this->query = 'FROM #__zoo_item AS element ';
        $this->filters = [];
        $this->filters[] = 'element.state = 1';
        $this->searchFields = ['element.id', 'element.name'];
        $this->pageInfo->order = 'element.id';
        $this->elementIdTable = 'element';
        $this->elementIdColumn = 'id';

        if (!acym_isAdmin() && $this->getParam('front', 'all') === 'author') {
            $this->filters[] = 'element.created_by = '.intval(acym_currentUserId());
        }

        parent::prepareListing();

        // If we filtered the listing for a specific category, we display only the elements of this category
        if (!empty($this->pageInfo->filter_cat)) {
            $this->query .= 'JOIN #__zoo_category_item AS map ON element.id = map.item_id ';
            $this->filters[] = 'map.category_id = '.intval($this->pageInfo->filter_cat);
        }

        $filterType = acym_getVar('string', 'plugin_zootype', '');
        if (!empty($filterType)) {
            $this->filters[] = 'element.type = '.acym_escapeDB($filterType);
        }

        $listingOptions = [
            'header' => [
                'name' => [
                    'label' => 'ACYM_TITLE',
                    'size' => '7',
                ],
                'publish_up' => [
                    'label' => 'ACYM_PUBLISHING_DATE',
                    'size' => '4',
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
        acym_loadLanguageFile('com_zoo', JPATH_ADMINISTRATOR);
        acym_loadLanguageFile('com_zoo', JPATH_ROOT);
        $this->replaceMultiple($email);
        $this->replaceOne($email);
    }

    public function generateByCategory(&$email): stdClass
    {
        $tags = $this->pluginHelper->extractTags($email, 'auto'.$this->name);

        $this->tags = [];
        $time = time();

        if (empty($tags)) {
            return $this->generateCampaignResult;
        }

        foreach ($tags as $oneTag => $parameter) {
            if (isset($this->tags[$oneTag])) continue;

            $query = 'SELECT DISTINCT element.`id` 
                    FROM #__zoo_item AS element 
                    LEFT JOIN #__zoo_category_item AS map ON element.id = map.item_id ';

            $where = [];

            $selectedArea = $this->getSelectedArea($parameter);
            if (!empty($selectedArea)) {
                $where[] = 'map.category_id IN ('.implode(',', $selectedArea).')';
            }

            if (!empty($parameter->type)) {
                $where[] = 'element.type = '.acym_escapeDB($parameter->type);
            }

            $where[] = 'element.state = 1';
            $where[] = 'element.`publish_up` < '.acym_escapeDB(date('Y-m-d H:i:s', $time - date('Z')));
            $where[] = 'element.`publish_down` > '.acym_escapeDB(date('Y-m-d H:i:s', $time - date('Z'))).' OR element.`publish_down` = 0 OR element.`publish_down` IS NULL';
            if (!empty($parameter->min_publish)) {
                $parameter->min_publish = acym_date(acym_replaceDate($parameter->min_publish), 'Y-m-d H:i:s', false);
                $where[] = 'element.`publish_up` >= '.acym_escapeDB($parameter->min_publish);
            }

            if (!empty($parameter->datefilter)) {
                $lastGenerated = $this->getLastGenerated($email->id);
                if (!empty($lastGenerated)) {
                    $condition = 'element.publish_up > '.acym_escapeDB(acym_date($lastGenerated, 'Y-m-d H:i:s', false));
                    if ($parameter->datefilter === 'onlymodified') {
                        $condition .= ' OR element.modified > '.acym_escapeDB(acym_date($lastGenerated, 'Y-m-d H:i:s', false));
                    }
                    $where[] = $condition;
                }
            }

            $query .= ' WHERE ('.implode(') AND (', $where).')';

            $this->tags[$oneTag] = $this->finalizeCategoryFormat($query, $parameter, 'element');
        }

        return $this->generateCampaignResult;
    }

    public function replaceIndividualContent($tag): string
    {
        $query = 'SELECT `element`.*, `app`.`application_group` 
                    FROM #__zoo_item AS `element` 
                    JOIN #__zoo_application AS `app` 
                        ON `element`.`application_id` = `app`.`id`
                    WHERE `element`.`state` = 1
                        AND `element`.`id` = '.intval($tag->id);

        $element = $this->initIndividualContent($tag, $query);
        if (empty($element)) {
            return '';
        }

        if (empty($this->contentTypes[$element->type])) {
            if (!$this->initType($element->application_group, $element->type)) {
                return '';
            }
        }

        $varFields = $this->getCustomLayoutVars($element);
        $element->elements = json_decode($element->elements, true);

        $link = 'index.php?option=com_zoo&task=item&item_id='.$tag->id;
        $link = $this->finalizeLink($link, $tag);
        $varFields['{link}'] = $link;

        $title = '';
        $afterTitle = '';
        $afterArticle = '';
        $imagePath = '';
        $contentText = '';
        $customFields = [];

        $varFields['{title}'] = $element->name;
        if (in_array('title', $tag->display)) {
            $title = $varFields['{title}'];
        }

        $varFields['{picthtml}'] = '';
        $teaserImagePath = '';
        $teaserImageKey = $this->getKeyOfProperty($element->type, 'Teaser Image');
        if (!empty($teaserImageKey) && !empty($element->elements[$teaserImageKey]['file'])) {
            $teaserImagePath = acym_rootURI().$element->elements[$teaserImageKey]['file'];
        }

        $fullImagePath = '';
        $imageKey = $this->getKeyOfProperty($element->type, 'Image');
        if (!empty($imageKey) && !empty($element->elements[$imageKey]['file'])) {
            $fullImagePath = acym_rootURI().$element->elements[$imageKey]['file'];
        }

        if (in_array('teaser_image', $tag->display)) {
            if (!empty($teaserImagePath)) {
                $imagePath = $teaserImagePath;
            } elseif (!empty($fullImagePath)) {
                $imagePath = $fullImagePath;
            }
        } elseif (in_array('image', $tag->display)) {
            if (!empty($fullImagePath)) {
                $imagePath = $fullImagePath;
            } elseif (!empty($teaserImagePath)) {
                $imagePath = $teaserImagePath;
            }
        }

        if (!empty($imagePath)) {
            $varFields['{picthtml}'] = '<img alt="" class="content_main_image" src="'.acym_escape($imagePath).'" />';
        }


        $teaserDescription = '';
        $teaserDescriptionKey = $this->getKeyOfProperty($element->type, 'Teaser Description');
        if (!empty($teaserDescriptionKey) && !empty($element->elements[$teaserDescriptionKey][0]['value'])) {
            $teaserDescription = $element->elements[$teaserDescriptionKey][0]['value'];
        }

        $description = '';
        $descriptionKey = $this->getKeyOfProperty($element->type, 'Description');
        if (!empty($descriptionKey) && !empty($element->elements[$descriptionKey][0]['value'])) {
            $description = $element->elements[$descriptionKey][0]['value'];
        }

        $varFields['{teaser_desc}'] = $teaserDescription;
        $varFields['{desc}'] = $description;

        if (in_array('teaser_desc', $tag->display)) {
            if (!empty($varFields['{teaser_desc}'])) {
                $contentText .= $varFields['{teaser_desc}'];
            } elseif (!in_array('desc', $tag->display)) {
                $contentText .= $varFields['{desc}'];
            }
        }

        if (in_array('desc', $tag->display)) {
            if (!empty($varFields['{desc}'])) {
                $contentText .= $varFields['{desc}'];
            } elseif (!in_array('teaser_desc', $tag->display)) {
                $contentText .= $varFields['{teaser_desc}'];
            }
        }

        if (in_array('extra', $tag->display)) {
            $handledKeys = [$teaserImageKey, $imageKey, $teaserDescriptionKey, $descriptionKey];
            foreach ($this->contentTypes[$element->type] as $key => $field) {
                if (empty($element->elements[$key]) || in_array($key, $handledKeys)) {
                    continue;
                }

                $value = $this->formatFieldValue($field, $element->elements[$key]);

                if (empty($value)) {
                    continue;
                }

                $customFields[] = [$value, acym_translation($field['name'])];
            }
        }

        $varFields['{readmore}'] = '<a class="acymailing_readmore_link" style="text-decoration:none;" target="_blank" href="'.$link.'">';
        $varFields['{readmore}'] .= '<span class="acymailing_readmore">'.acym_escape(acym_translation('ACYM_READ_MORE')).'</span>';
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
        $format->link = empty($tag->clickable) && empty($tag->clickableimg) ? '' : $link;
        $format->customFields = $customFields;
        $result = '<div class="acymailing_content acymailing_'.$this->name.'">'.$this->pluginHelper->getStandardDisplay($format).'</div>';

        return $this->finalizeElementFormat($result, $tag, $varFields);
    }

    private function initType(string $group, string $type): bool
    {
        $configFilePath = ACYM_ROOT.'media'.DS.'zoo'.DS.'applications'.DS.$group.DS.'types'.DS.$type.'.config';
        if (!file_exists($configFilePath)) {
            return false;
        }

        $configurationFile = acym_fileGetContent($configFilePath);
        if (empty($configurationFile)) {
            return false;
        }

        $configurationFile = @json_decode($configurationFile, true);
        if (empty($configurationFile['elements'])) {
            return false;
        }

        $this->contentTypes[$type] = $configurationFile['elements'];

        return true;
    }

    private function getKeyOfProperty(string $type, string $propertyName)
    {
        foreach ($this->contentTypes[$type] as $key => $property) {
            if ($property['name'] === $propertyName) {
                return $key;
            }
        }

        return false;
    }

    private function formatFieldValue(array $field, $fieldValues): string
    {
        if (in_array($field['type'], ['text', 'textarea'])) {
            if (empty($fieldValues[0]['value'])) {
                return '';
            }

            $texts = [];
            foreach ($fieldValues as $oneValue) {
                $texts[] = trim($oneValue['value']);
            }

            return implode('<br />', $texts);
        } elseif ($field['type'] === 'date') {
            if (empty($fieldValues[0]['value'])) {
                return '';
            }

            $dates = [];
            foreach ($fieldValues as $oneValue) {
                $date = trim($oneValue['value']);
                $format = strpos($date, '00:00:00') === false ? 'ACYM_DATE_FORMAT_LC2' : 'ACYM_DATE_FORMAT_LC1';
                $dates[] = acym_date(strtotime($date), $format);
            }

            return implode(', ', $dates);
        } elseif ($field['type'] === 'image') {
            if (empty($fieldValues['file'])) {
                return '';
            }

            return '<img alt="" src="'.acym_escape(acym_rootURI().$fieldValues['file']).'" />';
        } elseif ($field['type'] === 'select') {
            $selectedValues = [];
            foreach ($fieldValues['option'] as $oneSelectedOptionValue) {
                if (!empty($oneSelectedOptionValue)) {
                    foreach ($field['option'] as $oneOption) {
                        if ($oneOption['value'] === $oneSelectedOptionValue) {
                            $selectedValues[] = acym_translation($oneOption['name']);
                            break;
                        }
                    }
                }
            }

            return empty($selectedValues) ? '' : implode(', ', $selectedValues);
        } elseif ($field['type'] === 'rating') {
            if (empty($fieldValues['votes'])) {
                return '';
            }

            return acym_translationSprintf('%S RATING FROM %S VOTES', $fieldValues['value'].'/'.$field['stars'], $fieldValues['votes']);
        } elseif ($field['type'] === 'country') {
            $countries = [];
            $this->initCountryNames();
            foreach ($fieldValues['country'] as $oneSelectedCountry) {
                if (empty($this->countryNames[$oneSelectedCountry])) {
                    continue;
                }
                $countries[] = acym_translation($this->countryNames[$oneSelectedCountry]);
            }

            return implode(', ', $countries);
        } elseif ($field['type'] === 'link') {
            $links = [];
            foreach ($fieldValues as $oneLink) {
                if (empty($oneLink['value'])) {
                    continue;
                }
                $text = $oneLink['value'];
                if (!empty($oneLink['text'])) {
                    $text = $oneLink['text'];
                }
                $links[] = '<a target="_blank" href="'.$oneLink['value'].'">'.$text.'</a>';
            }

            return implode(', ', $links);
        }

        return '';
    }

    private function initCountryNames(): void
    {
        if (!empty($this->countryNames)) {
            return;
        }

        $countryClass = acym_fileGetContent(rtrim(JPATH_ADMINISTRATOR, DS).DS.'components'.DS.'com_zoo'.DS.'framework'.DS.'helpers'.DS.'country.php');
        preg_match('#\$_iso_to_name =[^\n]*\n([^;]+);#is', $countryClass, $matches);

        $countries = explode("\n", $matches[1]);
        foreach ($countries as $oneCountry) {
            $oneCountry = trim($oneCountry, ', 	');
            $countryDefinition = explode('=>', $oneCountry);
            $countryCode = trim($countryDefinition[0], ' "\'');
            $countryName = trim($countryDefinition[1], ' "\')]');

            if (strpos($countryName, ',') !== false) {
                $countryName = implode(' ', array_reverse(explode(', ', $countryName)));
            }

            $this->countryNames[$countryCode] = $countryName;
        }
    }
}
