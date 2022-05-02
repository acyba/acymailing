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

    public function createNewCampaign($mail, $content)
    {
        $listIdSendinblue = 0;
        $this->list->getListExternalSendingMethod($listIdSendinblue, $mail->id);

        $subjectAttribute = $this->user->getSubjectAttributeName($mail->id);
        $contentAttribute = $this->user->getAttributeName($mail->id);

        if (strpos($content, 'acym__wysid__template') === false || strpos($content, '<body') === false) {
            $commonContent = '<html>{% autoescape off %}{{ contact.'.$contentAttribute.' }}{% endautoescape %}</html>';
        } else {
            $commonContent = preg_replace('#<title>(.*)</title>#Uis', '<title>{{ contact.'.$subjectAttribute.' }}</title>', $content);
            $commonContent = preg_replace('#(<body[^>]*>).*</body>#Uis', '$1{% autoescape off %}{{ contact.'.$contentAttribute.' }}{% endautoescape %}</body>', $commonContent);
        }

        $data = [
            'sender' => $this->sender->getSender($mail),
            'name' => 'AcyMailing Mail '.$mail->id.' ('.$mail->subject.')',
            'htmlContent' => $commonContent,
            'scheduledAt' => date('c', time() + 60),
            'subject' => '{{ contact.'.$this->user->getSubjectAttributeName($mail->id).' }}',
            'replyTo' => $this->sender->getReplyToEmail($mail),
            'recipients' => [
                'listIds' => [$listIdSendinblue],
            ],
            'footer' => '<span style="display: none !important;">{'.acym_translation('ACYM_UNSUBSCRIBE').'}</span>',
            'inlineImageActivation' => !empty($this->config->get('embed_images', 0)),
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
            $this->headers
        );

        if (empty($response['campaigns'])) return true;

        $startSendDate = date('c', $time - $cleanFrequency);

        foreach ($response['campaigns'] as $campaign) {
            //If it is a recent campaign we don't delete it
            $sendDate = strtotime($campaign['sentDate']);
            if ($sendDate > $startSendDate) continue;

            //If it's not an AcyMailing campaign we get out
            preg_match('#AcyMailing Mail ([0-9]+)#is', $campaign['name'], $match);
            if (empty($match)) continue;

            //We get the id of the email
            $id = intval($match[1]);

            if (empty($id)) continue;

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
