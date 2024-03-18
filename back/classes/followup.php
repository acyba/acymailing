<?php

namespace AcyMailing\Classes;

use AcyMailing\Helpers\PaginationHelper;
use AcyMailing\Libraries\acymClass;

class FollowupClass extends acymClass
{
    var $table = 'followup';
    var $pkey = 'id';

    const DEFAULT_DELAY_UNIT = 86400;
    const DELAY_UNIT = [
        60 => 'ACYM_MINUTES',
        3600 => 'ACYM_HOURS',
        86400 => 'ACYM_DAYS',
        604800 => 'ACYM_WEEKS',
        2628000 => 'ACYM_MONTHS',
    ];

    public function getDelayUnits()
    {
        $return = self::DELAY_UNIT;
        foreach ($return as $key => $value) {
            $return[$key] = acym_translation($value);
        }

        return $return;
    }

    public function getMatchingElements($settings = [])
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

        if (!empty($followup->condition)) $followup->condition = json_decode($followup->condition, true);

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
        if (!empty($element->condition) && is_array($element->condition)) $element->condition = json_encode($element->condition);

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
            $list->type = $listClass::LIST_TYPE_FOLLOWUP;
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
            if (($followupList->send_once == 1 && !empty($followupList->status) && $followupList->status == 1) || (!empty($followupList->status) && $followupList->status != 1)) continue;
            $followupListIds[$listId] = $followupList->id;
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

        $valuesToInsert = [];

        foreach ($followups as $followupId => $mailsInfo) {
            foreach ($mailsInfo as $mailInfo) {
                $sendDate = time() + (intval($mailInfo->delay) * intval($mailInfo->delay_unit));
                $sendDate = acym_date($sendDate, 'Y-m-d H:i:s', false);
                $valuesToInsert[] = ' ('.intval($mailInfo->mail_id).', '.intval($userId).', '.acym_escapeDB($sendDate).', 2) ';
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
        $mailStatNew = new \stdClass();
        $mailStatNew->mail_id = intval($mailId);
        if (empty($mailStat)) {
            $mailStatNew->total_subscribers = 1;
            $mailStatNew->send_date = acym_date('now', 'Y-m-d H:i:s', false);
        } else {
            $mailStatNew->total_subscribers = $mailStat->total_subscribers + 1;
        }

        $mailStatClass->save($mailStatNew);
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
}
