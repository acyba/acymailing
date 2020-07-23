<?php

class plgAcymCbuilder extends acymPlugin
{
    var $sendervalues = [];

    public function __construct()
    {
        parent::__construct();
        $this->cms = 'Joomla';
        $this->installed = acym_isExtensionActive('com_comprofiler');

        $this->pluginDescription->name = 'Community Builder';
    }

    public function dynamicText()
    {
        return $this->pluginDescription;
    }

    public function textPopup()
    {
        ?>

		<script language="javascript" type="text/javascript">
            function applyCB(tagname, element) {
                var string = '{cbtag:' + tagname + '|info:' + jQuery('input[name="typeinfo"]:checked').val() + '}';
                setTag(string, jQuery(element));
            }
		</script>

        <?php

        $text = '<div class="grid-x acym__popup__listing">';

        $typeinfo = [];
        $typeinfo[] = acym_selectOption('receiver', 'ACYM_RECEIVER_INFORMATION');
        $typeinfo[] = acym_selectOption('sender', 'ACYM_SENDER_INFORMATION');
        $text .= acym_radio($typeinfo, 'typeinfo', 'receiver');

        $fieldType = acym_loadObjectList('SELECT name, type FROM #__comprofiler_fields', 'name');

        $text .= '<div class="cell acym__row__no-listing acym__listing__row__popup" onclick="applyCB(\'thumb\');" >Thumb Avatar</div>';

        $fields = acym_getColumns('comprofiler', false);
        foreach ($fields as $fieldname) {
            $type = '';
            if (strpos(strtolower($fieldname), 'date') !== false) {
                $type = '| type:date';
            }
            if (!empty($fieldType[$fieldname]) && $fieldType[$fieldname]->type == 'image') {
                $type = '| type:image';
            }
            $text .= '<div class="cell acym__row__no-listing acym__listing__row__popup" onclick="applyCB(\''.$fieldname.$type.'\', this);" >'.$fieldname.'</div>';
        }

        $otherFields = acym_loadObjectList("SELECT * FROM #__comprofiler_fields WHERE tablecolumns = '' AND published = 1");
        foreach ($otherFields as $oneField) {
            $text .= '<div class="cell acym__row__no-listing acym__listing__row__popup" onclick="applyCB(\'cbapi_'.$oneField->name.'\');" >'.$oneField->name.'</div>';
        }

        $text .= '</div>';

        echo $text;
    }

