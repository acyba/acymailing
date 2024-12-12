<?php

namespace AcyMailing\Classes;

use AcyMailing\Helpers\PaginationHelper;
use AcyMailing\Libraries\acymClass;

class FollowupClass extends acymClass
{
    const DEFAULT_DELAY_UNIT = 86400;
    const DELAY_UNIT = [
        60 => 'ACYM_MINUTES',
        3600 => 'ACYM_HOURS',
        86400 => 'ACYM_DAYS',
        604800 => 'ACYM_WEEKS',
        2628000 => 'ACYM_MONTHS',
    ];

    public function __construct()
    {
        parent::__construct();

        $this->table = 'followup';
        $this->pkey = 'id';
    }

    public function getDelayUnits()
    {
        $return = self::DELAY_UNIT;
        foreach ($return as $key => $value) {
            $return[$key] = acym_translation($value);
        }

        return $return;
    }

    public function getMatchingElements(array $settings = []): array
    {
        $query = 'SELECT `followup`.*, COUNT(`mails`.`mail_id`) AS nbEmails 
                FROM #__acym_followup AS followup 
                LEFT JOIN #__acym_followup_has_mail AS mails 
                    ON followup.id = mails.followup_id';

        $queryCount = 'SELECT COUNT(*) 
                FROM #__acym_followup AS followup ';

        $filters = [];

        if (!empty($settings['search'])) {
            $filters[] = 'followup.name LIKE '.acym_escapeDB('%'.$settings['search'].'%');
        }

        if (!empty($filters)) {
            $query .= ' WHERE ('.implode(') AND (', $filters).')';
            $queryCount .= ' WHERE ('.implode(') AND (', $filters).')';
        }

        $query .= ' GROUP BY `followup`.`id` ';

        if (empty($settings['ordering'])) $settings['ordering'] = $this->pkey;
        $query .= ' ORDER BY `'.acym_secureDBColumn($settings['ordering']).'`';
        if (!empty($settings['ordering_sort_order'])) $query .= ' '.acym_secureDBColumn(strtoupper($settings['ordering_sort_order']));

        if (empty($settings['offset']) || $settings['offset'] < 0) {
            $settings['offset'] = 0;
        }

        if (empty($settings['elementsPerPage']) || $settings['elementsPerPage'] < 1) {
            $pagination = new PaginationHelper();
            $settings['elementsPerPage'] = $pagination->getListLimit();
        }

        $results = [];
        $results['elements'] = acym_loadObjectList($query, '', $settings['offset'], $settings['elementsPerPage']);
        $urlClickClass = new UrlClickClass();
        foreach ($results['elements'] as $key => $oneFollowup) {
            $results['elements'][$key]->subscribers = $this->getNumberSubscribersByListId($oneFollowup->list_id);
            $this->getGlobalStats($results['elements'][$key], $urlClickClass);
        }

        $results['total'] = acym_loadResult($queryCount);

        return $results;
    }

    public function getNumberSubscribersByListId($listId = 0, $onlySubscribed = false)
    {
        if (empty($listId)) return 0;

        $query = 'SELECT COUNT(*) 
                FROM #__acym_user_has_list 
                WHERE `list_id` = '.intval($listId);

        if ($onlySubscribed) {
            $query .= ' AND `status` = 1';
        }

        return acym_loadResult($query);
    }

