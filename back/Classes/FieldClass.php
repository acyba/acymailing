<?php

namespace AcyMailing\Classes;

use AcyMailing\Core\AcymClass;

class FieldClass extends AcymClass
{
    const LANGUAGE_FIELD_ID_KEY = 'language_field_id';

    public function __construct()
    {
        parent::__construct();

        $this->table = 'field';
        $this->pkey = 'id';
    }

    public function getMatchingElements(array $settings = []): array
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

    public function getFieldsByID(array $ids): array
    {
        if (empty($ids)) {
            return [];
        }

        acym_arrayToInteger($ids);

        return acym_loadObjectList('SELECT * FROM #__acym_field WHERE `id` IN('.implode(',', $ids).') ORDER BY `ordering` ASC');
    }

    public function getFieldsByNameKey(array $nameKeys): array
    {
        if (empty($nameKeys)) {
            return [];
        }

        $nameKeys = array_map('acym_escapeDB', $nameKeys);

        return acym_loadObjectList('SELECT * FROM #__acym_field WHERE `namekey` IN('.implode(',', $nameKeys).') ORDER BY `ordering` ASC', 'id');
    }

    public function getFieldsByType(array $types): array
    {
        if (empty($types)) {
            return [];
        }

        $types = array_map('acym_escapeDB', $types);
        $query = 'SELECT * FROM #__acym_field WHERE `type` IN('.implode(',', $types).') ORDER BY `ordering` ASC';

        return acym_loadObjectList($query);
    }

    public function getOrdering(): int
    {
        return (int)acym_loadResult('SELECT COUNT(*) FROM #__acym_field');
    }

    public function getAllFieldsForUser(): array
    {
        return acym_loadObjectList('SELECT * FROM #__acym_field WHERE id NOT IN (1, 2) ORDER BY `ordering` ASC', 'id');
    }

    public function getAllFieldsForModuleFront(): array
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

    public function getFieldsValueByUserId(int $userId): array
    {
        return acym_loadObjectList('SELECT * FROM #__acym_user_has_field WHERE user_id = '.intval($userId), 'field_id');
    }

    public function getFieldsValueByFieldId(int $fieldId): array
    {
        return acym_loadObjectList('SELECT * FROM #__acym_user_has_field WHERE field_id = '.intval($fieldId));
    }

    public function generateNamekey(string $name, string $namekey = ''): string
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

    public function getValueFromDB(object $fieldDB): array
    {
        $query = 'SELECT '.acym_secureDBColumn($fieldDB->value).' AS value, '.acym_secureDBColumn($fieldDB->title).' AS title
                    FROM `'.acym_secureDBColumn($fieldDB->database).'`.`'.acym_secureDBColumn($fieldDB->table).'`';
        $query .= isset($fieldDB->where_value) && strlen($fieldDB->where_value) > 0 ? ' WHERE `'.acym_secureDBColumn($fieldDB->where).'` '.$fieldDB->where_sign.' '.acym_escapeDB(
                $fieldDB->where_value
            ) : '';
        if (!empty($fieldDB->order_by)) $query .= ' ORDER BY '.acym_secureDBColumn($fieldDB->order_by).' '.acym_secureDBColumn($fieldDB->sort_order);

        return acym_loadObjectList($query);
    }

