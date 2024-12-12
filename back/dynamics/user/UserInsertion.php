<?php

trait UserInsertion
{
    private $customFields;
    private $usergroups;

    //Keep the sender information to not load them every time
    private $sendervalues = [];

    public function dynamicText($mailId)
    {
        return $this->pluginDescription;
    }

    public function textPopup()
    {
        ?>
		<script type="text/javascript">
            let selectedUserDText;

            function changeUserTag(tagname) {
                if (!tagname) return;

                selectedUserDText = tagname;

                let dText;
                const iscf = tagname.toLowerCase().indexOf('custom');

                if (iscf >= 0) {
                    dText = '{usertag:' + tagname.substring(0, iscf) + '|type:custom';
                } else {
                    dText = '{usertag:' + tagname;
                }

                if (tagname.toLowerCase().indexOf('date') >= 0) {
                    dText += '|type:date';
                }
                dText += '|info:' + jQuery('input[name="typeInfoUser"]:checked').val() + '}';

                setTag(dText, jQuery('#' + tagname + 'option'));
            }
		</script>

        <?php

        $isAutomation = acym_getVar('string', 'automation');
        echo '<div class="acym__popup__listing text-center grid-x">';

        $typeinfo = [];
        $typeinfo[] = acym_selectOption('receiver', 'ACYM_RECEIVER_INFORMATION');
        $typeinfo[] = acym_selectOption('sender', 'ACYM_SENDER_INFORMATION');
        if (!empty($isAutomation)) {
            $typeinfo[] = acym_selectOption('current', 'ACYM_USER_TRIGGERING_AUTOMATION');
        }

        echo acym_radio(
            $typeinfo,
            'typeInfoUser',
            'receiver',
            ['onclick' => 'changeUserTag(selectedUserDText)'],
            ['containerClass' => 'margin-bottom-1']
        );

        $fields = [
            $this->cmsUserVars->username => 'ACYM_LOGIN_NAME',
            $this->cmsUserVars->name => 'ACYM_USER_NAME',
            $this->cmsUserVars->registered => 'ACYM_REGISTRATION_DATE',
            'groups' => 'ACYM_USER_GROUPS',
        ];

        foreach ($fields as $fieldname => $description) {
            echo '<div class="grid-x medium-12 cell acym__row__no-listing acym__listing__row__popup text-left" id="'.$fieldname.'option" onclick="changeUserTag(\''.$fieldname.'\');" >
					<div class="cell medium-6 small-12 acym__listing__title acym__listing__title__dynamics">'.acym_escape($fieldname).'</div>
					<div class="cell medium-6 small-12 acym__listing__title acym__listing__title__dynamics">'.acym_escape(acym_translation($description)).'</div>
				 </div>';
        }

        // Handle joomla custom fields
        if (ACYM_CMS == 'joomla' && ACYM_J37) {
            // Load field groups
            $groups = acym_loadObjectList('SELECT id, title FROM #__fields_groups WHERE context = "com_users.user" AND state = 1 ORDER BY title ASC');
            $defaultGroup = new stdClass();
            $defaultGroup->id = 0;
            $defaultGroup->title = acym_translation('ACYM_NO_GROUP');
            array_unshift($groups, $defaultGroup);

            // Load custom fields
            $customFields = acym_loadObjectList('SELECT id, title, group_id FROM #__fields WHERE context = "com_users.user" AND state = 1 ORDER BY title ASC');
            if (!empty($customFields)) {
                echo '<h1 class="acym__title acym__title__secondary text-center cell" style="margin-top: 20px;">'.acym_translation('ACYM_CUSTOM_FIELDS').'</h1>';

                foreach ($groups as $oneGroup) {
                    foreach ($customFields as $oneCF) {
                        if ($oneCF->group_id != $oneGroup->id) {
                            continue;
                        }
                        echo '<div class="grid-x medium-12 cell acym__row__no-listing acym__listing__row__popup text-left" id="'.$oneCF->id.'customoption" onclick="changeUserTag(\''.$oneCF->id.'custom\');" >
								<div class="cell medium-6 small-12 acym__listing__title acym__listing__title__dynamics">'.acym_escape($oneCF->title).'</div>
							 </div>';
                    }
                }
            }
        }

        echo '</div>';
    }

