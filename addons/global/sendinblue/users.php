<?php

use AcyMailing\Classes\UserClass;

class SendinblueUsers extends SendinblueClass
{
    const MAX_USERS_IMPORT_NB = 5000;
    var $list;

    public function __construct(&$plugin, $headers, $list)
    {
        parent::__construct($plugin, $headers);
        $this->list = $list;
    }

    public function createUser($user)
    {
        $sendingMethod = $this->config->get('mailer_method', 'phpmail');
        if ($sendingMethod != plgAcymSendinblue::SENDING_METHOD_ID) return;

        $userName = empty($user->name) ? '' : $user->name;

        $nameParts = explode(' ', $userName, 2);
        $userData = [
            'email' => acym_strtolower($user->email),
            'attributes' => [
                'LASTNAME' => empty($nameParts[1]) ? '' : $nameParts[1],
                'FIRSTNAME' => $nameParts[0],
            ],
            'updateEnabled' => true,
        ];
        $this->callApiSendingMethod('contacts', $userData, $this->headers, 'POST');
    }

    public function createFromImportedSource($source)
    {
        $userClass = new UserClass();
        $importedUsers = $userClass->getByColumnValue('source', $source);
        $this->importUsers($importedUsers);
    }

    public function importUsers($users, $ajax = false)
    {
        $sendingMethod = $this->config->get('mailer_method', 'phpmail');
        if ($sendingMethod != plgAcymSendinblue::SENDING_METHOD_ID) return;

        if (!acym_createFolder(ACYM_TMP_FOLDER)) {
            $message = acym_translation('ACYM_ERROR_CREATING_EXPORT_FILE');
            acym_logError($message, 'sendinblue');
            if ($ajax) {
                acym_sendAjaxResponse($message, [], false);
            } else {
                acym_enqueueMessage($message, 'error');
            }

            return;
        }

        static $listId = null;

        if (empty($listId)) {
            $listId = $this->list->createList('Import '.time());
        }

        $this->errors = [];
        $filePath = ACYM_TMP_FOLDER.plgAcymSendinblue::SENDING_METHOD_ID.'.txt';
        file_put_contents($filePath, "LASTNAME;FIRSTNAME;EMAIL\n");
        $limit = self::MAX_USERS_IMPORT_NB;
        $buffer = '';

        foreach ($users as $oneUser) {
            $nameParts = explode(' ', $oneUser->name, 2);
            $lastName = str_replace('"', '', empty($nameParts[1]) ? '' : $nameParts[1]);
            $firstName = str_replace('"', '', $nameParts[0]);
            $buffer .= '"'.$lastName.'";"'.$firstName.'";"'.acym_strtolower($oneUser->email)."\"\n";
            $limit--;

            if ($limit === 0) {
                file_put_contents($filePath, $buffer, FILE_APPEND);
                $this->sendUsers($listId);
                file_put_contents($filePath, "LASTNAME;FIRSTNAME;EMAIL\n");
                $limit = self::MAX_USERS_IMPORT_NB;
                $buffer = '';
            }
        }
        if (!empty($buffer)) {
            file_put_contents($filePath, $buffer, FILE_APPEND);
            $this->sendUsers($listId);
        }

        if (empty($this->errors)) {
            $message = acym_translation('ACYM_USERS_SUNCHRONIZED');
            acym_logError($message, 'sendinblue');
            if ($ajax) {
                acym_sendAjaxResponse($message);
            }
        } else {
            $message = implode('<br />', $this->errors);
            acym_logError($message, 'sendinblue');
            if ($ajax) {
                acym_sendAjaxResponse($message, [], false);
            } else {
                acym_enqueueMessage($message, 'error');
            }
        }
    }

    private function sendUsers($listId)
    {
        // Call API to import
        $data = [
            'fileUrl' => ACYM_TMP_URL.plgAcymSendinblue::SENDING_METHOD_ID.'.txt',
            'listIds' => [$listId],
            'updateExistingContacts' => true,
        ];

        $response = $this->callApiSendingMethod('contacts/import', $data, $this->headers, 'POST');
        //TODO delete the import file to avoid leaking user data

        if (!empty($response['error_curl'])) {
            $this->errors[] = acym_translationSprintf('ACYM_ERROR_OCCURRED_WHILE_CALLING_API', $response['error_curl']);
        } elseif (!empty($response['code']) && !empty($response['message'])) {
            $this->errors[] = acym_translationSprintf('ACYM_API_RETURN_THIS_ERROR', $response['message']);
        }
    }

    public function deleteUsers($users)
    {
        $sendingMethod = $this->config->get('mailer_method', 'phpmail');
        if ($sendingMethod != plgAcymSendinblue::SENDING_METHOD_ID) return;

        $deleteUrl = 'contacts/';

        $userClass = new UserClass();

        foreach ($users as $oneUser) {
            $userToDelete = $userClass->getOneById($oneUser);
            $this->callApiSendingMethod($deleteUrl.urlencode(acym_strtolower($userToDelete->email)), [], $this->headers, 'DELETE');
        }
    }

