<?php

use AcyMailing\Classes\CampaignClass;
use AcyMailing\Classes\MailClass;
use AcyMailing\Helpers\MailerHelper;
use AcyMailing\Core\AcymPlugin;

class plgAcymElasticemail extends AcymPlugin
{
    const SENDING_METHOD_ID = 'elasticemail';
    const SENDING_METHOD_NAME = 'Elastic Email';
    const SENDING_METHOD_API_URL = 'https://api.elasticemail.com/v4/';

    private $mailClass;
    private $campaignClass;
    private $attachmentsFetched = false;
    private $uploadedAttachments = [];

    public function __construct()
    {
        parent::__construct();
        $this->pluginDescription->name = self::SENDING_METHOD_NAME;
    }

    public function onAcymGetSendingMethods(&$data, $isMailer = false)
    {
        $data['sendingMethods'][self::SENDING_METHOD_ID] = [
            'name' => $this->pluginDescription->name,
            'image' => ACYM_IMAGES.'mailers/elasticemail.png',
        ];
    }

    public function onAcymGetSendingMethodsHtmlSetting(&$data)
    {
        ob_start();
        ?>
        <div class="send_settings grid-x cell" id="<?php echo self::SENDING_METHOD_ID; ?>_settings">
            <div class="cell grid-x acym_vcenter acym__sending__methods__one__settings">
                <label for="<?php echo self::SENDING_METHOD_ID; ?>_password" class="cell shrink margin-right-1">
                    <?php echo acym_translation('ACYM_API_KEY'); ?>
                </label>
                <?php
                echo $this->getLinks(
                    'https://elasticemail.com/referral-reward?r=7b884a0b-b979-4473-8803-06ae39d76599',
                    'https://elasticemail.com/email-api-pricing?r=7b884a0b-b979-4473-8803-06ae39d76599'
                );
                ?>
                <input id="<?php echo self::SENDING_METHOD_ID; ?>_password"
                       class="cell"
                       type="text"
                       name="config[<?php echo self::SENDING_METHOD_ID; ?>_password]"
                       value="<?php echo str_repeat('*', strlen($this->config->get(self::SENDING_METHOD_ID.'_password'))); ?>">
                <?php echo $this->getTestCredentialsSendingMethodButton(self::SENDING_METHOD_ID); ?>
            </div>
        </div>
        <?php
        $data['sendingMethodsHtmlSettings'][self::SENDING_METHOD_ID] = ob_get_clean();
    }

    public function onAcymTestCredentialSendingMethod($sendingMethod, $credentials)
    {
        if ($sendingMethod !== self::SENDING_METHOD_ID) {
            return;
        }

        $response = $this->callApiSendingMethod(
            self::SENDING_METHOD_API_URL.'files?limit=1',
            [],
            $this->getAuthenticationHeaders($credentials),
        );

        if (!empty($response['error_curl'])) {
            acym_sendAjaxResponse(acym_translationSprintf('ACYM_ERROR_OCCURRED_WHILE_CALLING_API', $response['error_curl']), [], false);
        } elseif (!empty($response['Error'])) {
            $message = $response['Error'] === 'APIKey Expired'
                ? acym_translation('ACYM_AUTHENTICATION_FAILS_WITH_API_KEY')
                : acym_translationSprintf(
                    'ACYM_API_RETURN_THIS_ERROR',
                    $response['Error']
                );
            acym_sendAjaxResponse($message, [], false);
        } else {
            acym_sendAjaxResponse(acym_translation('ACYM_API_KEY_CORRECT'));
        }
    }

    public function onAcymSendEmail(
        array        &$response,
        MailerHelper $mailerHelper,
        array        $to,
        array        $from,
        array        $reply_to,
        array        $bcc = [],
        array        $attachments = [],
                     $sendingMethodListParams = []
    ): void {
        // https://elasticemail.com/developers/api-documentation/rest-api#operation/emailsTransactionalPost
        if ($mailerHelper->externalMailer !== self::SENDING_METHOD_ID) {
            return;
        }

        $data = [
            'recipients' => new stdClass(),
            'content' => new stdClass(),
        ];

        $this->handleAddresses($data, $mailerHelper, $from, $reply_to, $to, $bcc);
        $this->handleEmailContent($data, $mailerHelper);
        $this->handleAttachments($data, $attachments);
        $this->handleHeaders($data, $mailerHelper);
        $this->handleGA($data, $mailerHelper);

        $responseMailer = $this->callApiSendingMethod(
            self::SENDING_METHOD_API_URL.'emails/transactional',
            $data,
            $this->getAuthenticationHeaders(),
            'POST'
        );

        if (!empty($responseMailer['Error'])) {
            $response['error'] = true;
            $response['message'] = $responseMailer['Error'];
        } else {
            $response['error'] = false;
        }
    }

    public function onAcymSendingMethodOptions(&$data)
    {
        $data['embedImage'][self::SENDING_METHOD_ID] = false;
    }

    private function handleAddresses(array &$data, MailerHelper $mailerHelper, array $from, array $reply_to, array $to, array $bcc): void
    {
        $data['recipients']->to = [$to['email']];

        if (!empty($bcc)) {
            $data['recipients']->bcc = [$bcc[0][0]];
        }

        $data['content']->From = empty($from['name']) ? $from['email'] : $from['name'].' <'.$from['email'].'>';
        $data['content']->ReplyTo = empty($reply_to['name']) ? $reply_to['email'] : $reply_to['name'].' <'.$reply_to['email'].'>';

        $bounceAddress = $this->getBounceAddress($mailerHelper);
        if (!empty($bounceAddress)) {
            $data['content']->EnvelopeFrom = $bounceAddress;
        }
    }

