<?php

namespace AcyMailing\Classes;

use AcyMailing\Helpers\MailerHelper;
use AcyMailing\Helpers\PaginationHelper;
use AcyMailing\Core\AcymClass;

class ListClass extends AcymClass
{
    const LIST_TYPE_STANDARD = 'standard';
    const LIST_TYPE_FRONT = 'front';
    const LIST_TYPE_FOLLOWUP = 'followup';

    public function __construct()
    {
        parent::__construct();

        $this->table = 'list';
        $this->pkey = 'id';
    }

    public function getMatchingElements(array $settings = []): array
    {
        $columns = 'list.*';
        if (!empty($settings['columns'])) {
            foreach ($settings['columns'] as $key => $value) {
                if ($value == 'lists.list_id') {
                    unset($settings['columns'][$key]);
                    continue;
                }
                $settings['columns'][$key] = $key === 'join' ? $value : 'list.'.$value;
            }
            $columns = implode(', ', $settings['columns']);
        }

        $query = 'SELECT '.$columns.' FROM #__acym_list AS list';
        $queryCount = 'SELECT COUNT(list.id) AS total FROM #__acym_list AS list';
        if (!empty($settings['join'])) $query .= $this->getJoinForQuery($settings['join']);
        /*
        This query will return for example:

        array(
            6 => 0,
            2 => 1,
            5 => 2,
            12 => 3
        )

        It would mean that:

        6 lists are disabled and invisible 0 + (0*2) = 0
        2 lists are active and invisible 1 + (0*2) = 1
        5 lists are inactive and visible 0 + (1*2) = 2
        12 lists are active and visible 1 + (1*2) = 3

        So there are 2 + 12 active lists and 5 + 12 visible lists, get it?
        */
        $queryStatus = 'SELECT COUNT(id) AS number, active + (visible*2) AS score FROM #__acym_list AS list';
        $filters = [];
        $listsId = [];

        if (!empty($settings['tag'])) {
            $tagJoin = ' JOIN #__acym_tag AS tag ON list.id = tag.id_element';
            $query .= $tagJoin;
            $queryCount .= $tagJoin;
            $queryStatus .= $tagJoin;
            $filters[] = 'tag.name = '.acym_escapeDB($settings['tag']);
            $filters[] = 'tag.type = "list"';
        }

        if (!empty($settings['search'])) {
            $filters[] = 'list.name LIKE '.acym_escapeDB('%'.$settings['search'].'%');
        }

        if (!empty($settings['creator_id']) && !acym_isAdmin()) {
            $userGroups = acym_getGroupsByUser($settings['creator_id']);
            $groupCondition = '(list.access LIKE "%,'.implode(',%" OR list.access LIKE "%,', $userGroups).',%")';

            $filters[] = 'list.cms_user_id = '.intval($settings['creator_id']).' OR '.$groupCondition.'';
        }

        $filters[] = 'list.type = '.acym_escapeDB(self::LIST_TYPE_STANDARD);

        if (!empty($filters)) {
            $queryStatus .= ' WHERE ('.implode(') AND (', $filters).')';
        }

        if (!empty($settings['status'])) {
            $allowedStatus = [
                'active' => 'active = 1',
                'inactive' => 'active = 0',
                'visible' => 'visible = 1',
                'invisible' => 'visible = 0',
            ];
            if (empty($allowedStatus[$settings['status']])) {
                die('Injection denied');
            }
            $filters[] = 'list.'.$allowedStatus[$settings['status']];
        }

        if (!empty($settings['where'])) {
            $filters[] = $settings['where'];
        }


        if (!empty($filters)) {
            $query .= ' WHERE ('.implode(') AND (', $filters).')';
            $queryCount .= ' WHERE ('.implode(') AND (', $filters).')';
        }

        if (!empty($settings['ordering']) && !empty($settings['ordering_sort_order'])) {
            $query .= ' ORDER BY list.'.acym_secureDBColumn($settings['ordering']).' '.acym_secureDBColumn(strtoupper($settings['ordering_sort_order']));
        }

        if (empty($settings['offset']) || $settings['offset'] < 0) {
            $settings['offset'] = 0;
        }

        if (empty($settings['elementsPerPage']) || $settings['elementsPerPage'] < 1) {
            $pagination = new PaginationHelper();
            $settings['elementsPerPage'] = $pagination->getListLimit();
        }

        $results['elements'] = acym_loadObjectList($query, '', $settings['offset'], $settings['elementsPerPage']);
        if (!empty($settings['join'])) {
            $this->setSelectedList($results['elements'], $settings['join']);
        }
        foreach ($results['elements'] as $i => $oneList) {
            array_push($listsId, $oneList->id);
            $results['elements'][$i]->sendable_users = 0;
            $results['elements'][$i]->unconfirmed_users = 0;
            $results['elements'][$i]->unsubscribed_users = 0;
            $results['elements'][$i]->inactive_users = 0;
            $results['elements'][$i]->newSub = 0;
            $results['elements'][$i]->newUnsub = 0;
        }

        $countUserByList = [];
        $countEvolByList = [];
        if (!empty($listsId) && empty($settings['entitySelect'])) {
            $countUserByList = $this->getSubscribersCountPerStatusByListId($listsId);
            $countEvolByList = $this->getSubscribersEvolutionByList($listsId);
        }

        foreach ($results['elements'] as $i => $list) {
            $results['elements'][$i]->tags = [];
            foreach ($countUserByList as $userList) {
                if ($list->id == $userList->list_id) {
                    $results['elements'][$i]->sendable_users = $userList->sendable_users;
                    $results['elements'][$i]->unconfirmed_users = $userList->unconfirmed_users;
                    $results['elements'][$i]->unsubscribed_users = $userList->unsubscribed_users;
                    $results['elements'][$i]->inactive_users = $userList->inactive_users;
                }
            }
            if (isset($countEvolByList[$list->id]->newSub)) $results['elements'][$i]->newSub = $countEvolByList[$list->id]->newSub;
            if (isset($countEvolByList[$list->id]->newUnsub)) $results['elements'][$i]->newUnsub = $countEvolByList[$list->id]->newUnsub;
        }

        $results['total'] = acym_loadObject($queryCount);

        $listsPerStatus = acym_loadObjectList($queryStatus.' GROUP BY score', 'score');
        for ($i = 0 ; $i < 4 ; $i++) {
            $listsPerStatus[$i] = empty($listsPerStatus[$i]) ? 0 : $listsPerStatus[$i]->number;
        }

        $results['status'] = [
            'all' => array_sum($listsPerStatus),
            'active' => $listsPerStatus[1] + $listsPerStatus[3],
            'inactive' => $listsPerStatus[0] + $listsPerStatus[2],
            'visible' => $listsPerStatus[2] + $listsPerStatus[3],
            'invisible' => $listsPerStatus[0] + $listsPerStatus[1],
        ];

        return $results;
    }

