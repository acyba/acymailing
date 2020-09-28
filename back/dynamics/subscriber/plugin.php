<?php

use AcyMailing\Libraries\acymPlugin;
use AcyMailing\Classes\UserClass;
use AcyMailing\Classes\MailClass;
use AcyMailing\Classes\FieldClass;
use AcyMailing\Classes\AutomationClass;
use AcyMailing\Types\OperatorType;

class plgAcymSubscriber extends acymPlugin
{
    /**
     * Array of fields loaded to have the right option value
     */
    var $fields = [];
    const TRIGGERS = [
        'user_creation' => 'ACYM_ON_USER_CREATION',
        'user_modification' => 'ACYM_ON_USER_MODIFICATION',
        'user_click' => 'ACYM_WHEN_USER_CLICKS_MAIL',
        'user_open' => 'ACYM_WHEN_USER_OPEN_MAIL',
        'user_subscribe' => 'ACYM_WHEN_USER_SUBSCRIBES',
        'user_unsubscribe' => 'ACYM_WHEN_USER_UNSUBSCRIBES',
        'user_confirmation' => 'ACYM_WHEN_USER_CONFIRMS_SUBSCRIPTION',
    ];

    public function __construct()
    {
        parent::__construct();
        $this->pluginDescription->name = acym_translation('ACYM_SUBSCRIBER');
    }

    public function dynamicText()
    {
        return $this->pluginDescription;
    }

    public function textPopup()
    {
        ?>

		<script language="javascript" type="text/javascript">
            <!--
            var selectedTag;

            function changeUserTag(tagname, element) {
                if (!tagname) return;

                var finalTag = '{<?php echo $this->name; ?>:' + tagname;

                if (jQuery('input[name="typeinfo"]:checked').length > 0) {
                    finalTag += '|info:' + jQuery('input[name="typeinfo"]:checked').val() + '';
                }
                finalTag += '}';


                setTag(finalTag, element);
            }

            -->
		</script>

        <?php
        $fieldClass = new FieldClass();
        $fieldsUser = acym_getColumns('user');
        $fieldsStats = acym_getColumns('user_stat');
        $fields = array_merge($fieldsUser, $fieldsStats);
        $customFields = $fieldClass->getAllFieldsForUser();
        $descriptions = [];
        $isAutomationAdmin = acym_getVar('string', 'automation');

        foreach ($customFields as $one) {
            $descriptions[$one->namekey] = acym_translation('ACYM_CUSTOM_FIELD');
            $fields[] = $one->namekey;
        }


        $descriptions['id'] = acym_translation('ACYM_USER_ID');
        $descriptions['email'] = acym_translation('ACYM_USER_EMAIL');
        $descriptions['name'] = acym_translation('ACYM_USER_NAME');
        $descriptions['cms_id'] = acym_translation('ACYM_USER_CMSID');
        $descriptions['source'] = acym_translation('ACYM_USER_SOURCE');
        $descriptions['confirmed'] = acym_translation('ACYM_USER_CONFIRMED');
        $descriptions['active'] = acym_translation('ACYM_USER_ACTIVE');
        $descriptions['creation_date'] = acym_translation('ACYM_USER_CREATION_DATE');
        $descriptions['open_date'] = acym_translation('ACYM_USER_OPEN_DATE');
        $descriptions['date_click'] = acym_translation('ACYM_USER_CLICK_DATE');
        $descriptions['send_date'] = acym_translation('ACYM_USER_SEND_DATE');

        $text = '<div class="acym__popup__listing text-center grid-x">';
        if (!empty($isAutomationAdmin)) {
            $typeinfo = [];
            $typeinfo[] = acym_selectOption('receiver', 'ACYM_RECEIVER_INFORMATION');
            $typeinfo[] = acym_selectOption('current', 'ACYM_USER_TRIGGERING_AUTOMATION');
            $text .= acym_radio($typeinfo, 'typeinfo', 'receiver', ['onclick' => 'changeUserTag(selectedTag)']);
        }
        $text .= '<h1 class="acym__popup__plugin__title cell">'.acym_translation('ACYM_RECEIVER_INFORMATION').'</h1>
					';

        $others = [];
        $others['name|part:first|ucfirst'] = ['name' => acym_translation('ACYM_USER_FIRSTPART'), 'desc' => acym_translation('ACYM_USER_FIRSTPART_DESC')];
        $others['name|part:last|ucfirst'] = ['name' => acym_translation('ACYM_USER_LASTPART'), 'desc' => acym_translation('ACYM_USER_LASTPART_DESC')];

        foreach ($others as $tagname => $tag) {
            $text .= '<div style="cursor:pointer" class="grid-x medium-12 cell acym__row__no-listing acym__listing__row__popup text-left" onclick="changeUserTag(\''.$tagname.'\', jQuery(this));" ><div class="cell medium-6 small-12 acym__listing__title acym__listing__title__dynamics">'.$tag['name'].'</div><div class="cell medium-6 small-12 acym__listing__title acym__listing__title__dynamics">'.$tag['desc'].'</div></div>';
        }

        foreach ($fields as $fieldname) {
            if (empty($descriptions[$fieldname])) {
                continue;
            }

            $type = '';
            if (in_array($fieldname, ['creation_date', 'open_date', 'date_click', 'send_date'])) {
                $type = '|type:time';
            }

            $text .= '<div style="cursor:pointer" class="grid-x medium-12 cell acym__row__no-listing acym__listing__row__popup text-left" onclick="changeUserTag(\''.$fieldname.$type.'\', jQuery(this));" >
                        <div class="cell medium-6 small-12 acym__listing__title acym__listing__title__dynamics">'.$fieldname.'</div>
                        <div class="cell medium-6 small-12 acym__listing__title acym__listing__title__dynamics">'.$descriptions[$fieldname].'</div>
                     </div>';
        }

        $text .= '</div>';

        echo $text;
    }