    public function createAttribute($mailId)
    {
        $existingAttributes = $this->config->get('sendinblue_attributes', '{}');
        $existingAttributes = json_decode($existingAttributes, true);

        $subjectAttributeName = $this->getSubjectAttributeName($mailId);
        $contentAttributeName = $this->getAttributeName($mailId);

        $added = $this->addAttribute($existingAttributes, $subjectAttributeName);
        $added = $this->addAttribute($existingAttributes, $contentAttributeName) || $added;

        if ($added) {
            $this->config->save(['sendinblue_attributes' => json_encode($existingAttributes)]);
        }
    }

    private function addAttribute(&$existingAttributes, $attributeName): bool
    {
        if (!empty($existingAttributes[$attributeName])) return false;
        $this->callApiSendingMethod(
            'contacts/attributes/normal/'.$attributeName,
            ['type' => 'text'],
            $this->headers,
            'POST'
        );
        $existingAttributes[$attributeName] = true;

        return true;
    }

    public function addUserToList($email, $mailId, &$warnings): bool
    {
        $listId = 0;
        $this->list->getListExternalSendingMethod($listId, $mailId);

        if (empty($listId)) return false;

        $email = acym_strtolower($email);
        $data = [
            'emails' => [$email],
        ];

        $response = $this->callApiSendingMethod('contacts/lists/'.$listId.'/contacts/add', $data, $this->headers, 'POST');
        $success = !empty($response['contacts']['success']) && in_array($email, $response['contacts']['success']);
        $alreadyInList = !empty($response['message']) && strpos($response['message'], 'Contact already in list') !== false;

        if (!$success && !empty($response['message']) && !$alreadyInList) {
            $warnings .= $response['message'];
            acym_logError('Error trying to add user '.$email.' to list '.$listId.' for mail ID '.$mailId.': '.$response['message'], 'sendinblue');
        } else {
            if (!$success) {
                if (!empty($response['message'])) {
                    acym_logError('Error trying to add user '.$email.' to list '.$listId.' for mail ID '.$mailId.': '.$response['message'], 'sendinblue');
                } else {
                    acym_logError('Error trying to add user '.$email.' to list '.$listId.' for mail ID '.$mailId.' - unknown error: '.json_encode($response), 'sendinblue');
                }
            }
        }

        return $success || $alreadyInList;
    }

    public function removeUserFromList($mailId)
    {
        $listId = 0;
        $this->list->getListExternalSendingMethod($listId, $mailId);

        if (empty($listId)) return;

        $this->callApiSendingMethod('contacts/lists/'.$listId.'/contacts/remove', ['all' => true], $this->headers, 'POST');
    }

    public function addAttributeToUser($email, $subjectContent, $htmlContent, $mailId)
    {
        $subjectAttribute = $this->getSubjectAttributeName($mailId);
        $contentAttribute = $this->getAttributeName($mailId);

        if (strpos($htmlContent, 'acym__wysid__template') === false || strpos($htmlContent, '<body') === false) {
            $personalContent = $htmlContent;
        } else {
            $personalContent = preg_replace('#^.*<body[^>]*>(.*)</body>.*$#Uis', '$1', $htmlContent);
        }

        $data = [
            'attributes' => [
                $subjectAttribute => $subjectContent,
                $contentAttribute => $personalContent,
            ],
            'email' => acym_strtolower($email),
            'updateEnabled' => true,
        ];
        $this->callApiSendingMethod('contacts', $data, $this->headers, 'POST');
    }

    public function getAttributeName($mailId): string
    {
        return 'HTML_CONTENT_'.$mailId;
    }

    public function getSubjectAttributeName($mailId): string
    {
        return 'SUBJECT_'.$mailId;
    }

    public function deleteAttribute($mailId)
    {
        $subjectAttributeName = $this->getSubjectAttributeName($mailId);
        $contentAttributeName = $this->getAttributeName($mailId);

        $existingAttributes = $this->config->get('sendinblue_attributes', '{}');
        $existingAttributes = json_decode($existingAttributes, true);

        $removedAnAttribute = $this->removeAttribute($existingAttributes, $subjectAttributeName);
        $removedAnAttribute = $this->removeAttribute($existingAttributes, $contentAttributeName) || $removedAnAttribute;

        if ($removedAnAttribute) {
            $this->config->save(['sendinblue_attributes' => json_encode($existingAttributes)]);
        }
    }

    private function removeAttribute(&$existingAttributes, $attributeName): bool
    {
        $this->callApiSendingMethod(plgAcymSendinblue::SENDING_METHOD_API_URL.'contacts/attributes/normal/'.$attributeName, [], $this->headers, 'DELETE');

        if (empty($existingAttributes[$attributeName])) return false;

        unset($existingAttributes[$attributeName]);

        return true;
    }

    public function synchronizeExistingUsers()
    {
        // Generate file with user to import
        $userClass = new UserClass();
        $users = $userClass->getAllSimpleData();
        if (empty($users)) {
            acym_sendAjaxResponse(acym_translation('ACYM_NO_USER_TO_SYNCHRONIZE'));
            acym_logError(acym_translation('ACYM_NO_USER_TO_SYNCHRONIZE'), 'sendinblue');
        }

        $this->importUsers($users, true);
    }
}
