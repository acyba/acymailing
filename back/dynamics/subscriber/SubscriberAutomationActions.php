<?php

use AcyMailing\Classes\UserClass;
use AcyMailing\Classes\MailClass;
use AcyMailing\Classes\FieldClass;

trait SubscriberAutomationActions
{
    /**
     * Function called with ajax to search email to remove from the queue
     */
    public function searchEmails()
    {
        $id = acym_getVar('int', 'id');
        if (!empty($id)) {
            if ($id == -1) {
                $name = acym_translation('ACYM_ALL_MAILS');
            } else {
                $name = acym_loadResult(
                    'SELECT name 
                    FROM #__acym_mail 
                    WHERE id = '.intval($id)
                );
                $name = empty($name) ? '' : acym_utf8Decode($name);
            }
            echo json_encode(['value' => $name]);
            exit;
        }

        $mailClass = new MailClass();

        $return = [];
        $return[] = [-1, acym_translation('ACYM_ALL_MAILS')];
        $search = acym_utf8Encode(acym_getVar('string', 'search', ''));
        $elements = acym_loadObjectList(
            'SELECT `id`, `subject`, `name` 
            FROM #__acym_mail 
            WHERE (`subject` LIKE '.acym_escapeDB('%'.$search.'%').' 
                OR `name` LIKE '.acym_escapeDB('%'.$search.'%').') 
                AND `type` != '.acym_escapeDB($mailClass::TYPE_NOTIFICATION).'
            ORDER BY `subject` ASC 
            LIMIT 20'
        );

        foreach ($elements as $oneElement) {
            $return[] = [$oneElement->id, $oneElement->name];
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
        $actions['acy_user']->option = '<div class="intext_select_automation cell">'.acym_select(
                $userActions,
                'acym_action[actions][__and__][acy_user][action]',
                null,
                ['class' => 'acym__select']
            ).'</div>';


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
                $customFieldValues[$field->id] .= acym_select(
                    $values,
                    '[actions][__and__][acy_user_value][value]',
                    null,
                    [
                        'class' => 'acym__select acym__automation__actions__fields__select',
                        'data-action-field' => $field->id,
                    ]
                );
                $customFieldValues[$field->id] .= '</div>';
            } elseif ('date' == $field->type) {
                $field->option = json_decode($field->option, true);
                $customFieldValues[$field->id] = acym_tooltip(
                    '<input class="acym__automation__one-field acym__automation__actions__fields__select intext_input_automation cell" type="text" name="[actions][__and__][acy_user_value][value]" style="display: none" data-action-field="'.$field->id.'">',
                    acym_translationSprintf('ACYM_DATE_AUTOMATION_INPUT', $field->option['format']),
                    'intext_select_automation cell'
                );
            }
        }

        $actions['acy_user_value'] = new stdClass();
        $actions['acy_user_value']->name = acym_translation('ACYM_SET_USER_VALUE');
        $actions['acy_user_value']->option = '<div class="intext_select_automation">'.acym_select(
                $userFields,
                'acym_action[actions][__and__][acy_user_value][field]',
                null,
                ['class' => 'acym__select acym__automation__actions__fields__dropdown']
            ).'</div><div class="intext_select_automation cell">'.acym_select(
                $userOperator,
                'acym_action[actions][__and__][acy_user_value][operator]',
                null,
                ['class' => 'acym__select acym__automation__actions__operator__dropdown']
            ).'</div><input type="text" name="acym_action[actions][__and__][acy_user_value][value]" class="intext_input_automation cell acym__automation__one-field acym__automation__action__regular-field">';
        $actions['acy_user_value']->option .= implode(' ', $customFieldValues);

