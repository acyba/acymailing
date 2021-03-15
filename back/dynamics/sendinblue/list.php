<?php

class SendinblueList extends SendinblueClass
{
    var $headers;

    public function getListExternalSendingMethod(&$listId, $mailId, $dontCreate = false)
    {
        $sendinblueLists = $this->config->get('list_sendinblue', '[]');
        $sendinblueLists = json_decode($sendinblueLists, true);

        if (!empty($sendinblueLists[$mailId])) {
            $listId = $sendinblueLists[$mailId];

            return;
        }

        if ($dontCreate) return;

        $data = [
            'name' => 'list_for_acym_mail_'.$mailId,
            'folderId' => intval($this->getFolderId()),
        ];

        $response = $this->callApiSendingMethod('contacts/lists', $data, $this->headers, 'POST');

        // The folder Id is saved in the config but the user removed it from Sendinblue, recreate a folder
        if (!empty($response['message']) && $response['message'] === 'Folder ID does not exist') {
            $this->config->save(['sendinblue_folder_id' => 0]);
            $this->getListExternalSendingMethod($listId, $mailId);

            return;
        }

        $sendinblueLists[$mailId] = $response['id'];

        $this->config->save(['list_sendinblue' => json_encode($sendinblueLists)]);
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

        $this->config->save(['sendinblue_folder_id' => $response['id']]);

        return $response['id'];
    }

    public function deleteList($mailId)
    {
        $listId = 0;
        $this->getListExternalSendingMethod($listId, $mailId, true);

        if (empty($listId)) return false;

        $this->callApiSendingMethod(plgAcymSendinblue::SENDING_METHOD_API_URL.'contacts/lists/'.$listId, [], $this->headers, 'DELETE');

        return true;
    }
}