    private function getGlobalStats(&$element, $urlClickClass)
    {
        $mailsStats = acym_loadObjectList(
            'SELECT mail_stat.* 
            FROM #__acym_mail_stat AS mail_stat 
            JOIN #__acym_followup_has_mail AS fhm ON fhm.mail_id = mail_stat.mail_id 
            WHERE fhm.followup_id = '.intval($element->id)
        );

        $element->open = 0;
        $element->click = 0;
        $element->income = 0;
        $numberMailSent = 0;
        if (empty($mailsStats)) return;

        foreach ($mailsStats as $key => $mailsStat) {
            $element->open += $mailsStat->open_unique;
            $element->click += $urlClickClass->getNumberUsersClicked($mailsStat->mail_id);
            $numberMailSent += $mailsStat->sent;
        }

        if (!empty($numberMailSent)) {
            $element->open = number_format($element->open / $numberMailSent * 100, 2);
            $element->click = number_format($element->click / $numberMailSent * 100, 2);
        }

        //Tracking sales
        if (!acym_isTrackingSalesActive()) return;

        $trackingSales = acym_loadObject(
            'SELECT SUM(user_stat.tracking_sale) AS sale, user_stat.currency 
            FROM #__acym_user_stat AS user_stat 
            JOIN #__acym_followup_has_mail AS fhm ON fhm.mail_id = user_stat.mail_id
            WHERE fhm.followup_id = '.intval($element->id)
        );

        $element->sale = $trackingSales->sale;
        if (empty($element->currency)) $element->currency = '';
        acym_trigger('getCurrency', [&$element->currency]);
    }

    public function delete($elements)
    {
        if (!is_array($elements)) $elements = [$elements];
        acym_arrayToInteger($elements);

        if (empty($elements)) return 0;

        $emailIds = $this->getEmailsByIds($elements);
        if (!empty($emailIds)) {
            $mailClass = new MailClass();
            $mailClass->delete($emailIds);
        }

        $listIds = $this->getListsByIds($elements);

        $result = parent::delete($elements);

        $listClass = new ListClass();
        $listClass->delete($listIds);

        return $result;
    }

    public function getEmailsByIds($followupIds)
    {
        if (!is_array($followupIds)) $followupIds = [$followupIds];
        acym_arrayToInteger($followupIds);

        if (empty($followupIds)) return [];

        return acym_loadResultArray(
            'SELECT `mail_id` 
            FROM #__acym_followup_has_mail 
            WHERE `followup_id` IN ('.implode(', ', $followupIds).')
            ORDER BY `delay`*`delay_unit` ASC'
        );
    }

    public function getListsByIds($followupIds)
    {
        if (!is_array($followupIds)) $followupIds = [$followupIds];
        acym_arrayToInteger($followupIds);

        if (empty($followupIds)) return [];

        return acym_loadResultArray(
            'SELECT `list_id` 
            FROM #__acym_followup
            WHERE `id` IN ('.implode(', ', $followupIds).')'
        );
    }

    public function getOneById($id)
    {
        $followup = parent::getOneById($id);

        if (empty($followup)) return false;

        if (!empty($followup->condition)) {
            $followup->condition = json_decode($followup->condition, true);
        }

        if (!empty($followup->loop_mail_skip)) {
            $followup->loop_mail_skip = json_decode($followup->loop_mail_skip, true);
        }

        return $followup;
    }

    public function getOneByListId($listId)
    {
        $followup = acym_loadObject('SELECT * FROM `#__acym_followup` WHERE `list_id` = '.intval($listId));

        if (empty($followup)) return null;

        if (!empty($followup->condition)) $followup->condition = json_decode($followup->condition, true);

        return $followup;
    }

    public function save($element)
    {
        if (!empty($element->condition) && is_array($element->condition)) {
            $element->condition = json_encode($element->condition);
        }

        if (!empty($element->loop_mail_skip) && is_array($element->loop_mail_skip)) {
            $element->loop_mail_skip = json_encode($element->loop_mail_skip);
        }

        $this->updateListFollowup($element);

        return parent::save($element);
    }

    private function updateListFollowup(&$element)
    {
        $listClass = new ListClass();
        if (empty($element->list_id)) {
            $list = new \stdClass();
            $list->name = $element->display_name;
            $list->description = '';
            $randColor = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9', 'a', 'b', 'c', 'd', 'e', 'f'];
            $list->color = '#'.$randColor[rand(0, 15)].$randColor[rand(0, 15)].$randColor[rand(0, 15)].$randColor[rand(0, 15)].$randColor[rand(0, 15)].$randColor[rand(0, 15)];
            $list->access = '';
            $list->type = ListClass::LIST_TYPE_FOLLOWUP;
        } else {
            $list = $listClass->getOneById($element->list_id);
            $list->name = $element->display_name;
        }

        $element->list_id = $listClass->save($list);
    }