    public function replaceUserInformation(&$email, &$user, $send = true)
    {
        $extractedTags = $this->pluginHelper->extractTags($email, $this->name);
        $backwardsTags = $this->pluginHelper->extractTags($email, 'subtag');
        foreach ($backwardsTags as $tag => $params) {
            $extractedTags[$tag] = $params;
        }

        if (empty($extractedTags)) return;

        $userClass = new UserClass();
        $user = $userClass->getAllUserFields($user);

        $tags = [];
        foreach ($extractedTags as $i => $oneTag) {
            if (isset($tags[$i])) continue;

            if (!empty($oneTag->info) && $oneTag->info == 'current') continue;
            $tags[$i] = empty($user->id) ? $oneTag->default : $this->replaceSubTag($oneTag, $user);
        }

        $this->pluginHelper->replaceTags($email, $tags);
    }

    private function replaceSubTag(&$mytag, $user)
    {
        $fieldClass = new FieldClass();
        $field = $mytag->id;
        if (strpos($mytag->id, 'custom') === false) {
            $replaceme = (isset($user->$field) && strlen($user->$field) > 0) ? $user->$field : $mytag->default;
        } else {
            $fieldId = explode(',', $field)[1];
            $value = empty($user->id) ? '' : $fieldClass->getAllFieldsListingByUserIds($user->id, $fieldId);

            $replaceme = empty($value) ? $mytag->default : $value[$fieldId.'-'.$user->id];
        }
        $replaceme = acym_translation(nl2br($replaceme));

        $this->pluginHelper->formatString($replaceme, $mytag);

        return $replaceme;
    }

    public function onAcymDeclareTriggers(&$triggers)
    {
        foreach (self::TRIGGERS as $key => $name) {
            $triggers['user'][$key] = new stdClass();
            $triggers['user'][$key]->name = acym_translation($name);
            $triggers['user'][$key]->option = '<input type="hidden" name="[triggers][user]['.$key.'][]" value="">';
        }
    }

    public function onAcymExecuteTrigger(&$step, &$execute, &$data)
    {
        if (empty($data['userId'])) return;

        $triggers = $step->triggers;

        foreach (self::TRIGGERS as $identifier => $name) {
            if (empty($triggers[$identifier])) continue;

            $execute = true;
            break;
        }
    }

