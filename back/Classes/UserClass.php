<?php

namespace AcyMailing\Classes;

use AcyMailing\Helpers\AutomationHelper;
use AcyMailing\Helpers\MailerHelper;
use AcyMailing\Helpers\PaginationHelper;
use AcyMailing\Core\AcymClass;

class UserClass extends AcymClass
{
    var $checkVisitor = true;
    var $restrictedFields = ['id', 'key', 'confirmed', 'active', 'cms_id', 'creation_date'];
    var $allowModif = false;
    var $requireId = false;
    var $sendConf = true;
    var $forceConf = false;
    public bool $confirmationSentSuccess = false;
    var $newUser = false;
    var $blockNotifications = false;
    var $subscribed = false;
    var $confirmationSentError;
    var $triggers = true;

    // For integration, for example someone is activating user subscription on membership pro on the joomla backend and he needs to send the confirmation emails
    var $forceConfAdmin = false;

    public $sendWelcomeEmail = true;
    public $sendUnsubscribeEmail = true;

    public function __construct()
    {
        parent::__construct();

        $this->table = 'user';
        $this->pkey = 'id';
    }

    /**
     * Get users depending on filters (search, status, pagination)
     */
    public function getMatchingElements(array $settings = []): array
    {
        $results = [];
        // Initialize the queries
        $columns = '`user`.*';
        if (!empty($settings['columns'])) {
            foreach ($settings['columns'] as $key => $value) {
                $settings['columns'][$key] = $key === 'join' ? $value : 'user.'.$value;
            }
            $columns = implode(', ', $settings['columns']);
        }

        $query = $columns.' FROM #__acym_user AS `user`';
        $queryCount = 'SELECT COUNT(DISTINCT user.id) AS total FROM #__acym_user AS user';
        $queryStatus = 'SELECT COUNT(DISTINCT user.id) AS number, user.confirmed + user.active*2 AS score FROM #__acym_user AS user';
        $filters = [];

        if (!empty($settings['join'])) {
            $query .= $this->getJoinForQuery($settings['join']);
        }
        $this->handleSegmentFilter($settings, $filters, $query, $queryCount, $queryStatus);
        $this->handleSubscriptionFilter($settings, $filters, $query, $queryCount, $queryStatus);
        $this->handleFrontend($settings, $query, $queryCount, $queryStatus);
        $this->handleSearchFilter($settings, $query, $queryCount, $queryStatus, $filters);
        $results['status'] = $this->handleUserStatusFilter($settings, $queryStatus, $filters);
        $entityResult = $this->handleEntitySelect($settings, $filters);

        if (!empty($entityResult)) return $entityResult;

        if (!empty($filters)) {
            $query .= ' WHERE ('.implode(') AND (', $filters).')';
            $queryCount .= ' WHERE ('.implode(') AND (', $filters).')';
        }

        if (!empty($settings['ordering']) && !empty($settings['ordering_sort_order'])) {
            $query .= ' ORDER BY user.'.acym_secureDBColumn($settings['ordering']).' '.acym_secureDBColumn(strtoupper($settings['ordering_sort_order']));
        }

        if (empty($settings['offset']) || $settings['offset'] < 0) {
            $settings['offset'] = 0;
        }

        if (empty($settings['elementsPerPage']) || $settings['elementsPerPage'] < 1) {
            $pagination = new PaginationHelper();
            $settings['elementsPerPage'] = $pagination->getListLimit();
        }

        // This line warns the database that a big query is about to be ran
        acym_query('SET SQL_BIG_SELECTS=1');
        // The beginning of $query can be modified in handleSearchFilter, don't move the SELECT up
        $results['elements'] = acym_loadObjectList('SELECT DISTINCT '.$query, '', $settings['offset'], $settings['elementsPerPage']);
        $results['total'] = acym_loadObject($queryCount);

        // Get CMS username separately if needed
        if (!empty($results['elements']) && !empty($settings['cms_username'])) {
            $cmsIds = array_diff(array_column($results['elements'], 'cms_id'), [0, '']);
            if (!empty($cmsIds)) {
                global $acymCmsUserVars;
                $userNames = acym_loadObjectList(
                    'SELECT '.$acymCmsUserVars->id.' AS id, '.$acymCmsUserVars->username.' AS `cms_username` 
                    FROM '.$acymCmsUserVars->table.' 
                    WHERE id IN ('.implode(', ', array_values($cmsIds)).')',
                    'id'
                );
                foreach ($results['elements'] as $key => $oneElement) {
                    if (empty($results['elements'][$key]->cms_id)) continue;
                    // Clean cms link on subscribers where CMS user no longer exists
                    if (empty($userNames[$results['elements'][$key]->cms_id])) {
                        $oneUser = $this->getOneById($results['elements'][$key]->id);
                        $oneUser->cms_id = 0;
                        $this->save($oneUser);
                        $results['elements'][$key]->cms_id = 0;
                        continue;
                    }
                    $results['elements'][$key]->cms_username = $userNames[$results['elements'][$key]->cms_id]->cms_username;
                }
            }
        }

        return $results;
    }

    private function handleSubscriptionFilter($settings, &$filters, &$query, &$queryCount, &$queryStatus)
    {
        if (empty($settings['list'])) return;

        $listJoin = ' JOIN #__acym_user_has_list AS list ON user.id = list.user_id AND list.list_id = '.intval($settings['list']).' ';

        if ($settings['list_status'] === 'none') {
            $listJoin = ' LEFT'.$listJoin;
            $filters[] = 'list.user_id IS NULL';
        } else {
            $filters[] = 'list.status = '.($settings['list_status'] === 'sub' ? 1 : 0);
        }

        $query .= $listJoin;
        $queryCount .= $listJoin;
        $queryStatus .= $listJoin;
    }

    private function handleFrontend($settings, &$query, &$queryCount, &$queryStatus)
    {
        if (!acym_isAdmin()) {
            $settings['creator_id'] = acym_currentUserId();
            if (empty($settings['creator_id'])) $settings['creator_id'] = '-1';
        }

        if (!empty($settings['creator_id'])) {
            $userGroups = acym_getGroupsByUser($settings['creator_id']);
            $groupCondition = 'list.access LIKE "%,'.implode(',%" OR list.access LIKE "%,', $userGroups).',%"';
            $joinList = ' JOIN #__acym_user_has_list as user_list ON user_list.user_id = user.id JOIN #__acym_list AS list ON list.id = user_list.list_id AND (list.cms_user_id = '.intval(
                    $settings['creator_id']
                ).' OR '.$groupCondition.')';

            $query .= $joinList;
            $queryCount .= $joinList;
            $queryStatus .= $joinList;
        }
    }

    private function handleSearchFilter($settings, &$query, &$queryCount, &$queryStatus, &$filters)
    {
        if (empty($settings['search'])) return;

        $searchValue = acym_escapeDB('%'.$settings['search'].'%');
        $searchFilter = 'user.email LIKE '.$searchValue.' OR user.name LIKE '.$searchValue.' OR user.id = '.intval($settings['search']);

        // Search in visible custom fields
        $listingFields = acym_loadResultArray('SELECT `id` FROM #__acym_field WHERE `'.(acym_isAdmin() ? 'back' : 'front').'end_listing` = 1');
        if (!empty($listingFields)) {
            $query = 'DISTINCT '.$query;
            $join = ' LEFT JOIN #__acym_user_has_field AS userfield ON user.id = userfield.user_id AND userfield.field_id IN ('.implode(', ', $listingFields).') AND ';
            $cfSearch = 'userfield.value LIKE '.$searchValue;

            $fieldsWithMultipleValues = acym_loadObjectList(
                'SELECT * 
                FROM #__acym_field 
                WHERE id IN ('.implode(', ', $listingFields).')
                    AND `value` LIKE '.acym_escapeDB('%"title":"'.$settings['search'].'"%')
            );

            if (!empty($fieldsWithMultipleValues)) {
                foreach ($fieldsWithMultipleValues as $oneField) {
                    $oneField->value = json_decode($oneField->value, true);
                    foreach ($oneField->value as $value) {
                        if (stripos($value['title'], $settings['search']) !== false) {
                            $cfSearch .= ' OR (userfield.field_id = '.intval($oneField->id).' AND userfield.value LIKE '.acym_escapeDB('%'.$value['value'].'%').')';
                        }
                    }
                }
            }

            $join .= '('.$cfSearch.')';
            $searchFilter .= ' OR userfield.field_id IS NOT NULL';

            $query .= $join;
            $queryCount .= $join;
            $queryStatus .= $join;
        }

        $filters[] = $searchFilter;
    }

