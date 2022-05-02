<?php

namespace AcyMailing\Classes;

use AcyMailing\Libraries\acymClass;

class FieldClass extends acymClass
{
    var $table = 'field';
    var $pkey = 'id';

    const LANGUAGE_FIELD_ID_KEY = 'language_field_id';

    public function getMatchingElements($settings = [])
    {
        $query = 'SELECT * FROM #__acym_field';
        $queryCount = 'SELECT COUNT(*) FROM #__acym_field';

        $where = [];
        if (!empty($settings['types'])) {
            foreach ($settings['types'] as $i => $oneType) {
                $settings['types'][$i] = acym_escapeDB($oneType);
            }
            $where[] = ' type IN ('.implode(',', $settings['types']).')';
        }

        if (!empty($where)) {
            $query .= ' WHERE '.implode(' AND ', $where);
            $queryCount .= ' WHERE '.implode(' AND ', $where);
        }

        $query .= ' ORDER BY `ordering` ASC';

        return [
            'elements' => acym_loadObjectList($query, 'id'),
            'total' => acym_loadResult($queryCount),
        ];
    }

    public function getFieldsByID($ids)
    {
        acym_arrayToInteger($ids);
        if (empty($ids)) return [];
        $query = 'SELECT * FROM #__acym_field WHERE `id` IN('.implode(',', $ids).') ORDER BY `ordering` ASC';

        return acym_loadObjectList($query);
    }

    public function getFieldsByType($types)
    {
        if (empty($types)) return [];
        if (!is_array($types)) $types = [$types];
        $types = array_map('acym_escapeDB', $types);
        $query = 'SELECT * FROM #__acym_field WHERE `type` IN('.implode(',', $types).') ORDER BY `ordering` ASC';

        return acym_loadObjectList($query);
    }

    public function getOrdering()
    {
        return acym_loadResult('SELECT COUNT(id) FROM #__acym_field');
    }

    public function getAllFieldsForUser()
    {
        $query = 'SELECT * FROM #__acym_field WHERE id NOT IN (1, 2) ORDER BY `ordering` ASC';

        return acym_loadObjectList($query, 'id');
    }

    public function getAllFieldsForModuleFront()
    {
        return acym_loadObjectList(
            'SELECT * 
            FROM #__acym_field 
            WHERE id != 2 
                AND active = 1 
            ORDER BY `ordering` ASC',
            'id'
        );
    }

    public function getFieldsValueByUserId($userId)
    {
        $query = 'SELECT * FROM #__acym_user_has_field WHERE user_id = '.intval($userId);

        return acym_loadObjectList($query, 'field_id');
    }

    public function getFieldsValueByFieldId($fieldId)
    {
        return acym_loadObjectList('SELECT * FROM #__acym_user_has_field WHERE field_id = '.intval($fieldId));
    }

    public function generateNamekey($name, $namekey = '')
    {
        $fieldsNamekey = acym_loadResultArray('SELECT namekey FROM #__acym_field');
        $columnsUser = acym_getColumns('user');
        $namekeyForbidden = array_merge($fieldsNamekey, $columnsUser);

        $namekey = empty($namekey) ? substr(preg_replace('#[^a-z0-9_]#i', '', strtolower($name)), 0, 50) : $namekey;
        $baseNamekey = $namekey;
        $baseCount = count($namekeyForbidden);

        while (in_array($namekey, $namekeyForbidden)) {
            $namekey = $baseNamekey.'_'.$baseCount;
            $baseCount++;
        }

        return $namekey;
    }

    public function getValueFromDB($fieldDB)
    {
        $query = 'SELECT '.acym_secureDBColumn($fieldDB->value).' AS value, '.acym_secureDBColumn($fieldDB->title).' AS title
                    FROM '.acym_secureDBColumn($fieldDB->database).'.'.acym_secureDBColumn($fieldDB->table);
        $query .= isset($fieldDB->where_value) && strlen($fieldDB->where_value) > 0 ? ' WHERE `'.acym_secureDBColumn($fieldDB->where).'` '.$fieldDB->where_sign.' '.acym_escapeDB(
                $fieldDB->where_value
            ) : '';
        if (!empty($fieldDB->order_by)) $query .= ' ORDER BY '.acym_secureDBColumn($fieldDB->order_by).' '.acym_secureDBColumn($fieldDB->sort_order);

        return acym_loadObjectList($query);
    }