    public function onAcymDeclareConditions(&$conditions)
    {
        $userClass = new UserClass();
        $fieldClass = new FieldClass();
        $fields = $userClass->getAllColumnsUserAndCustomField();
        unset($fields['automation']);

        $customFields = $fieldClass->getAllFieldsForUser();
        $customFieldValues = [];
        foreach ($customFields as $field) {
            if (in_array($field->type, ['single_dropdown', 'radio', 'checkbox', 'multiple_dropdown']) && !empty($field->value)) {
                $values = [];
                $field->value = json_decode($field->value, true);
                foreach ($field->value as $value) {
                    $valueTmp = new stdClass();
                    $valueTmp->text = $value['title'];
                    $valueTmp->value = $value['value'];
                    if ($value['disabled'] == 'y') $valueTmp->disable = true;
                    $values[$value['value']] = $valueTmp;
                }
                $customFieldValues[$field->id] = '<div class="acym__automation__one-field intext_select_automation cell" style="display: none">';
                $customFieldValues[$field->id] .= acym_select(
                    $values,
                    '[conditions][__numor__][__numand__][acy_field][value]',
                    null,
                    'class="acym__select acym__automation__conditions__fields__select" data-condition-field="'.intval($field->id).'"'
                );
                $customFieldValues[$field->id] .= '</div>';
            } elseif ('date' == $field->type) {
                $field->option = json_decode($field->option, true);
                $customFieldValues[$field->id] = acym_tooltip(
                    '<input class="acym__automation__one-field acym__automation__conditions__fields__select intext_input_automation cell" type="text" name="[conditions][__numor__][__numand__][acy_field][value]" style="display: none" data-condition-field="'.intval($field->id).'">',
                    acym_translation_sprintf('ACYM_DATE_AUTOMATION_INPUT', $field->option['format']),
                    'intext_select_automation cell'
                );
            }
        }
        $operator = new OperatorType();

        $conditions['user']['acy_field'] = new stdClass();
        $conditions['user']['acy_field']->name = acym_translation('ACYM_ACYMAILING_FIELD');
        $conditions['user']['acy_field']->option = '<div class="intext_select_automation cell">';
        $conditions['user']['acy_field']->option .= acym_select(
            $fields,
            'acym_condition[conditions][__numor__][__numand__][acy_field][field]',
            null,
            'class="acym__select acym__automation__conditions__fields__dropdown"'
        );
        $conditions['user']['acy_field']->option .= '</div>';
        $conditions['user']['acy_field']->option .= '<div class="intext_select_automation cell">';
        $conditions['user']['acy_field']->option .= $operator->display(
            'acym_condition[conditions][__numor__][__numand__][acy_field][operator]',
            '',
            'acym__automation__conditions__operator__dropdown'
        );
        $conditions['user']['acy_field']->option .= '</div>';
        $conditions['user']['acy_field']->option .= '<input class="acym__automation__one-field intext_input_automation cell acym__automation__condition__regular-field" type="text" name="acym_condition[conditions][__numor__][__numand__][acy_field][value]">';
        $conditions['user']['acy_field']->option .= implode(' ', $customFieldValues);
    }

    public function onAcymDeclareFilters(&$filters)
    {
        $this->filtersFromConditions($filters);
    }

    private function _processAcyField(&$query, &$options, $num)
    {
        $usersColumns = acym_getColumns('user');

        if (!in_array($options['field'], $usersColumns)) {
            $fieldClass = new FieldClass();
            $field = $fieldClass->getOneFieldByID($options['field']);

            $type = 'phone' == $field->type ? 'phone' : '';

            $query->leftjoin['userfield'.$num] = ' #__acym_user_has_field as userfield'.$num.' ON userfield'.$num.'.user_id = user.id AND userfield'.$num.'.field_id = '.intval($options['field']);
            $query->where[] = $query->convertQuery('userfield'.$num, 'value', $options['operator'], $options['value'], $type);
        } else {
            if (in_array($options['field'], ['creation_date', 'confirmation_date'])) {
                $options['value'] = acym_replaceDate($options['value']);
                if (!is_numeric($options['value'])) {
                    $options['value'] = strtotime($options['value']);
                }
                $options['value'] = acym_date($options['value'], "Y-m-d H:i:s");
            }
            $query->where[] = $query->convertQuery('user', $options['field'], $options['operator'], $options['value']);
        }

        return $query->count();
    }

    public function onAcymProcessCondition_acy_field(&$query, &$options, $num, &$conditionNotValid)
    {
        $affectedRows = $this->_processAcyField($query, $options, $num);
        if (empty($affectedRows)) $conditionNotValid++;
    }

