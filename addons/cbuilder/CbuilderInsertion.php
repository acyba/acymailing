<?php

trait CbuilderInsertion
{
    public function dynamicText($mailId)
    {
        return $this->pluginDescription;
    }

    public function textPopup()
    {
        ?>
		<script type="text/javascript">
            var selectedCBUserDText;

            function applyCB(tagname, element) {
                if (!tagname) return;

                selectedCBUserDText = tagname;
                let string = '{cbtag:' + tagname + '|info:' + jQuery('input[name="typeInfoCB"]:checked').val() + '}';
                setTag(string, jQuery(element));
            }
		</script>
        <?php
        $text = '<div class="grid-x acym__popup__listing">';

        $typeinfo = [];
        $typeinfo[] = acym_selectOption('receiver', 'ACYM_RECEIVER_INFORMATION');
        $typeinfo[] = acym_selectOption('sender', 'ACYM_SENDER_INFORMATION');
        $text .= acym_radio($typeinfo, 'typeInfoCB', 'receiver', ['onclick' => 'applyCB(selectedCBUserDText, this)']);

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
            $uservalues = acym_loadObject('SELECT * FROM #__comprofiler WHERE user_id = '.intval($user->cms_id));
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
                    $this->sendervalues[$email->id] = acym_loadObject('SELECT * FROM #__comprofiler WHERE user_id = '.intval($email->creator_id));
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
}