    public function getTriggerNiceName($trigger)
    {
        $triggers = [];
        acym_trigger('getFollowupTriggers', [&$triggers]);
        if (key_exists($trigger, $triggers)) {
            return $triggers[$trigger];
        }

        return '';
    }

    public function getConditionSummary($condition, $trigger)
    {
        $statusArray = [
            'is' => acym_strtolower(acym_translation('ACYM_IS')),
            'is_not' => acym_strtolower(acym_translation('ACYM_IS_NOT')),
        ];
        $listClass = new ListClass();
        $segmentClass = new SegmentClass();

        $return = [];
        if (empty($condition['lists_status']) || empty($condition['lists'])) {
            $return[] = acym_translation('ACYM_NO_CONDITION_USER_SUBSCRIPTION');
        } else {
            $lists = $listClass->getListsByIds($condition['lists']);
            $listsToDisplay = [];
            foreach ($lists as $list) {
                $listsToDisplay[] = $list->name;
            }

            $translationKey = $trigger === 'user_subscribe' ? 'ACYM_X_SUBSCRIBING_X_LIST' : 'ACYM_X_SUBSCRIBED_X_LIST';
            $return[] = acym_translationSprintf($translationKey, acym_strtolower($statusArray[$condition['lists_status']]), implode(',', $listsToDisplay));
        }


        if (empty($condition['segments_status']) || empty($condition['segments'])) {
            $return[] = acym_translation('ACYM_NO_CONDITION_SEGMENT');
        } else {
            $segments = $segmentClass->getByIds($condition['segments']);
            $segmentsToDisplay = [];
            foreach ($segments as $segment) {
                $segmentsToDisplay[] = $segment->name;
            }
            $return[] = acym_translationSprintf('ACYM_X_PART_X_SEGMENT', acym_strtolower($statusArray[$condition['segments_status']]), implode(',', $segmentsToDisplay));
        }

        acym_trigger('getFollowupConditionSummary', [&$return, $condition, $trigger, $statusArray]);

        return $return;
    }

    private function getKeyMailArray($key, $mailsKey)
    {
        if (!empty($mailsKey) && in_array($key, $mailsKey)) {
            return $this->getKeyMailArray(++$key, $mailsKey);
        }

        return $key;
    }

    public function getOneByIdWithMails($id)
    {
        $followup = $this->getOneById($id);

        if (empty($followup)) return false;

        $mailClass = new MailClass();
        $mails = $mailClass->decode(
            acym_loadObjectList(
                'SELECT mail.subject, mail.id, followup_mail.delay, followup_mail.delay_unit 
            FROM #__acym_mail AS mail 
            JOIN #__acym_followup_has_mail AS followup_mail ON mail.id = followup_mail.mail_id AND followup_mail.followup_id = '.intval($id),
                'id'
            )
        );

        $return = [];
        foreach ($mails as $key => $mail) {
            $mail->delay_display = $this->getDelayDisplay($mail->delay, $mail->delay_unit);
            $mail->edit_link = acym_completeLink('mails&task=edit&step=editEmail&type=followup&id='.$key.'&followup_id='.$id.'&return='.urlencode(acym_currentURL()), false, true);
            $finalKey = $this->getKeyMailArray(intval($mail->delay) * intval($mail->delay_unit), array_keys($return));
            $return[$finalKey] = $mail;
        }

        ksort($return);

        $followup->mails = empty($return) ? [] : $return;

        return $followup;
    }

    private function getDelayDisplay($delay, $delayUnit)
    {
        $delayUnits = $this->getDelayUnits();

        return acym_translationSprintf('ACYM_X_PLUS_X_FOLLOW_UP', $delayUnits[$delayUnit], $delay);
    }