    public function replaceUserInformation(&$email, &$user, $send = true)
    {
        $extractedTags = $this->pluginHelper->extractTags($email, 'cbtag');
        if (empty($extractedTags)) return;

        $uservalues = null;
        if (!empty($user->cms_id)) {
            $uservalues = acym_loadObject('SELECT * FROM #__comprofiler WHERE user_id = '.intval($user->cms_id).' LIMIT 1');
        }

        $fieldObjects = acym_loadObjectList('SELECT fieldid, `table`, name, type, params FROM #__comprofiler_fields', 'name');

        //That may be useful..
        if (!include_once ACYM_ROOT.'administrator'.DS.'components'.DS.'com_comprofiler'.DS.'plugin.foundation.php') return;
        cbimport('cb.database');
        $currentCBUser = null;

        $tags = [];
        foreach ($extractedTags as $i => $oneTag) {
            if (isset($tags[$i])) continue;

            $field = $oneTag->id;
            //We initiate the $values variable based on the information (sender or receiver)
            $values = new stdClass();

            //Load sender information... or
            if (!empty($oneTag->info) && $oneTag->info == 'sender') {
                if (empty($this->sendervalues[$email->id]) && !empty($email->creator_id)) {
                    $this->sendervalues[$email->id] = acym_loadObject('SELECT * FROM #__comprofiler WHERE user_id = '.intval($email->creator_id).' LIMIT 1');
                }
                if (!empty($this->sendervalues[$email->id])) {
                    $values = $this->sendervalues[$email->id];
                }
            } else {
                $values = $uservalues;
            }

            if (substr($field, 0, 6) == 'cbapi_') {
                if (!empty($oneTag->info) && $oneTag->info == 'sender') {
                    if (empty($this->sendervalues[$email->id]->$field) && !empty($email->creator_id)) {
                        $currentSender = CBuser::getInstance($email->creator_id);
                        $values->$field = $currentSender->getField(substr($field, 6), $oneTag->default, 'html', 'none', 'profile', 0, true);
                        //We keep the value to not execute the queries again and again.
                        $this->sendervalues[$email->id]->$field = $values->$field;
                    } elseif (!empty($this->sendervalues[$email->id]->$field)) {
                        $values->$field = @$this->sendervalues[$email->id]->$field;
                    }
                } elseif (!empty($user->cms_id)) {
                    if (empty($currentCBUser)) {
                        $currentCBUser = CBuser::getInstance($user->cms_id);
                    }
                    if (!empty($currentCBUser)) {
                        $values->$field = $currentCBUser->getField(substr($field, 6), $oneTag->default, 'html', 'none', 'profile', 0, true);
                    }
                    //Progress CB field isn't loaded on Fron-end, so load it manually

                    $fieldName = substr($field, 6);
                    if (empty($values->$field) && !empty($fieldObjects[$fieldName]) && $fieldObjects[$fieldName]->type == 'progress') {
                        $fieldObjects[$fieldName]->decodedParams = json_decode($fieldObjects[$fieldName]->params);
                        if (!empty($fieldObjects[$fieldName]->decodedParams->prg_fields)) {
                            $requiredFields = explode('|*|', $fieldObjects[$fieldName]->decodedParams->prg_fields);
                            $filled_in = 0;
                            foreach ($fieldObjects as $oneField) {
                                if (!in_array($oneField->fieldid, $requiredFields) || !in_array($oneField->table, ['#__comprofiler', '#__users'])) continue;

                                $fieldName = $oneField->name;
                                if (!empty($currentCBUser->_cbuser->$fieldName)) {
                                    $filled_in++;
                                }
                            }
                            $values->$field = intval(($filled_in * 100) / count($requiredFields)).'%';
                        }
                    }
                }
            }

            $replaceme = isset($values->$field) ? $values->$field : $oneTag->default;
            if (!empty($oneTag->type)) {
                if ($oneTag->type == 'image' && !empty($replaceme)) {
                    $url = 'images/comprofiler/'.$replaceme;
                    $canvasUrl = str_replace('gallery/', 'gallery/canvas/', $url);
                    if (!file_exists(ACYM_ROOT.$url) && file_exists(ACYM_ROOT.$canvasUrl)) $url = $canvasUrl;
                    $replaceme = '<img src="'.ACYM_LIVE.$url.'" alt="'.acym_escape(@$user->name).'" />';
                }
            }

            //special one
            if ($field == 'thumb') {
                $replaceme = '<img src="'.ACYM_LIVE.'images/comprofiler/tn'.$values->avatar.'" alt="'.acym_escape(@$user->name).'" />';
            } elseif ($field == 'avatar') {
                $replaceme = '<img src="'.ACYM_LIVE.'images/comprofiler/'.$values->avatar.'" alt="'.acym_escape(@$user->name).'" />';
            }

            $tags[$i] = $replaceme;
            $this->pluginHelper->formatString($tags[$i], $oneTag);
        }

        $this->pluginHelper->replaceTags($email, $tags);
    }