    public function getOneById($id)
    {
        $list = parent::getOneById($id);

        if (!empty($list)) {
            $list->translation = empty($list->translation) ? [] : json_decode($list->translation, true);
        }

        return $list;
    }

    private function setSelectedList(&$elements, $join)
    {
        if (strpos($join, 'join_list') !== false) {
            $listsIds = explode('-', $join);
            $listsIds = $listsIds[1];
            $listsIds = explode(',', $listsIds);
            foreach ($elements as $key => $element) {
                $elements[$key]->list_id = in_array($element->id, $listsIds) ? $element->id : null;
            }
        }
    }

    public function getJoinForQuery($joinType)
    {
        if (strpos($joinType, 'join_mail') !== false) {
            $mailId = explode('-', $joinType);

            return ' LEFT JOIN #__acym_mail_has_list as maillist ON list.id = maillist.list_id AND maillist.mail_id = '.intval($mailId[1]);
        }
        if (strpos($joinType, 'join_user') !== false) {
            $userId = explode('-', $joinType);

            return ' LEFT JOIN #__acym_user_has_list as userlist ON list.id = userlist.list_id AND userlist.status = 1 AND userlist.user_id = '.intval($userId[1]);
        }

        return '';
    }

    public function getListsWithIdNameCount(array $settings): array
    {
        $filters = [];

        if (isset($settings['ids'])) {
            if (empty($settings['ids'])) {
                return ['lists' => [], 'total' => 0];
            } else {
                acym_arrayToInteger($settings['ids']);
                $filters[] = 'list.id IN ('.implode(',', $settings['ids']).')';
            }
        }

        $query = 'SELECT list.id, list.name, list.color, list.active, COUNT(user.id) AS subscribers
            FROM #__acym_list AS list
            LEFT JOIN #__acym_user_has_list AS userList
                ON list.id = userList.list_id
                AND userList.status = 1
            LEFT JOIN #__acym_user AS user 
                ON user.id = userList.user_id
                AND user.active = 1 ';

        if (intval($this->config->get('require_confirmation', 1)) === 1) {
            $query .= ' AND user.confirmed = 1 ';
        }

        $queryCount = 'SELECT COUNT(list.id) FROM #__acym_list AS list';

        if (!acym_isAdmin()) {
            $currentUserId = acym_currentUserId();
            if (empty($currentUserId)) {
                return [
                    'lists' => [],
                    'total' => 0,
                ];
            }

            $userGroups = acym_getGroupsByUser($currentUserId);
            $groupCondition = '(list.access LIKE "%,'.implode(',%" OR list.access LIKE "%,', $userGroups).',%")';

            $filters[] = 'list.cms_user_id = '.intval($currentUserId).' OR '.$groupCondition;
        }

        if (!empty($settings['search'])) {
            $filters[] = 'list.name LIKE '.acym_escapeDB('%'.$settings['search'].'%');
        }

        if (!empty($settings['already'])) {
            acym_arrayToInteger($settings['already']);
            $filters[] = 'list.id NOT IN('.implode(',', $settings['already']).')';
        }
        $filters[] = 'list.type = '.acym_escapeDB(self::LIST_TYPE_STANDARD);

        if (!empty($filters)) {
            $query .= ' WHERE ('.implode(') AND (', $filters).')';
            $queryCount .= ' WHERE ('.implode(') AND (', $filters).')';
        }

        $query .= ' GROUP BY list.id ';

        return [
            'lists' => acym_loadObjectList($query, '', $settings['offset'], $settings['listsPerPage']),
            'total' => (int)acym_loadResult($queryCount),
        ];
    }

