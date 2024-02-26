<?php

namespace AcyMailing\FrontControllers\Api;

use AcyMailing\Classes\ListClass;
use AcyMailing\Classes\UserClass;

trait Subscription
{
    public function getUserSubscriptionById(): void
    {
        $userId = acym_getVar('int', 'userId', 0);

        if (empty($userId)) {
            // Handle the case where 'userId' is not present in the request
            $this->sendJsonResponse(['message' => 'User ID not provided in the query parameters.'], 422);
        }

        $userClass = new UserClass();
        $userSubscriptions = $userClass->getUserSubscriptionById($userId);

        $this->sendJsonResponse(array_values($userSubscriptions));
    }

    public function getUnsubscribedUsersFromLists(): void
    {
        $listIds = acym_getVar('array', 'listIds', []);
        $offset = acym_getVar('int', 'offset', 0);
        $limit = acym_getVar('int', 'limit', 100);
        $connector = acym_getVar('bool', 'connector', false);

        if (empty($listIds)) {
            $this->sendJsonResponse(['message' => 'List IDs not provided in the query parameters.'], 422);
        }

        acym_arrayToInteger($listIds);

        $options = [
            'listIds' => $listIds,
            'offset' => $offset,
            'limit' => $limit,
            'status' => 0,
        ];

        if ($connector) {
            $lastTriggerDate = $this->config->get('connector_trigger_getUnsubscribedUsersFromLists');
            $this->config->save(['connector_trigger_getUnsubscribedUsersFromLists' => date('Y-m-d H:i:s')]);

            // If this is the first time zapier calls, or if the trigger has been halted for more than 1 day, we don't send the users to "init" the trigger
            if (empty($lastTriggerDate) || $lastTriggerDate < date('Y-m-d H:i:s', strtotime('-1 day'))) {
                $this->sendJsonResponse([]);
            }

            $options['unsubscribed_after'] = $lastTriggerDate;
        }

        $listClass = new ListClass();
        $users = $listClass->getSubscribersForList($options);

        foreach ($users as $i => $oneUser) {
            $users[$i] = $this->removeExtraColumns(self::TYPE_USER, $oneUser);
        }

        $this->sendJsonResponse(array_values($users));
    }

    /**
     * Get all subscribers from one or more mailing lists.
     */
    public function getSubscribersFromLists(): void
    {
        $listIds = acym_getVar('array', 'listIds', []);
        $offset = acym_getVar('int', 'offset', 0);
        $limit = acym_getVar('int', 'limit', 100);
        $connector = acym_getVar('bool', 'connector', false);

        if (empty($listIds)) {
            $this->sendJsonResponse(['message' => 'List IDs not provided in the query parameters.'], 422);
        }

        acym_arrayToInteger($listIds);

        $options = [
            'listIds' => $listIds,
            'offset' => $offset,
            'limit' => $limit,
            'status' => 1,
        ];

        if ($connector) {
            $lastTriggerDate = $this->config->get('zapier_trigger_getSubscribersFromLists');
            $this->config->save(['zapier_trigger_getSubscribersFromLists' => date('Y-m-d H:i:s')]);

            // If this is the first time zapier calls, or if the trigger has been halted for more than 1 day, we don't send the users to "init" the trigger
            if (empty($lastTriggerDate) || $lastTriggerDate < date('Y-m-d H:i:s', strtotime('-1 day'))) {
                $this->sendJsonResponse([]);
            }

            $options['subscribed_after'] = $lastTriggerDate;
        }

        $listClass = new ListClass();
        $users = $listClass->getSubscribersForList($options);

        foreach ($users as $i => $oneUser) {
            $users[$i] = $this->removeExtraColumns(self::TYPE_USER, $oneUser);
        }

        $this->sendJsonResponse($users);
    }

    public function subscribeUsers()
    {
        $decodedData = acym_getJsonData();

        if (!isset($decodedData['emails'])) {
            $this->sendJsonResponse(['message' => 'Emails not provided in the request body.'], 422);
        }

        if (!isset($decodedData['listIds'])) {
            $this->sendJsonResponse(['message' => 'List IDs not provided in the request body.'], 422);
        }

        $listIds = is_array($decodedData['listIds']) ? $decodedData['listIds'] : [$decodedData['listIds']];
        acym_arrayToInteger($listIds);

        $userClass = new UserClass();
        $userClass->sendWelcomeEmail = $decodedData['sendWelcomeEmail'] ?? true;

        $errors = [];
        if (is_string($decodedData['emails'])) {
            $decodedData['emails'] = [$decodedData['emails']];
        }
        foreach ($decodedData['emails'] as $oneEmail) {
            $user = $userClass->getOneByEmail($oneEmail);

            if (empty($user)) {
                $errors[] = $oneEmail;
                continue;
            }

            $userClass->subscribe($user->id, $listIds, $decodedData['trigger'] ?? true);
        }
        $errorMsg = [];
        if (count($errors) === count($decodedData['emails'])) {
            $this->sendJsonResponse(['message' => 'No users found.'], 404);
        }

        $result = ['message' => 'Users subscribed.'];
        if (!empty($userClass->errors)) {
            $errorMsg[] = implode(', ', $userClass->errors);
        }
        if (!empty($errors)) {
            $errorMsg[] = acym_translation_sprintf('ACYM_SEND_ERROR_USER', implode(', ', $errors));
        }
        if (!empty($errorMsg)) {
            $result['errors'] = implode(', ', $errorMsg);
        }
        $this->sendJsonResponse($result);
    }

    /**
     * Unsubscribe users from one or more mailing lists.
     */
    public function unsubscribeUsers(): void
    {
        $decodedData = acym_getJsonData();

        if (!isset($decodedData['emails'])) {
            $this->sendJsonResponse(['message' => 'Emails not provided in the request body.'], 422);
        }

        if (!isset($decodedData['listIds'])) {
            $this->sendJsonResponse(['message' => 'List IDs not provided in the request body.'], 422);
        }

        $listIds = is_array($decodedData['listIds']) ? $decodedData['listIds'] : [$decodedData['listIds']];
        acym_arrayToInteger($listIds);

        $userClass = new UserClass();
        $userClass->sendUnsubscribeEmail = $decodedData['sendUnsubscribeEmail'] ?? true;

        $errors = [];
        if (is_string($decodedData['emails'])) {
            $decodedData['emails'] = [$decodedData['emails']];
        }
        foreach ($decodedData['emails'] as $oneEmail) {
            $user = $userClass->getOneByEmail($oneEmail);

            if (empty($user)) {
                $errors[] = $oneEmail;
                continue;
            }

            $userClass->triggers = $decodedData['trigger'] ?? true;
            $userClass->unsubscribe($user->id, $listIds);
        }

        $errorMsg = [];

        if (count($errors) === count($decodedData['emails'])) {
            $this->sendJsonResponse(['message' => 'No users found.'], 404);
        }

        $result = ['message' => 'Users unsubscribed.'];
        if (!empty($userClass->errors)) {
            $errorMsg[] = $userClass->errors;
        }
        if (!empty($errors)) {
            $errorMsg[] = acym_translation_sprintf('ACYM_SEND_ERROR_USER', implode(', ', $errors));
        }
        if (!empty($errorMsg)) {
            $result['errors'] = implode(', ', $errorMsg);
        }
        $this->sendJsonResponse($result);
    }
}