    public function store($userID, $fields, $ajax = false)
    {
        if (!empty($_FILES['customField'])) {
            $uploadFolder = trim(acym_cleanPath(html_entity_decode(acym_getFilesFolder(true))), DS.' ').DS;
            $uploadPath = acym_cleanPath(ACYM_ROOT.$uploadFolder.'userfiles'.DS);
            $allowedExtensions = explode(',', $this->config->get('allowed_files'));

            foreach ($_FILES['customField']['tmp_name'] as $key => $value) {
                if (is_array($value) && isset($value[0])) $value = $value[0];
                if (empty($value)) continue;

                // Get the uploaded file name
                $fileName = $_FILES['customField']['name'][$key];
                while (is_array($fileName) && isset($fileName[0])) {
                    $fileName = $fileName[0];
                }

                if (!preg_match('#\.('.implode('|', $allowedExtensions).')$#Ui', $fileName)) {
                    $ext = substr($fileName, strrpos($fileName, '.') + 1);
                    if ($ajax) {
                        $this->errors[] = acym_translationSprintf(
                            'ACYM_ACCEPTED_TYPE',
                            acym_escape($ext),
                            implode(', ', $allowedExtensions)
                        );
                    } else {
                        acym_enqueueMessage(
                            acym_translationSprintf(
                                'ACYM_ACCEPTED_TYPE',
                                acym_escape($ext),
                                implode(', ', $allowedExtensions)
                            ),
                            'error',
                            5000
                        );
                    }

                    continue;
                }

                if (!acym_uploadFile($value, $uploadPath.$fileName)) {
                    if ($ajax) {
                        $this->errors[] = acym_translation('ACYM_ERROR_SAVING');
                    } else {
                        acym_enqueueMessage(acym_translation('ACYM_ERROR_SAVING'), 'error');
                    }

                    continue;
                }
                $fields[$key] = $_FILES['customField']['name'][$key];
            }
        }

        if (empty($fields)) return;

        foreach ($fields as $id => $value) {
            $field = $this->getOneById($id);
            if (empty($field)) {
                acym_enqueueMessage(acym_translationSprintf('ACYM_WRONG_FIELD_ID', $id), 'error');
                continue;
            }
            $fieldOptions = json_decode($field->option);

            if (is_array($value)) {
                if (in_array($field->type, ['multiple_dropdown', 'radio', 'phone'])) {
                    if ($field->type === 'phone' && !empty($fieldOptions->max_characters) && !empty($value['phone'])) {
                        $value['phone'] = substr($value['phone'], 0, $fieldOptions->max_characters);
                    }

                    $value = implode(',', $value);
                    if ($value === ',') $value = '';
                } elseif ($field->type == 'checkbox') {
                    $value = implode(',', array_keys($value));
                } elseif ($field->type == 'date') {
                    $formatToDisplay = explode('%', $fieldOptions->format);
                    unset($formatToDisplay[0]);

                    $year = '0000';
                    $month = '00';
                    $day = '00';
                    $i = 0;
                    foreach ($formatToDisplay as $one) {
                        if ($one === 'd') {
                            $day = $value[$i];
                        }
                        if ($one === 'm') {
                            $month = $value[$i];
                        }
                        if ($one === 'y') {
                            $year = $value[$i];
                        }
                        $i++;
                    }

                    $value = $year.'-'.$month.'-'.$day;
                } else {
                    $value = json_encode($value);
                }
            }

            if (in_array($field->type, ['text', 'textarea']) && !empty($fieldOptions->max_characters)) {
                $value = substr($value, 0, $fieldOptions->max_characters);
            }

            // The user removed a value, don't add an empty line and remove any previous value from the bd
            if (strlen($value) === 0) {
                $query = 'DELETE FROM `#__acym_user_has_field` 
                          WHERE `user_id` = '.intval($userID).' 
                            AND `field_id` = '.intval($id);
            } else {
                $query = 'INSERT INTO #__acym_user_has_field (`user_id`, `field_id`, `value`) VALUES ('.intval($userID).', '.intval($id).', '.acym_escapeDB($value).')';
                $query .= ' ON DUPLICATE KEY UPDATE `value`= VALUES(`value`)';
            }

            acym_query($query);
        }
    }

