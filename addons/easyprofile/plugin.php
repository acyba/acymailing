<?php

use AcyMailing\Libraries\acymPlugin;
use AcyMailing\Helpers\TabHelper;
use AcyMailing\Types\OperatorType;

class plgAcymEasyprofile extends acymPlugin
{
    var $epfields = [];
    var $bannedFields = [];

    public function __construct()
    {
        parent::__construct();
        $this->cms = 'Joomla';
        $this->pluginDescription->name = 'Easy Profile';
        $this->pluginDescription->icon = ACYM_DYNAMICS_URL.basename(__DIR__).'/icon.png';

        $this->installed = acym_isExtensionActive('com_jsn');
        if ($this->installed) {
            $this->epfields = acym_loadObjectList('SELECT `title`, `alias`, `type`, `params` FROM #__jsn_fields');
            $jsnColumns = acym_getColumns('jsn_users', false);
            $jUserColumns = acym_getColumns('users', false);
            foreach ($this->epfields as $key => $field) {
                if (!empty($field->params) && is_string($field->params)) {
                    $this->epfields[$key]->params = json_decode($field->params);
                }

                if (in_array($field->alias, $jsnColumns)) {
                    $this->epfields[$key]->table = '#__jsn_users';
                } elseif (in_array($field->alias, $jUserColumns)) {
                    $this->epfields[$key]->table = '#__users';
                } else {
                    unset($this->epfields[$key]);
                }
            }
            $this->bannedFields = ['password', 'avatar'];

            $this->initDisplayOptionsCustomView();
            $this->initReplaceOptionsCustomView();
            $this->initElementOptionsCustomView();

            $this->settings = [
                'custom_view' => [
                    'type' => 'custom_view',
                    'tags' => array_merge($this->displayOptions, $this->elementOptions, $this->replaceOptions),
                ],
                'front' => [
                    'type' => 'select',
                    'label' => 'ACYM_FRONT_ACCESS',
                    'value' => 'all',
                    'data' => [
                        'all' => 'ACYM_ALL_ELEMENTS',
                        'hide' => 'ACYM_DONT_SHOW',
                    ],
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
        acym_loadLanguageFile('com_jsn', JPATH_ADMINISTRATOR);

        $text = '<div class="grid-x acym__popup__listing">';

        foreach ($this->epfields as $field) {
            if (in_array($field->alias, $this->bannedFields)) continue;
            $text .= '<div 
                        class="cell acym__row__no-listing acym__listing__row__popup" 
                        onclick="setTag(\'{'.$this->name.'field:'.$field->alias.'}\', jQuery(this));">'.acym_translation($field->title).'</div>';
        }

        $text .= '</div>';

        echo $text;
    }

    public function replaceUserInformation(&$email, &$user, $send = true)
    {
        $extractedTags = $this->pluginHelper->extractTags($email, $this->name.'field');
        if (empty($extractedTags)) return;

        // Get the current user
        $fieldsToSelect = [];
        foreach ($this->epfields as $field) {
            if ($field->type == '' || in_array($field->alias, $this->bannedFields)) continue;
            if ($field->table == '#__jsn_users') {
                $fieldsToSelect[] = 'jsnuser.`'.$field->alias.'`';
            } else {
                $fieldsToSelect[] = 'juser.'.$field->alias;
            }
        }

        $query = 'SELECT '.implode(', ', $fieldsToSelect).' 
                FROM #__users AS juser
                JOIN #__jsn_users AS jsnuser ON juser.id = jsnuser.id 
                JOIN #__acym_user AS acyuser ON juser.id = acyuser.cms_id 
                WHERE acyuser.id = '.intval($user->id);
        $userFields = acym_loadObject($query);

        $tags = [];
        foreach ($extractedTags as $i => $oneTag) {
            if (isset($tags[$i])) continue;
            $value = empty($userFields->{$oneTag->id}) ? '' : $userFields->{$oneTag->id};
            $field = null;
            foreach ($this->epfields as $oneField) {
                if ($oneField->alias === $oneTag->id) {
                    $field = $oneField;
                    break;
                }
            }
            $tags[$i] = empty($user->id) || empty($field) ? $oneTag->default : $this->formatFieldDisplay($value, $field);
        }

        $this->pluginHelper->replaceTags($email, $tags);
    }

    public function getStandardStructure(&$customView)
    {
        $tag = new stdClass();
        $tag->id = 0;

        $format = new stdClass();
        $format->tag = $tag;
        $format->title = '{firstname} {username}';
        $format->afterTitle = '';
        $format->afterArticle = 'Register date: {registerdate}';
        $format->imagePath = '{avatar}';
        $format->description = '';
        $format->link = '{link}';
        $format->customFields = [];
        $customView = '<div class="acymailing_content">'.$this->pluginHelper->getStandardDisplay($format).'</div>';
    }

    public function initReplaceOptionsCustomView()
    {
        $this->replaceOptions = [
            'link' => ['ACYM_LINK'],
            'picthtml' => ['ACYM_IMAGE'],
        ];
    }

    public function initElementOptionsCustomView()
    {
        $query = 'SELECT ';
        $fieldsToSelect = [];
        foreach ($this->epfields as $field) {
            if ($field->type == '' || in_array($field->alias, $this->bannedFields)) continue;
            if ($field->table == '#__jsn_users') {
                $fieldsToSelect[] = 'jsnuser.`'.$field->alias.'`';
            } else {
                $fieldsToSelect[] = 'juser.'.$field->alias;
            }
        }
        $fieldsToSelect[] = 'jsnuser.avatar ';

        $query .= implode(', ', $fieldsToSelect).' FROM #__users AS juser
                JOIN #__jsn_users AS jsnuser ON juser.id=jsnuser.id';
        $element = acym_loadObject($query);
        if (empty($element)) return;
        foreach ($element as $key => $value) {
            $this->elementOptions[$key] = [$key];
        }
    }

    public function initDisplayOptionsCustomView()
    {
        acym_loadLanguageFile('com_jsn', JPATH_ADMINISTRATOR);

        foreach ($this->epfields as $field) {
            if ($field->type == '' || in_array($field->alias, $this->bannedFields)) continue;
            $selected = (rand(0, 1) == 1);
            $this->displayOptions[$field->alias] = [acym_translation($field->title), $selected];
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

        $this->categories = acym_loadObjectList(
            'SELECT id, parent_id, title
            FROM #__usergroups'
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
        $tabHelper->startTab(acym_translation('ACYM_BY_GROUP'), !empty($this->defaultValues->defaultPluginTab) && $identifier === $this->defaultValues->defaultPluginTab);

        $catOptions = [
            [
                'title' => 'ACYM_ORDER_BY',
                'type' => 'select',
                'name' => 'order',
                'options' => [
                    'id' => 'ACYM_ID',
                    'rand' => 'ACYM_RANDOM',
                ],
            ],
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
        $this->querySelect = 'SELECT user.id, user.username, user.email ';
        $this->query = 'FROM #__users AS user ';
        $this->searchFields = ['user.username', 'user.email'];
        $this->pageInfo->order = 'user.id';
        $this->elementIdTable = 'user';
        $this->elementIdColumn = 'id';


        parent::prepareListing();

        $listingOptions = [
            'header' => [
                'username' => [
                    'label' => 'ACYM_SMTP_USERNAME',
                    'size' => '6',
                ],
                'email' => [
                    'label' => 'ACYM_EMAIL',
                    'size' => '6',
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

    public function replaceIndividualContent($tag)
    {
        $query = 'SELECT ';
        $fieldsToSelect = [];
        foreach ($this->epfields as $field) {
            if ($field->type == '' || in_array($field->alias, $this->bannedFields)) continue;
            if ($field->table == '#__jsn_users') {
                $fieldsToSelect[] = 'jsnuser.`'.$field->alias.'`';
            } else {
                $fieldsToSelect[] = 'juser.'.$field->alias;
            }
        }
        $fieldsToSelect[] = 'jsnuser.avatar ';

        $query .= implode(', ', $fieldsToSelect).' FROM #__users AS juser
                JOIN #__jsn_users AS jsnuser ON juser.id = jsnuser.id
                WHERE jsnuser.id = '.intval($tag->id);

        $element = $this->initIndividualContent($tag, $query);

        if (empty($element)) return '';

        $varFields = $this->getCustomLayoutVars($element);

        $link = 'index.php?option=com_jsn&id='.$tag->id;
        $link = $this->finalizeLink($link);
        $varFields['{link}'] = $link;

        $afterTitle = '';
        $afterArticle = '';

        $contentText = '';
        $customFields = [];
        $titleAliases = ['lastname', 'firstname', 'secondname'];

        $titleElements = [];
        foreach ($titleAliases as $field) {
            $varFields['{'.$field.'}'] = $element->$field;
            if (in_array($field, $tag->display)) $titleElements[] = $element->$field;
        }
        $titleElements = implode(' ', $titleElements);
        $title = $titleElements;

        $customFieldsNoTitle = [];
        foreach ($this->epfields as $field) {
            if (in_array($field->alias, $titleAliases) || in_array($field->alias, $this->bannedFields)) continue;

            $varFields['{'.$field->alias.'}'] = $element->{$field->alias};
            if (in_array($field->alias, $tag->display)) {
                $varFields['{'.$field->alias.'}'] = $this->formatFieldDisplay($element->{$field->alias}, $field);
                if (empty($field->params->hidetitle)) {
                    $fieldTitle = $field->title;
                    if ($field->table === '#__users') {
                        $fieldTitle = ucfirst(strtolower($fieldTitle));
                    }
                    $customFields[] = [$varFields['{'.$field->alias.'}'], $fieldTitle];
                } else {
                    $customFieldsNoTitle[] = [$varFields['{'.$field->alias.'}']];
                }
            }
        }
        $customFields = array_merge($customFields, $customFieldsNoTitle);

        $imagePath = empty($element->avatar) ? '' : acym_rootURI().$element->avatar;
        $varFields['{picthtml}'] = '<img alt="" src="'.acym_escape($imagePath).'" />';
        if (empty($tag->pict)) $imagePath = '';

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

    private function formatFieldDisplay($value, $field)
    {
        if ($field->type === 'image') {
            return '<img src="'.$value.'"/>';
        }

        if (in_array($field->type, ['date', 'registerdate', 'lastvisitdate'])) {
            $format = $field->params->date_format;
            if (empty($format)) $format = acym_translation('DATE_FORMAT_LC');

            return acym_getDate(
                acym_getTime($value),
                $format
            );
        }

        if ($field->type === 'link') {
            return '<a href="'.$value.'" target="_blank">'.$value.'</a>';
        }

        if (in_array($field->type, ['email', 'usermail'])) {
            return '<a href="mailto:'.$value.'">'.$value.'</a>';
        }

        if ($field->type === 'radiolist') {
            $allOptions = explode("\n", $field->params->radio_options);
            foreach ($allOptions as $oneOpt) {
                $values = explode('|', $oneOpt);
                if ($values[0] != $value) continue;

                return $values[1];
            }

            return '';
        }

        if ($field->type === 'selectlist') {
            $allOptions = explode("\n", $field->params->select_options);
            foreach ($allOptions as $oneOpt) {
                $values = explode('|', $oneOpt);
                $uservalue = json_decode($value);
                if (($uservalue !== null && $values[0] != $uservalue) || ($uservalue === null && $values[0] != $value)) continue;

                return $values[1];
            }

            return '';
        }

        if ($field->type === 'checkboxlist') {
            $options = [];
            $optionsBrutes = explode("\n", $field->params->checkbox_options);
            foreach ($optionsBrutes as $oneOpt) {
                $values = explode('|', $oneOpt);
                $options[$values[0]] = $values[1];
            }
            $selected = json_decode($value);
            foreach ($selected as $i => $oneOpt) {
                $selected[$i] = $options[$oneOpt];
            }

            return implode(', ', $selected);
        }

        return $value;
    }

    public function generateByCategory(&$email)
    {
        $tags = $this->pluginHelper->extractTags($email, 'auto'.$this->name);
        $this->tags = [];

        if (empty($tags)) return $this->generateCampaignResult;

        foreach ($tags as $oneTag => $parameter) {
            if (isset($this->tags[$oneTag])) continue;

            $query = 'SELECT DISTINCT jsnuser.id FROM #__jsn_users AS jsnuser ';

            $where = [];

            $selectedArea = $this->getSelectedArea($parameter);
            if (!empty($selectedArea)) {
                $query .= 'JOIN #__user_usergroup_map AS map ON map.user_id = jsnuser.id ';
                $where[] = 'map.group_id IN ('.implode(',', $selectedArea).')';
            }

            if (!empty($where)) $query .= ' WHERE ('.implode(') AND (', $where).')';

            $this->tags[$oneTag] = $this->finalizeCategoryFormat($query, $parameter, 'jsnuser');
        }

        return $this->generateCampaignResult;
    }

    public function onAcymDeclareConditions(&$conditions)
    {
        $fields = [];

        foreach ($this->epfields as $field) {
            if ($field->type == '' || in_array($field->alias, $this->bannedFields)) continue;
            $text = ucfirst(strtolower($field->title));
            $fields[] = acym_selectOption($field->alias, $text);
        }
        $conditions['user']['epfield'] = new stdClass();
        $conditions['user']['epfield']->name = 'Easy Profile - '.acym_translation('ACYM_FIELDS');

        $operator = new OperatorType();

        $conditions['user']['epfield']->option = '<div class="intext_select_automation cell">';
        $conditions['user']['epfield']->option .= acym_select($fields, 'acym_condition[conditions][__numor__][__numand__][epfield][field]', null, 'class="acym__select"');
        $conditions['user']['epfield']->option .= '</div>';
        $conditions['user']['epfield']->option .= '<div class="intext_select_automation cell">';
        $conditions['user']['epfield']->option .= $operator->display('acym_condition[conditions][__numor__][__numand__][epfield][operator]');
        $conditions['user']['epfield']->option .= '</div>';
        $conditions['user']['epfield']->option .= '<input class="intext_input_automation cell" type="text" name="acym_condition[conditions][__numor__][__numand__][epfield][value]">';
    }

    public function onAcymDeclareFilters(&$filters)
    {
        return $this->filtersFromConditions($filters);
    }

    public function onAcymProcessFilterCount_epfield(&$query, $options, $num)
    {
        $this->onAcymProcessFilter_epfield($query, $options, $num);

        return acym_translation_sprintf('ACYM_SELECTED_USERS', $query->count());
    }

    public function onAcymProcessFilter_epfield(&$query, $options, $num)
    {
        $this->processConditionFilter_epfield($query, $options, $num);
    }

    public function onAcymProcessCondition_epfield(&$query, $options, $num, &$conditionNotValid)
    {
        $this->processConditionFilter_epfield($query, $options, $num);
        $affectedRows = $query->count();
        if (empty($affectedRows)) $conditionNotValid++;
    }

    public function processConditionFilter_epfield(&$query, $options, $num)
    {
        $fieldName = $options['field'];
        if (empty($fieldName)) return;

        $tableName = '';
        foreach ($this->epfields as $field) {
            if ($field->alias == $fieldName) {
                $tableName = $field->table;
                break;
            }
        }

        $query->join['epfield'.$num] = $tableName.' AS epfield'.$num.' ON epfield'.$num.'.id = user.cms_id';

        $query->where[] = $query->convertQuery('epfield'.$num, $options['field'], $options['operator'], $options['value']);
    }

    public function onAcymDeclareSummary_conditions(&$automationCondition)
    {
        $this->summaryConditionFilters($automationCondition);
    }

    public function onAcymDeclareSummary_filters(&$automationFilter)
    {
        $this->summaryConditionFilters($automationFilter);
    }

    public function summaryConditionFilters(&$automationCondition)
    {
        if (!empty($automationCondition['epfield'])) {
            $automationCondition = acym_translation_sprintf(
                'ACYM_CONDITION_X_FIELD_SUMMARY',
                $this->pluginDescription->name,
                $automationCondition['epfield']['field'],
                $automationCondition['epfield']['operator'],
                $automationCondition['epfield']['value']
            );
        }
    }
}