    private function handleUserStatusFilter($settings, $queryStatus, &$filters)
    {
        if (!empty($filters)) {
            $queryStatus .= ' WHERE ('.implode(') AND (', $filters).')';
        }

        $usersPerStatusObject = acym_loadObjectList($queryStatus.' GROUP BY score', 'score');

        $usersPerStatus = [];
        for ($i = 0 ; $i < 4 ; $i++) {
            $usersPerStatus[$i] = empty($usersPerStatusObject[$i]) ? 0 : $usersPerStatusObject[$i]->number;
        }

        if (!empty($settings['status'])) {
            $allowedStatus = [
                'active' => 'active = 1',
                'inactive' => 'active = 0',
                'confirmed' => 'confirmed = 1',
                'unconfirmed' => 'confirmed = 0',
            ];
            if (empty($allowedStatus[$settings['status']])) {
                die('Injection denied');
            }
            $filters[] = 'user.'.$allowedStatus[$settings['status']];
        }

        return [
            'all' => array_sum($usersPerStatus),
            'active' => $usersPerStatus[2] + $usersPerStatus[3],
            'inactive' => $usersPerStatus[0] + $usersPerStatus[1],
            'confirmed' => $usersPerStatus[1] + $usersPerStatus[3],
            'unconfirmed' => $usersPerStatus[0] + $usersPerStatus[2],
        ];
    }

    private function handleEntitySelect($settings, &$filters)
    {
        if (isset($settings['hiddenElements'])) {
            if (empty($settings['hiddenElements'])) {
                $filters[] = 'user.id IS NOT NULL';
            } else {
                acym_arrayToInteger($settings['hiddenElements']);
                $filters[] = 'user.id NOT IN('.implode(',', $settings['hiddenElements']).')';
            }
        }

        if (isset($settings['onlyElements'])) {
            if (empty($settings['onlyElements'])) {
                $filters[] = 'user.id IS NULL';
            } else {
                acym_arrayToInteger($settings['onlyElements']);
                $filters[] = 'user.id IN('.implode(',', $settings['onlyElements']).')';
            }
        }

        // For the entity select when selecting "show only selected users"
        if (!empty($settings['showOnlySelected'])) {
            if (!empty($settings['selectedUsers'])) {
                acym_arrayToInteger($settings['selectedUsers']);
                $filters[] = 'user.id IN('.implode(',', $settings['selectedUsers']).')';
            } else {
                return ['users' => [], 'total' => 0];
            }
        }

        return null;
    }

    private function handleSegmentFilter($settings, &$filters, &$query, &$queryCount, &$queryStatus)
    {
        if (!array_key_exists('segment', $settings)) return;
        if ($settings['segment'] == 0) return;

        $segmentClass = (new SegmentClass())->getOneById($settings['segment']);
        $automationHelpers = [];
        if (!empty($segmentClass) && !empty($segmentClass->filters)) {
            foreach ($segmentClass->filters as $or => $orValues) {
                if (empty($orValues)) continue;
                $automationHelpers[$or] = new AutomationHelper();
                foreach ($orValues as $and => $andValues) {
                    $and = intval($and);
                    foreach ($andValues as $filterName => $options) {
                        acym_trigger('onAcymProcessFilter_'.$filterName, [&$automationHelpers[$or], &$options, $and.'_'.$or]);
                    }
                }
            }
        }

        $where = '';
        $join = '';
        foreach ($automationHelpers as $index => $automationHelper) {
            if (!empty($automationHelper->where)) {
                $where .= ' ('.implode(') and (', $automationHelper->where).')';
                // Add 'or' except for the last condition
                if ($index != count($automationHelpers) - 1) $where .= ' OR ';
            }
            if (!empty($automationHelper->join)) $join .= ' JOIN '.implode(' JOIN ', $automationHelper->join);
            if (!empty($automationHelper->leftjoin)) $join .= ' LEFT JOIN '.implode(' LEFT JOIN ', $automationHelper->leftjoin);
        }
        if (!empty($where)) {
            $filters[] = $where;
        }
        $query .= $join;
        $queryCount .= $join;
        $queryStatus .= $join;
    }

    public function getJoinForQuery($joinType)
    {
        if (strpos($joinType, 'join_list') !== false) {
            $listId = explode('-', $joinType);

            return ' LEFT JOIN #__acym_user_has_list AS userlist ON user.id = userlist.user_id AND userlist.status = 1 AND userlist.list_id = '.intval($listId[1]);
        }

        return '';
    }

    /**
     * @param ?int $id
     *
     * @return object
     */
    public function getOneByCMSId(?int $id)
    {
        $query = 'SELECT * FROM #__acym_user WHERE cms_id = '.intval($id);

        return acym_loadObject($query);
    }

    /**
     * @param ?string $email
     *
     * @return object
     */
    public function getOneByEmail(?string $email)
    {
        $query = 'SELECT * FROM #__acym_user WHERE email = '.acym_escapeDB($email);

        return acym_loadObject($query);
    }

    /**
     * @param string $column
     * @param string $value
     *
     * @return array
     */
    public function getByColumnValue($column, $value)
    {
        $userColumns = acym_getColumns('user');
        if (!in_array($column, $userColumns)) return [];

        $query = 'SELECT * FROM #__acym_user WHERE '.acym_secureDBColumn($column).' = '.acym_escapeDB($value);

        return acym_loadObjectList($query, $this->pkey);
    }

    /**
     * Get the subscription of one user
     *
     * @param int     $userId
     * @param string  $key
     * @param boolean $includeManagement
     * @param boolean $visible
     * @param boolean $needTranslation
     * @param boolean $sortBySubscription
     *
     * @return array
     */
    public function getUserSubscriptionById(
        $userId,
        $key = 'id',
        $includeManagement = false,
        $visible = false,
        $needTranslation = false,
        $sortBySubscription = false,
        $mailId = 0,
        $campaignOnly = false
    ) {
        $mailType = null;
        if (!empty($mailId)) {
            $mailType = acym_loadResult('SELECT type FROM #__acym_mail WHERE id = '.intval($mailId));
        }

        $query = 'SELECT list.id, list.translation, list.name, list.display_name, list.color, list.active, list.visible, list.description, userlist.status, userlist.subscription_date, userlist.unsubscribe_date 
                FROM #__acym_list AS list 
                JOIN #__acym_user_has_list AS userlist 
                    ON list.id = userlist.list_id ';

        if (!empty($mailId) && $campaignOnly && !empty($mailType) && $mailType !== MailClass::TYPE_FOLLOWUP) {
            $query .= 'JOIN #__acym_mail_has_list AS mail_list ON list.id = mail_list.list_id AND mail_list.mail_id = '.intval($mailId);
        }

        $query .= ' WHERE userlist.user_id = '.intval($userId);

        if (!$includeManagement && !empty($mailType) && $mailType === MailClass::TYPE_FOLLOWUP) {
            $query .= ' AND list.type = '.acym_escapeDB(ListClass::LIST_TYPE_FOLLOWUP);
        } elseif (!$includeManagement) {
            $types = [ListClass::LIST_TYPE_STANDARD, ListClass::LIST_TYPE_FOLLOWUP];
            $types = array_map('acym_escapeDB', $types);
            $query .= ' AND list.type IN ('.implode(',', $types).')';
        }

        if ($visible) {
            $query .= ' AND list.visible = 1';
        }

        if ($sortBySubscription) {
            $query .= ' ORDER BY userlist.subscription_date DESC';
        }

        $lists = acym_loadObjectList($query, $key);

        if (acym_isMultilingual() && $needTranslation) {
            $listClass = new ListClass();
            $lists = $listClass->getTranslatedNameDescription($lists);
        }

        return $lists;
    }

    public function getUserStandardListIdById(int $userId)
    {
        $query = 'SELECT list.id FROM #__acym_list AS list 
	              JOIN #__acym_user_has_list AS user_list ON list.id = user_list.list_id AND user_list.status = 1 AND user_list.user_id = '.$userId.' 
	              WHERE list.visible = 1 AND list.type = '.acym_escapeDB(ListClass::LIST_TYPE_STANDARD);

        return acym_loadResultArray($query);
    }