    public function getAllFieldsListingByUserIds($userIds, $fieldIds, $listing = '')
    {
        $query = 'SELECT field.type AS `type`, field.value AS `value`, field.name AS field_name, user_field.user_id AS user_id, user_field.field_id AS field_id, user_field.value AS field_value, field.active, field.option 
                    FROM #__acym_user_has_field AS user_field
                    LEFT JOIN #__acym_field AS field ON user_field.field_id = field.id';

        $conditions = [];

        if (!empty($listing)) $conditions[] = $listing;

        if (!is_array($userIds)) $userIds = [$userIds];
        acym_arrayToInteger($userIds);
        if (empty($userIds)) return [];

        if (!is_array($fieldIds)) $fieldIds = [$fieldIds];
        acym_arrayToInteger($fieldIds);
        if (empty($fieldIds)) return [];

        $conditions[] = 'user_field.user_id IN ('.implode(',', $userIds).')';
        $conditions[] = 'user_field.field_id IN ('.implode(',', $fieldIds).')';

        if (!empty($conditions)) $query .= ' WHERE ('.implode(') AND (', $conditions).')';

        $fieldValues = [];
        $values = acym_loadObjectList($query);
        $fieldsTypeWithInField = ['radio', 'checkbox', 'single_dropdown', 'multiple_dropdown'];
        foreach ($values as $one) {
            if (intval($one->active) === 0) continue;

            if (!in_array($one->type, $fieldsTypeWithInField)) {
                if ($one->type === 'phone') {
                    $fieldValues[$one->field_id.'-'.$one->user_id] = empty($one->field_value) ? '' : '+'.preg_replace('/,/', ' ', $one->field_value, 1);
                } elseif ($one->type === 'date') {
                    if (empty($one->field_value)) {
                        $fieldValues[$one->field_id.'-'.$one->user_id] = '';
                    } else {
                        $one->option = json_decode($one->option);
                        $fieldValues[$one->field_id.'-'.$one->user_id] = acym_displayDateFormat($one->option->format, '', $one->field_value, [], false);
                    }
                } else {
                    $decoded = json_decode($one->field_value);
                    $fieldValues[$one->field_id.'-'.$one->user_id] = is_array($decoded) ? implode(', ', $decoded) : $one->field_value;
                }
            } else {
                $defaultValues = json_decode($one->value, true);
                foreach ($defaultValues as $oneValue) {
                    if (!in_array($oneValue['value'], explode(',', $one->field_value))) continue;
                    $fieldValues[$one->field_id.'-'.$one->user_id][] = $oneValue['title'];
                }
                if (!empty($fieldValues[$one->field_id.'-'.$one->user_id])) {
                    $fieldValues[$one->field_id.'-'.$one->user_id] = implode(', ', $fieldValues[$one->field_id.'-'.$one->user_id]);
                }
            }
        }

        return $fieldValues;
    }

    public function getAllFieldsBackendListing()
    {
        $whereLanguage = '(backend_listing = 1 OR `type` = "language")';
        $query = 'SELECT id, name FROM #__acym_field WHERE '.$whereLanguage.' AND active = 1 AND id NOT IN (1, 2) ORDER BY ordering';

        $return = [
            'names' => [],
            'ids' => [],
        ];

        foreach (acym_loadObjectList($query) as $one) {
            $return['names'][] = $one->name;
            $return['ids'][] = $one->id;
        }

        return $return;
    }

    public function getAllFieldsFrontendListing()
    {
        $query = 'SELECT id, name FROM #__acym_field WHERE frontend_listing = 1 AND active = 1 AND id NOT IN (1, 2)';

        $return = [
            'names' => [],
            'ids' => [],
        ];

        foreach (acym_loadObjectList($query) as $one) {
            $return['names'][] = $one->name;
            $return['ids'][] = $one->id;
        }

        return $return;
    }

    public function delete($elements)
    {
        if (empty($elements)) return 0;

        if (!is_array($elements)) $elements = [$elements];
        acym_arrayToInteger($elements);

        acym_trigger('specialActionOnDelete', ['field', $elements]);

        acym_query('DELETE FROM #__acym_user_has_field WHERE field_id IN ('.implode(',', $elements).')');

        return parent::delete($elements);
    }