    public function getOneByName($name)
    {
        return acym_loadObject('SELECT * FROM #__acym_list WHERE `name` = '.acym_escapeDB($name));
    }

    public function getListsByIds($ids)
    {
        if (!is_array($ids)) $ids = [$ids];
        acym_arrayToInteger($ids);
        if (empty($ids)) return [];

        $query = 'SELECT * FROM #__acym_list WHERE id IN ('.implode(', ', $ids).')';

        return acym_loadObjectList($query);
    }

    /**
     * @return array
     */
    public function getAllListUsers()
    {
        $query = 'SELECT #__acym_user_has_list.list_id, count(*) 
                FROM #__acym_list AS list
                JOIN #__acym_user_has_list
                ON list.id = #__acym_user_has_list.list_id
                JOIN #__acym_user
                ON #__acym_user.id = #__acym_user_has_list.user_id
                GROUP BY list.id';

        return acym_loadObjectList($query);
    }

    public function getSubscribersCountByListId($id)
    {
        $confirmed = $this->config->get('require_confirmation', 1) == 1 ? ' AND users.confirmed = 1 ' : '';

        $query = 'SELECT COUNT(userLists.user_id) AS subscribers
                FROM #__acym_user_has_list AS userLists
                JOIN #__acym_user AS users ON userLists.user_id = users.id
                WHERE userLists.list_id = '.intval($id).'
                    AND userLists.status = 1
                    AND users.active = 1 '.$confirmed.'
                GROUP BY userLists.list_id';

        $result = acym_loadResult($query);

        return empty($result) ? 0 : $result;
    }

    public function getSubscribersCount($listsIds)
    {
        acym_arrayToInteger($listsIds);
        if (empty($listsIds)) return 0;

        $query = 'SELECT COUNT(DISTINCT user.id)
                FROM #__acym_user AS user
                JOIN #__acym_user_has_list AS userList ON user.id = userList.user_id
                WHERE userList.list_id IN ('.implode(",", $listsIds).') AND userList.status = 1 AND user.active = 1';

        if ($this->config->get('require_confirmation', 1) == 1) {
            $query .= ' AND user.confirmed = 1';
        }

        return acym_loadResult($query);
    }

    public function getSubscribersIdsById($listId, $returnUnsubscribed = false)
    {
        $query = 'SELECT user_id FROM #__acym_user_has_list WHERE list_id = '.intval($listId);

        //in case we add the possibility to choose to not display the unsub
        if (!$returnUnsubscribed) {
            $query .= ' AND status = 1';
        }

        return acym_loadResultArray($query);
    }