    public function getDelaySettingToMail(&$mail, $followupId)
    {
        $settings = acym_loadObject('SELECT delay, delay_unit FROM #__acym_followup_has_mail WHERE followup_id = '.intval($followupId).' AND mail_id = '.intval($mail->id));

        if (empty($settings)) return false;

        $mail->delay = $settings->delay;
        $mail->delay_unit = $settings->delay_unit;

        return true;
    }

    public function saveDelaySettings($followupData, $mailId)
    {
        if (empty($followupData['id']) || empty($followupData['delay_unit']) || empty($mailId)) return false;

        acym_arrayToInteger($followupData);

        $affectedRow = acym_query(
            'INSERT INTO #__acym_followup_has_mail (`mail_id`, `followup_id`, `delay`, `delay_unit`) VALUE ('.intval(
                $mailId
            ).', '.$followupData['id'].', '.$followupData['delay'].', '.$followupData['delay_unit'].') ON DUPLICATE KEY UPDATE delay = '.$followupData['delay'].', delay_unit = '.$followupData['delay_unit'].''
        );

        return $affectedRow !== false;
    }

    public function duplicateMail($mailId, $id)
    {
        if (empty($mailId) || empty($id)) return false;
        $mailClass = new MailClass();
        $mail = $mailClass->getOneById($mailId);

        if (empty($mail)) return false;

        $delaySettings = new \stdClass();
        $delaySettings->id = $mail->id;
        $this->getDelaySettingToMail($delaySettings, $id);

        unset($mail->id);
        $mail->name .= '_copy';

        $mail->id = $mailClass->save($mail);
        if (empty($mail->id)) return false;

        $affectedRow = acym_query(
            'INSERT INTO #__acym_followup_has_mail (`mail_id`, `followup_id`, `delay`, `delay_unit`) VALUE ('.intval($mail->id).', '.$id.', '.intval(
                $delaySettings->delay
            ).', '.intval($delaySettings->delay_unit).')'
        );

        return !empty($affectedRow);
    }

    public function deleteMail($mailId)
    {
        if (empty($mailId)) return false;
        $mailClass = new MailClass();

        return $mailClass->delete($mailId);
    }

    public function getFollowupsWithMailsInfoByIds($followupIds)
    {
        if (!is_array($followupIds)) $followupIds = [$followupIds];
        acym_arrayToInteger($followupIds);

        $mailsInfo = acym_loadObjectList(
            'SELECT followup_mail.*, followup.send_once
                  FROM #__acym_followup_has_mail AS followup_mail
                  JOIN #__acym_followup AS followup ON followup.id = followup_mail.followup_id AND followup_mail.followup_id IN ('.implode(',', $followupIds).')'
        );

        if (empty($mailsInfo)) return [];

        $return = [];

        foreach ($mailsInfo as $mailInfo) {
            if (!isset($return[$mailInfo->followup_id])) $return[$mailInfo->followup_id] = [];
            $return[$mailInfo->followup_id][] = $mailInfo;
        }

        return $return;
    }

    public function subscribeUserToFollowupList(array $followupIds, $userId): array
    {
        acym_arrayToInteger($followupIds);

        $followupLists = acym_loadObjectList(
            'SELECT followup.list_id, IF(user_list.status IS NULL, "", status) AS status, followup.id, followup.send_once FROM #__acym_followup AS followup
             LEFT JOIN #__acym_user_has_list AS user_list ON followup.list_id = user_list.list_id AND (user_list.user_id = '.intval($userId).' OR user_list.user_id IS NULL)
             WHERE followup.id IN ('.implode(',', $followupIds).')',
            'list_id'
        );

        if (empty($followupLists)) {
            return [];
        }

        $followupListIds = [];
        foreach ($followupLists as $listId => $followupList) {
            // The user unsubscribed, don't re-subscribe them
            if (in_array($followupList->status, [0, '0'], true)) {
                continue;
            }

            if ($followupList->status === '' || $followupList->send_once == 0) {
                $followupListIds[$listId] = $followupList->id;
            }
        }

        if (empty($followupListIds)) {
            return [];
        }

        $userClass = new UserClass();
        $userClass->subscribe($userId, array_keys($followupListIds), false);

        return $followupListIds;
    }

