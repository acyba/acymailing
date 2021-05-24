<?php

class SendinblueSender extends SendinblueClass
{
    public function getSender($mail)
    {
        $response = $this->callApiSendingMethod('senders', [], $this->headers);

        if (!empty($response['code'])) return false;

        $data = [
            'name' => empty($mail->from_name) ? $this->config->get('from_name') : $mail->from_name,
            'email' => empty($mail->from_email) ? $this->config->get('from_email') : $mail->from_email,
        ];

        if (!empty($response['senders'])) {
            foreach ($response['senders'] as $sender) {
                if ($sender['email'] != $data['email']) continue;

                return [
                    'name' => $sender['name'],
                    'id' => $sender['id'],
                ];
            }
        }

        $response = $this->callApiSendingMethod('senders', $data, $this->headers, 'POST');

        if (!empty($response['code'])) return false;

        return [
            'name' => $data['name'],
            'id' => $response['id'],
        ];
    }

    public function getReplyToEmail($mail)
    {
        if (!empty($mail->reply_to_email)) return $mail->reply_to_email;

        $fromEmail = $this->config->get('from_email');
        $replyToEmail = $this->config->get('replyto_email');

        if (!empty($this->config->get('from_as_replyto', 0))) return $fromEmail;

        return empty($replyToEmail) ? $fromEmail : $replyToEmail;
    }
}