    public function getSubscribersForList(array $options = [])
    {
        $listIds = $options['listIds'] ?? [];
        $offset = $options['offset'] ?? 0;
        $limit = $options['limit'] ?? 100;
        $status = $options['status'] ?? null;
        $orderBy = $options['orderBy'] ?? '';
        $orderBySort = $options['orderBySort'] ?? '';
        $subscribedAfter = $options['subscribed_after'] ?? null;
        $unsubscribedAfter = $options['unsubscribed_after'] ?? null;

        if (empty($listIds)) {
            return [];
        }

        acym_arrayToInteger($listIds);

        $statusCondition = '';
        if (!is_null($status)) {
            $statusCondition .= ' AND user_list.status = '.intval($status);
        }

        if (!empty($subscribedAfter)) {
            $statusCondition .= ' AND user_list.subscription_date > '.acym_escapeDB($subscribedAfter);
        }

        if (!empty($unsubscribedAfter)) {
            $statusCondition .= ' AND user_list.unsubscribe_date > '.acym_escapeDB($unsubscribedAfter);
        }

        $requestSub = 'SELECT user.*, user_list.status, user_list.subscription_date, user_list.unsubscribe_date FROM #__acym_user AS user';
        $requestSub .= ' LEFT JOIN #__acym_user_has_list AS user_list ON user.id = user_list.user_id';
        $requestSub .= ' WHERE user.active = 1 AND user_list.list_id IN ('.implode(', ', $listIds).')'.$statusCondition;

        if (!empty($orderBy)) {
            if (empty($orderBySort)) $orderBySort = 'desc';
            $requestSub .= ' ORDER BY '.acym_secureDBColumn($orderBy).' '.acym_secureDBColumn($orderBySort);
        }

        return acym_loadObjectList(
            $requestSub,
            '',
            $offset,
            $limit
        );
    }

    public function delete($elements, $force = false)
    {
        if (!is_array($elements)) $elements = [$elements];
        if (!$force) {
            $this->onlyManageableLists($elements);
        }

        if (empty($elements)) return 0;

        foreach ($elements as $id) {
            acym_query('DELETE FROM #__acym_mail_has_list WHERE list_id = '.intval($id));
            acym_query('DELETE FROM #__acym_user_has_list WHERE list_id = '.intval($id));
            acym_query('DELETE FROM #__acym_tag WHERE `id_element` = '.intval($id).' AND `type` = "list"');
        }

        return parent::delete($elements);
    }

    public function synchDeleteCmsList($userId)
    {
        $query = 'SELECT * FROM #__acym_list WHERE type = "'.self::LIST_TYPE_FRONT.'" AND cms_user_id = '.intval($userId);
        $listFrontManagement = acym_loadObject($query);
        if (!empty($listFrontManagement)) $this->delete([$listFrontManagement->id]);
    }

    public function save($list)
    {
        if (isset($list->tags)) {
            $tags = $list->tags;
            unset($list->tags);
        }

        $oldList = null;

        if (empty($list->id)) {
            if (empty($list->cms_user_id)) {
                $list->cms_user_id = acym_currentUserId();
            }

            $list->creation_date = acym_date('now', 'Y-m-d H:i:s', false);
        } else {
            $oldList = $this->getOneById($list->id);
        }

        if (!empty($list->translation)) {
            if (is_array($list->translation)) $list->translation = json_encode($list->translation);
        } else {
            $list->translation = '';
        }

        foreach ($list as $oneAttribute => $value) {
            if (empty($value)) continue;

            $list->$oneAttribute = strip_tags($value);
        }

        if (empty($list->description)) {
            $list->description = '';
        }

        if (!isset($list->access) && empty($list->id)) {
            $list->access = '';
        }

        if (empty($list->id)) {
            acym_trigger('onAcymBeforeListCreate', [&$list]);
        } else {
            acym_trigger('onAcymBeforeListModify', [&$list]);
        }

        $listID = parent::save($list);

        if (!empty($listID) && isset($tags)) {
            $tagClass = new TagClass();
            $tagClass->setTags(TagClass::TYPE_LIST, intval($listID), $tags);
        }

        $list->id = $listID;

        if (is_null($oldList)) {
            acym_trigger('onAcymAfterListCreate', [&$list]);
        } else {
            acym_trigger('onAcymAfterListModify', [&$list, &$oldList]);
        }

        return $listID;
    }