    public function displayField(
        $field,
        $defaultValue,
        $size,
        $valuesArray,
        $displayOutside = true,
        $displayFront = false,
        $user = null,
        $display = 1,
        $displayIf = '',
        $userClasses = []
    ) {
        $allClasses = !empty($userClasses['all']) ? $userClasses['all'] : '';
        $textClasses = !empty($userClasses['text']) ? $userClasses['text'] : '';
        $emailClasses = !empty($userClasses['email']) ? $userClasses['email'] : '';
        $radioClasses = !empty($userClasses['radio']) ? $userClasses['radio'] : '';
        $checkboxClasses = !empty($userClasses['checkbox']) ? $userClasses['checkbox'] : '';
        $dateClasses = !empty($userClasses['date']) ? $userClasses['date'] : '';
        $phoneClasses = !empty($userClasses['phone']) ? $userClasses['phone'] : '';
        $singleDropdownClasses = !empty($userClasses['single_dropdown']) ? $userClasses['single_dropdown'] : '';
        $multipleDropdownClasses = !empty($userClasses['multiple_dropdown']) ? $userClasses['multiple_dropdown'] : '';
        $languageClasses = !empty($userClasses['language']) ? $userClasses['language'] : '';

        $isCoreField = $field->core == 1;

        if (!$isCoreField && !acym_level(ACYM_ENTERPRISE)) return '';

        $return = '';

        if ($display == 0 && acym_isAdmin() && acym_level(ACYM_ENTERPRISE) && !$isCoreField) return '';

        if ($display == 0 && !acym_isAdmin()) return '';

        $cmsUser = false;
        if ($displayFront && !empty($user->id)) {
            $cmsUser = !empty($user->cms_id);
            if ($field->id == 1) {
                $defaultValue = $user->name;
            } elseif ($field->id == 2) {
                $defaultValue = $user->email;
            } else {
                $allValues = [];
                $defaultUserValue = $this->getFieldsValueByUserId($user->id);
                if (!empty($defaultUserValue)) {
                    foreach ($defaultUserValue as $one) {
                        $allValues[$one->field_id] = $one->value;
                    }
                }

                if (isset($allValues[$field->id])) {
                    $defaultValue = is_null(json_decode($allValues[$field->id])) ? $allValues[$field->id] : json_decode($allValues[$field->id]);
                }
            }
        }

        if (in_array($field->type, ['radio', 'checkbox'])) {
            $valuesArrayTmp = [];
            foreach ($valuesArray as $oneValue) {
                if (!is_object($oneValue)) {
                    $valuesArrayTmp = $valuesArray;
                    break;
                }

                if (!empty($oneValue->disable)) continue;
                $valuesArrayTmp[$oneValue->value] = $oneValue->text;
            }
            $valuesArray = $valuesArrayTmp;
        }

        // Translate text of the options
        if (is_array($valuesArray)) {
            foreach ($valuesArray as $key => $oneValue) {
                if (is_object($oneValue) && !empty($valuesArray[$key]->text)) {
                    $valuesArray[$key]->text = acym_translation($valuesArray[$key]->text);
                } elseif (is_string($oneValue)) {
                    $valuesArray[$key] = acym_translation($valuesArray[$key]);
                }
            }
        }

        if (acym_isMultilingual() && $displayFront && !empty($field->translation)) {
            $field->translation = json_decode($field->translation, true);
            if (!empty($field->translation[acym_getLanguageTag()]) && !empty($field->translation[acym_getLanguageTag()]['name'])) {
                $field->name = $field->translation[acym_getLanguageTag()]['name'];
            }
        }

        $field->name = acym_translation($field->name);

        if (empty((array)$field->option) || !isset($field->option->authorized_content)) {
            $authorizedContent = '';
        } else {
            if (empty($field->option->error_message_invalid)) {
                $field->option->authorized_content->message = acym_translationSprintf('ACYM_INCORRECT_FIELD_VALUE', $field->name);
            } else {
                $field->option->authorized_content->message = $field->option->error_message_invalid;
            }
            $authorizedContent = ' data-authorized-content="'.acym_escape($field->option->authorized_content).'"';
        }

        $maxCharacters = empty($field->option->max_characters) ? '' : ' maxlength="'.$field->option->max_characters.'"';
        $style = empty($size) ? '' : ' style="'.$size.'"';
        $messageRequired = empty($field->option->error_message) ? '' : acym_translation($field->option->error_message);
        $requiredJson = json_encode(['type' => $field->type, 'message' => $messageRequired]);
        $required = $field->required ? ' data-required="'.acym_escape($requiredJson).'"' : '';
        $placeholder = '';
        if (!$displayOutside) $placeholder = ' placeholder="'.acym_escape($field->name).'"';

        $name = 'customField['.intval($field->id).']';
        $nameAttribute = ' name="'.$name.'"';
        if ($field->type === 'text') {
            $value = ' value="'.acym_escape($defaultValue).'"';
        }

        if (($displayOutside && (in_array($field->id, [1, 2]) || in_array(
                    $field->type,
                    ['text', 'textarea', 'single_dropdown', 'multiple_dropdown', 'custom_text', 'file', 'language']
                )))) {
            $return .= '<label '.$displayIf.' class="cell margin-top-1"><div class="acym__users__creation__fields__title">'.$field->name.'</div>';
        }
        if ($displayOutside && in_array($field->type, ['date', 'radio', 'checkbox'])) {
            $return .= '<div '.$displayIf.' class="cell margin-top-1"><div class="acym__users__creation__fields__title">'.$field->name.'</div>';
        }

        $readonly = '';

        if ($display == 0 && acym_isAdmin() && acym_level(ACYM_ENTERPRISE) && $isCoreField) {
            $readonly = ' disabled ';
            $required = '';
        }

        if ($field->id == 1) {
            $nameAttribute = ' name="user[name]"';
            $inputTmp = '<input '.$nameAttribute.$placeholder.$required.$value.$authorizedContent.$style.$maxCharacters.$readonly.' type="text" class="cell '.$allClasses.' '.$textClasses.'"'.'>';
            if (!empty($readonly)) $inputTmp = acym_tooltip($inputTmp, acym_translation('ACYM_CF_EDITION_BLOCKED'));
            $return .= $inputTmp;
        } elseif ($field->id == 2) {
            $nameAttribute = ' name="user[email]"';
            $uniqueId = 'email_field_'.rand(100, 900);
            $inputTmp = '<input id="'.$uniqueId.'" '.$nameAttribute.$placeholder.$value.$authorizedContent.$style.$maxCharacters.$readonly.' required type="email" class="cell acym__user__edit__email '.$allClasses.' '.$emailClasses.'"'.($displayFront && $cmsUser
                    ? 'disabled' : '').'>';
            if (!empty($readonly)) $inputTmp = acym_tooltip($inputTmp, acym_translation('ACYM_CF_EDITION_BLOCKED'));
            $return .= $inputTmp;
            if ($displayFront && !$cmsUser && !empty($this->config->get('email_spellcheck'))) {
                $return .= '<ul acym-data-field="'.$uniqueId.'" class="acym_email_suggestions" style="display: none;"></ul>';
            }
        } elseif ($field->type === 'language') {
            if (empty($defaultValue) && !empty($user->language)) {
                $defaultValue = $user->language;
            }

            $selectTmp = acym_select(
                $this->getLanguagesForDropdown(),
                'user[language]',
                empty($defaultValue) ? acym_getLanguageTag() : $defaultValue,
                'class="acym__select '.$allClasses.' '.$languageClasses.'"'.$style.$required.$readonly
            );
            if (!empty($readonly)) $selectTmp = acym_tooltip($selectTmp, acym_translation('ACYM_CF_EDITION_BLOCKED'));
            $return .= $selectTmp;
        } elseif ($field->type === 'text') {
            $return .= '<input '.$nameAttribute.$placeholder.$required.$value.$authorizedContent.$style.$maxCharacters.' type="text">';
        } elseif ($field->type === 'textarea') {
            $return .= '<textarea '.$nameAttribute.$required.$maxCharacters.' rows="'.intval($field->option->rows).'" cols="'.intval(
                    $field->option->columns
                ).'">'.(empty($defaultValue) ? '' : $defaultValue).'</textarea>';
        } elseif ($field->type === 'radio') {
            $defaultValue = strlen($defaultValue) === 0 ? null : $defaultValue;
            if ($displayFront) {
                foreach ($valuesArray as $key => $oneValue) {
                    $isCkecked = $defaultValue == $key ? 'checked' : '';
                    $return .= '<label><input '.$nameAttribute.$required.' type="radio" value="'.acym_escape(
                            $key
                        ).'" '.$isCkecked.((!empty($allClasses) || !empty($radioClasses)) ? ' class="'.$allClasses.' '.$radioClasses.'"' : '').'> '.$oneValue.'</label>';
                }
            } else {
                $return .= acym_radio(
                    $valuesArray,
                    $name.'[]',
                    $defaultValue,
                    $field->required ? ['data-required' => $requiredJson] : []
                );
            }
        } elseif ($field->type === 'checkbox') {
            if ($displayFront) {
                $defaultValue = empty($defaultValue) ? null : (explode(',', $defaultValue));
                foreach ($valuesArray as $key => $oneValue) {
                    $checked = !empty($defaultValue) && in_array($key, $defaultValue) ? 'checked' : '';
                    $return .= '<label><input '.$required.' type="checkbox" name="'.$name.'['.acym_escape($key).']" value="'.acym_escape(
                            $key
                        ).'" '.$checked.((!empty($allClasses) || !empty($checkboxClasses)) ? ' class="'.$allClasses.' '.$checkboxClasses.'"' : '').'> '.$oneValue.'</label>';
                }
            } else {
                if (!empty($defaultValue) && !is_object($defaultValue)) {
                    $defaultValue = explode(',', $defaultValue);
                    $temporaryObject = new \stdClass();
                    foreach ($defaultValue as $oneValue) {
                        $temporaryObject->$oneValue = 'on';
                    }
                    $defaultValue = $temporaryObject;
                }
                $defaultValue = is_object($defaultValue) ? $defaultValue : new \stdClass();
                foreach ($valuesArray as $key => $oneValue) {
                    if (empty($defaultValue->$key)) {
                        $labelClass = 'class="cell margin-top-1"';
                        $attributes = '';
                    } else {
                        $labelClass = '';
                        $attributes = 'checked '.$required;
                    }
                    $return .= '<label '.$labelClass.'>'.$oneValue;
                    $return .= '<input '.$attributes.' type="checkbox" name="'.$name.'['.acym_escape($key).']" class="acym__users__creation__fields__checkbox"></label>';
                }
            }
        } elseif ($field->type === 'single_dropdown') {
            $return .= acym_select(
                $valuesArray,
                $name,
                empty($defaultValue) ? '' : $defaultValue,
                'class="acym__custom__fields__select__form acym__select '.$allClasses.' '.$singleDropdownClasses.'"'.$style.$required
            );
        } elseif ($field->type === 'multiple_dropdown') {
            $defaultValue = is_array($defaultValue) ? $defaultValue : explode(',', $defaultValue);

            $attributes = [
                'class' => 'acym__custom__fields__select__multiple__form acym__select '.$allClasses.' '.$multipleDropdownClasses,
                'style' => $size,
            ];
            if ($field->required) $attributes['data-required'] = $displayFront ? acym_escape($requiredJson) : $requiredJson;

            $return .= acym_selectMultiple($valuesArray, $name, empty($defaultValue) ? [] : $defaultValue, $attributes);
        } elseif ($field->type === 'date') {
            $attributes = [
                'class' => 'acym__custom__fields__select__form acym__select '.$allClasses.' '.$dateClasses,
                'acym-field-type' => 'date',
            ];
            if (!empty($required)) {
                $attributes['data-required'] = $requiredJson;
            }
            $return .= acym_displayDateFormat(
                $field->option->format,
                $name.'[]',
                $defaultValue,
                $attributes
            );
        } elseif ($field->type === 'file') {
            $defaultValue = is_array($defaultValue) ? $defaultValue[0] : $defaultValue;
            if ($displayFront) {
                $return .= '<input '.$nameAttribute.$required.' type="file">';
            } else {
                $return .= acym_inputFile($name.'[]', $defaultValue, '', '', $required);
            }
        } elseif ($field->type === 'phone') {
            $defaultValue = !empty($defaultValue) ? explode(',', $defaultValue) : '';
            $indicator = !empty($defaultValue[0]) ? $defaultValue[0] : '';
            $number = !empty($defaultValue[1]) ? $defaultValue[1] : '';

            if ($displayOutside) $return .= '<div '.$displayIf.' class="cell margin-top-1 grid-x"><div class="acym__users__creation__fields__title cell">'.$field->name.'</div>';
            $return .= '<div class="cell large-5 medium-4 padding-right-1">';
            $return .= acym_generateCountryNumber($name.'[code]', $indicator);
            $return .= '</div>';
            $return .= '<input '.$placeholder.$required.$style.$maxCharacters.' class="cell large-7 medium-8 '.$allClasses.' '.$phoneClasses.'"'.' type="tel" name="'.$name.'[phone]" value="'.acym_escape(
                    $number
                ).'">';
            if ($displayOutside) $return .= '</div>';
        } elseif ($field->type === 'custom_text') {
            $return .= $field->option->custom_text;
        }

        $labelTypes = ['text', 'textarea', 'single_dropdown', 'multiple_dropdown', 'custom_text', 'file', 'language'];
        if ($displayOutside && (in_array($field->id, [1, 2]) || in_array($field->type, $labelTypes))) {
            $return .= '</label>';
        }
        if ($displayOutside && in_array($field->type, ['date', 'radio', 'checkbox'])) {
            $return .= '</div>';
        }

        $return .= '<div class="acym__field__error__block" data-acym-field-id="'.intval($field->id).'"></div>';

        return $return;
    }