    public function onAcymProcessFilter_acy_field(&$query, &$options, $num)
    {
        $this->_processAcyField($query, $options, $num);
    }

    public function onAcymProcessFilterCount_acy_field(&$query, $options, $num)
    {
        $this->onAcymProcessFilter_acy_field($query, $options, $num);

        return acym_translation_sprintf('ACYM_SELECTED_USERS', $query->count());
    }

    /**
     * Function called with ajax to search email to remove from the queue
     */
    public function searchEmails()
    {
        $id = acym_getVar('int', 'id');
        if (!empty($id)) {
            if ($id == -1) {
                $subject = acym_translation('ACYM_ALL_MAILS');
            } else {
                $subject = acym_loadResult(
                    'SELECT subject 
					FROM #__acym_mail 
					WHERE id = '.intval($id)
                );
                if (empty($subject)) $subject = '';
            }
            echo json_encode(['value' => $subject]);
            exit;
        }

        $return = [];
        $return[] = [-1, acym_translation('ACYM_ALL_MAILS')];
        $search = acym_getVar('string', 'search', '');
        $elements = acym_loadObjectList(
            'SELECT `id`, `subject`, `name` 
				FROM #__acym_mail 
				WHERE (`subject` LIKE '.acym_escapeDB('%'.$search.'%').' 
					OR `name` LIKE '.acym_escapeDB('%'.$search.'%').') 
					AND `template` IN(0, 2) 
					AND `name` != "acy_report" 
				ORDER BY `subject` ASC 
				LIMIT 20'
        );

        foreach ($elements as $oneElement) {
            if (empty($oneElement->subject)) $oneElement->subject = $oneElement->name;
            $return[] = [$oneElement->id, $oneElement->subject];
        }

        echo json_encode($return);
        exit;
    }