    public function getAllWithIdName(): array
    {
        $endQuery = '';
        if (!acym_isAdmin()) {
            $creatorId = acym_currentUserId();

            $userGroups = acym_getGroupsByUser($creatorId);
            $groupCondition = '(access LIKE "%,'.implode(',%" OR access LIKE "%,', $userGroups).',%")';

            $endQuery = ' AND (cms_user_id = '.intval($creatorId).' OR '.$groupCondition.')';
        }
        $lists = acym_loadObjectList('SELECT id, name FROM #__acym_list WHERE type = '.acym_escapeDB(self::LIST_TYPE_STANDARD).$endQuery, 'id');

        $listsToReturn = [];

        foreach ($lists as $key => $list) {
            $listsToReturn[$key] = $list->name;
        }

        return $listsToReturn;
    }

    public function getAllForSelect($emptyFirst = true, $userFrontID = 0, $needTranslation = false, $needFrontLabel = false)
    {
        $groupCondition = '';
        if (!empty($userFrontID)) {
            $userGroups = acym_getGroupsByUser($userFrontID);
            $groupCondition = ' AND (cms_user_id = '.intval($userFrontID).' OR (access LIKE "%,'.implode(',%" OR access LIKE "%,', $userGroups).',%"))';
        }

        $lists = acym_loadObjectList('SELECT * FROM #__acym_list WHERE type = '.acym_escapeDB(self::LIST_TYPE_STANDARD).$groupCondition, 'id');

        if (acym_isMultilingual() && $needTranslation) {
            $lists = $this->getTranslatedNameDescription($lists);
        }

        $return = [];

        if ($emptyFirst) $return[''] = acym_translation('ACYM_SELECT_A_LIST');

        foreach ($lists as $key => $list) {
            $return[$key] = $needFrontLabel && !empty($list->display_name) ? $list->display_name : $list->name;
        }

        return $return;
    }

    public function getAllWithoutManagement($needTranslation = false)
    {
        $lists = acym_loadObjectList('SELECT * FROM #__acym_list WHERE `type` = '.acym_escapeDB(self::LIST_TYPE_STANDARD), 'id');

        if (acym_isMultilingual() && $needTranslation) {
            $lists = $this->getTranslatedNameDescription($lists);
        }

        return $lists;
    }

    public function getTranslatedNameDescription($lists)
    {
        $currentLanguageTag = acym_getLanguageTag();
        foreach ($lists as $id => $list) {
            if (empty($list->translation)) continue;

            $list->translation = json_decode($list->translation, true);
            if (!empty($list->translation[$currentLanguageTag])) {
                if (!empty($list->translation[$currentLanguageTag]['name'])) {
                    $lists[$id]->name = $list->translation[$currentLanguageTag]['name'];
                }

                if (!empty($list->translation[$currentLanguageTag]['display_name'])) {
                    $lists[$id]->display_name = $list->translation[$currentLanguageTag]['display_name'];
                }

                if (!empty($list->translation[$currentLanguageTag]['description'])) {
                    $lists[$id]->description = $list->translation[$currentLanguageTag]['description'];
                }
            }
        }

        return $lists;
    }

    /**
     * Sends the welcome emails attached to the specified lists
     *
     * @param int   $userID
     * @param array $listIDs
     * @param bool  $forceFront
     */
    public function sendWelcome(int $userID, array $listIDs, bool $forceFront = false)
    {
        if (!$forceFront && acym_isAdmin()) {
            return;
        }

        acym_arrayToInteger($listIDs);
        if (empty($listIDs)) {
            return;
        }

        $messages = acym_loadObjectList('SELECT `welcome_id` FROM #__acym_list WHERE `id` IN ('.implode(',', $listIDs).')  AND `active` = 1');

        if (empty($messages)) {
            return;
        }

        $alreadySent = [];
        $mailerHelper = new MailerHelper();
        $mailerHelper->report = (bool)$this->config->get('welcome_message', 1);
        foreach ($messages as $oneMessage) {
            $mailid = $oneMessage->welcome_id;
            if (empty($mailid)) continue;

            //We don't send twice the same message
            if (isset($alreadySent[$mailid])) {
                continue;
            }

            $mailerHelper->trackEmail = true;
            $mailerHelper->sendOne($mailid, $userID);
            $alreadySent[$mailid] = true;
        }
    }

