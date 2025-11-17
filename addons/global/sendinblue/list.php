<?php

class SendinblueList extends SendinblueClass
{
    public function getListExternalSendingMethod(&$listId, $mailId, $createIfNotExists = true)
    {
        $brevoLists = $this->config->get('list_sendinblue', '[]');
        $brevoLists = json_decode($brevoLists, true);

        if (!empty($brevoLists[$mailId])) {
            $listId = $brevoLists[$mailId];

            return;
        }

        if (!$createIfNotExists) return;

        $listId = $this->createList('list_for_acym_mail_'.$mailId);

        $brevoLists[$mailId] = $listId;
        $this->config->saveConfig(['list_sendinblue' => json_encode($brevoLists)]);
    }

    public function createList($name)
    {
        $data = [
            'name' => $name,
            'folderId' => intval($this->getFolderId()),
        ];

        $response = $this->callApiSendingMethod('contacts/lists', $data, $this->headers, 'POST');

        // The folder Id is saved in the config but the user removed it from Sendinblue, recreate a folder
        if (!empty($response['message']) && $response['message'] === 'Folder ID does not exist') {
            $this->config->saveConfig(['sendinblue_folder_id' => 0]);

            return $this->createList($name);
        }

        return $response['id'];
    }

    public function getFolderId()
    {
        $folderId = $this->config->get('sendinblue_folder_id', 0);
        if (!empty($folderId)) return $folderId;

        $data = [
            'name' => 'AcyMailing Integration',
        ];

        $response = $this->callApiSendingMethod('contacts/folders', $data, $this->headers, 'POST');

        // The API key may be wrong
        if (empty($response['id'])) return 0;

        $this->config->saveConfig(['sendinblue_folder_id' => $response['id']]);

        return $response['id'];
    }

    public function deleteList($mailId): bool
    {
        $listId = 0;
        $this->getListExternalSendingMethod($listId, $mailId, false);

        if (empty($listId)) return false;

        $this->callApiSendingMethod(plgAcymSendinblue::SENDING_METHOD_API_URL.'contacts/lists/'.$listId, [], $this->headers, 'DELETE');

        return true;
    }
}