    public function onAcymDeclareActions(&$actions)
    {
        $userActions = [
            'confirm' => acym_translation('ACYM_CONFIRM_USER'),
            'unconfirm' => acym_translation('ACYM_UNCONFIRM_USER'),
            'active' => acym_translation('ACYM_ACTIVE_USER'),
            'block' => acym_translation('ACYM_BLOCK_USER'),
            'delete' => acym_translation('ACYM_DELETE_USER'),
        ];

        $actions['acy_user'] = new stdClass();
        $actions['acy_user']->name = acym_translation('ACYM_ACTION_ON_USERS');
        $actions['acy_user']->option = '<div class="intext_select_automation cell">'.acym_select($userActions, 'acym_action[actions][__and__][acy_user][action]', null, 'class="acym__select"').'</div>';


        $userClass = new UserClass();
        $userFields = $userClass->getAllColumnsUserAndCustomField(true);
        unset($userFields['id']);
        unset($userFields['cms_id']);
        unset($userFields['key']);
        unset($userFields['active']);
        unset($userFields['source']);
        unset($userFields['confirmed']);
        unset($userFields['automation']);
        unset($userFields['creation_date']);

        $userOperator = [
            '=' => '=',
            '-' => '-',
            '+' => '+',
            'add_end' => acym_translation('ACYM_ADD_AT_END'),
            'add_begin' => acym_translation('ACYM_ADD_AT_BEGINNING'),
        ];

        $fieldClass = new FieldClass();
        $customFields = $fieldClass->getAllFieldsForUser();
        $customFieldValues = [];
        foreach ($customFields as $field) {
            if (in_array($field->type, ['single_dropdown', 'radio', 'checkbox', 'multiple_dropdown']) && !empty($field->value)) {
                $values = [];
                $field->value = json_decode($field->value, true);
                foreach ($field->value as $value) {
                    $valueTmp = new stdClass();
                    $valueTmp->text = $value['title'];
                    $valueTmp->value = $value['value'];
                    if ($value['disabled'] == 'y') $valueTmp->disable = true;
                    $values[$value['value']] = $valueTmp;
                }
                $customFieldValues[$field->id] = '<div class="acym__automation__one-field intext_select_automation cell" style="display: none">';
                $customFieldValues[$field->id] .= acym_select($values, '[actions][__and__][acy_user_value][value]', null, 'class="acym__select acym__automation__actions__fields__select" data-action-field="'.$field->id.'"');
                $customFieldValues[$field->id] .= '</div>';
            } elseif ('date' == $field->type) {
                $field->option = json_decode($field->option, true);
                $customFieldValues[$field->id] = acym_tooltip('<input class="acym__automation__one-field acym__automation__actions__fields__select intext_input_automation cell" type="text" name="[actions][__and__][acy_user_value][value]" style="display: none" data-action-field="'.$field->id.'">', acym_translation_sprintf('ACYM_DATE_AUTOMATION_INPUT', $field->option['format']), 'intext_select_automation cell');
            }
        }

        $actions['acy_user_value'] = new stdClass();
        $actions['acy_user_value']->name = acym_translation('ACYM_SET_USER_VALUE');
        $actions['acy_user_value']->option = '<div class="intext_select_automation">'.acym_select($userFields, 'acym_action[actions][__and__][acy_user_value][field]', null, 'class="acym__select acym__automation__actions__fields__dropdown"').'</div><div class="intext_select_automation cell">'.acym_select($userOperator, 'acym_action[actions][__and__][acy_user_value][operator]', null, 'class="acym__select acym__automation__actions__operator__dropdown"').'</div><input type="text" name="acym_action[actions][__and__][acy_user_value][value]" class="intext_input_automation cell acym__automation__one-field acym__automation__action__regular-field">';
        $actions['acy_user_value']->option .= implode(' ', $customFieldValues);

        $actions['acy_add_queue'] = new stdClass();
        $actions['acy_add_queue']->name = acym_translation('ACYM_ADD_EMAIL_QUEUE');
        $actions['acy_add_queue']->option = '<button class="shrink grid-x cell acy_button_submit button " type="button" data-task="createMail" data-and="__and__">'.acym_translation('ACYM_CREATE_MAIL').'</button>';
        $actions['acy_add_queue']->option .= '<input type="hidden" name="acym_action[actions][__and__][acy_add_queue][mail_id]">';
        $actions['acy_add_queue']->option .= '<div class="shrink acym__automation__action__mail__name"></div>';
        $actions['acy_add_queue']->option .= '<div class="shrink margin-left-1 margin-right-1">'.strtolower(acym_translation('ACYM_OR')).' </div>';
        $actions['acy_add_queue']->option .= '<button type="button" data-modal-name="acym__template__choose__modal__and__" data-open="acym__template__choose__modal" aria-controls="acym__template__choose__modal" tabindex="0" aria-haspopup="true" class="cell medium-shrink button-secondary auto button ">'.acym_translation('ACYM_CHOOSE_EXISTING').'</button>';
        $actions['acy_add_queue']->option .= acym_info('ACYM_CHOOSE_EXISTING_DESC', '', 'margin-left-0');
        $actions['acy_add_queue']->option .= '<div class="medium-4 grid-x cell">';
        $actions['acy_add_queue']->option .= acym_dateField('acym_action[actions][__and__][acy_add_queue][time]', '[time]');
        $actions['acy_add_queue']->option .= '</div>';

        $actions['acy_remove_queue'] = new stdClass();
        $actions['acy_remove_queue']->name = acym_translation('ACYM_REMOVE_EMAIL_QUEUE');
        $actions['acy_remove_queue']->option = '<div class="intext_select_automation">';
        $ajaxParams = json_encode(
            [
                'plugin' => __CLASS__,
                'trigger' => 'searchEmails',
            ]
        );
        $actions['acy_remove_queue']->option .= acym_select(
            [],
            'acym_action[actions][__and__][acy_remove_queue][mail_id]',
            null,
            'class="acym_select2_ajax" data-min="0" data-placeholder="'.acym_translation('ACYM_SELECT_AN_EMAIL', true).'" data-params="'.acym_escape($ajaxParams).'"'
        );
        $actions['acy_remove_queue']->option .= '</div>';

        if ($this->config->get('require_confirmation', '1') === '1') {
            $actions['resend_confirmation'] = new stdClass();
            $actions['resend_confirmation']->name = acym_translation('ACYM_RESEND_CONFIRMATION');
            // The action doesn't save if there are no options
            $actions['resend_confirmation']->option = '<input type="hidden" name="acym_action[actions][__and__][resend_confirmation][save]" />';
        }
    }