        $actions['acy_add_queue'] = new stdClass();
        $actions['acy_add_queue']->name = acym_translation('ACYM_ADD_EMAIL_QUEUE');
        $actions['acy_add_queue']->option = '<button class="shrink grid-x cell acy_button_submit button " type="button" data-task="createMail" data-and="__and__">'.acym_translation(
                'ACYM_CREATE_MAIL'
            ).'</button>';
        $actions['acy_add_queue']->option .= '<input type="hidden" name="acym_action[actions][__and__][acy_add_queue][mail_id]">';
        $actions['acy_add_queue']->option .= '<div class="shrink acym__automation__action__mail__name"></div>';
        $actions['acy_add_queue']->option .= '<div class="shrink margin-left-1 margin-right-1">'.acym_strtolower(acym_translation('ACYM_OR')).' </div>';
        $actions['acy_add_queue']->option .= '<button type="button" data-modal-name="acym__template__choose__modal__and__" data-open="acym__template__choose__modal" aria-controls="acym__template__choose__modal" tabindex="0" aria-haspopup="true" class="cell medium-shrink button-secondary auto button ">'.acym_translation(
                'ACYM_CHOOSE_EXISTING'
            ).'</button>';
        $actions['acy_add_queue']->option .= acym_info('ACYM_CHOOSE_EXISTING_DESC', '', 'margin-left-0');
        $actions['acy_add_queue']->option .= '<div class="medium-4 grid-x cell">';
        $actions['acy_add_queue']->option .= acym_dateField('acym_action[actions][__and__][acy_add_queue][time]', '[time]', '', '', '+');
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
            [
                'class' => 'acym_select2_ajax',
                'data-min' => 0,
                'data-placeholder' => acym_translation('ACYM_SELECT_AN_EMAIL'),
                'data-params' => $ajaxParams,
            ]
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
            if (!empty($usersToDelete)) $userClass->delete($usersToDelete, true);
        } else {
            $fieldToUpdate = '';
            if ($action['action'] == 'confirm') $fieldToUpdate = 'confirmed = 1';
            if ($action['action'] == 'unconfirm') $fieldToUpdate = 'confirmed = 0';
            if ($action['action'] == 'active') $fieldToUpdate = 'active = 1';
            if ($action['action'] == 'block') $fieldToUpdate = 'active = 0';

            $queryToProcess = 'UPDATE #__acym_user AS `user` SET '.$fieldToUpdate.' WHERE ('.implode(') AND (', $query->where).')';
            $nbRows = acym_query($queryToProcess);

            return acym_translationSprintf('ACYM_X_USERS_X', $nbRows, acym_translation('ACYM_ACTION_'.strtoupper($action['action'])));
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
            $field = $fieldClass->getOneById($action['field']);
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
            $execute = 'INSERT INTO #__acym_user_has_field ('.$allColumn.') SELECT id AS user_id, '.intval(
                    $action['field']
                ).' AS field_id, '.$newValue.' AS value FROM #__acym_user AS user WHERE ('.implode(
                    ') AND (',
                    $query->where
                ).') ON DUPLICATE KEY UPDATE '.$column.' = VALUES('.$column.')';
        }

        $nbAffected = acym_query($execute);

        if (!empty($customFieldAlreadyExists)) {
            $nbAffected -= $customFieldAlreadyExists;
        }

        return acym_translationSprintf('ACYM_UPDATED_USERS', $nbAffected);
    }

    public function onAcymProcessAction_acy_add_queue(&$query, &$action, $automationAdmin)
    {
        if (empty($action['time']) || empty($action['mail_id'])) return '';

        $sendDate = acym_replaceDate($action['time']);
        $sendDate = acym_date($sendDate, 'Y-m-d H:i:s', false);
        $mailClass = new MailClass();

        //We generate the new mail if it's a template
        $mail = $mailClass->getOneById($action['mail_id']);

        if (empty($mail)) return '';

        if (MailClass::TYPE_AUTOMATION !== $mail->type) {
            unset($mail->id);
            $mail->type = MailClass::TYPE_AUTOMATION;
            $mail->id = $mailClass->save($mail);
        }

        $userIds = acym_loadResultArray($query->getQuery(['user.id']));
        if (empty($userIds)) {
            $result = 0;
        } else {
            $result = $mailClass->sendAutomation($mail->id, $userIds, $sendDate, $automationAdmin);
        }

        if (is_numeric($result)) {
            return acym_translationSprintf('ACYM_EMAILS_ADDED_QUEUE', $result);
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

        return acym_translationSprintf('ACYM_EMAILS_REMOVED_QUEUE', $nbRows);
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
            return acym_translationSprintf('ACYM_EMAILS_ADDED_QUEUE', $nbInserted);
        } else {
            return $nbInserted;
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
                $field = $fieldClass->getOneById($automationAction['acy_user_value']['field']);
                $automationAction['acy_user_value']['field'] = $field->name;
            }
            $automationAction = acym_translationSprintf(
                'ACYM_ACTION_USER_VALUE_SUMMARY',
                $automationAction['acy_user_value']['field'],
                $automationAction['acy_user_value']['operator'],
                $automationAction['acy_user_value']['value']
            );
        }

        if (!empty($automationAction['acy_add_queue'])) {
            $mailClass = new MailClass();
            $mail = $mailClass->getOneById($automationAction['acy_add_queue']['mail_id']);
            if (empty($mail)) {
                $automationAction = '<span class="acym__color__red">'.acym_translation('ACYM_SELECT_AN_EMAIL').'</span>';
            } else {
                $automationAction = acym_translationSprintf(
                    'ACYM_ACTION_ADD_QUEUE_SUMMARY',
                    $mail->name,
                    acym_date(acym_replaceDate($automationAction['acy_add_queue']['time']), 'd M Y H:i')
                );
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
                $automationAction = acym_translationSprintf('ACYM_ACTION_REMOVE_QUEUE_SUMMARY', $mail->name);
            }
        }

        if (!empty($automationAction['resend_confirmation'])) {
            $automationAction = acym_translation('ACYM_RESEND_CONFIRMATION_SUMMARY');
        }
    }
}