    /**
     * Sends the unsubscribe emails attached to the specified lists
     *
     * @param $userID
     * @param $listIDs
     */
    public function sendUnsubscribe($userID, $listIDs)
    {
        if (acym_isAdmin()) {
            return;
        }

        acym_arrayToInteger($listIDs);
        if (empty($listIDs)) {
            return;
        }

        $messages = acym_loadObjectList('SELECT `unsubscribe_id` FROM #__acym_list WHERE `id` IN ('.implode(',', $listIDs).')  AND `active` = 1');

        if (empty($messages)) {
            return;
        }

        $alreadySent = [];
        $mailerHelper = new MailerHelper();
        $mailerHelper->report = (bool)$this->config->get('unsub_message', 1);
        foreach ($messages as $oneMessage) {
            if (!empty($oneMessage->unsubscribe_id)) {
                $mailid = $oneMessage->unsubscribe_id;

                //We don't send twice the same message
                if (isset($alreadySent[$mailid])) {
                    continue;
                }

                $mailerHelper->trackEmail = true;
                $mailerHelper->sendOne($mailid, $userID);
                $alreadySent[$mailid] = true;
            }
        }
    }

    public function addDefaultList()
    {
        $listId = acym_loadResult('SELECT `id` FROM #__acym_list LIMIT 1');
        if (empty($listId)) {
            $defaultList = new \stdClass();
            $defaultList->name = 'Newsletters';
            $defaultList->color = '#3366ff';
            $defaultList->description = '';

            $this->save($defaultList);
        }
    }


    public function getTotalSubCount($ids)
    {
        if (empty($ids)) {
            return 0;
        }

        acym_arrayToInteger($ids);
        $this->onlyManageableLists($ids);
        $query = 'SELECT COUNT(DISTINCT hasList.user_id) 
                    FROM #__acym_user_has_list AS hasList 
                    JOIN #__acym_user AS user 
                        ON hasList.user_id = user.id
                    WHERE hasList.status = 1 
                        AND user.active = 1 
                        AND hasList.list_id IN ('.implode(',', $ids).')';


        if ($this->config->get('require_confirmation', 1) == 1) {
            $query .= ' AND user.confirmed = 1 ';
        }

        return intval(acym_loadResult($query));
    }

    /**
     * Get all mails attached to a list
     *
     * @param $listId int
     *
     * @return mixed all mail attached to the list
     */
    public function getMailsByListId($listId)
    {
        $query = 'SELECT mail_id FROM #__acym_mail_has_list WHERE list_id = '.intval($listId);

        return acym_loadResultArray($query);
    }

    public function getSubscribersCountPerStatusByListId($listIds = [])
    {
        $condList = '';
        if (!empty($listIds)) {
            if (!is_array($listIds)) $listIds = [$listIds];
            acym_arrayToInteger($listIds);
            $condList = 'AND userList.list_id IN ('.implode(',', $listIds).')';
        }

        /*
         * score:
         * An unconfirmed and active user will have a score of 0 + 1*2 = 2
         * A confirmed and active user will have a score of 1 + 1*2 = 3
         * A confirmed and inactive user will have a score of 1 + 0*2 = 1
         */
        $query = 'SELECT userList.list_id, COUNT(userList.user_id) AS users, acyuser.confirmed + acyuser.active*2 AS score 
                    FROM #__acym_user_has_list AS userList 
                    JOIN #__acym_user AS acyuser 
                        ON acyuser.id = userList.user_id 
                    WHERE userList.status = 1 
                        '.$condList.' 
                    GROUP BY score, userList.list_id';
        $results = acym_loadObjectList($query);

        $confirmationRequired = $this->config->get('require_confirmation', 1);
        $listsUserStats = [];
        foreach ($results as $oneResult) {
            if (!isset($listsUserStats[$oneResult->list_id])) {
                $listsUserStats[$oneResult->list_id] = $this->initList($oneResult->list_id);
            }

            // Joomla 4 casts the result into an int
            $oneResult->score = (string)$oneResult->score;

            if (in_array($oneResult->score, ['0', '1'])) {
                $listsUserStats[$oneResult->list_id]->inactive_users += $oneResult->users;
            }

            if (in_array($oneResult->score, ['0', '2'])) {
                $listsUserStats[$oneResult->list_id]->unconfirmed_users += $oneResult->users;
            }

            if ($oneResult->score === '3' || ($confirmationRequired != 1 && $oneResult->score == '2')) {
                $listsUserStats[$oneResult->list_id]->sendable_users += $oneResult->users;
            }
        }

        $query = 'SELECT userList.list_id, COUNT(userList.user_id) AS users
                    FROM #__acym_user_has_list AS userList
                    WHERE userList.status = 0
                        '.$condList.'
                    GROUP BY userList.list_id';
        $unsubscribed = acym_loadObjectList($query);

        foreach ($unsubscribed as $oneList) {
            if (!isset($listsUserStats[$oneList->list_id])) {
                $listsUserStats[$oneList->list_id] = $this->initList($oneList->list_id);
            }
            $listsUserStats[$oneList->list_id]->unsubscribed_users = $oneList->users;
        }

        return $listsUserStats;
    }