    public function onAcymProcessAction_acy_user(&$query, $action)
    {
        if ($action['action'] == 'delete') {
            $userClass = new UserClass();
            $usersToDelete = acym_loadResultArray($query->getQuery(['user.id']));
            if (!empty($usersToDelete)) $userClass->delete($usersToDelete);
        } else {
            $fieldToUpdate = '';
            if ($action['action'] == 'confirm') $fieldToUpdate = 'confirmed = 1';
            if ($action['action'] == 'unconfirm') $fieldToUpdate = 'confirmed = 0';
            if ($action['action'] == 'active') $fieldToUpdate = 'active = 1';
            if ($action['action'] == 'block') $fieldToUpdate = 'active = 0';

            $queryToProcess = 'UPDATE #__acym_user AS `user` SET '.$fieldToUpdate.' WHERE ('.implode(') AND (', $query->where).')';
            $nbRows = acym_query($queryToProcess);

            return acym_translation_sprintf('ACYM_X_USERS_X', $nbRows, acym_translation('ACYM_ACTION_'.strtoupper($action['action'])));
        }
    }

    public function onAcymProcessAction_acy_user_value(&$query, $action)
    {
        $value = $action['value'];
        $value = acym_replaceDateTags($value);

        if (empty($action['operator'])) $action['operator'] = '=';

        if (in_array($action['operator'], ['+', '-'])) {
            $value = intval($value);
        } else {
            $value = acym_escapeDB($value);
        }

        $usersColumns = acym_getColumns('user');

        if (in_array($action['field'], $usersColumns)) {
            $execute = 'UPDATE #__acym_user AS user';

            $column = "user.`".acym_secureDBColumn($action['field'])."`";
        } else {
            $fieldClass = new FieldClass();
            $field = $fieldClass->getOneFieldById($action['field']);
            if (empty($field)) return 'Unknown field: '.$action['field'];
            if ('date' == $field->type) $value = acym_escapeDB(json_encode(explode('/', trim($value, '"\''))));

            $allColumn = "`user_id`, `field_id`, `value`";
            $column = "`value`";
        }

        if ($action['operator'] == '=') {
            $newValue = $value;
        } elseif (in_array($action['operator'], ['+', '-'])) {
            $newValue = $column.' '.$action['operator']." ".$value;
        } elseif ($action['operator'] == 'add_end') {
            $newValue = "CONCAT(".$column.", ".$value.")";
        } elseif ($action['operator'] == 'add_begin') {
            $newValue = "CONCAT(".$value.", ".$column.")";
        } else {
            return 'Unknown operator: '.acym_escape($action['operator']);
        }

        if (in_array($action['field'], $usersColumns)) {
            $execute .= " SET ".$column." = ".$newValue;
            if (!empty($query->where)) $execute .= ' WHERE ('.implode(') AND (', $query->where).')';
        } else {
            $customFieldAlreadyExists = acym_loadResult('SELECT COUNT(user_id) FROM #__acym_user_has_field WHERE field_id = '.intval($action['field']));
            $execute = 'INSERT INTO #__acym_user_has_field ('.$allColumn.') SELECT id AS user_id, '.intval($action['field']).' AS field_id, '.$newValue.' AS value FROM #__acym_user AS user WHERE ('.implode(') AND (', $query->where).') ON DUPLICATE KEY UPDATE '.$column.' = VALUES('.$column.')';
        }

        $nbAffected = acym_query($execute);

        if (!empty($customFieldAlreadyExists)) {
            $nbAffected -= $customFieldAlreadyExists;
        }

        return acym_translation_sprintf('ACYM_UPDATED_USERS', $nbAffected);
    }

    public function onAcymProcessAction_acy_add_queue(&$query, &$action, $automationAdmin)
    {
        if (empty($action['time']) || empty($action['mail_id'])) return '';

        $sendDate = acym_replaceDate($action['time']);
        $sendDate = acym_date($sendDate, "Y-m-d H:i:s", false);
        $mailClass = new MailClass();

        //We generate the new mail if it's a template
        $mail = $mailClass->getOneById($action['mail_id']);

        if ('automation' != $mail->type) {
            unset($mail->id);
            $mail->type = 'automation';
            $mail->template = 2;
            $mail->id = $mailClass->save($mail);
        }

        $userIds = acym_loadResultArray($query->getQuery(['user.id']));
        if (empty($userIds)) {
            $result = 0;
        } else {
            $result = $mailClass->sendAutomation($mail->id, $userIds, $sendDate, $automationAdmin);
        }

        if (is_numeric($result)) {
            return acym_translation_sprintf('ACYM_EMAILS_ADDED_QUEUE', $result);
        } else {
            return $result;
        }
    }