    public function replaceUserInformation(&$email, &$user, $send = true)
    {
        $extractedTags = $this->pluginHelper->extractTags($email, 'usertag');
        if (empty($extractedTags)) {
            return;
        }

        if (empty($this->customFields) && ACYM_CMS == 'joomla' && ACYM_J37) {
            $this->customFields = acym_loadObjectList('SELECT * FROM #__fields WHERE context = "com_users.user"', 'id');
            foreach ($this->customFields as &$oneCF) {
                if (!empty($oneCF->fieldparams)) {
                    $oneCF->fieldparams = json_decode($oneCF->fieldparams, true);
                }
            }
        }

        $tags = [];
        $receivervalues = [];
        foreach ($extractedTags as $i => $mytag) {
            if (isset($tags[$i])) {
                continue;
            }
            $mytag->default = '';

            $values = new stdClass();
            $idused = 0;
            //Should we keep
            $save = false;

            //Sender information
            if (!empty($mytag->info) && $mytag->info == 'sender' && !empty($email->creator_id)) {
                $idused = $email->creator_id;
                $save = true;
            }

            //Current user information
            if (!empty($mytag->info) && $mytag->info == 'current') {
                continue;
            }

            //Receiver information
            if ((empty($mytag->info) || $mytag->info == 'receiver') && !empty($user->cms_id)) {
                $idused = $user->cms_id;
            }

            if (!empty($idused) && empty($this->sendervalues[$idused]) && empty($receivervalues[$idused])) {
                $receivervalues[$idused] = acym_loadObject('SELECT * FROM '.$this->cmsUserVars->table.' WHERE '.$this->cmsUserVars->id.' = '.intval($idused));

                //If we save the value in the object as we may reuse it...
                if ($save) {
                    $this->sendervalues[$idused] = $receivervalues[$idused];
                }
            }

            if (!empty($this->sendervalues[$idused])) {
                $values = $this->sendervalues[$idused];
            } elseif (!empty($receivervalues[$idused])) {
                $values = $receivervalues[$idused];
            }

            if ($mytag->id == 'groups') {
                $groups = acym_getGroupsByUser($idused, true, true);
                $values->groups = implode(', ', $groups);
            }

            if (empty($mytag->type)) {
                $mytag->type = '';
            }

            if ($mytag->type == 'custom' && ACYM_CMS == 'joomla') {
                $mytag->id = intval($mytag->id);
                if (empty($mytag->id)) {
                    $replaceme = '';
                } else {
                    $userFieldVals = acym_loadResultArray('SELECT value FROM #__fields_values WHERE item_id = '.intval($idused).' AND field_id = '.intval($mytag->id));

                    $fieldValues = trim(implode(', ', $userFieldVals), ', ');
                    if (empty($fieldValues)) {
                        $defaultValue = acym_loadObject('SELECT default_value, type FROM #__fields WHERE id = '.intval($mytag->id));
                        if (($defaultValue->type == 'user' && !empty($defaultValue->default_value)) || ($defaultValue->type != 'user' && strlen(
                                    $defaultValue->default_value
                                ) > 0)) {
                            $userFieldVals = [$defaultValue->default_value];
                        }
                    }

                    foreach ($userFieldVals as &$oneFieldVal) {
                        switch ($this->customFields[$mytag->id]->type) {
                            case 'radio':
                            case 'list':
                            case 'checkboxes':
                                foreach ($this->customFields[$mytag->id]->fieldparams['options'] as $oneOPT) {
                                    if ($oneOPT['value'] == $oneFieldVal) {
                                        $oneFieldVal = $oneOPT['name'];
                                        break;
                                    }
                                }
                                break;

                            case 'usergrouplist':
                                if (empty($this->usergroups)) {
                                    $this->usergroups = acym_loadObjectList('SELECT id, title FROM #__usergroups', 'id');
                                }

                                $oneFieldVal = $this->usergroups[$oneFieldVal]->title;
                                break;

                            case 'imagelist':
                                if (strlen($this->customFields[$mytag->id]->fieldparams['directory']) > 1) {
                                    $oneFieldVal = '/'.$oneFieldVal;
                                } else {
                                    $this->customFields[$mytag->id]->fieldparams['directory'] = '';
                                }
                                $oneFieldVal = '<img src="images/'.$this->customFields[$mytag->id]->fieldparams['directory'].$oneFieldVal.'" />';
                                break;

                            case 'url':
                                $oneFieldVal = '<a target="_blank" href="'.$oneFieldVal.'">'.$oneFieldVal.'</a>';
                                break;

                            case 'sql':
                                if (empty($this->customFields[$mytag->id]->options)) {
                                    $this->customFields[$mytag->id]->options = acym_loadObjectList($this->customFields[$mytag->id]->fieldparams['query'], 'value');
                                }

                                $oneFieldVal = $this->customFields[$mytag->id]->options[$oneFieldVal]->text;
                                break;

                            case 'user':
                                $oneFieldVal = acym_currentUserName($oneFieldVal);
                                break;

                            case 'media':
                                $oneFieldVal = '<img src="'.$oneFieldVal.'" />';
                                break;

                            case 'calendar':
                                $format = $this->customFields[$mytag->id]->fieldparams['showtime'] == '1' ? 'Y-m-d H:i' : 'Y-m-d';
                                $oneFieldVal = acym_date(strtotime($oneFieldVal), $format);
                                break;
                        }
                    }

                    $replaceme = implode(', ', $userFieldVals);
                }
            } else {
                $replaceme = isset($values->{$mytag->id}) ? $values->{$mytag->id} : $mytag->default;
            }

            $tags[$i] = $replaceme;
            $this->pluginHelper->formatString($tags[$i], $mytag);
        }

        $this->pluginHelper->replaceTags($email, $tags);
    }
}
