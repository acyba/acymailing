<?php

namespace AcyMailing\Controllers\MailboxActions;

use AcyMailing\Classes\ListClass;
use AcyMailing\Classes\MailboxClass;
use AcyMailing\Helpers\MailboxHelper;
use AcyMailing\Types\DelayType;

trait Edition
{
    public function mailboxAction(): void
    {
        $mailboxClass = new MailboxClass();
        acym_setVar('layout', 'mailbox_action');
        $mailboxId = acym_getVar('int', 'mailboxId', 0);
        $listsClass = new ListClass();

        if (!empty($mailboxId)) {
            $mailboxAction = $mailboxClass->getOneById($mailboxId);
            $this->breadcrumb[acym_translation($mailboxAction->name)] = acym_completeLink('bounces&task=mailboxAction&mailboxId='.$mailboxId);
        } else {
            $this->breadcrumb[acym_translation('ACYM_NEW')] = acym_completeLink('bounces&task=mailboxAction');
            $mailboxAction = new \stdClass();
            $mailboxAction->name = '';
            $mailboxAction->active = 0;
            $mailboxAction->frequency = 900;
            $mailboxAction->description = '';

            $mailboxAction->server = '';
            $mailboxAction->username = '';
            $mailboxAction->password = '';
            $mailboxAction->connection_method = 'imap';
            $mailboxAction->secure_method = 'ssl';
            $mailboxAction->port = '';
            $mailboxAction->self_signed = 1;

            $mailboxAction->delete_wrong_emails = 0;
            $mailboxAction->conditions = [
                'sender' => '',
                'specific' => '',
                'groups' => '',
                'lists' => '',
                'subject' => '',
                'subject_text' => '',
                'subject_regex' => '',
                'subject_remove' => 1,
            ];

            $mailboxAction->actions = [];
            $mailboxAction->senderfrom = 0;
            $mailboxAction->senderto = 0;
        }

        if (empty($mailboxAction->conditions['groups'])) {
            $mailboxAction->conditions['groups'] = [];
        }

        if (empty($mailboxAction->conditions['lists'])) {
            $mailboxAction->conditions['lists'] = [];
        }

        acym_trigger('onAcymMailboxActionDefine', [&$actions]);

        $actionOptions = ['' => acym_translation('ACYM_CHOOSE_ACTION')];
        $actionParameters = '';

        foreach ($actions as $key => $oneAction) {
            $actionOptions[$key] = $oneAction->name;
            $actionParameters .= '<div class="acym__mailbox__edition__action__one__parameters '.$key.' margin-top-1">'.$oneAction->option.'</div>';
        }

        $initialAction = acym_select(
            $actionOptions,
            'acym_action[__num__][action]',
            '',
            [
                'class' => 'acym__select acym__mailbox__edition__action__one__choice',
                'acym-data-infinite' => '',
            ]
        );
        $initialAction .= $actionParameters;

        $data = [
            'mailboxId' => $mailboxId,
            'mailboxActions' => $mailboxAction,
            'delayType' => new DelayType(),
            'groups' => acym_getGroups(),
            'lists' => $listsClass->getAllWithIdName(),
            'initialAction' => $initialAction,
        ];

        parent::display($data);
    }

    public function applyMailboxAction(): void
    {
        $this->storeMailboxAction();
        $this->mailboxAction();
    }

    public function saveMailboxAction(): void
    {
        $this->storeMailboxAction();
        $this->listing();
    }

    public function storeMailboxAction(): void
    {
        $mailbox = acym_getVar('array', 'mailbox', []);
        $mailboxClass = new MailboxClass();
        $mailboxObject = new \stdClass();

        foreach ($mailbox as $column => $value) {
            acym_secureDBColumn($column);
            if (is_array($value) || is_object($value)) {
                $mailboxObject->$column = json_encode($value);
            } else {
                $mailboxObject->$column = $value;
            }
        }

        $actions = acym_getVar('array', 'acym_action', []);
        $mailboxObject->actions = [];
        foreach ($actions as $oneAction) {
            if (empty($oneAction['action'])) {
                continue;
            }

            $mailboxObject->actions[] = [$oneAction['action'] => $oneAction[$oneAction['action']]];
        }
        $mailboxObject->actions = json_encode($mailboxObject->actions);

        if (!empty($mailboxObject->password) && trim($mailboxObject->password, '*') === '') {
            unset($mailboxObject->password);
        }

        $mailboxId = $mailboxClass->save($mailboxObject);

        if (empty($mailboxId)) {
            acym_enqueueMessage(acym_translation('ACYM_ERROR_SAVING'), 'error');
        } else {
            acym_enqueueMessage(acym_translation('ACYM_SUCCESSFULLY_SAVED'));
            acym_setVar('mailboxId', $mailboxId);
        }
    }

    public function testMailboxAction(): void
    {
        $mailbox = acym_getVar('array', 'mailbox', []);

        if (empty($mailbox)) {
            acym_sendAjaxResponse(acym_translation('ACYM_NO_CONFIGURATION'), [], false);
        }

        $mailbox = (object)$mailbox;

        // If this is not a new mailbox or if it is not from the configuration menu, we need to get the password from the database
        if (!empty($mailbox->id)) {
            if ($mailbox->id !== 'configuration') {
                // We check if the password has not changed
                if (empty(trim($mailbox->password, '*'))) {
                    $mailboxClass = new MailboxClass();
                    $mailboxFromDatabase = $mailboxClass->getOneById($mailbox->id);
                    $mailbox->password = $mailboxFromDatabase->password;
                }
            } elseif (empty(trim($mailbox->password, '*'))) {
                // If it comes from the configuration menu, we need to get the password from the config table (not the mailbox_action table)
                $mailbox->password = $this->config->get('bounce_password');
                $mailbox->bounce_access_token = str_replace('Bearer ', '', $this->config->get('bounce_access_token', ''));
                $mailbox->connection_method = $this->config->get('connection_method', 'imap');
            }
        }

        $mailboxHelper = new MailboxHelper();
        try {
            $isConnectionValid = $mailboxHelper->isConnectionValid($mailbox);
        } catch (\Exception $e) {
            $this->mailboxReport[] = $e->getMessage();
        }

        if (empty($isConnectionValid)) {
            acym_sendAjaxResponse(acym_translation('ACYM_CONNECTION_FAILED'), ['report' => $this->mailboxReport], false);
        } else {
            acym_sendAjaxResponse(acym_translation('ACYM_CONNECTION_SUCCESSFUL'));
        }
    }
}