    public function addFollowupEmailsQueue($followupTrigger, $userId, $params = [])
    {
        if (empty($followupTrigger) || empty($userId)) return false;

        $followupToTrigger = acym_loadObjectList('SELECT * FROM #__acym_followup WHERE active = 1 AND `trigger` = '.acym_escapeDB($followupTrigger), 'id');

        if (empty($followupToTrigger)) return false;

        foreach ($followupToTrigger as $key => $followup) {
            if (!empty($followup->condition)) {
                $followupToTrigger[$key]->condition = json_decode($followup->condition, true);
            }
        }

        acym_trigger('matchFollowupsConditions', [&$followupToTrigger, $userId, $params]);

        if (empty($followupToTrigger)) return false;

        $followupIds = $this->subscribeUserToFollowupList(array_keys($followupToTrigger), $userId);

        if (empty($followupIds)) return false;

        $followups = $this->getFollowupsWithMailsInfoByIds($followupIds);

        if (empty($followups)) return false;

        $priority = $this->config->get('followup_max_priority', 0) == 1 ? 1 : 2;

        $valuesToInsert = [];

        foreach ($followups as $followupId => $mailsInfo) {
            foreach ($mailsInfo as $mailInfo) {
                $sendDate = time() + (intval($mailInfo->delay) * intval($mailInfo->delay_unit));
                $sendDate = acym_date($sendDate, 'Y-m-d H:i:s', false);
                $valuesToInsert[] = ' ('.intval($mailInfo->mail_id).', '.intval($userId).', '.acym_escapeDB($sendDate).', '.intval($priority).') ';
                $this->addMailStat($mailInfo->mail_id);
            }
            $followupToTrigger[$followupId]->last_trigger = time();
            $this->save($followupToTrigger[$followupId]);
        }

        $query = 'INSERT IGNORE INTO #__acym_queue (`mail_id`, `user_id`, `sending_date`, `priority`) VALUES '.implode(',', $valuesToInsert);

        $affectedRows = acym_query($query);

        return !empty($affectedRows);
    }

    private function addMailStat($mailId)
    {
        $mailStatClass = new MailStatClass();
        $mailStat = $mailStatClass->getOneRowByMailId($mailId);
        $newMailStat = [
            'mail_id' => intval($mailId),
            'total_subscribers' => 1,
        ];
        if (empty($mailStat)) {
            $newMailStat['send_date'] = acym_date('now', 'Y-m-d H:i:s', false);
        }

        $mailStatClass->save($newMailStat);
    }

    public function getFollowupDailyBases()
    {
        $triggers = [];
        acym_trigger('onAcymGetFollowupDailyBases', [&$triggers]);

        return acym_loadObjectList('SELECT * FROM #__acym_followup WHERE `trigger` IN ("'.implode('","', $triggers).'") AND active  = 1');
    }

    public function queueForSubscribers($emailId)
    {
        if (empty($emailId)) return false;

        $mailInfo = acym_loadObject(
            'SELECT map.*, followup.list_id 
            FROM #__acym_followup_has_mail AS map 
            JOIN #__acym_followup AS followup 
                ON followup.id = map.followup_id 
            WHERE map.mail_id = '.intval($emailId)
        );
        if (empty($mailInfo)) return false;

        $this->addMailStat($mailInfo->mail_id);

        $delay = intval($mailInfo->delay) * intval($mailInfo->delay_unit);
        $query = 'INSERT IGNORE INTO #__acym_queue (`mail_id`, `user_id`, `sending_date`, `priority`) 
                SELECT '.intval($mailInfo->mail_id).', user_id, TIMESTAMPADD(SECOND, '.$delay.', subscription_date), 2
                FROM #__acym_user_has_list 
                WHERE status = 1 
                    AND list_id = '.intval($mailInfo->list_id);

        return acym_query($query);
    }

