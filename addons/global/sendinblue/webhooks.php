<?php

class SendinblueWebhooks extends SendinblueClass
{
    public function addWebhooks()
    {
        $sendingMethod = $this->config->get('mailer_method', 'phpmail');
        if ($sendingMethod != plgAcymSendinblue::SENDING_METHOD_ID) return;

        $webhooks = $this->config->get(plgAcymSendinblue::SENDING_METHOD_ID.'_webhooks_added');
        if (!empty($webhooks)) return;

        $securityKey = $this->config->get(plgAcymSendinblue::SENDING_METHOD_ID.'_webhooks_seckey');
        if (empty($securityKey)) {
            $securityKey = acym_generateKey(40);
            $this->config->save([plgAcymSendinblue::SENDING_METHOD_ID.'_webhooks_seckey' => $securityKey]);
        }

        $types = ['transactional', 'marketing'];

        $webhooks = [];
        foreach ($types as $type) {
            $response = $this->callApiSendingMethod(
                'webhooks?type='.$type,
                [
                    'events' => ['hardBounce', 'spam', 'unsubscribed'],
                    'type' => $type,
                    'url' => acym_frontendLink('frontservices&task=sendinblue&seckey='.$securityKey),
                    'description' => 'Disable users in AcyMailing',
                ],
                $this->headers,
                'POST'
            );

            if (!empty($response['id'])) {
                $webhooks[$type] = $response['id'];
            }
        }

        $this->config->save([plgAcymSendinblue::SENDING_METHOD_ID.'_webhooks_added' => json_encode($webhooks)]);
    }
}