    public function onAcymDeclareConditions(&$conditions)
    {
        // Load CB language
        $languages = [];
        $langPath = JPATH_SITE.DS.'components'.DS.'com_comprofiler'.DS.'plugin'.DS.'language'.DS.'default_language'.DS;
        if (file_exists($langPath.'language.php')) {
            if (!defined('CBLIB')) include_once JPATH_SITE.DS.'libraries/CBLib/CB/Application/CBApplication.php';
            $languages = include_once $langPath.'language.php';
        } elseif (file_exists($langPath.'default_language.php')) {
            include_once $langPath.'default_language.php';
        }

        // Load the fields
        $fieldTitles = acym_loadObjectList('SELECT `name`, `title` FROM #__comprofiler_fields WHERE `table` LIKE "#__comprofiler"', 'name');
        $fields = acym_getColumns('comprofiler', false);

        $cbfields = [];
        foreach ($fields as $alias) {
            $text = $alias;

            if (!empty($fieldTitles[$alias])) {
                if (empty($languages[$fieldTitles[$alias]->title])) {
                    if (defined($fieldTitles[$alias]->title)) {
                        $text = constant($fieldTitles[$alias]->title);
                    } else {
                        $text = $fieldTitles[$alias]->title;
                    }
                } else {
                    $text = $languages[$fieldTitles[$alias]->title];
                }
            }

            $cbfields[] = acym_selectOption($alias, $text);
        }

        usort($cbfields, [$this, 'sortFields']);

        $operator = acym_get('type.operator');

        $conditions['user']['cbfield'] = new stdClass();
        $conditions['user']['cbfield']->name = acym_translation_sprintf('ACYM_COMBINED_TRANSLATIONS', 'Community Builder', acym_translation('ACYM_FIELDS'));
        $conditions['user']['cbfield']->option = '<div class="intext_select_automation cell">';
        $conditions['user']['cbfield']->option .= acym_select($cbfields, 'acym_condition[conditions][__numor__][__numand__][cbfield][field]', null, 'class="acym__select"');
        $conditions['user']['cbfield']->option .= '</div>';
        $conditions['user']['cbfield']->option .= '<div class="intext_select_automation cell">';
        $conditions['user']['cbfield']->option .= $operator->display('acym_condition[conditions][__numor__][__numand__][cbfield][operator]');
        $conditions['user']['cbfield']->option .= '</div>';
        $conditions['user']['cbfield']->option .= '<input class="intext_input_automation cell" type="text" name="acym_condition[conditions][__numor__][__numand__][cbfield][value]">';
    }

    public function sortFields($a, $b)
    {
        return strcmp($a->text, $b->text);
    }

    public function onAcymProcessCondition_cbfield(&$query, $options, $num, &$conditionNotValid)
    {
        $this->processConditionFilter_cbfield($query, $options, $num);
        $affectedRows = $query->count();
        if (empty($affectedRows)) $conditionNotValid++;
    }

    public function processConditionFilter_cbfield(&$query, $options, $num)
    {
        if (empty($options['field'])) return;

        $query->leftjoin['cbfield'.$num] = '#__comprofiler AS cbfield'.$num.' ON cbfield'.$num.'.id = user.cms_id';
        $query->where[] = $query->convertQuery('cbfield'.$num, $options['field'], $options['operator'], $options['value']);
    }

    public function onAcymDeclareSummary_conditions(&$automationCondition)
    {
        $this->summaryConditionFilters($automationCondition);
    }

    public function summaryConditionFilters(&$automationCondition)
    {
        if (!empty($automationCondition['cbfield'])) {
            $automationCondition = acym_translation_sprintf('ACYM_CONDITION_X_FIELD_SUMMARY', $this->pluginDescription->name, $automationCondition['cbfield']['field'], $automationCondition['cbfield']['operator'], $automationCondition['cbfield']['value']);
        }
    }

    public function onAcymDeclareFilters(&$filters)
    {
        $this->filtersFromConditions($filters);
    }

    public function onAcymProcessFilterCount_cbfield(&$query, $options, $num)
    {
        $this->onAcymProcessFilter_cbfield($query, $options, $num);

        return acym_translation_sprintf('ACYM_SELECTED_USERS', $query->count());
    }

    public function onAcymProcessFilter_cbfield(&$query, $options, $num)
    {
        $this->processConditionFilter_cbfield($query, $options, $num);
    }

    public function onAcymDeclareSummary_filters(&$automationFilter)
    {
        $this->summaryConditionFilters($automationFilter);
    }
}