    /**
     * Get the subscription of one user
     *
     * @param int     $userId
     * @param string  $key
     * @param boolean $needTranslation
     *
     * @return array
     */
    public function getAllListsUserSubscriptionById($userId, $key = 'id', $needTranslation = false)
    {
        $query = 'SELECT list.id, list.translation, list.name, list.color, list.active, list.visible, userlist.status, userlist.subscription_date, userlist.unsubscribe_date 
                FROM #__acym_list AS list 
                LEFT JOIN #__acym_user_has_list AS userlist 
                    ON list.id = userlist.list_id 
                    AND userlist.user_id = '.intval($userId);

        $lists = acym_loadObjectList($query, $key);

        if (acym_isMultilingual() && $needTranslation) {
            $listClass = new ListClass();
            $lists = $listClass->getTranslatedNameDescription($lists);
        }

        return $lists;
    }

    /**
     * Get the subscriptions of users by user id
     *
     * @param $usersId
     * @param $creatorId
     *
     * @return array
     */
    public function getUsersSubscriptionsByIds($usersId, $creatorId = 0)
    {
        $query = 'SELECT `list`.`id`, `list`.`color`, `list`.`name`, `userlist`.`user_id`, `userlist`.`status`
                FROM #__acym_list AS `list`
                JOIN #__acym_user_has_list AS `userlist` 
                    ON `list`.`id` = `userlist`.`list_id`
                WHERE `userlist`.`user_id` IN ('.implode(',', $usersId).')
                    AND `list`.`type` = '.acym_escapeDB(ListClass::LIST_TYPE_STANDARD);

        if (!empty($creatorId)) {
            $userGroups = acym_getGroupsByUser($creatorId);
            $groupCondition = '`list`.`access` LIKE "%,'.implode(',%" OR `list`.`access` LIKE "%,', $userGroups).',%"';
            $query .= ' AND (`list`.`cms_user_id` = '.intval($creatorId).' OR '.$groupCondition.')';
        }
        $query .= ' ORDER BY `userlist`.`status` DESC, `list`.`id` ASC';

        return acym_loadObjectList($query);
    }

    public function getCountTotalUsers(): int
    {
        $numberOfUsers = acym_loadResult('SELECT COUNT(id) FROM #__acym_user');

        return empty($numberOfUsers) ? 0 : $numberOfUsers;
    }

    public function getSubscriptionStatus($userId, $listIds = [], $wantedStatus = null)
    {
        $query = 'SELECT status, list_id
                    FROM #__acym_user_has_list  
                    WHERE user_id = '.intval($userId);
        if (!empty($listIds)) {
            acym_arrayToInteger($listIds);
            $query .= ' AND list_id IN ('.implode(',', $listIds).')';
        }

        if ($wantedStatus !== null) {
            $query .= ' AND status = '.intval($wantedStatus);
        }

        return acym_loadObjectList($query, 'list_id');
    }

    /**
     * Identify a user from the acy user_id and key of the e-mail
     *
     * @param bool $onlyValue only return the user, don't display errors
     *
     * @return mixed the identified user or false
     */
    public function identify(bool $onlyValue = false, $idName = null, $keyName = null)
    {
        $id = acym_getVar('int', empty($idName) ? 'id' : $idName, 0);
        $key = acym_getVar('string', empty($keyName) ? 'key' : $keyName, '');

        if (empty($id) && !empty($idName) && empty($key) && !empty($keyName)) {
            $id = acym_getVar('int', 'id', 0);
            $key = acym_getVar('string', 'key', 0);
        }

        if (empty($id) || empty($key)) {
            //Check if the user is not already in the session...
            //Check if the user is not loaded in...
            $currentUserid = acym_currentUserId();
            if (!empty($currentUserid)) {
                return $this->getOneByCMSId($currentUserid);
            }
            if (!$onlyValue) {
                acym_enqueueMessage(acym_translation('ACYM_LOGIN'), 'error');
            }

            return false;
        }

        $userIdentified = acym_loadObject('SELECT * FROM #__acym_user WHERE `id` = '.intval($id).' AND `key` = '.acym_escapeDB($key));

        if (!empty($userIdentified)) {
            return $userIdentified;
        }

        if (!$onlyValue) {
            acym_enqueueMessage(acym_translation('ACYM_USER_NOT_FOUND'), 'error');
        }

        return false;
    }

    public function subscribe($userIds, $addLists, $trigger = true, $forceFront = false)
    {
        if (empty($addLists)) {
            return false;
        }

        if (!is_array($userIds)) {
            $userIds = [$userIds];
        }

        if (!is_array($addLists)) {
            $addLists = [$addLists];
        }

        $listClass = new ListClass();
        $historyClass = new HistoryClass();

        $confirmationRequired = $this->config->get('require_confirmation', 1);
        $subscribedToLists = false;
        $historyData = acym_translationSprintf('ACYM_LISTS_NUMBERS', implode(', ', $addLists));

        foreach ($userIds as $userId) {
            $user = $this->getOneById($userId);
            if (empty($user)) continue;
            $currentSubscription = $this->getUserSubscriptionById($userId, 'id', true);

            $currentlySubscribed = [];
            $currentlyUnsubscribed = [];
            foreach ($currentSubscription as $oneList) {
                if ($oneList->status == 1) {
                    $currentlySubscribed[$oneList->id] = $oneList;
                }
                if ($oneList->status == 0) {
                    $currentlyUnsubscribed[$oneList->id] = $oneList;
                }
            }

            $subscribedLists = [];
            $listsNotExisting = [];
            foreach ($addLists as $oneListId) {
                // The user is already subscribed
                if (empty($oneListId) || !empty($currentlySubscribed[$oneListId])) {
                    continue;
                }

                $list = $listClass->getOneById($oneListId);
                if (empty($list)) {
                    $listsNotExisting[] = $oneListId;
                    continue;
                }

                $subscription = new \stdClass();
                $subscription->user_id = $userId;
                $subscription->list_id = $oneListId;
                $subscription->status = 1;
                $subscription->subscription_date = date('Y-m-d H:i:s', time() - date('Z'));

                if (empty($currentSubscription[$oneListId])) {
                    // The user isn't already subscribed, we subscribe him!
                    acym_insertObject('#__acym_user_has_list', $subscription);
                } elseif (!empty($currentlyUnsubscribed[$oneListId])) {
                    // The user re-subscribes or we have to confirm the subscription
                    acym_updateObject('#__acym_user_has_list', $subscription, ['user_id', 'list_id']);
                }

                $subscribedLists[] = $oneListId;
                $subscribedToLists = true;
            }

            if (!empty($listsNotExisting)) {
                $this->errors['list_not_existing'] = acym_translationSprintf('ACYM_LIST_NOT_FOUND', implode(', ', $listsNotExisting));
            }

            $historyClass->insert($userId, 'subscribed', [$historyData]);

            if ($trigger) {
                acym_trigger('onAcymAfterUserSubscribe', [&$user, &$subscribedLists]);
            }

            if ($this->sendWelcomeEmail && ($confirmationRequired == 0 || $user->confirmed == 1) && $user->active == 1) {
                $listClass->sendWelcome($userId, $subscribedLists, $forceFront);
            }
        }

        return $subscribedToLists;
    }

    private function registerUnsubUser($userIds)
    {
        $mailId = acym_getVar('int', 'mail_id', 0);
        if (empty($mailId)) return;

        $mailStatClass = new MailStatClass();
        $userStatClass = new UserStatClass();

        $countUnsubscribe = 0;

        foreach ($userIds as $id) {
            $userStat = $userStatClass->getOneByMailAndUserId($mailId, $id);
            // We didn't send this email to this user, something is wrong
            if (empty($userStat)) continue;

            $newUserStat = [
                'mail_id' => $mailId,
                'user_id' => $id,
                'unsubscribe' => empty($userStat->unsubscribe) ? 1 : $userStat->unsubscribe + 1,
            ];

            if (empty($userStat->unsubscribe)) $countUnsubscribe++;

            $userStatClass->save($newUserStat);
        }

        $mailStat = [
            'mail_id' => $mailId,
            'unsubscribe_total' => $countUnsubscribe,
        ];

        $mailStatClass->save($mailStat);
    }

