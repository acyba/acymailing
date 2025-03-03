<?php

namespace AcyMailing\FrontControllers\Api;

use AcyMailing\Classes\CampaignClass;
use AcyMailing\Classes\MailClass;

trait Campaigns
{
    /**
     * Create a new campaign in AcyMailing.
     */
    public function createOrUpdateCampaign(): void
    {
        try {
            $decodedData = acym_getJsonData();

            if (!isset($decodedData['subject']) || !isset($decodedData['name'])) {
                $this->sendJsonResponse(['message' => 'Missing required parameters \'subject\' or \'name\''], 422);
            }

            $campaignClass = new CampaignClass();
            $mailClass = new MailClass();

            $campaign = new \stdClass();
            $mail = new \stdClass();

            if (isset($decodedData['campaignId'])) {
                $campaign = $campaignClass->getOneById($decodedData['campaignId']);

                if (empty($campaign)) {
                    $this->sendJsonResponse(['message' => 'Campaign not found.'], 404);
                }

                $mail = $mailClass->getOneById($campaign->mail_id);

                if (empty($mail)) {
                    $mail = new \stdClass();
                }
            }

            // Create the Mail element
            $mail->subject = $decodedData['subject'] ?? (!empty($mail->subject) ? $mail->subject : '');
            $mail->name = $decodedData['name'] ?? (!empty($mail->name) ? $mail->name : '');
            $mail->body = $decodedData['body'] ?? (!empty($mail->body) ? $mail->body : '');
            $mail->from_name = $decodedData['from_name'] ?? (!empty($mail->from_name) ? $mail->from_name : '');
            $mail->from_email = $decodedData['from_email'] ?? (!empty($mail->from_email) ? $mail->from_email : '');
            $mail->reply_to_name = $decodedData['reply_to_name'] ?? (!empty($mail->reply_to_name) ? $mail->reply_to_name : '');
            $mail->reply_to_email = $decodedData['reply_to_email'] ?? (!empty($mail->reply_to_email) ? $mail->reply_to_email : '');
            $mail->bounce_email = $decodedData['bounce_email'] ?? (!empty($mail->bounce_email) ? $mail->bounce_email : '');
            $mail->bcc = $decodedData['bcc'] ?? (!empty($mail->bcc) ? $mail->bcc : '');

            $mail->preheader = $decodedData['preheader'] ?? (!empty($mail->preheader) ? $mail->preheader : '');
            $mail->type = MailClass::TYPE_STANDARD;
            $mail->drag_editor = 0;

            $mailId = $mailClass->save($mail);

            if (empty($mailId)) {
                $this->sendJsonResponse(['message' => 'Could not save the mail information: '.implode(' | ', $mailClass->errors)], 500);
            }

            // Create the Campaign element
            $campaign->draft = 1;
            $campaign->active = 0;
            $campaign->sent = 0;
            $campaign->mail_id = $mailId;
            if (!isset($decodedData['sending_type'])) {
                $campaign->sending_type = CampaignClass::SENDING_TYPE_NOW;
            } else {
                switch ($decodedData['sending_type']) {
                    case 'now':
                        $campaign->sending_type = CampaignClass::SENDING_TYPE_NOW;
                        break;
                    case 'scheduled':
                        if (!isset($decodedData['sending_date'])) {
                            $this->sendJsonResponse(['message' => '\'sending_date\' not provided in the request body.'], 422);
                        }
                        $campaign->sending_date = acym_date($decodedData['sending_date'], 'Y-m-d H:i:s', false);
                        $campaign->sending_type = CampaignClass::SENDING_TYPE_SCHEDULED;
                        break;
                    case 'auto':
                        if (!isset($decodedData['frequency'])) {
                            $this->sendJsonResponse(['message' => '\'frequency\' not provided in the request body.'], 422);
                        }
                        $sendingParams = [];

                        if ($decodedData['frequency'] === 'cron') {
                            $sendingParams['trigger_type'] = 'asap';
                        } elseif ($decodedData['frequency'] === 'every') {
                            if (!isset($decodedData['frequency_options'])) {
                                $this->sendJsonResponse(['message' => '\'frequency_options\' not provided in the request body.'], 422);
                            }

                            if (!isset($decodedData['frequency_options']['unit']) || !isset($decodedData['frequency_options']['value'])) {
                                $this->sendJsonResponse(['message' => 'Invalid frequency options.'], 422);
                            }

                            if (!is_numeric($decodedData['frequency_options']['value']) || $decodedData['frequency_options']['value'] < 1) {
                                $this->sendJsonResponse(['message' => 'Invalid frequency option value.'], 422);
                            }

                            $sendingParams['trigger_type'] = 'every';
                            $sendingParams['every'] = [];
                            if (!in_array($decodedData['frequency_options']['unit'], ['hour', 'day', 'week', 'month'])) {
                                $this->sendJsonResponse(['message' => 'Invalid frequency unit.'], 422);
                            }
                            $sendingParams['every']['type'] = $this->getUnitSecond($decodedData['frequency_options']['unit']);
                            $sendingParams['every']['number'] = $decodedData['frequency_options']['value'];
                        } else {
                            $this->sendJsonResponse(['message' => 'Invalid frequency.'], 422);
                        }

                        if (isset($decodedData['start_date'])) {
                            $sendingParams['start_date'] = acym_date($decodedData['start_date'], 'Y-m-d H:i:s', false);
                        }

                        $sendingParams['need_confirm_to_send'] = 1;

                        if (isset($decodedData['need_confirm']) && in_array($decodedData['need_confirm'], [0, 1])) {
                            $sendingParams['need_confirm_to_send'] = $decodedData['need_confirm'];
                        }

                        $campaign->sending_params = $sendingParams;

                        $campaign->sending_type = CampaignClass::SENDING_TYPE_AUTO;
                        break;
                    default:
                        $this->sendJsonResponse(['message' => 'Invalid sending type.'], 422);
                }
            }

            $campaignId = $campaignClass->save($campaign);

            if (empty($campaignId)) {
                $this->sendJsonResponse(['message' => 'Could not save the campaign: '.implode(' | ', $campaignClass->errors)], 500);
            }

            $lists = $decodedData['listIds'] ?? [];
            $unselectedLists = [];
            if (!empty($lists)) {
                $previousLists = $campaignClass->getListsByMailId($campaign->mail_id);
                $unselectedLists = array_diff($previousLists, $lists);
            }
            $campaignClass->manageListsToCampaign($lists, $mailId, $unselectedLists);

            $this->sendJsonResponse(['campaignId' => $campaignId], isset($decodedData['campaignId']) ? 201 : 200);
        } catch (\Exception $e) {
            $this->sendJsonResponse(['message' => 'Error: '.$e->getMessage()], 500);
        }
    }

