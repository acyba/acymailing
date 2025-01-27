<?php

namespace AcyMailing\FrontControllers;

use AcyMailing\FrontControllers\Api\Campaigns;
use AcyMailing\FrontControllers\Api\Emails;
use AcyMailing\FrontControllers\Api\Lists;
use AcyMailing\FrontControllers\Api\Statistics;
use AcyMailing\FrontControllers\Api\Subscription;
use AcyMailing\FrontControllers\Api\Templates;
use AcyMailing\FrontControllers\Api\Users;
use AcyMailing\FrontControllers\Api\FollowUp;
use AcyMailing\Core\AcymController;

class ApiController extends AcymController
{
    use Users;
    use Lists;
    use Campaigns;
    use Emails;
    use Subscription;
    use Statistics;
    use Templates;
    use FollowUp;

    private const AVAILABLE_TMPL_COLUMNS = [
        'name',
        'subject',
        'body',
        'from_name',
        'from_email',
        'reply_to_name',
        'reply_to_email',
        'headers',
        'preheader',
    ];

    private const TYPE_TEMPLATE = 'template';
    private const TYPE_MAIL = 'mail';
    private const TYPE_USER = 'user';

    private const COLUMNS_TO_RETURN_FOR_GET = [
        self::TYPE_TEMPLATE => [
            'id',
            'name',
            'creation_date',
            'drag_editor',
            'subject',
            'body',
            'settings',
            'stylesheet',
            'from_name',
            'from_email',
            'reply_to_name',
            'reply_to_email',
            'headers',
            'preheader',
        ],
        self::TYPE_MAIL => [
            'id',
            'name',
            'creation_date',
            'subject',
        ],
        self::TYPE_USER => [
            'id',
            'name',
            'email',
            'creation_date',
            'active',
            'source',
            'confirmed',
            'confirmation_date',
            'confirmation_ip',
            'tracking',
            'language',
            'last_sent_date',
            'last_open_date',
            'last_click_date',
            'status',
            'subscription_date',
            'unsubscribe_date',
        ],
    ];

    public function __construct()
    {
        parent::__construct();

        if ($this->config->get('rest_api', 0) != 1) {
            $this->sendJsonResponse(['message' => 'The REST API is not activated.'], 403);
        }

        $methodTasks = [
            'GET' => [
                'authenticate',
                'getSubscribersFromLists',
                'getUnsubscribedUsersFromLists',
                'getUserSubscriptionById',
                'getUsers',
                'getLists',
                'getCampaigns',
                'getCampaignStatistics',
                'getCampaignStatisticsDetailed',
                'getCampaignStatisticsClicks',
                'getCampaignStatisticsLinks',
                'getCampaignById',
                'getOneTemplate',
                'getTemplates',
                'getEmails',
                'getFollowUpById',
                'getFollowUps',
                'getFollowupStatistics',
            ],
            'POST' => [
                'createList',
                'insertEmailInQueue',
                'sendEmailToSingleUser',
                'createOrUpdateCampaign',
                'createOrUpdateUser',
                'unsubscribeUsers',
                'subscribeUsers',
                'createTemplate',
                'updateTemplate',
                'createOrUpdateFollowUp',
                'attachEmailToFollowUp',
                'sendCampaign',
            ],
            'DELETE' => [
                'deleteUser',
                'deleteList',
                'deleteCampaign',
                'deleteTemplate',
                'deleteEmailFromFollowUp',
                'deleteFollowUp',
            ],
        ];

        $taskCalled = acym_getVar('string', 'task', '');
        $methodUsed = acym_getVar('string', 'REQUEST_METHOD', '', 'SERVER');

        if ($methodUsed === 'POST' && acym_getHeader('Content-Type') !== 'application/json') {
            $this->sendJsonResponse(['message' => 'Content-Type must be application/json'], 415);
        }

        $existingTask = false;
        foreach ($methodTasks as $method => $tasks) {
            $existingTask = $existingTask || in_array($taskCalled, $tasks);

            foreach ($tasks as $task) {
                if ($taskCalled === $task && $methodUsed !== $method) {
                    $this->sendJsonResponse(['message' => 'Method not allowed.'], 405);
                }
                $this->publicFrontTasks[] = $task;
            }
        }

        if (!$existingTask) {
            $this->sendJsonResponse(['message' => 'Task not allowed.'], 403);
        }

        $this->authenticate($taskCalled === 'authenticate');
    }

    /**
     * Validate the API key and check the user's license before processing requests.
     */
    private function authenticate(bool $isRouteAuthenticate = false): void
    {
        $apiKey = acym_getHeader('Api-Key');

        if (empty($apiKey)) {
            $apiKey = acym_getHeader('API-KEY');
            if (empty($apiKey)) {
                $apiKey = acym_getHeader('api-key');
                if (empty($apiKey)) {
                    $this->sendJsonResponse(['message' => 'Header Api-Key is missing'], 401);
                }
            }
        }

        $licenseKey = $this->config->get('license_key');

        if ($licenseKey !== $apiKey) {
            $this->sendJsonResponse(['message' => 'License key is invalid'], 401);
        }

        if (!acym_isLicenseValidWeekly()) {
            $this->sendJsonResponse(['message' => 'License is expired'], 401);
        }

        if ($isRouteAuthenticate) {
            $this->sendJsonResponse(
                [
                    'message' => 'Successfully authenticated',
                    'siteName' => acym_getCMSConfig('sitename'),
                ]
            );
        }
    }

    /**
     * Send a JSON success response and exit.
     *
     * @param mixed $data The data to include in the response.
     */
    private function sendJsonResponse(array $data, int $statusCode = 200): void
    {
        acym_header('Content-Type: application/json');
        http_response_code($statusCode);
        echo json_encode($data);
        exit;
    }

    /**
     * Removes any extra columns from objects returned by the API on GET routes
     *
     * @param string $type
     * @param object $element
     *
     * @return object
     */
    private function removeExtraColumns(string $type, object $element): object
    {
        if (!in_array($type, array_keys(self::COLUMNS_TO_RETURN_FOR_GET))) {
            return $element;
        }

        foreach ($element as $key => $value) {
            if (!in_array($key, self::COLUMNS_TO_RETURN_FOR_GET[$type])) {
                unset($element->$key);
            }
        }

        return $element;
    }
}
