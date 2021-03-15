<?php

class SendinblueTransactional extends SendinblueClass
{
    public function sendTransactionalEmail(&$response, $sendingMethod, $to, $subject, $from, $reply_to, $body, $bcc = [], $attachments = [])
    {
        //https://developers.sendinblue.com/docs/send-a-transactional-email
        if ($sendingMethod != plgAcymSendinblue::SENDING_METHOD_ID) return;
        $data = [
            'sender' => $from,
            'replyTo' => $reply_to,
            'to' => [
                [
                    'email' => $to['email'],
                ],
            ],
            'subject' => $subject,
            'htmlContent' => $body,
        ];

        if (!empty($bcc)) {
            $data['bcc'] = [
                [
                    'email' => $bcc[0][0],
                ],
            ];
        }

        if (!empty($attachments)) {
            $data['attachment'] = [];
            foreach ($attachments as $key => $attachment) {
                $data['attachment'][] = [
                    'content' => $attachment['contentEncoded'],
                    'name' => $attachment[1],
                ];
            }
        }

        $responseMailer = $this->callApiSendingMethod('smtp/email', $data, $this->headers, 'POST');

        if (!empty($responseMailer['code'])) {
            $response['error'] = true;
            $response['message'] = $responseMailer['message'];
        } else {
            $response['error'] = false;
        }
    }
}