    private function getUnitSecond(string $unit): int
    {
        $unitSecondMatching = [
            'hour' => 3600,
            'day' => 86400,
            'week' => 604800,
            'month' => 2628000,
        ];

        return $unitSecondMatching[$unit];
    }

    public function getCampaignById(): void
    {
        $campaignId = acym_getVar('string', 'campaignId', '');

        if (empty($campaignId)) {
            $this->sendJsonResponse(['message' => 'campaignId not provided'], 422);
        }

        $campaignClass = new CampaignClass();
        $campaign = $campaignClass->getOneByIdWithMail($campaignId);

        if (empty($campaign)) {
            $this->sendJsonResponse(['message' => 'Campaign not found.'], 404);
        }

        $this->sendJsonResponse([$campaign]);
    }

    public function deleteCampaign(): void
    {
        $campaignId = acym_getVar('string', 'campaignId', '');

        if (empty($campaignId)) {
            $this->sendJsonResponse(['message' => 'campaignId not provided'], 422);
        }

        $campaignClass = new CampaignClass();

        $campaign = $campaignClass->getOneById($campaignId);

        if (empty($campaign)) {
            $this->sendJsonResponse(['message' => 'Campaign not found.'], 404);
        }

        $isCampaignDeleted = $campaignClass->delete($campaignId);

        if (empty($isCampaignDeleted)) {
            $responseData = ['message' => 'Could not delete campaign'];
            if (!empty($campaignClass->errors)) {
                $responseData['errors'] = $campaignClass->errors;
            }
            $this->sendJsonResponse($responseData, 500);
        }

        $this->sendJsonResponse(['message' => 'Campaign deleted successfully.']);
    }

    public function sendCampaign(): void
    {
        $campaignId = acym_getVar('int', 'campaignId', 0);

        if (empty($campaignId)) {
            $this->sendJsonResponse(['message' => 'campaignId not provided'], 422);
        }

        $campaignClass = new CampaignClass();
        $campaign = $campaignClass->getOneById($campaignId);

        if (empty($campaign)) {
            $this->sendJsonResponse(['message' => 'Campaign not found.'], 404);
        }

        $numberOfUsers = $campaignClass->send($campaignId);

        if (empty($numberOfUsers)) {
            $responseData = ['message' => 'Could not send campaign'];
            if (!empty($campaignClass->errors)) {
                $responseData['errors'] = $campaignClass->errors;
            }
            $this->sendJsonResponse($responseData, 500);
        }

        $this->sendJsonResponse(['message' => 'Campaign sent successfully.']);
    }

    public function getCampaigns(): void
    {
        $campaignClass = new CampaignClass();
        $campaigns = $campaignClass->getXCampaigns(
            [
                'offset' => acym_getVar('int', 'offset', 0),
                'limit' => acym_getVar('int', 'limit', 100),
                'filters' => acym_getVar('array', 'filters', []),
            ]
        );

        $this->sendJsonResponse(array_values($campaigns));
    }
}