    public function getFieldTypeById($id)
    {
        $query = 'SELECT type FROM #__acym_field WHERE id = '.intval($id);

        return acym_loadResult($query);
    }

    public function setInactive($elements)
    {
        if (!is_array($elements)) $elements = [$elements];
        if (empty($elements)) return 0;
        acym_arrayToInteger($elements);

        // Core fields can't be disabled
        $excludedFields = acym_loadResultArray('SELECT `id` FROM #__acym_field WHERE `core` = 1');
        acym_arrayToInteger($excludedFields);

        $elements = array_diff($elements, $excludedFields);

        parent::setInactive($elements);
    }

    public function insertLanguageField()
    {
        if ($this->getLanguageFieldId() !== false) {
            return false;
        }

        $field = new \stdClass();
        $field->name = 'ACYM_LANGUAGE';
        $field->type = 'language';
        $field->active = 1;
        $field->required = 1;
        $field->ordering = $this->getOrdering() + 1;
        $field->core = 1;
        $field->backend_edition = 1;
        $field->backend_listing = 0;
        $field->frontend_edition = 1;
        $field->frontend_listing = 0;
        $field->access = 'all';
        $field->namekey = 'acym_language';

        $rowId = acym_insertObject('#__acym_field', $field);

        if (empty($rowId)) return false;

        return $this->config->save([self::LANGUAGE_FIELD_ID_KEY => $rowId]);
    }

