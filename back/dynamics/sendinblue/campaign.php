<?php

class SendinblueCampaign extends SendinblueClass
{
    var $sender;
    var $user;
    var $list;
    var $headers;

    public function __construct(&$plugin, $headers, $sender, $user, $list)
    {
        parent::__construct($plugin, $headers);
        $this->sender = $sender;
        $this->user = $user;
        $this->list = $list;
    }

    public function createNewCampaign($mail)
    {
        $listIdSendinblue = 0;
        $this->list->getListExternalSendingMethod($listIdSendinblue, $mail->id);

        $data = [
            'sender' => $this->sender->getSender($mail),
            'name' => 'AcyMailing Mail '.$mail->id,
            'htmlContent' => '<html>{% autoescape off %}{{ contact.'.$this->user->getAttributeName($mail->id).' }}{% endautoescape %}</html>',
            'scheduledAt' => date('c', time()),
            'subject' => $mail->subject,
            'replyTo' => $this->sender->getReplyToEmail($mail),
            'recipients' => [
                'listIds' => [$listIdSendinblue],
            ],
            'inlineImageActivation' => empty($this->config->get('embed_images', 0)) ? false : true,
        ];

        $this->callApiSendingMethod('emailCampaigns', $data, $this->headers, 'POST');
    }

    public function cleanCampaigns()
    {
        $cleanFrequency = $this->config->get(plgAcymSendinblue::SENDING_METHOD_ID.'_clean_frequency', 2592000);
        $lastClean = $this->config->get(plgAcymSendinblue::SENDING_METHOD_ID.'_last_clean', 0);

        $time = time();

        if (!empty($lastClean) && $lastClean < ($time + $cleanFrequency)) return true;

        $response = $this->callApiSendingMethod(
            plgAcymSendinblue::SENDING_METHOD_API_URL.'emailCampaigns?status=sent&limit=500&offset=0&sort=desc',
            [],
            $this->headers,
            'GET'
        );

        if (empty($response['campaigns'])) return true;

        $startSendDate = date('c', $time - $cleanFrequency);

        foreach ($response['campaigns'] as $campaign) {
            //If it's not an AcyMailing campaign we get out
            if (strpos($campaign['name'], 'AcyMailing Mail ') === false) continue;

            //If it is a recent campaign we don't delete it
            $sendDate = strtotime($campaign['sentDate']);
            if ($sendDate > $startSendDate) continue;

            //We get the id of the email
            $id = intval(str_replace('AcyMailing Mail ', '', $campaign['name']));

            //We delete what we created in Sendinblue
            $this->user->deleteAttribute($id);
            $this->list->deleteList($id);
            $this->deleteCampaign($campaign['id']);
        }

        return true;
    }

    public function deleteCampaign($sendinblueCampaignId)
    {
        $this->callApiSendingMethod(plgAcymSendinblue::SENDING_METHOD_API_URL.'emailCampaigns/'.$sendinblueCampaignId, [], $this->headers, 'DELETE');
    }
}