    private function handleEmailContent(array &$data, MailerHelper $mailerHelper): void
    {
        $data['content']->Subject = $mailerHelper->Subject;
        $data['content']->Body = [
            (object)[
                'ContentType' => 'HTML',
                'Content' => $mailerHelper->Body,
                'Charset' => 'utf-8',
            ],
        ];

        if (!empty($mailerHelper->AltBody)) {
            $data['content']->Body[] = (object)[
                'ContentType' => 'PlainText',
                'Content' => $mailerHelper->AltBody,
                'Charset' => 'utf-8',
            ];
        }
    }

    private function handleAttachments(array &$data, array $attachments): void
    {
        if (empty($attachments)) {
            return;
        }

        if (!$this->attachmentsFetched) {
            $this->attachmentsFetched = true;

            // Uploaded attachments expire after 35 days by default, so 1000 should be more than enough
            $uploadedAttachments = $this->callApiSendingMethod(
                self::SENDING_METHOD_API_URL.'files?limit=1000',
                [],
                $this->getAuthenticationHeaders()
            );

            if (empty($uploadedAttachments['Error'])) {
                foreach ($uploadedAttachments as $oneAttachment) {
                    $this->uploadedAttachments[] = $oneAttachment['FileName'];
                }
            }
        }

        $data['content']->AttachFiles = [];
        foreach ($attachments as $attachment) {
            $fileName = $this->cleanFileName($attachment[1]);

            if (!in_array($fileName, $this->uploadedAttachments)) {
                $uploadedAttachment = $this->callApiSendingMethod(
                    self::SENDING_METHOD_API_URL.'files',
                    [
                        'BinaryContent' => $attachment['contentEncoded'],
                        'Name' => $fileName,
                        'ContentType' => $attachment[4],
                    ],
                    $this->getAuthenticationHeaders(),
                    'POST'
                );

                if (empty($uploadedAttachment['FileName'])) {
                    continue;
                }

                $this->uploadedAttachments[] = $fileName;
            }

            $data['content']->AttachFiles[] = $fileName;
        }
    }

    private function handleHeaders(array &$data, MailerHelper $mailerHelper): void
    {
        if (empty($mailerHelper->CustomHeader)) {
            return;
        }

        $data['content']->Headers = new stdClass();
        foreach ($mailerHelper->CustomHeader as $oneHeader) {
            $data['content']->Headers->{$oneHeader[0]} = $oneHeader[1];
        }
    }

    private function handleGA(array &$data, MailerHelper $mailerHelper): void
    {
        $trackingSystem = $this->config->get('trackingsystem', 'acymailing');
        if (strpos($trackingSystem, 'google') === false || empty($mailerHelper->mailId)) {
            return;
        }
        $data['content']->Utm = new stdClass();
        $data['content']->Utm->Source = 'newsletter';
        $data['content']->Utm->Medium = 'email';
        $data['content']->Utm->Campaign = acym_getAlias($data['content']->Subject);

        if (empty($this->mailClass)) {
            $this->mailClass = new MailClass();
        }
        $campaignId = $this->mailClass->getCampaignIdByMailId($mailerHelper->mailId);

        if (empty($campaignId)) {
            $data['content']->Utm->Source .= '_'.$mailerHelper->mailId;
        } else {
            if (empty($this->campaignClass)) {
                $this->campaignClass = new CampaignClass();
            }
            $campaign = $this->campaignClass->getOneById($campaignId);

            if (empty($campaign)) {
                return;
            }

            $data['content']->Utm->Source .= '_'.$campaignId;

            if (!empty($campaign->sending_params['utm_source'])) {
                $data['content']->Utm->Source = $campaign->sending_params['utm_source'];
            }

            if (!empty($campaign->sending_params['utm_medium'])) {
                $data['content']->Utm->Medium = $campaign->sending_params['utm_medium'];
            }

            if (!empty($campaign->sending_params['utm_campaign'])) {
                $data['content']->Utm->Campaign = $campaign->sending_params['utm_campaign'];
            }
        }
    }

    private function getAuthenticationHeaders(array $credentials = []): array
    {
        if (
            !isset($credentials[self::SENDING_METHOD_ID.'_password'])
            || (
                !empty($credentials[self::SENDING_METHOD_ID.'_password'])
                && empty(trim($credentials[self::SENDING_METHOD_ID.'_password'], '*'))
            )
        ) {
            return [
                'X-ElasticEmail-ApiKey: '.$this->config->get(self::SENDING_METHOD_ID.'_password'),
                'Content-Type: application/json',
            ];
        }

        return [
            'X-ElasticEmail-ApiKey: '.$credentials[self::SENDING_METHOD_ID.'_password'],
            'Content-Type: application/json',
        ];
    }

    private function cleanFileName(string $fileName): string
    {
        // Replace accents with their non-accented equivalent
        if (function_exists('iconv')) {
            $fileName = iconv('UTF-8', 'ASCII//TRANSLIT', $fileName);
        }

        return preg_replace('#[^a-z0-9\.\-\_]#i', '_', $fileName);
    }
}