    public function onAcymProcessAction_acy_remove_queue(&$query, $action)
    {
        if (empty($action['mail_id'])) return '';

        $mailCondition = '';
        if ($action['mail_id'] != -1) {
            $mailCondition = ' AND `mail_id` = '.intval($action['mail_id']);
        }
        $nbRows = acym_query('DELETE FROM #__acym_queue WHERE `user_id` IN ('.$query->getQuery(['user.id']).')'.$mailCondition);

        return acym_translation_sprintf('ACYM_EMAILS_REMOVED_QUEUE', $nbRows);
    }

    public function onAcymProcessAction_resend_confirmation(&$query, &$action, $automationAdmin)
    {
        $mailClass = new MailClass();
        $confirmationMail = $mailClass->getOneByName('acy_confirm');
        $sendDate = acym_date('now', 'Y-m-d H:i:s', false);

        $queryInsert = 'INSERT IGNORE INTO #__acym_queue ';
        $queryInsert .= $query->getQuery(
            [
                intval($confirmationMail->id),
                'user.id',
                acym_escapeDB($sendDate),
                '1',
                '0',
            ]
        );
        $nbInserted = acym_query($queryInsert);

        if (is_numeric($nbInserted)) {
            return acym_translation_sprintf('ACYM_EMAILS_ADDED_QUEUE', $nbInserted);
        } else {
            return $nbInserted;
        }
    }

    public function onAcymAfterUserCreate(&$user)
    {
        $automationClass = new AutomationClass();
        $automationClass->trigger('user_creation', ['userId' => $user->id]);
    }

    public function onAcymAfterUserModify(&$user)
    {
        $automationClass = new AutomationClass();
        $automationClass->trigger('user_modification', ['userId' => $user->id]);
    }

    public function onAcymDeclareSummary_conditions(&$automation)
    {
        $this->onAcymDeclareSummary_conditionsFilters($automation, 'ACYM_CONDITION_ACY_FIELD_SUMMARY');
    }

    public function onAcymDeclareSummary_filters(&$automation)
    {
        $this->onAcymDeclareSummary_conditionsFilters($automation, 'ACYM_FILTER_ACY_FIELD_SUMMARY');
    }

    private function onAcymDeclareSummary_conditionsFilters(&$automation, $key)
    {
        if (!empty($automation['acy_field'])) {

            $usersColumns = acym_getColumns('user');

            if (!in_array($automation['acy_field']['field'], $usersColumns)) {
                $fieldClass = new FieldClass();
                $field = $fieldClass->getOneFieldById($automation['acy_field']['field']);
                $automation['acy_field']['field'] = $field->name;
            }
            $automation = acym_translation_sprintf(
                $key,
                $automation['acy_field']['field'],
                $automation['acy_field']['operator'],
                $automation['acy_field']['value']
            );
        }
    }