    public function unsubscribe($userIds, $lists)
    {
        if (empty($lists)) return false;

        if (!is_array($userIds)) $userIds = [$userIds];
        if (!is_array($lists)) $lists = [$lists];

        $listClass = new ListClass();
        $unsubscribedFromLists = false;
        foreach ($userIds as $userId) {
            $user = $this->getOneById($userId);
            if (empty($user)) continue;

            $currentSubscription = $this->getUserSubscriptionById($userId);

            $currentlySubscribed = [];
            $currentlyUnsubscribed = [];
            foreach ($currentSubscription as $oneList) {
                if ($oneList->status == 0) {
                    $currentlyUnsubscribed[$oneList->id] = $oneList;
                } elseif ($oneList->status == 1) {
                    $currentlySubscribed[$oneList->id] = $oneList;
                }
            }

            $unsubscribedLists = [];
            $listsNotExisting = [];
            foreach ($lists as $oneListId) {
                // The user is already unsubscribed
                if (empty($oneListId) || !empty($currentlyUnsubscribed[$oneListId])) continue;

                // Don't unsubscribe from non-subscribed lists
                if (empty($currentlySubscribed[$oneListId])) continue;

                $list = $listClass->getOneById($oneListId);
                if (empty($list)) {
                    $listsNotExisting[] = $oneListId;
                    continue;
                }

                $subscription = new \stdClass();
                $subscription->user_id = $userId;
                $subscription->list_id = $oneListId;
                $subscription->status = 0;
                $subscription->unsubscribe_date = date('Y-m-d H:i:s', time() - date('Z'));
                if (empty($currentSubscription[$oneListId])) {
                    // The user isn't already subscribed, we unsubscribe him directly
                    acym_insertObject('#__acym_user_has_list', $subscription);
                } else {
                    // The user unsubscribed from a list
                    acym_updateObject('#__acym_user_has_list', $subscription, ['user_id', 'list_id']);
                }

                $unsubscribedLists[] = $oneListId;
                $unsubscribedFromLists = true;
            }

            if (!empty($listsNotExisting)) {
                $this->errors['list_not_existing'] = acym_translationSprintf('ACYM_LIST_NOT_FOUND', implode(', ', $listsNotExisting));
            }

            acym_query(
                'DELETE FROM #__acym_queue WHERE user_id = '.intval($userId).' AND mail_id IN (
                    SELECT followup_mail.mail_id FROM #__acym_followup_has_mail AS followup_mail
                    JOIN #__acym_followup AS followup ON followup.id = followup_mail.followup_id AND followup.list_id IN ('.implode(',', $lists).')
                )'
            );

            $historyClass = new HistoryClass();
            $historyData = acym_translationSprintf('ACYM_LISTS_NUMBERS', implode(', ', $lists));
            $historyClass->insert($userId, 'unsubscribed', [$historyData], 0, acym_getVar('string', 'unsubscribe_reason'));

            if ($this->triggers) {
                acym_trigger('onAcymAfterUserUnsubscribe', [&$user, &$unsubscribedLists]);
            }

            if ($this->sendUnsubscribeEmail) {
                $listClass->sendUnsubscribe($userId, $unsubscribedLists);
            }

            if (!empty($unsubscribedLists)) {
                foreach ($unsubscribedLists as $i => $oneListId) {
                    $currentList = $listClass->getOneById($oneListId);
                    $unsubscribedLists[$i] = $currentList->name;
                }
                $this->sendNotification($user->id, 'acy_notification_unsub', ['lists' => implode(', ', $unsubscribedLists)]);
            }
        }

        if ($unsubscribedFromLists) $this->registerUnsubUser($userIds);