    public function store(int $userID, array $fields, bool $ajax = false): void
    {
        if (!empty($_FILES['customField'])) {
            $uploadFolder = trim(acym_cleanPath(html_entity_decode(acym_getFilesFolder(true))), DS.' ').DS;
            $uploadPath = acym_cleanPath(ACYM_ROOT.$uploadFolder.'userfiles'.DS.$userID.DS);
            if (!file_exists($uploadPath)) {
                acym_createDir($uploadPath);
            }
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
            $fieldOptions = @json_decode(empty($field->option) ? '{}' : $field->option);

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

            // If deleting a file field, also delete the physical file
            if ($field->type === 'file' && strlen($value) === 0) {
                $oldValue = acym_loadResult(
                    'SELECT `value` FROM #__acym_user_has_field WHERE `user_id` = '.intval($userID).' AND `field_id` = '.intval($id)
                );
                if (!empty($oldValue)) {
                    $fileName = @json_decode($oldValue, true);
                    if (is_array($fileName)) $fileName = $fileName[0];
                    if (empty($fileName)) $fileName = $oldValue;

                    $uploadFolder = trim(acym_cleanPath(html_entity_decode(acym_getFilesFolder(true))), DS.' ').DS;
                    // Try new path (with user folder) first, then legacy path (without user folder)
                    $filePath = acym_cleanPath(ACYM_ROOT.$uploadFolder.'userfiles'.DS.$userID.DS.$fileName);
                    if (!file_exists($filePath)) {
                        $filePath = acym_cleanPath(ACYM_ROOT.$uploadFolder.'userfiles'.DS.$fileName);
                    }
                    acym_deleteFile($filePath, false);
                }
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

    public function getAllFieldsByUserIds(array $userIds): array
    {
        acym_arrayToInteger($userIds);
        if (empty($userIds)) return [];

        $query = 'SELECT user_field.user_id, user_field.value, field.name FROM #__acym_user_has_field AS user_field
                    JOIN #__acym_field AS field ON user_field.field_id = field.id
                    WHERE user_field.user_id IN ('.implode(',', $userIds).')';

        return acym_loadObjectList($query);
    }

    public function getAllFieldsListingByUserIds(array $userIds, array $fieldIds, string $listing = ''): array
    {
        acym_arrayToInteger($userIds);
        if (empty($userIds)) return [];

        acym_arrayToInteger($fieldIds);
        if (empty($fieldIds)) return [];

        $query = 'SELECT field.type AS `type`, field.value AS `value`, field.name AS field_name, user_field.user_id AS user_id, user_field.field_id AS field_id, user_field.value AS field_value, field.active, field.option 
                    FROM #__acym_user_has_field AS user_field
                    JOIN #__acym_field AS field ON user_field.field_id = field.id';

        $conditions = [];

        if (!empty($listing)) {
            $conditions[] = $listing;
        }

        $conditions[] = 'user_field.user_id IN ('.implode(',', $userIds).')';
        $conditions[] = 'user_field.field_id IN ('.implode(',', $fieldIds).')';

        if (!empty($conditions)) {
            $query .= ' WHERE ('.implode(') AND (', $conditions).')';
        }

        $fieldValues = [];
        $values = acym_loadObjectList($query);

        $fieldsTypeWithInField = ['radio', 'checkbox', 'single_dropdown', 'multiple_dropdown'];
        foreach ($values as $one) {
            if (intval($one->active) === 0) {
                continue;
            }

            $key = $one->field_id.'-'.$one->user_id;

            if (!in_array($one->type, $fieldsTypeWithInField)) {
                if ($one->type === 'phone') {
                    if (empty($one->field_value)) {
                        $fieldValues[$key] = '';
                    } else {
                        if (substr($one->field_value, 0, 1) === ',') {
                            $fieldValues[$key] = str_replace(',', '', $one->field_value);
                        } else {
                            $fieldValues[$key] = '+'.str_replace(',', ' ', $one->field_value);
                        }
                    }
                } elseif ($one->type === 'date') {
                    if (empty($one->field_value)) {
                        $fieldValues[$key] = '';
                    } else {
                        $one->option = json_decode($one->option);
                        $fieldValues[$key] = acym_displayDateFormat($one->option->format, '', $one->field_value, [], false);
                    }
                } else {
                    $decoded = json_decode($one->field_value);
                    $fieldValues[$key] = is_array($decoded) ? implode(', ', $decoded) : $one->field_value;
                }
            } else {
                $defaultValues = json_decode($one->value, true);
                $defaultValues = array_filter($defaultValues, function ($fieldOption) {
                    return !empty($fieldOption['value']) || !empty($fieldOption['title']);
                });

                if (empty($defaultValues)) {
                    // Options from database
                    if (!empty($one->field_value)) {
                        $fieldOptions = json_decode($one->option, true);
                        if (!empty($fieldOptions['fieldDB'])) {
                            $dbOptions = json_decode($fieldOptions['fieldDB'], true);
                            if (!empty($dbOptions['database']) && !empty($dbOptions['table']) && !empty($dbOptions['value']) && !empty($dbOptions['title'])) {
                                $query = 'SELECT '.acym_secureDBColumn($dbOptions['title']).' 
                                            FROM '.acym_secureDBColumn($dbOptions['database']).'.'.acym_secureDBColumn($dbOptions['table']).' 
                                            WHERE '.acym_secureDBColumn($dbOptions['value']).' = '.acym_escapeDB($one->field_value);
                                $fieldValues[$key][] = acym_loadResult($query);
                            }
                        }
                    }
                } else {
                    // Classic options
                    foreach ($defaultValues as $oneValue) {
                        if (!in_array($oneValue['value'], explode(',', $one->field_value))) continue;
                        $fieldValues[$key][] = $oneValue['title'];
                    }
                }

                if (!empty($fieldValues[$key])) {
                    $fieldValues[$key] = implode(', ', $fieldValues[$key]);
                }
            }
        }

        return $fieldValues;
    }

    public function getAllFieldsBackendListing(): array
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

    public function getAllFieldsFrontendListing(): array
    {
        $fields = acym_loadObjectList('SELECT id, name FROM #__acym_field WHERE frontend_listing = 1 AND active = 1 AND id NOT IN (1, 2)');

        $return = [
            'names' => [],
            'ids' => [],
        ];

        foreach ($fields as $oneField) {
            $return['names'][] = $oneField->name;
            $return['ids'][] = $oneField->id;
        }

        return $return;
    }

    public function delete(array $elements): int
    {
        if (empty($elements)) return 0;

        acym_arrayToInteger($elements);

        acym_trigger('specialActionOnDelete', ['field', $elements]);

        acym_query('DELETE FROM #__acym_user_has_field WHERE field_id IN ('.implode(',', $elements).')');

        return parent::delete($elements);
    }

    /**
     * @param mixed $defaultValue
     */
    public function displayField(
        object  $field,
                $defaultValue,
        array   $valuesArray,
        bool    $displayOutside = true,
        bool    $displayFront = false,
        ?object $user = null,
        bool    $showField = true
    ): string {
        if (intval($field->active) === 0 && intval($field->core) !== 1) {
            return '';
        }

        $extraErrors = $this->config->get('extra_errors', '0');
        $isCoreField = $field->core == 1;

        if (!$isCoreField && !acym_level(ACYM_ENTERPRISE)) {
            return '';
        }

        if (!$showField) {
            if (!acym_isAdmin()) {
                return '';
            }

            if (acym_isAdmin() && acym_level(ACYM_ENTERPRISE) && !$isCoreField) {
                return '';
            }
        }

        $return = '';

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
            $authorizedContent = ' data-authorized-content="'.acym_escape($field->option->authorized_content, false).'"';
        }

        $attributesSelectField = [];
        $maxCharacters = empty($field->option->max_characters) ? '' : ' maxlength="'.$field->option->max_characters.'"';
        $style = '';
        if (!empty($field->option->size)) {
            $attributesSelectField['style'] = 'width:'.$field->option->size.'px';
            $style = ' style="width:'.$field->option->size.'px"';
        }

        $placeholder = '';
        if (!$displayOutside) {
            $placeholder = ' placeholder="'.acym_escape($field->name).'" aria-label="'.acym_escape($field->name).'"';
        }

        $name = 'customField['.intval($field->id).']';
        $nameAttribute = ' name="'.$name.'"';
        if ($field->type === 'text') {
            $value = ' value="'.acym_escape($defaultValue).'"';
        }

        if (
            $displayOutside
            && (
                in_array($field->id, [1, 2])
                || in_array($field->type, ['text', 'textarea', 'single_dropdown', 'multiple_dropdown', 'custom_text', 'file', 'language'])
            )
        ) {
            $return .= '<label class="cell margin-top-1"><span class="acym__users__creation__fields__title">'.$field->name.'</span>';
        }
        if ($displayOutside && in_array($field->type, ['date', 'radio', 'checkbox'])) {
            $return .= '<div class="cell margin-top-1"><div class="acym__users__creation__fields__title">'.$field->name.'</div>';
        }

        $messageRequired = empty($field->option->error_message) ? '' : acym_translation($field->option->error_message);
        $requiredJson = json_encode(['type' => $field->type, 'message' => $messageRequired]);
        $required = '';
        if ($field->required) {
            $required = ' data-required="'.acym_escape($requiredJson).'"';
            $attributesSelectField['data-required'] = $requiredJson;
        }

        $readonly = '';
        if (!$showField && acym_isAdmin() && acym_level(ACYM_ENTERPRISE) && $isCoreField) {
            $attributesSelectField['disabled'] = true;
            unset($attributesSelectField['data-required']);
            $readonly = ' disabled ';
            $required = '';
        }

        $defaultValue = $this->prepareDefaultValue($defaultValue, $field->type, $user);

        if ($field->id == 1) {
            $nameAttribute = ' name="user[name]"';
            $inputTmp = '<input autocomplete="name" '.$nameAttribute.$placeholder.$required.$value.$authorizedContent.$style.$maxCharacters.$readonly.' type="text" class="cell">';
            if (!empty($readonly)) {
                $inputTmp = acym_tooltip(
                    [
                        'hoveredText' => $inputTmp,
                        'textShownInTooltip' => acym_translation('ACYM_CF_EDITION_BLOCKED'),
                    ]
                );
            }
            $return .= $inputTmp;
        } elseif ($field->id == 2) {
            $nameAttribute = ' name="user[email]"';
            $uniqueId = 'email_field_'.rand(100, 900);
            $inputTmp = '<input autocomplete="email" id="'.$uniqueId.'" '.$nameAttribute.$placeholder.$value.$authorizedContent.$style.$maxCharacters.$readonly.' required type="email" class="cell acym__user__edit__email"'.($displayFront && $cmsUser
                    ? 'disabled' : '').'>';
            if (!empty($readonly)) {
                $inputTmp = acym_tooltip(
                    [
                        'hoveredText' => $inputTmp,
                        'textShownInTooltip' => acym_translation('ACYM_CF_EDITION_BLOCKED'),
                    ]
                );
            }
            $return .= $inputTmp;
            if ($displayFront && !$cmsUser && !empty($this->config->get('email_spellcheck'))) {
                $return .= '<ul acym-data-field="'.$uniqueId.'" class="acym_email_suggestions" style="display: none;"></ul>';
            }
        } elseif ($field->type === 'language') {
            $attributesSelectField['class'] = 'acym__select';
            $selectTmp = acym_select(
                $this->getLanguagesForDropdown(),
                'user[language]',
                $defaultValue,
                $attributesSelectField
            );

            if (!empty($readonly)) {
                $selectTmp = acym_tooltip(
                    [
                        'hoveredText' => $selectTmp,
                        'textShownInTooltip' => acym_translation('ACYM_CF_EDITION_BLOCKED'),
                    ]
                );
            }
            $return .= $selectTmp;
        } elseif ($field->type === 'text') {
            $return .= '<input '.$nameAttribute.$placeholder.$required.$value.$authorizedContent.$style.$maxCharacters.' type="text">';
        } elseif ($field->type === 'textarea') {
            $return .= '<textarea '.$nameAttribute.$required.$maxCharacters.' rows="'.intval($field->option->rows).'" cols="'.intval(
                    $field->option->columns
                ).'">'.$defaultValue.'</textarea>';
        } elseif ($field->type === 'radio') {
            if ($displayFront) {
                foreach ($valuesArray as $key => $oneValue) {
                    $isCkecked = $defaultValue == $key ? 'checked' : '';
                    $return .= '<label><input '.$nameAttribute.$required.' type="radio" value="'.acym_escape(
                            $key
                        ).'" '.$isCkecked.'> '.$oneValue.'</label>';
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
            $return .= '<input type="hidden" name="'.acym_escape($name).'" value="">';
            if ($displayFront) {
                foreach ($valuesArray as $key => $oneValue) {
                    $checked = in_array($key, $defaultValue) ? 'checked' : '';
                    $return .= '<label><input '.$required.' type="checkbox" name="'.$name.'['.acym_escape($key).']" value="'.acym_escape(
                            $key
                        ).'" '.$checked.'> '.$oneValue.'</label>';
                }
            } else {
                foreach ($valuesArray as $key => $oneValue) {
                    if (in_array($key, $defaultValue)) {
                        $labelClass = '';
                        $attributes = 'checked '.$required;
                    } else {
                        $labelClass = 'class="cell margin-top-1"';
                        $attributes = '';
                    }
                    $return .= '<label '.$labelClass.'>';
                    $return .= '<input '.$attributes.' type="checkbox" name="'.$name.'['.acym_escape(
                            $key
                        ).']" class="acym__users__creation__fields__checkbox">'.$oneValue.'</label>';
                }
            }
        } elseif ($field->type === 'single_dropdown') {
            $attributesSelectField['class'] = 'acym__custom__fields__select__form acym__select';
            $return .= acym_select(
                $valuesArray,
                $name,
                $defaultValue,
                $attributesSelectField
            );
        } elseif ($field->type === 'multiple_dropdown') {
            $attributes = [
                'class' => 'acym__custom__fields__select__multiple__form acym__select',
                'style' => empty($field->option->size) ? '' : 'width:'.$field->option->size.'px',
            ];
            if ($field->required) $attributes['data-required'] = $displayFront ? acym_escape($requiredJson) : $requiredJson;

            $return .= acym_selectMultiple($valuesArray, $name, $defaultValue, $attributes);
        } elseif ($field->type === 'date') {
            $attributes = [
                'class' => 'acym__custom__fields__select__form acym__select',
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
            if ($displayFront) {
                $return .= '<input '.$nameAttribute.$required.' type="file">';
            } else {
                $downloadUrl = '';
                if (!empty($defaultValue) && !empty($user->id)) {
                    $fileName = is_array($defaultValue) ? $defaultValue[0] : $defaultValue;
                    if (!empty($fileName)) {
                        $uploadFolder = trim(acym_cleanPath(html_entity_decode(acym_getFilesFolder(true))), DS.' ').DS;
                        $userFilePath = $uploadFolder.'userfiles'.DS.$user->id.DS.$fileName;
                        $legacyFilePath = $uploadFolder.'userfiles'.DS.$fileName;

                        if (file_exists(acym_cleanPath(ACYM_ROOT.$userFilePath))) {
                            $downloadUrl = ACYM_LIVE.str_replace(DS, '/', $userFilePath);
                        } elseif (file_exists(acym_cleanPath(ACYM_ROOT.$legacyFilePath))) {
                            $downloadUrl = ACYM_LIVE.str_replace(DS, '/', $legacyFilePath);
                        }
                    }
                }
                $return .= acym_inputFile($name.'[]', $defaultValue, '', $required, $downloadUrl);
            }
        } elseif ($field->type === 'phone') {
            $indicator = !empty($defaultValue[0]) ? $defaultValue[0] : '';
            $number = !empty($defaultValue[1]) ? $defaultValue[1] : '';

            if ($displayOutside) $return .= '<div class="cell margin-top-1 grid-x"><div class="acym__users__creation__fields__title cell">'.$field->name.'</div>';
            $return .= '<div class="cell large-5 medium-4 padding-next-1">';
            $return .= acym_generateCountryNumber($name.'[code]', $indicator);
            $return .= '</div>';
            $return .= '<input autocomplete="tel-national" '.$placeholder.$required.$style.$maxCharacters.' class="cell large-7 medium-8" type="tel" name="'.$name.'[phone]" value="'.acym_escape(
                    $number
                ).'">';
            if ($displayOutside) $return .= '</div>';
        } elseif ($field->type === 'custom_text') {
            $return .= $field->option->custom_text;
        }

        $labelTypes = ['text', 'textarea', 'single_dropdown', 'multiple_dropdown', 'custom_text', 'language'];
        if ($displayOutside && (in_array($field->id, [1, 2]) || in_array($field->type, $labelTypes))) {
            $return .= '</label>';
        }
        if ($displayOutside && in_array($field->type, ['date', 'radio', 'checkbox'])) {
            $return .= '</div>';
        }

        $return .= '<div class="acym__field__error__block" data-acym-field-id="'.intval($field->id).'"></div>';
        if ($displayFront && $extraErrors && !acym_isAdmin()) {
            $return .= '<div class="acym__message__invalid__field acym__color__error" style="display: none;">';
            $return .= '<i class="acymicon-times-circle acym__cross__invalid acym__color__error"></i>'.acym_translation('ACYM_THANKS_TO_FILL_IN_THIS_FIELD').'</div>';
        }

        return $return;
    }

    /**
     * @param mixed $defaultValue
     *
     * @return mixed
     */
    private function prepareDefaultValue($defaultValue, string $fieldType, ?object $user)
    {
        if ($fieldType === 'language') {
            if (empty($defaultValue) && !empty($user->language)) {
                $defaultValue = $user->language;
            }

            return empty($defaultValue) ? acym_getLanguageTag() : $defaultValue;
        }

        if ($fieldType === 'phone') {
            return !empty($defaultValue) ? explode(',', $defaultValue) : '';
        }

        if ($fieldType === 'file') {
            return is_array($defaultValue) ? $defaultValue[0] : $defaultValue;
        }

        if (in_array($fieldType, ['single_dropdown', 'textarea'])) {
            return empty($defaultValue) ? '' : $defaultValue;
        }

        if ($fieldType === 'radio') {
            return strlen($defaultValue) === 0 ? null : $defaultValue;
        }

        if (in_array($fieldType, ['checkbox', 'multiple_dropdown'])) {
            if (empty($defaultValue)) {
                return [];
            }

            if (is_string($defaultValue)) {
                return explode(',', $defaultValue);
            }

            if (is_object($defaultValue)) {
                $defaultValues = [];
                foreach ($defaultValue as $oneKey => $oneValue) {
                    if (!empty($oneValue)) {
                        $defaultValues[] = $oneKey;
                    }
                }

                return $defaultValues;
            }

            return is_array($defaultValue) ? $defaultValue : [];
        }

        return $defaultValue;
    }

    public function getFieldTypeById(int $id): string
    {
        return acym_loadResult('SELECT type FROM #__acym_field WHERE id = '.intval($id));
    }

    public function setInactive(array $elements): void
    {
        if (empty($elements)) {
            return;
        }

        acym_arrayToInteger($elements);

        // Core fields can't be disabled
        $excludedFields = acym_loadResultArray('SELECT `id` FROM #__acym_field WHERE `core` = 1');
        acym_arrayToInteger($excludedFields);

        $elements = array_diff($elements, $excludedFields);

        parent::setInactive($elements);
    }

    public function insertLanguageField(): void
    {
        if (!empty($this->getLanguageFieldId())) {
            return;
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
        $field->namekey = 'acym_language';

        $rowId = acym_insertObject('#__acym_field', $field);

        if (empty($rowId)) {
            return;
        }

        $this->config->saveConfig([self::LANGUAGE_FIELD_ID_KEY => $rowId]);
    }

    public function getLanguageFieldId(): int
    {
        $languageFieldId = acym_loadResult('SELECT `id` FROM `#__acym_field` WHERE `namekey` = "acym_language"');

        return empty($languageFieldId) ? 0 : $languageFieldId;
    }

    public function getLanguagesForDropdown(): array
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

    public function setEmailConfirmationField(bool $displayOutside, object $field, string $container = 'div', bool $displayInline = false): string
    {
        $uniqueId = 'email_confirmation_field_'.rand(100, 900);

        $return = '<'.$container.' class="onefield acym_email_confirmation_field acyfield_text">';
        if ($displayOutside) {
            $return .= '<label class="cell margin-top-1">';
            $return .= '<span class="acym__users__creation__fields__title">'.acym_translation('ACYM_EMAIL_CONFIRMATION').'</span>';
        }

        $style = empty($field->option->size) ? '' : ' style="width:'.intval($field->option->size).'px"';
        $placeholder = !$displayOutside ? acym_translation('ACYM_EMAIL_CONFIRMATION') : '';
        $return .= '<input id="'.acym_escape($uniqueId).'" 
                        '.$style.' 
                        required 
                        type="email" 
                        class="cell acym__user__edit__email" 
                        name="user[email_confirmation]" 
                        placeholder="'.acym_escape($placeholder).'" />';
        $return .= '<span class="acym__field__error__block"></span>';

        $extraErrors = $this->config->get('extra_errors', '0');
        if ($extraErrors && !acym_isAdmin()) {
            $return .= '<span class="acym__message__invalid__field acym__color__error" style="display: none;">';
            $return .= '<i class="acymicon-times-circle acym__cross__invalid acym__color__error"></i>'.acym_translation('ACYM_THANKS_TO_FILL_IN_THIS_FIELD').'</span>';
        }

        if ($displayOutside) {
            $return .= '</label>';
        }
        $return .= '</'.$container.'>';

        if ($container === 'td' && !$displayInline) {
            //TODO either open tr here, or close it where setEmailConfirmationField is called
            $return .= '</tr>';
        }

        return $return;
    }
}