    public function getLanguageFieldId()
    {
        $languageFieldId = acym_loadResult('SELECT `id` FROM `#__acym_field` WHERE `namekey` = "acym_language"');

        return empty($languageFieldId) ? false : $languageFieldId;
    }

    public function getLanguagesForDropdown()
    {
        $languages = acym_getLanguages(false, true);
        $dataLanguages = [];

        foreach ($languages as $langCode => $language) {
            if ($langCode == "xx-XX") continue;

            $oneLanguage = new \stdClass();
            $oneLanguage->value = $langCode;
            $oneLanguage->text = $language->name;

            $dataLanguages[] = $oneLanguage;
        }

        usort($dataLanguages, function ($a, $b) {
            return strtolower($a->text) > strtolower($b->text) ? 1 : -1;
        });

        return $dataLanguages;
    }

    public function setEmailConfirmationField($displayOutside, $size, $container = 'div', $displayInline = false)
    {
        $uniqueId = 'email_confirmation_field_'.rand(100, 900);
        $name = acym_translation('ACYM_EMAIL_CONFIRMATION');
        $placeholder = !$displayOutside ? acym_translation('ACYM_EMAIL_CONFIRMATION') : '';
        $style = empty($size) ? '' : ' style="'.$size.'"';


        $return = '<'.$container.' class="onefield acym_email_confirmation_field acyfield_text">';
        if ($displayOutside) $return .= '<label class="cell margin-top-1"><div class="acym__users__creation__fields__title">'.$name.'</div>';
        $return .= '<input id="'.$uniqueId.'" '.$style.' required type="email" class="cell acym__user__edit__email" name="user[email_confirmation]" placeholder="'.$placeholder.'">';
        $return .= '<div class="acym__field__error__block"></div>';
        if ($displayOutside) $return .= '</label>';
        $return .= '</'.$container.'>';

        if ($container == 'td' && !$displayInline) {
            $return .= '</tr>';
        }

        return $return;
    }
}

