<?php

use AcyMailing\Classes\UserClass;

class SendinblueUsers extends SendinblueClass
{
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

        $nameParts = explode(' ', $user->name, 2);
        $userData = [
            'email' => $user->email,
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
        foreach ($importedUsers as $oneUser) {
            $this->createUser($oneUser);
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
            $this->callApiSendingMethod($deleteUrl.urlencode($userToDelete->email), [], $this->headers, 'DELETE');
        }
    }

    public function createAttribute($mailId)
    {
        $data = [
            'type' => 'text',
        ];

        $attributeName = $this->getAttributeName($mailId);

        $existingAttributes = $this->config->get('sendinblue_attributes', '{}');
        $existingAttributes = json_decode($existingAttributes, true);

        if (empty($existingAttributes[$attributeName])) {
            $this->callApiSendingMethod('contacts/attributes/normal/'.$attributeName, $data, $this->headers, 'POST');
            $existingAttributes[$attributeName] = true;
            $this->config->save(['sendinblue_attributes' => json_encode($existingAttributes)]);
        }
    }

    public function addUserToList($email, $mailId, &$warnings)
    {
        $listId = 0;
        $this->list->getListExternalSendingMethod($listId, $mailId);

        if (empty($listId)) return false;

        $data = [
            'emails' => [$email],
        ];

        $response = $this->callApiSendingMethod('contacts/lists/'.$listId.'/contacts/add', $data, $this->headers, 'POST');
        $success = !empty($response['contacts']) && !empty($response['contacts']['success']) && in_array($email, $response['contacts']['success']);
        $alreadyInList = !empty($response['message']) && strpos($response['message'], 'Contact already in list') !== false;

        if (!$success && !empty($response['message']) && !$alreadyInList) {
            $warnings .= $response['message'];
        }

        return $success || $alreadyInList;
    }

    public function addAttributeToUser($email, $htmlContent, $mailId)
    {
        $attribute = $this->getAttributeName($mailId);

        $data = [
            'attributes' => [
                $attribute => $htmlContent,
            ],
            'email' => $email,
            'updateEnabled' => true,
        ];
        $this->callApiSendingMethod('contacts', $data, $this->headers, 'POST');
    }

    public function getAttributeName($mailId)
    {
        return 'HTML_CONTENT_'.$mailId;
    }

    public function deleteAttribute($mailId)
    {
        $this->callApiSendingMethod(plgAcymSendinblue::SENDING_METHOD_API_URL.'contacts/attributes/normal/'.$this->getAttributeName($mailId), [], $this->headers, 'DELETE');

        $existingAttributes = $this->config->get('sendinblue_attributes', '{}');
        $existingAttributes = json_decode($existingAttributes, true);
        $attributeName = $this->getAttributeName($mailId);

        if (!empty($existingAttributes[$attributeName])) {
            unset($existingAttributes[$attributeName]);
            $this->config->save(['sendinblue_attributes' => json_encode($existingAttributes)]);
        }
    }

    public function synchronizeExistingUsers()
    {
        // Generate file with user to import
        $userClass = new UserClass();
        $users = $userClass->getAllSimpleData();
        if (empty($users)) acym_sendAjaxResponse(acym_translation('ACYM_NO_USER_TO_SYNCHRONIZE'));

        $ret = acym_createFolder(ACYM_TMP_FOLDER);
        if (!$ret) acym_sendAjaxResponse(acym_translation('ACYM_ERROR_CREATING_EXPORT_FILE'));

        $filePath = ACYM_TMP_FOLDER.plgAcymSendinblue::SENDING_METHOD_ID.'.txt';
        file_put_contents($filePath, "LASTNAME;FIRSTNAME;EMAIL\n");
        $limit = 5000;
        $buffer = '';
        foreach ($users as $oneUser) {
            $nameParts = explode(' ', $oneUser->name, 2);
            $lastName = str_replace('"', '', empty($nameParts[1]) ? '' : $nameParts[1]);
            $firstName = str_replace('"', '', $nameParts[0]);
            $buffer .= '"'.$lastName.'";"'.$firstName.'";"'.$oneUser->email."\"\n";
            $limit--;

            if ($limit === 0) {
                file_put_contents($filePath, $buffer, FILE_APPEND);
                $limit = 5000;
                $buffer = '';
            }
        }
        if (!empty($buffer)) {
            file_put_contents($filePath, $buffer, FILE_APPEND);
        }

        // Create folder (needed to import users to assign list)
        $folderId = $this->list->getFolderId();

        // Call API to import
        $data = [
            'fileUrl' => ACYM_TMP_URL.plgAcymSendinblue::SENDING_METHOD_ID.'.txt',
            'newList' => [
                'listName' => 'Import '.time(),
                'folderId' => (int)$folderId,
            ],
            'updateExistingContacts' => true,
        ];

        $response = $this->callApiSendingMethod('contacts/import', $data, $this->headers, 'POST');
        if (!empty($response['error_curl'])) {
            acym_sendAjaxResponse(acym_translationSprintf('ACYM_ERROR_OCCURRED_WHILE_CALLING_API', $response['error_curl']), [], false);
        } elseif (!empty($response['code'])) {
            acym_sendAjaxResponse(acym_translationSprintf('ACYM_API_RETURN_THIS_ERROR', $response['message']), [], false);
        } else {
            acym_sendAjaxResponse(acym_translation('ACYM_USERS_SUNCHRONIZED'));
        }
    }
}
