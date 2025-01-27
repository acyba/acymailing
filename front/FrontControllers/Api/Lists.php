<?php

namespace AcyMailing\FrontControllers\Api;

use AcyMailing\Classes\ListClass;
use AcyMailing\Classes\UserClass;

trait Lists
{
    public function createList(): void
    {
        $decodedData = acym_getJsonData();

        if (empty($decodedData['name'])) {
            $this->sendJsonResponse(['message' => 'List name missing.'], 422);
        }

        $welcomeId = $decodedData['welcomeId'] ?? null;
        $unsubscribeId = $decodedData['unsubscribeId'] ?? null;
        $color = $decodedData['color'] ?? null;

        $list = new \stdClass();
        $list->name = $decodedData['name'];
        $list->active = $decodedData['active'] ?? 1;
        $list->visible = $decodedData['visible'] ?? 1;

        if (!empty($welcomeId)) {
            $list->welcome_id = $welcomeId;
        }

        if (!empty($unsubscribeId)) {
            $list->unsubscribe_id = $unsubscribeId;
        }

        if (!empty($color)) {
            $list->color = $color;
        }

        $listClass = new ListClass();
        $listId = $listClass->save($list);

        if (empty($listId)) {
            $this->sendJsonResponse(['message' => 'Error creating list.', 'errors' => $listClass->errors], 500);
        }

        $this->sendJsonResponse(['listId' => $listId], 201);
    }

    public function deleteList(): void
    {
        $listId = acym_getVar('int', 'listId', 0);

        if (empty($listId)) {
            $this->sendJsonResponse(['message' => 'List id not provided in the request body.'], 422);
        }

        $listClass = new ListClass();
        $list = $listClass->getOneById($listId);

        if (empty($list)) {
            $this->sendJsonResponse(['message' => 'List not found.'], 404);
        }

        if ($listClass->delete($listId, true)) {
            $this->sendJsonResponse(['message' => 'List deleted.']);
        }

        $this->sendJsonResponse(['message' => 'Error deleting list.'], 500);
    }

    public function getLists(): void
    {
        $listClass = new ListClass();
        $lists = $listClass->getXLists(
            [
                'offset' => acym_getVar('int', 'offset', 0),
                'limit' => acym_getVar('int', 'limit', 100),
                'filters' => acym_getVar('array', 'filters', []),
            ]
        );

        $this->sendJsonResponse(array_values($lists));
    }
}