        return $unsubscribedFromLists;
    }

    public function unsubscribeOnSubscriptions($userId, $listIds)
    {
        if (empty($listIds)) {
            return false;
        }

        if (!is_array($listIds)) {
            $listIds = [$listIds];
        }
        acym_arrayToInteger($listIds);

        $subscribedLists = acym_loadResultArray('SELECT list_id FROM #__acym_user_has_list WHERE user_id = '.intval($userId).' AND list_id IN ('.implode(',', $listIds).')');

        if (!empty($subscribedLists)) $this->unsubscribe($userId, $subscribedLists);

        return true;
    }

    public function removeSubscription($userIds, $listIds = [])
    {
        if (!is_array($userIds)) {
            $userIds = [$userIds];
        }

        if (!is_array($listIds) || empty($listIds) || empty($userIds)) {
            return false;
        }

        acym_arrayToInteger($listIds);

        return acym_query(
            'DELETE FROM #__acym_user_has_list 
            WHERE user_id IN ('.implode(',', $userIds).') 
                AND list_id IN ('.implode(',', $listIds).')'
        );
    }

    public function onlyManageableUsers(&$elements)
    {
        if (acym_isAdmin()) {
            return;
        }

        $listClass = new ListClass();
        $manageableLists = $listClass->getManageableLists();
        if (empty($manageableLists)) {
            return;
        }

        $currentUserId = acym_currentUserId();
        acym_arrayToInteger($elements);

        $selfManagement = in_array($currentUserId, $elements);

        $elements = acym_loadResultArray(
            'SELECT DISTINCT user_id 
            FROM #__acym_user_has_list 
            WHERE `list_id` IN ('.implode(',', $manageableLists).') 
                AND `user_id` IN ('.implode(',', $elements).')'
        );

        if ($selfManagement && !in_array($currentUserId, $elements)) {
            $elements[] = $currentUserId;
        }
    }

    public function delete($elements, $forceDelete = false)
    {
        if (!is_array($elements)) {
            $elements = [$elements];
        }

        acym_arrayToInteger($elements);
        $this->onlyManageableUsers($elements);

        if (empty($elements)) {
            return 0;
        }

        if (acym_isAdmin() || $forceDelete || 'delete' === $this->config->get('frontend_delete_button', 'delete')) {
            acym_query('DELETE FROM #__acym_user_has_list WHERE user_id IN ('.implode(',', $elements).')');
            acym_query('DELETE FROM #__acym_queue WHERE user_id IN ('.implode(',', $elements).')');
            acym_query('DELETE FROM #__acym_user_has_field WHERE user_id IN ('.implode(',', $elements).')');
            acym_query('DELETE FROM #__acym_history WHERE user_id IN ('.implode(',', $elements).')');
            acym_query('DELETE FROM #__acym_user_stat WHERE user_id IN ('.implode(',', $elements).')');

            $scenarioProcessIds = acym_loadResultArray('SELECT id FROM #__acym_scenario_process WHERE user_id IN ('.implode(',', $elements).')');

            if (!empty($scenarioProcessIds)) {
                acym_query('DELETE FROM #__acym_scenario_history_line WHERE scenario_process_id IN ('.implode(',', $scenarioProcessIds).')');
                acym_query('DELETE FROM #__acym_scenario_queue WHERE scenario_process_id IN ('.implode(',', $scenarioProcessIds).')');
                acym_query('DELETE FROM #__acym_scenario_process WHERE id IN ('.implode(',', $scenarioProcessIds).')');
            }

            return parent::delete($elements);
        } else {
            $listClass = new ListClass();
            $manageableLists = $listClass->getManageableLists();

            if (!empty($manageableLists)) {
                $this->removeSubscription($elements, $manageableLists);
                acym_query(
                    'DELETE queue 
                    FROM #__acym_queue AS queue 
                    JOIN #__acym_mail_has_list AS maillist 
                        ON queue.mail_id = maillist.mail_id 
                    WHERE queue.user_id IN ('.implode(',', $elements).') 
                        AND maillist.list_id IN ('.implode(',', $manageableLists).')'
                );
            }

            return count($elements);
        }
    }

    public function save($user, $customFields = [], $ajax = false)
    {
        if (empty($user->email) && empty($user->id)) {
            return false;
        }

        if (isset($user->email)) {
            $user->email = strtolower($user->email);
            if (!acym_isValidEmail($user->email)) {
                $this->errors[] = acym_translation('ACYM_VALID_EMAIL');

                return false;
            }
        }

        if (empty($user->id)) {
            if (!isset($user->active)) {
                $user->active = 1;
            }

            if (empty($user->language)) {
                // Take the user account's language
                if (!acym_isAdmin()) {
                    $cmsUserLanguage = acym_getCmsUserLanguage();
                }

                if (!empty($cmsUserLanguage)) {
                    $user->language = $cmsUserLanguage;
                } elseif (acym_isMultilingual()) {
                    // Take the configuration's language
                    $configUserLanguage = $this->config->get('multilingual_user_default', 'current_language');
                    $user->language = $configUserLanguage === 'current_language' ? acym_getLanguageTag() : $configUserLanguage;
                }
            }

            $currentUserid = acym_currentUserId();
            $currentEmail = acym_currentUserEmail();
            $allowVisitors = $this->config->get('allow_visitor', 1) == 1;
            if ($this->checkVisitor && !acym_isAdmin() && !$allowVisitors && (empty($currentUserid) || strtolower($currentEmail) !== $user->email)) {
                //We don't accept the subscription as either the user is not logged in or it's not the good one
                $this->errors[] = acym_translation('ACYM_ONLY_LOGGED');

                return false;
            }

            if (empty($user->name) && $this->config->get('generate_name', 1)) {
                $user->name = ucwords(trim(str_replace(['.', '_', ')', ',', '(', '-', 1, 2, 3, 4, 5, 6, 7, 8, 9, 0], ' ', substr($user->email, 0, strpos($user->email, '@')))));
            }

            $source = acym_getVar('string', 'acy_source', '');
            if (empty($user->source) && !empty($source)) $user->source = $source;

            if (empty($user->key)) $user->key = acym_generateKey(14);

            $user->creation_date = date('Y-m-d H:i:s', time() - date('Z'));
        } elseif (!empty($user->confirmed)) {
            $oldUser = $this->getOneByIdWithCustomFields($user->id);
            if (!empty($oldUser) && empty($oldUser['confirmed'])) {
                $user->confirmation_date = date('Y-m-d H:i:s', time() - date('Z'));
                $user->confirmation_ip = acym_getIP();

                if ($this->triggers) {
                    acym_trigger('onAcymAfterUserConfirm', [&$user]);
                }
            }
        } else {
            $oldUser = $this->getOneByIdWithCustomFields($user->id);
        }

        foreach ($user as $oneAttribute => $value) {
            if (empty($value)) continue;

            $oneAttribute = trim(strtolower($oneAttribute));
            if (!in_array($oneAttribute, $this->restrictedFields)) {
                $user->$oneAttribute = strip_tags($value);
            }

            // Convert into utf-8 in case of it's not already
            // Double test on UTF-8 because the preg_match returns an error on string longer than 200 characters
            if (is_numeric($user->$oneAttribute)) continue;

            if (function_exists('mb_detect_encoding')) {
                if (mb_detect_encoding($user->$oneAttribute, 'UTF-8', true) !== 'UTF-8') {
                    $user->$oneAttribute = acym_utf8Encode($user->$oneAttribute);
                }
            } elseif (!preg_match(
                '%^(?:[\x09\x0A\x0D\x20-\x7E]|[\xC2-\xDF][\x80-\xBF]|\xE0[\xA0-\xBF][\x80-\xBF]|[\xE1-\xEC\xEE\xEF][\x80-\xBF]{2}|\xED[\x80-\x9F][\x80-\xBF]|\xF0[\x90-\xBF][\x80-\xBF]{2}|[\xF1-\xF3][\x80-\xBF]{3}|\xF4[\x80-\x8F][\x80-\xBF]{2})*$%xs',
                $user->$oneAttribute
            )) {
                $user->$oneAttribute = acym_utf8Encode($user->$oneAttribute);
            }
        }

        if (empty($user->id)) {
            if (empty($user->cms_id) && !empty($user->email)) {
                global $acymCmsUserVars;
                $userCmsID = acym_loadResult(
                    'SELECT '.acym_secureDBColumn($acymCmsUserVars->id).' FROM '.$acymCmsUserVars->table.' WHERE '.acym_secureDBColumn(
                        $acymCmsUserVars->email
                    ).' = '.acym_escapeDB($user->email)
                );
                if (!empty($userCmsID)) $user->cms_id = $userCmsID;
            }
            if ($this->triggers) {
                $result = acym_trigger('onAcymBeforeUserCreate', [&$user]);
                if (in_array(false, $result)) {
                    $acycheckerError = acym_getVar('string', 'acychecker_error');
                    if (!empty($acycheckerError)) {
                        if ($ajax) {
                            $this->errors[] = $acycheckerError;
                        } else {
                            acym_enqueueMessage($acycheckerError, 'error');
                        }
                    }

                    return false;
                }
            }
        } else {
            if ($this->triggers) {
                acym_trigger('onAcymBeforeUserModify', [&$user]);
            }
        }

        $userID = parent::save($user);

        // Save custom fields if there are any
        $fieldClass = new FieldClass();
        if (!empty($customFields)) {
            foreach ($customFields as $key => $value) {
                if (!is_string($value)) {
                    continue;
                }

                // Remove most of the existing emojis that are not supported
                $value = preg_replace('/[\x{10000}-\x{10FFFF}]/u', '', $value);

                $field = $fieldClass->getOneById($key);
                if (empty($field) || $field->type !== 'textarea') {
                    $value = preg_replace('/\s+/', ' ', $value);
                }
                $customFields[$key] = $value;
            }
        }
        $fieldClass->store($userID, $customFields, $ajax);
        if (!empty($fieldClass->errors)) {
            $this->errors = array_merge($this->errors, $fieldClass->errors);
        }

        $historyClass = new HistoryClass();
        if (empty($user->id)) {
            $user->id = $userID;
            $historyClass->insert($user->id, 'created');
            if ($this->triggers) {
                acym_trigger('onAcymAfterUserCreate', [&$user]);
                acym_triggerCmsHook('onAcymAfterUserSave', [$user, true]);
            }
        } else {
            $historyClass->insert($user->id, 'modified');
            if ($this->triggers) {
                acym_trigger('onAcymAfterUserModify', [&$user, &$oldUser]);
                acym_triggerCmsHook('onAcymAfterUserSave', [$user, false]);
            }
        }

        $this->sendConfirmation($userID);

        return $userID;
    }

    public function saveForm($ajax = false)
    {
        $allowUserModifications = (bool)($this->config->get('allow_modif', 'data') == 'all') || $this->allowModif;
        $allowSubscriptionModifications = (bool)($this->config->get('allow_modif', 'data') != 'none') || $this->allowModif;

        $user = new \stdClass();
        $connectedUser = $this->identify(true, 'userId', 'userKey');

        $userData = acym_getVar('array', 'user', []);
        if (!empty($userData)) {
            foreach ($userData as $attribute => $value) {
                $user->$attribute = $value;
            }
        }

        if (empty($user->email)) {
            if (!empty($connectedUser->email)) {
                $user->email = $connectedUser->email;
            }
        }

        if (empty($user->email)) {
            $this->errors[] = acym_translation('ACYM_VALID_EMAIL');

            return false;
        }

        if (!$allowUserModifications && !empty($connectedUser)) {
            if ($connectedUser->email != $user->email) {
                $this->errors[] = acym_translation('ACYM_NOT_ALLOWED_MODIFY_USER');

                return false;
            }

            $allowUserModifications = true;
            $allowSubscriptionModifications = true;
        }

        // Check if it already exists...
        if (!empty($user->email)) {
            // Do we already have a user with this same e-mail address?
            if (empty($user->id)) {
                $user->id = 0;
            }
            $existUser = acym_loadObject('SELECT * FROM #__acym_user WHERE email = '.acym_escapeDB($user->email).' AND id != '.intval($user->id));
            if (!empty($existUser->id)) {
                if (!$this->allowModif && !$allowSubscriptionModifications) {
                    $this->errors[] = acym_translation('ACYM_ADDRESS_TAKEN');

                    return false;
                } else {
                    $user->id = $existUser->id;
                }
            }
        }

        // Did the user ask for its e-mail address to be modified?
        // If so, we set it as not confirmed automatically.
        if (!empty($user->id) && !empty($user->email)) {
            $existUser = $this->getOneById($user->id);
            if (trim(strtolower($user->email)) != strtolower($existUser->email)) {
                $user->confirmed = 0;
            }
        }

        $this->newUser = empty($user->id);
        if (empty($user->id) || $allowUserModifications) {
            if (isset($user->confirmed) && $user->confirmed != 1) {
                $user->confirmed = 0;
            }
            if (isset($user->active) && $user->active != 1) {
                $user->active = 0;
            }
            // Get custom fields to save them if exist
            $customFieldData = acym_getVar('array', 'customField', []);
            $id = $this->save($user, $customFieldData, $ajax);
            $allowSubscriptionModifications = true;
        } else {
            $id = $user->id;
            //We didn't save the user... but we should check the confirm field anyway
            if (isset($user->confirmed) && empty($user->confirmed)) {
                $this->sendConfirmation($id);
            }
        }

        if (empty($id)) return false;

        $formData = acym_getVar('array', 'data', []);
        acym_setVar('userId', $id);

        if (!acym_isAdmin()) {
            $hiddenlistsString = acym_getVar('string', 'hiddenlists', '');
            $hiddenlists = explode(',', $hiddenlistsString);
            acym_arrayToInteger($hiddenlists);
            $visibleSubscription = acym_getVar('array', 'subscription', []);
            $subscribeLists = array_merge($hiddenlists, $visibleSubscription);
            if (!empty($subscribeLists)) {
                foreach ($subscribeLists as $oneListId) {
                    $formData['listsub'][$oneListId] = ['status' => 1];
                }
            }
        }

        $fromProfile = acym_getVar('int', 'acyprofile', 0) == 1;

        if (empty($formData['listsub'])) {
            if (!$fromProfile) $this->sendNotification($id, 'acy_notification_subform');

            $this->sendNotification(
                $id,
                $this->newUser ? 'acy_notification_create' : 'acy_notification_profile'
            );

            return true;
        }

        if (!$allowSubscriptionModifications) {
            $this->requireId = true;
            $this->errors[] = acym_translation('ACYM_NOT_ALLOWED_MODIFY_USER');

            return false;
        }

        $addLists = [];
        $unsubLists = [];
        foreach ($formData['listsub'] as $listID => $oneList) {
            if ($oneList['status'] == 1) {
                $addLists[] = $listID;
            } else {
                $unsubLists[] = $listID;
            }
        }

        $this->subscribed = $this->subscribe($id, $addLists);

        if (!$fromProfile) $this->sendNotification($id, 'acy_notification_subform');

        $this->sendNotification(
            $id,
            $this->newUser ? 'acy_notification_create' : 'acy_notification_profile'
        );

        if (!$this->newUser) $this->unsubscribe($id, $unsubLists);

        return true;
    }

    public function sendConfirmation($userID)
    {
        if (!$this->forceConf && !$this->sendConf) {
            return;
        }

        if ($this->config->get('require_confirmation', 1) != 1 || (acym_isAdmin() && !$this->forceConfAdmin)) {
            return;
        }

        $myUser = $this->getOneById($userID);

        if (!empty($myUser->confirmed)) {
            return;
        }

        $mailerHelper = new MailerHelper();
        $mailerHelper->report = (bool)$this->config->get('confirm_message', 0);

        //TODO $mailerHelper->addParam('user:subscription', implode('<br/>', $subscription));
        $mailClass = new MailClass();
        $confirmationEmail = $mailClass->getOneByName('acy_confirm');
        $this->confirmationSentSuccess = !empty($confirmationEmail) && $mailerHelper->sendOne($confirmationEmail->id, $myUser);
        $this->confirmationSentError = $mailerHelper->reportMessage;
    }

    public function deactivate($userId)
    {
        acym_query('UPDATE `#__acym_user` SET `active` = 0 WHERE `id` = '.intval($userId));
    }

    public function confirm($userId)
    {
        $user = $this->getOneById($userId);
        if (empty($user)) return;

        // We confirm the user and add the confirmation_date and confirmation_ip in the table.
        $confirmDate = date('Y-m-d H:i:s', time() - date('Z'));
        $ip = acym_getIP();
        $query = 'UPDATE `#__acym_user`';
        $query .= ' SET `confirmed` = 1, `confirmation_date` = '.acym_escapeDB($confirmDate).', `confirmation_ip` = '.acym_escapeDB($ip);
        $query .= ' WHERE `id` = '.intval($userId).' LIMIT 1';

        try {
            $res = acym_query($query);
        } catch (\Exception $e) {
            $res = false;
        }
        if ($res === false) {
            $errorMessage = isset($e) ? $e->getMessage() : acym_getDBError();
            // If there is an error we definitely want to warn the user about it.
            $msg = acym_translation('ACYM_CONTACT_ADMIN_ERROR').'<br />'.substr(strip_tags($errorMessage), 0, 200).'...';
            acym_display($msg, 'error');
            exit;
        }

        acym_trigger('onAcymAfterUserConfirm', [&$user]);
        $this->sendNotification($userId, 'acy_notification_confirm');

        $historyClass = new HistoryClass();
        $historyClass->insert($userId, 'confirmed');

        if (empty($this->config->get('require_confirmation', 1))) return;

        $listIDs = acym_loadResultArray('SELECT `list_id` FROM `#__acym_user_has_list` WHERE `status` = 1 AND `user_id` = '.intval($userId));

        if (empty($listIDs)) return;

        $listClass = new ListClass();
        $listClass->sendWelcome($userId, $listIDs);
    }

    public function getOneByIdWithCustomFields($id): array
    {
        $user = $this->getOneById($id);
        if (empty($user)) {
            return [];
        }

        $user = get_object_vars($user);

        $fieldsValue = acym_loadObjectList(
            'SELECT user_field.`value`, field.`name` 
            FROM #__acym_user_has_field AS `user_field` 
            LEFT JOIN #__acym_field AS `field` ON user_field.`field_id` = field.`id` 
            WHERE user_field.`user_id` = '.intval($id),
            'name'
        );

        foreach ($fieldsValue as $key => $value) {
            $fieldsValue[$key] = $value->value;
        }

        return array_merge($user, $fieldsValue);
    }

    /**
     * @param $column
     * @param $value
     *
     * @return array
     */
    public function getOneWithCustomFields($column, $value): array
    {
        switch ($column) {
            case 'id':
                $user = $this->getOneById($value);
                break;
            case 'email':
                $user = $this->getOneByEmail($value);
                break;
            case 'cms_id':
                $user = $this->getOneByCMSId($value);
                break;
            default:
                return [];
        }

        if (empty($user)) {
            return [];
        }
        $user = get_object_vars($user);

        $fieldsValue = acym_loadObjectList(
            'SELECT user_field.`value`, field.`name` 
            FROM #__acym_user_has_field AS `user_field` 
            LEFT JOIN #__acym_field AS `field` ON user_field.`field_id` = field.`id` 
            WHERE user_field.`user_id` = '.intval($user['id']),
            'name'
        );

        foreach ($fieldsValue as $key => $value) {
            $fieldsValue[$key] = $value->value;
        }

        return array_merge($user, $fieldsValue);
    }

    public function getCustomFieldValueById($id)
    {
        $fieldsValue = acym_loadObjectList(
            'SELECT user_field.value AS value, field.name AS name, field.type AS type, field.value AS field_params, field.id AS id
            FROM #__acym_user_has_field as user_field 
            LEFT JOIN #__acym_field as field ON user_field.field_id = field.id 
            WHERE user_field.user_id = '.intval($id),
            'id'
        );

        $fieldReturn = [];

        foreach ($fieldsValue as $fieldId => $field) {
            if (in_array($field->type, ['checkbox', 'radio', 'single_dropdown', 'multiple_dropdown'])) {
                $field->field_params = json_decode($field->field_params, true);
                $field->value = explode(',', $field->value);
                $values = [];
                foreach ($field->value as $oneValue) {
                    $key = array_search($oneValue, array_column($field->field_params, 'value'));
                    $values[] = $field->field_params[$key]['title'];
                }
                $fieldReturn[$field->name] = implode(',', $values);
            } else {
                $fieldReturn[$field->name] = $field->value;
            }
        }

        return $fieldReturn;
    }

    public function getAllColumnsUserAndCustomField($inAction = false, $withSubInfos = false): array
    {
        $return = [];

        // Basic fields in the user table
        $userFields = acym_getColumns('user');
        foreach ($userFields as $value) {
            $return[$value] = $value;
        }

        $languageFieldId = $this->config->get(FieldClass::LANGUAGE_FIELD_ID_KEY, 0);

        // Acy custom fields except name, email and language because they already are in the user table
        $customFields = acym_loadObjectList(
            'SELECT * FROM #__acym_field WHERE id NOT IN (1, 2, '.intval($languageFieldId).') '.($inAction ? 'AND type != "phone"' : ''),
            'id'
        );
        if (!empty($customFields)) {
            foreach ($customFields as $key => $value) {
                $return[$key] = $value->name;
            }
        }

        if ($withSubInfos) {
            $listCLass = new ListClass();
            $listFields = $listCLass->getAll('name');
            if (!empty($listFields)) {
                foreach ($listFields as $key => $value) {
                    if ($value->type === ListClass::LIST_TYPE_FOLLOWUP) {
                        continue;
                    }
                    $return['subscription_date_'.$value->id] = acym_translation('ACYM_SUBSCRIPTION_DATE').' '.$value->name;
                    $return['unsubscription_date_'.$value->id] = acym_translation('ACYM_UNSUBSCRIPTION_DATE').' '.$value->name;
                }
            }
        }

        return $return;
    }

    public function getAllUserFields($user)
    {
        if (empty($user->id)) return $user;
        $query = 'SELECT field.*, field.value AS field_value, userfield.* 
                    FROM #__acym_field AS field 
                    LEFT JOIN #__acym_user_has_field AS userfield ON field.id = userfield.field_id AND userfield.user_id = '.intval($user->id).' 
                    WHERE field.id NOT IN(1, 2)';

        $allFields = acym_loadObjectList($query);

        foreach ($allFields as $oneField) {
            $fieldOptions = @json_decode(empty($oneField->option) ? '{}' : $oneField->option);
            if (in_array($oneField->type, ['multiple_dropdown', 'radio', 'checkbox', 'single_dropdown'])) {
                $oneField->field_value = json_decode($oneField->field_value, true);
                if (!empty($oneField->value)) {
                    $oneField->value = explode(',', $oneField->value);
                    $values = [];
                    foreach ($oneField->field_value as $oneFieldValue) {
                        foreach ($oneField->value as $oneValue) {
                            if ($oneFieldValue['value'] == $oneValue) {
                                $values[] = $oneFieldValue['title'];
                            }
                        }
                    }
                    $oneField->value = implode(',', $values);
                }
            } elseif ($oneField->type === 'file' && !empty($oneField->value)) {
                $tryJsonDecode = json_decode($oneField->value);
                if (json_last_error() === JSON_ERROR_NONE && is_array($tryJsonDecode)) {
                    $oneField->value = !empty($tryJsonDecode) ? $tryJsonDecode[0] : null;
                }
            } elseif ($oneField->type === 'date') {
                if (empty($fieldOptions->format)) {
                    acym_logError('Date field with ID '.$oneField->id.' without format option');
                    continue;
                }
                $formatToDisplay = explode('%', str_replace('y', 'Y', $fieldOptions->format));
                unset($formatToDisplay[0]);
                if (!empty($oneField->value)) {
                    $oneField->value = acym_date($oneField->value, implode('-', $formatToDisplay));
                }
            }
            $user->{$oneField->namekey} = empty($oneField->value) ? '' : $oneField->value;
        }

        return $user;
    }

    public function getAllSimpleData()
    {
        return acym_loadObjectList('SELECT email, name FROM #__acym_user');
    }

    public function synchSaveCmsUser($user, $isnew, $oldUser = null)
    {
        // If the source is not already defined, we define it here
        $source = acym_getVar('string', 'acy_source', '');
        if (empty($source)) acym_setVar('acy_source', ACYM_CMS);

        if (!$this->config->get('regacy', 0)) return;

        $this->checkVisitor = false;
        $this->sendConf = false;

        $regacyForceConf = $this->config->get('regacy_forceconf', 0);
        /* * * * * * * * * * * * * * * * * * *
         * Step 1: create / update the user  *
         * * * * * * * * * * * * * * * * * * */
        $cmsUser = new \stdClass();
        $cmsUser->email = trim(strip_tags($user['email']));
        if (!acym_isValidEmail($cmsUser->email)) return;
        if (!empty($user['name'])) $cmsUser->name = trim(strip_tags($user['name']));
        if (!$regacyForceConf) $cmsUser->confirmed = 1;
        $cmsUser->active = 1 - intval($user['block']);
        $cmsUser->cms_id = $user['id'];

        if (!$isnew && !empty($oldUser['email']) && $user['email'] != $oldUser['email']) {
            // The user changed its email address, load the current Acy user if any
            $acyUser = $this->getOneByEmail($oldUser['email']);
            if (!empty($acyUser)) $cmsUser->id = $acyUser->id;
        }

        // Just in case of this is an existing user but the e-mail address has been modified by something else
        if (empty($cmsUser->id) && !empty($cmsUser->cms_id)) {
            $acyUser = $this->getOneByCMSId($cmsUser->cms_id);
            if (!empty($acyUser)) $cmsUser->id = $acyUser->id;
        }

        $acyUser = $this->getOneByEmail($cmsUser->email);
        // If an Acy user with the same email address already exists
        if (!empty($acyUser)) {
            // And wasn't linked to the site account, link it
            if (empty($cmsUser->id)) {
                $cmsUser->id = $acyUser->id;
            } elseif ($cmsUser->id != $acyUser->id) {
                // And has a different id, delete it
                $this->delete($acyUser->id);
            }
        } else {
            $cmsUser->source = $source;
        }

        $isnew = $isnew || empty($cmsUser->id);

        $id = $this->save($cmsUser);

        // Force trigger confirmation process on cms user confirmation (send welcome emails, automation, save history...)
        $confirmationRequired = $this->config->get('require_confirmation', 1);
        if (!$isnew && !$regacyForceConf && $user['block'] == 0 && !empty($oldUser['block']) && $confirmationRequired == 1) {
            $this->confirm($id);
        }

        /* * * * * * * * * * * * * * * * * * * * * *
         * Step 2: Handle the user's subscription  *
         * * * * * * * * * * * * * * * * * * * * * */

        $currentSubscription = $this->getSubscriptionStatus($id);

        // In the Acy configuration, we can tell AcyMailing to automatically subscribe newly created users to some lists
        $autoLists = $isnew ? $this->config->get('regacy_autolists', '') : '';
        $autoLists = explode(',', $autoLists);
        acym_arrayToInteger($autoLists);

        $listsClass = new ListClass();
        $allLists = $listsClass->getAll();

        // The user can select some lists on the registration form
        $visibleLists = acym_getVar('string', 'regacy_visible_lists', '');
        $visibleLists = explode(',', $visibleLists);
        acym_arrayToInteger($visibleLists);

        $visibleListsChecked = acym_getVar('array', 'regacy_visible_lists_checked', []);
        acym_arrayToInteger($visibleListsChecked);


        // Handle the unsubscription
        if (!$isnew && !empty($visibleLists)) {
            $currentlySubscribedLists = [];
            foreach ($currentSubscription as $oneSubscription) {
                if ($oneSubscription->status == 1) $currentlySubscribedLists[] = $oneSubscription->list_id;
            }
            $unsubscribeLists = array_intersect($currentlySubscribedLists, array_diff($visibleLists, $visibleListsChecked));
            $this->unsubscribe($id, $unsubscribeLists);
        }

        // Handle the subscription
        $listsToSubscribe = [];
        foreach ($allLists as $oneList) {
            if (!$oneList->active) continue;
            if (!empty($currentSubscription[$oneList->id]) && $currentSubscription[$oneList->id]->status == 1) continue;

            if (in_array($oneList->id, $visibleListsChecked) || (in_array($oneList->id, $autoLists) && !in_array(
                        $oneList->id,
                        $visibleLists
                    ) && empty($currentSubscription[$oneList->id]))) {
                $listsToSubscribe[] = $oneList->id;
            }
        }

        if (!empty($listsToSubscribe)) $this->subscribe($id, $listsToSubscribe);

        if ($isnew) $this->sendNotification($id, 'acy_notification_create');

        // We don't force the confirmation email, or the user is disabled, or he's already confirmed
        $acymailingUser = $this->getOneById($id);
        if (!empty($user['block']) || !empty($acymailingUser->confirmed)) return;

        // New active user, or just activated the user, send the email
        if ($isnew || !empty($oldUser['block'])) {
            if ($confirmationRequired && $regacyForceConf) {
                $this->forceConf = true;
                $this->sendConfirmation($id);
            }

            // Send welcome emails on CMS user confirmation (no Acym confirmation required)
            if (!$confirmationRequired && !empty($oldUser['email'])) {
                $listIDs = acym_loadResultArray('SELECT `list_id` FROM `#__acym_user_has_list` WHERE `status` = 1 AND `user_id` = '.intval($id));
                if (empty($listIDs)) return;
                $listsClass->sendWelcome($id, $listIDs);
            }
        }
    }

    public function synchDeleteCmsUser($userEmail)
    {
        $acyUser = $this->getOneByEmail($userEmail);

        if (empty($acyUser)) return;

        if ($this->config->get('regacy', '0') == 1 && $this->config->get('regacy_delete', '0') == 1) {
            $this->delete($acyUser->id);
        } else {
            acym_query('UPDATE #__acym_user SET `cms_id` = 0 WHERE `id` = '.intval($acyUser->id));
        }
    }

    /**
     * Search users via partial email address
     *
     * @param $pattern String part of the email
     *
     * @return array|mixed
     */
    public function getUsersLikeEmail($pattern)
    {
        $query = 'SELECT id, email FROM #__acym_user WHERE email LIKE '.acym_escapeDB('%'.$pattern.'%');

        return acym_loadObjectList($query);
    }

    public function sendNotification($userId, string $notification, array $params = [])
    {
        if (empty($userId) || $this->blockNotifications) return;

        $notifyUsers = explode(',', $this->config->get($notification));
        if (acym_isAdmin() || empty($notifyUsers)) return;

        $mailerHelper = new MailerHelper();
        $mailerHelper->report = false;
        $mailerHelper->autoAddUser = true;

        $user = $this->getOneById($userId);
        $userField = $this->getAllUserFields($user);
        if (!empty($userField)) {
            foreach ($userField as $map => $value) {
                $mailerHelper->addParam('user:'.$map, $value);
            }
        }

        // Load the subscription
        $rawSubscription = $this->getUserSubscriptionById($userId);
        $subscription = [''];
        foreach ($rawSubscription as $listId => $listData) {
            $currentList = $listData->name.' => ';
            if (intval($listData->status) === 1) {
                $currentList .= acym_translation('ACYM_SUBSCRIBED').' - '.$listData->subscription_date;
            } else {
                $currentList .= acym_translation('ACYM_UNSUBSCRIBED').' - '.$listData->unsubscribe_date;
            }
            $subscription[] = $currentList;
        }
        $mailerHelper->addParam('user:subscription', implode('<br/>', $subscription));

        if (!empty($params)) {
            foreach ($params as $name => $value) {
                $mailerHelper->addParam($name, $value);
            }
        }

        $mailClass = new MailClass();
        foreach ($notifyUsers as $oneUser) {
            if (!acym_isValidEmail($oneUser)) {
                continue;
            }

            $notificationEmail = $mailClass->getOneByName($notification);
            if (!empty($notificationEmail)) {
                $mailerHelper->sendOne($notificationEmail->id, $oneUser);
            }
        }
    }

    public function getMailHistory($userId)
    {
        $query = 'SELECT user_stat.*, mail.id, mail.subject, SUM(url_click.click) as click FROM #__acym_user_stat AS user_stat
                  JOIN #__acym_mail AS mail ON mail.id = user_stat.mail_id
                  LEFT JOIN #__acym_url_click AS url_click ON user_stat.mail_id = url_click.mail_id AND url_click.user_id = '.intval($userId).'
                  WHERE user_stat.user_id = '.intval($userId).' GROUP BY mail_id ORDER BY send_date DESC LIMIT 50';

        $mailHistory = acym_loadObjectList($query, 'mail_id');
        $mailClass = new MailClass();

        return $mailClass->decode($mailHistory);
    }

    public function deleteHistoryPeriod()
    {
        if (empty($this->config->get('delete_user_history_enabled', 0))) return;
        $deleteOverSecond = $this->config->get('delete_user_history', 0);
        if (empty($deleteOverSecond)) return;
        $date = time() - $deleteOverSecond;

        $query = 'DELETE FROM #__acym_history WHERE date < '.intval($date);

        try {
            $status = acym_query($query);
            $message = empty($status) ? '' : acym_translationSprintf('ACYM_DELETE_X_ROWS_TABLE_X', $status, strtolower(acym_translation('ACYM_USER_HISTORY')));
        } catch (\Exception $e) {
            $status = false;
            $message = $e->getMessage();
        }

        return ['status' => $status !== false, 'message' => $message];
    }

    public function resetSubscription($userIds, $lists)
    {
        if (empty($lists)) return false;

        if (!is_array($userIds)) $userIds = [$userIds];
        if (!is_array($lists)) $lists = [$lists];

        acym_arrayToInteger($userIds);
        acym_arrayToInteger($lists);

        foreach ($userIds as $userId) {
            acym_query(
                'DELETE FROM `#__acym_user_has_list` 
                WHERE user_id = '.$userId.' 
                    AND list_id IN ('.implode(',', $lists).')'
            );

            acym_query(
                'DELETE FROM #__acym_queue WHERE user_id = '.$userId.' AND mail_id IN (
                    SELECT followup_mail.mail_id FROM #__acym_followup_has_mail AS followup_mail
                    JOIN #__acym_followup AS followup ON followup.id = followup_mail.followup_id AND followup.list_id IN ('.implode(',', $lists).')
                )'
            );

            $historyClass = new HistoryClass();
            $historyData = acym_translationSprintf('ACYM_LISTS_NUMBERS', implode(', ', $lists));
            $historyClass->insert($userId, 'reset_subscription', [$historyData]);
        }

        return true;
    }

    public function addMissingKeys()
    {
        $usersMissingKey = acym_loadResultArray('SELECT `id` FROM #__acym_user WHERE `key` IS NULL OR `key` = ""');
        foreach ($usersMissingKey as $oneUserId) {
            acym_query('UPDATE #__acym_user SET `key` = '.acym_escapeDB(acym_generateKey(14)).' WHERE `id` = '.intval($oneUserId));
        }

        return count($usersMissingKey);
    }

    public function hasUserAccess($subscriberId): bool
    {
        $userId = acym_currentUserId();
        if (empty($userId)) {
            return false;
        }

        if (acym_isAdmin()) {
            return true;
        }

        $user = $this->getOneById($subscriberId);
        $userEmail = acym_currentUserEmail();
        if (!empty($user) && $user->email === $userEmail) {
            return true;
        }

        $listClass = new ListClass();
        $allowedListIds = $listClass->getManageableLists();
        if (empty($allowedListIds)) {
            return false;
        }

        $query = 'SELECT COUNT(*) FROM #__acym_user AS user 
            JOIN #__acym_user_has_list AS map ON map.user_id = user.id 
            WHERE map.list_id IN ('.implode(',', $allowedListIds).')
                AND user.id = '.intval($subscriberId);

        return acym_loadResult($query) > 0;
    }

    public function getXUsers(array $options = [])
    {
        $limit = $options['limit'] ?? 10;
        $offset = $options['offset'] ?? 0;
        $filters = $options['filters'] ?? [];

        $conditions = [];
        foreach ($filters as $column => $filter) {
            switch ($column) {
                case 'id':
                case 'cms_id':
                case 'active':
                case 'confirmed':
                case 'tracking':
                    $conditions[] = acym_secureDBColumn($column).' = '.intval($filter);
                    break;
                default:
                    $conditions[] = acym_secureDBColumn($column).' LIKE '.acym_escapeDB('%'.$filter.'%');
            }
        }

        if (!empty($options['created_after'])) {
            $conditions[] = 'creation_date > '.acym_escapeDB($options['created_after']);
        }

        if (!empty($options['confirmed_after'])) {
            $conditions[] = 'confirmation_date > '.acym_escapeDB($options['confirmed_after']);
        }

        $query = 'SELECT * FROM #__acym_user';
        if (!empty($conditions)) {
            $query .= ' WHERE '.implode(' AND ', $conditions);
        }

        return acym_loadObjectList($query, $this->pkey, $offset, $limit);
    }
}
