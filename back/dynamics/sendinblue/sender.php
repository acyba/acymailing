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

        if (!empty($response['senders']) && !empty($mail->from_email)) {
            foreach ($response['senders'] as $sender) {
                if ($sender['email'] == $mail->from_email) return $data;
            }
        }

        $response = $this->callApiSendingMethod('senders', $data, $this->headers, 'POST');

        if (!empty($response['code'])) return false;

        return $data;
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