    public function getSubscribersEvolutionByList($listIds)
    {
        $condList = '';
        if (!empty($listIds)) {
            if (!is_array($listIds)) $listIds = [$listIds];
            acym_arrayToInteger($listIds);
            $condList = ' AND list_id IN ('.implode(',', $listIds).')';
        }
        $dateNow = acym_loadResult('SELECT DATE_SUB(NOW(), INTERVAL 1 MONTH)');
        $queryEvolSub = 'SELECT list_id, COUNT(*) AS newSub FROM #__acym_user_has_list';
        $queryEvolSub .= ' WHERE subscription_date > '.acym_escapeDB($dateNow).' '.$condList;
        $queryEvolSub .= ' GROUP BY list_id';
        $evolSubscibers = acym_loadObjectList($queryEvolSub, 'list_id');

        $queryEvolUnsub = 'SELECT list_id, COUNT(*) AS newUnsub FROM #__acym_user_has_list';
        $queryEvolUnsub .= ' WHERE unsubscribe_date > '.acym_escapeDB($dateNow).' '.$condList;
        $queryEvolUnsub .= ' GROUP BY list_id';
        $newUnsubscribers = acym_loadObjectList($queryEvolUnsub, 'list_id');

        foreach ($newUnsubscribers as $listId => $oneUnsub) {
            if (empty($evolSubscibers[$listId])) $evolSubscibers[$listId] = new \stdClass();
            $evolSubscibers[$listId]->newUnsub = $oneUnsub->newUnsub;
        }

        return $evolSubscibers;
    }

    public function getYearSubEvolutionPerList($listId)
    {
        $listId = intval($listId);
        // Get next month from 1 year ago
        $month = date('n') + 1;
        $year = date('Y') - 1;
        $initDate = $year.'-'.$month.'-01';
        if ($month == 13) {
            $initDate = date('Y').'-01-01';
        }

        // Get new subscribers per month
        $queryEvolSub = 'SELECT MONTH(subscription_date) as monthSub, DATE_FORMAT(subscription_date,"%Y_%m") AS unit, COUNT(user_id) AS nbUser';
        $queryEvolSub .= ' FROM `#__acym_user_has_list`';
        $queryEvolSub .= ' WHERE subscription_date >= "'.$initDate.'" AND list_id = '.$listId;
        $queryEvolSub .= ' GROUP BY unit';
        $evolSubscibers = acym_loadObjectList($queryEvolSub, 'unit');

        // Get unsubscribers per month
        $queryEvolUnsub = 'SELECT MONTH(unsubscribe_date) as monthUnsub, DATE_FORMAT(unsubscribe_date,"%Y_%m") AS unit, COUNT(user_id) AS nbUser';
        $queryEvolUnsub .= ' FROM `#__acym_user_has_list`';
        $queryEvolUnsub .= ' WHERE unsubscribe_date >= "'.$initDate.'" AND list_id = '.$listId;
        $queryEvolUnsub .= ' GROUP BY unit';
        $evolUnsubscibers = acym_loadObjectList($queryEvolUnsub, 'unit');

        return [
            'subscribers' => $evolSubscibers,
            'unsubscribers' => $evolUnsubscibers,
        ];
    }

    private function initList($listId)
    {
        $list = new \stdClass();
        $list->list_id = $listId;
        $list->sendable_users = 0;
        $list->unconfirmed_users = 0;
        $list->inactive_users = 0;
        $list->unsubscribed_users = 0;

        return $list;
    }

    public function getManageableLists()
    {
        $idCurrentUser = acym_currentUserId();
        if (empty($idCurrentUser)) return [];

        $userGroups = acym_getGroupsByUser($idCurrentUser);

        return acym_loadResultArray(
            'SELECT id 
            FROM #__acym_list 
            WHERE cms_user_id = '.intval($idCurrentUser).' 
                OR (access LIKE "%,'.implode(',%" OR access LIKE "%,', $userGroups).',%")'
        );
    }

    public function onlyManageableLists(&$elements)
    {
        if (acym_isAdmin()) return;

        $manageableLists = $this->getManageableLists();
        $elements = array_intersect($elements, $manageableLists);
    }