    public function onAcymDeclareSummary_actions(&$automationAction)
    {
        if (!empty($automationAction['acy_user'])) {
            $userActions = [
                'confirm' => acym_translation('ACYM_WILL_CONFIRM'),
                'unconfirm' => acym_translation('ACYM_WILL_UNCONFIRM'),
                'active' => acym_translation('ACYM_WILL_ACTIVE'),
                'block' => acym_translation('ACYM_WILL_BLOCK'),
                'delete' => acym_translation('ACYM_WILL_DELETE'),
            ];
            $automationAction = $userActions[$automationAction['acy_user']['action']];
        }

        if (!empty($automationAction['acy_user_value'])) {
            $usersColumns = acym_getColumns('user');

            if (!in_array($automationAction['acy_user_value']['field'], $usersColumns)) {
                $fieldClass = new FieldClass();
                $field = $fieldClass->getOneFieldById($automationAction['acy_user_value']['field']);
                $automationAction['acy_user_value']['field'] = $field->name;
            }
            $automationAction = acym_translation_sprintf('ACYM_ACTION_USER_VALUE_SUMMARY', $automationAction['acy_user_value']['field'], $automationAction['acy_user_value']['operator'], $automationAction['acy_user_value']['value']);
        }

        if (!empty($automationAction['acy_add_queue'])) {
            $mailClass = new MailClass();
            $mail = $mailClass->getOneById($automationAction['acy_add_queue']['mail_id']);
            if (empty($mail)) {
                $automationAction = '<span class="acym__color__red">'.acym_translation('ACYM_SELECT_AN_EMAIL').'</span>';
            } else {
                $automationAction = acym_translation_sprintf('ACYM_ACTION_ADD_QUEUE_SUMMARY', $mail->name, acym_date(acym_replaceDate($automationAction['acy_add_queue']['time']), 'd M Y H:i'));
            }
        }

        if (!empty($automationAction['acy_remove_queue'])) {
            $mailClass = new MailClass();
            $mail = $mailClass->getOneById($automationAction['acy_remove_queue']['mail_id']);
            if (empty($mail)) {
                if ($automationAction['acy_remove_queue']['mail_id'] == -1) {
                    $automationAction = acym_translation('ACYM_EMPTY_QUEUE_USER');
                } else {
                    $automationAction = '<span class="acym__color__red">'.acym_translation('ACYM_SELECT_AN_EMAIL').'</span>';
                }
            } else {
                $automationAction = acym_translation_sprintf('ACYM_ACTION_REMOVE_QUEUE_SUMMARY', $mail->name);
            }
        }

        if (!empty($automationAction['resend_confirmation'])) {
            $automationAction = acym_translation('ACYM_RESEND_CONFIRMATION_SUMMARY');
        }
    }

    public function onAcymDeclareSummary_triggers(&$automation)
    {
        if (!empty($automation->triggers['user_open'])) $automation->triggers['user_open'] = acym_translation('ACYM_WHEN_USER_OPEN_MAIL');
        if (!empty($automation->triggers['user_click'])) $automation->triggers['user_click'] = acym_translation('ACYM_WHEN_USER_CLICKS_MAIL');
        if (!empty($automation->triggers['user_modification'])) $automation->triggers['user_modification'] = acym_translation('ACYM_ON_USER_MODIFICATION');
        if (!empty($automation->triggers['user_creation'])) $automation->triggers['user_creation'] = acym_translation('ACYM_ON_USER_CREATION');
        if (!empty($automation->triggers['user_subscribe'])) $automation->triggers['user_subscribe'] = acym_translation('ACYM_WHEN_USER_SUBSCRIBES');
        if (!empty($automation->triggers['user_unsubscribe'])) $automation->triggers['user_unsubscribe'] = acym_translation('ACYM_WHEN_USER_UNSUBSCRIBES');
        if (!empty($automation->triggers['user_confirmation'])) $automation->triggers['user_confirmation'] = acym_translation('ACYM_WHEN_USER_CONFIRMS_SUBSCRIPTION');
    }

    public function onAcymToggleUserConfirmed($userId, $newValue)
    {
        if ($newValue == 1) {
            $userClass = new UserClass();
            $userClass->confirm($userId);
        }
    }

    public function onAcymDeclareDataSourcesBirthdayTrigger(&$dataSources)
    {
        $data = [
            'source_name' => 'AcyMailing',
            'fields' => [],
            'no_fields_error_message' => 'ACYM_NO_FIELDS_BIRTHDAY_TRIGGER',
        ];

        $fieldClass = new FieldClass();

        $fieldsData = $fieldClass->getMatchingElements(['types' => ['date']]);
        $fields = $fieldsData['elements'];

        foreach ($fields as $oneField) {
            $option = json_decode($oneField->option);

            if (empty($option->format)) continue;

            $format = explode('%', $option->format);
            unset($format[0]);
            $format = implode('/', $format);
            $format = str_replace('y', 'Y', $format);

            $data['fields'][] = [
                'name' => $oneField->name,
                'id' => $oneField->id,
                'format' => $format,
                'query' => 'SELECT user_id, value AS date FROM #__acym_user_has_field WHERE field_id = '.intval($oneField->id),
            ];
        }

        $dataSources['acymailing'] = $data;
    }
}