    public function getXFollowups(array $options): array
    {
        $limit = $options['limit'] ?? 10;
        $offset = $options['offset'] ?? 0;
        $filters = $options['filters'] ?? [];

        $conditions = [];
        foreach ($filters as $column => $filter) {
            switch ($column) {
                case 'id':
                case 'active':
                case 'send_one':
                    $conditions[] = acym_secureDBColumn($column).' = '.intval($filter);
                    break;
                case 'name':
                case 'trigger':
                case 'display_name':
                    $conditions[] = '`'.acym_secureDBColumn($column).'` LIKE '.acym_escapeDB('%'.$filter.'%');
                    break;
                default:
                    $conditions[] = acym_secureDBColumn($column).' = '.acym_escapeDB('%'.$filter.'%');
            }
        }

        $query = 'SELECT * FROM #__acym_followup ';

        if (!empty($conditions)) {
            $query .= ' WHERE '.implode(' AND ', $conditions);
        }

        $followUps = acym_loadObjectList($query, $this->pkey, $offset, $limit);

        if (empty($followUps)) {
            return [];
        }

        foreach ($followUps as &$followUp) {
            if (!empty($followUp->condition)) {
                $followUp->condition = json_decode($followUp->condition, true);
            }
        }

        return $followUps;
    }

    /**
     * @param string $mailId
     *
     * @return object|null
     */
    public function getOneByMailId(int $mailId): ?object
    {
        return acym_loadObject(
            'SELECT followup.* 
            FROM #__acym_followup AS followup
            JOIN #__acym_followup_has_mail AS map
                 ON followup.id = map.followup_id
            WHERE mail_id = '.intval($mailId)
        );
    }

    /**
     * @param $followupId
     *
     * @return object
     */
    public function getLastEmail(int $followupId): object
    {
        return acym_loadObject(
            'SELECT mail_id as id, delay * delay_unit as totalDelay FROM #__acym_followup_has_mail WHERE followup_id = '.intval($followupId).' ORDER BY totalDelay DESC'
        );
    }

    /**
     * @param int   $followUpId
     * @param int   $userId
     * @param int   $additionalDelay
     * @param array $mailIdToSkip
     *
     * @return void
     */
    public function triggerFollowUp(int $followUpId, int $userId, int $additionalDelay = 0, array $mailIdToSkip = []): void
    {
        $followUp = $this->getOneById($followUpId);

        if (empty($followUp)) {
            return;
        }

        $values = intval($userId).', '.intval($followUp->list_id).', 1, '.acym_escapeDB(acym_date(time(), 'Y-m-d H:i:s'));

        acym_query('INSERT IGNORE INTO #__acym_user_has_list (`user_id`, `list_id`, `status`, `subscription_date`) VALUES ('.$values.') ON DUPLICATE KEY UPDATE status = 1');

        $followups = $this->getFollowupsWithMailsInfoByIds($followUpId);
        $allValues = [];
        foreach ($followups as $mails) {
            foreach ($mails as $mail) {
                if (in_array($mail->mail_id, $mailIdToSkip)) {
                    continue;
                }
                $sendDate = time() + (intval($mail->delay) * intval($mail->delay_unit)) + $additionalDelay;
                $sendDate = acym_escapeDB(acym_date($sendDate, 'Y-m-d H:i:s', false));
                $allValues[] = '('.intval($mail->mail_id).', '.intval($userId).', '.$sendDate.', '.$this->config->get('priority_newsletter', 3).', 0'.')';
            }
        }

        $queryToProcess = 'INSERT IGNORE INTO #__acym_queue (`mail_id`, `user_id`, `sending_date`, `priority`, `try`) VALUES '.implode(', ', $allValues);

        if (empty(acym_query($queryToProcess))) {
            acym_logError("Error adding follow up email to queue \n Values: ".implode(', ', $allValues)." \n Error: ".acym_getDBError());
        }
    }
}