    public function hasUserAccess($listId): bool
    {
        if (acym_isAdmin()) {
            return true;
        }

        return in_array($listId, $this->getManageableLists());
    }

    public function getfrontManagementList()
    {
        $idCurrentUser = acym_currentUserId();
        if (empty($idCurrentUser)) return 0;

        $frontListId = acym_loadResult('SELECT id FROM #__acym_list WHERE type = '.acym_escapeDB(self::LIST_TYPE_FRONT).' AND cms_user_id = '.intval($idCurrentUser));

        if (!empty($frontListId)) return $frontListId;

        $frontList = new \stdClass();
        $frontList->name = 'frontlist_'.$idCurrentUser;
        $frontList->active = 1;
        $frontList->visible = 0;
        $frontList->cms_user_id = $idCurrentUser;
        $frontList->type = self::LIST_TYPE_FRONT;

        return $this->save($frontList);
    }

    public function setWelcomeUnsubEmail($listIds, $mailId, $type)
    {
        if (!in_array($type, [MailClass::TYPE_WELCOME, MailClass::TYPE_UNSUBSCRIBE]) || empty($mailId)) {
            return false;
        }

        $column = acym_escape($type).'_id';
        $columnsList = acym_getColumns('list');
        if (!in_array($column, $columnsList)) return false;

        if (!is_array($listIds)) {
            if (!empty($listIds)) $listIds = [$listIds];
        }

        if (!$this->removeWelcomeUnsubByMailId($mailId, $listIds, $column)) return false;

        foreach ($listIds as $listId) {

            $list = $this->getOneById($listId);
            if (empty($list)) return false;

            $list->{$column} = $mailId;

            if (!$this->save($list)) return false;
        }

        return true;
    }

    private function removeWelcomeUnsubByMailId($mailId, $listIds, $column)
    {
        if (empty($mailId)) return false;

        $where = $column.' = '.intval($mailId);
        if (!empty($listIds)) $where .= ' AND id NOT IN ('.implode(',', $listIds).')';

        $lists = acym_loadObjectList('SELECT * FROM #__acym_list WHERE '.$where);
        if (empty($lists)) return true;

        foreach ($lists as $list) {
            $list->{$column} = null;

            if (!$this->save($list)) return false;
        }

        return true;
    }

    public function getListIdsByWelcomeUnsub($mailId, $welcome = true)
    {
        $type = $welcome ? 'welcome_id' : 'unsubscribe_id';

        $return = acym_loadResultArray('SELECT id FROM #__acym_list WHERE '.$type.' = '.intval($mailId));

        return empty($return) ? [] : $return;
    }

    public function getUsersForSummaryModal($id, $offset, $limit, $search)
    {
        $whereQuery = ' AND user_list.status = 1 AND user.active = 1';

        if (!empty($search)) {
            $search = acym_escapeDB('%'.$search.'%');
            $whereQuery .= ' AND (user.email LIKE '.$search.' OR user.name LIKE '.$search.' OR user.id LIKE '.$search.') ';
        }

        if ($this->config->get('require_confirmation', '1') === '1') {
            $whereQuery .= ' AND user.confirmed = 1';
        }

        $query = 'SELECT user.email, user.name, user.id
                  FROM #__acym_user as user 
                  JOIN #__acym_user_has_list as user_list ON user.id = user_list.user_id 
                  WHERE user_list.list_id = '.intval($id).$whereQuery.' LIMIT '.intval($offset).', '.intval($limit);


        return acym_loadObjectList($query, 'id');
    }

    public function getXLists(array $options = [])
    {
        $limit = $options['limit'] ?? 10;
        $offset = $options['offset'] ?? 0;
        $filters = $options['filters'] ?? ['type' => self::LIST_TYPE_STANDARD];

        $conditions = [];
        foreach ($filters as $column => $filter) {
            switch ($column) {
                case 'id':
                case 'active':
                case 'visible':
                case 'clean':
                case 'welcome_id':
                case 'unsubscribe_id':
                case 'cms_user_id':
                case 'tracking':
                    $conditions[] = acym_secureDBColumn($column).' = '.intval($filter);
                    break;
                default:
                    $conditions[] = acym_secureDBColumn($column).' LIKE '.acym_escapeDB('%'.$filter.'%');
            }
        }

        $query = 'SELECT * FROM #__acym_list';
        if (!empty($conditions)) {
            $query .= ' WHERE '.implode(' AND ', $conditions);
        }

        return acym_loadObjectList($query, $this->pkey, $offset, $limit);
    }
}
