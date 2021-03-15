<?php

namespace AcyMailing\Classes;

use AcyMailing\Controllers\SegmentsController;
use AcyMailing\Helpers\AutomationHelper;
use AcyMailing\Helpers\PaginationHelper;
use AcyMailing\Libraries\acymClass;

class CampaignClass extends acymClass
{
    var $table = 'campaign';
    var $pkey = 'id';
    const SENDING_TYPE_NOW = 'now';
    const SENDING_TYPE_SCHEDULED = 'scheduled';
    const SENDING_TYPE_AUTO = 'auto';
    const SENDING_TYPES = [
        self::SENDING_TYPE_NOW,
        self::SENDING_TYPE_SCHEDULED,
        self::SENDING_TYPE_AUTO,
    ];
    var $encodedColumns = ['sending_params'];

    public function getConstNow()
    {
        return self::SENDING_TYPE_NOW;
    }

    public function getConstScheduled()
    {
        return self::SENDING_TYPE_SCHEDULED;
    }

    public function getConstAuto()
    {
        return self::SENDING_TYPE_AUTO;
    }

    public function decode($campaign, $decodeMail = true)
    {
        if (empty($campaign)) return $campaign;

        if (is_array($campaign)) {
            foreach ($campaign as $i => $oneCampaign) {
                $campaign[$i] = $this->decode($oneCampaign, false);
            }
        }

        foreach ($this->encodedColumns as $oneColumn) {
            if (!isset($campaign->$oneColumn)) continue;

            $campaign->$oneColumn = empty($campaign->$oneColumn) ? [] : json_decode($campaign->$oneColumn, true);
        }

        if ($decodeMail) {
            $mailClass = new MailClass();
            $campaign = $mailClass->decode($campaign);
        }

        return $campaign;
    }

    public function getAll($key = null)
    {
        $allCampaigns = parent::getAll($key);

        return $this->decode($allCampaigns);
    }

    public function getMatchingElements($settings = [])
    {
        $tagClass = new TagClass();
        $mailClass = new MailClass();

        $query = 'SELECT campaign.*, mail.name, mail_stat.sent AS subscribers, mail_stat.open_unique FROM #__acym_campaign AS campaign';
        $queryCount = 'SELECT campaign.* FROM #__acym_campaign AS campaign';


        $filters = [];
        $mailIds = [];

        $query .= ' JOIN #__acym_mail AS mail ON campaign.mail_id = mail.id';
        $queryCount .= ' JOIN #__acym_mail AS mail ON campaign.mail_id = mail.id';
        $query .= ' LEFT JOIN #__acym_mail_stat AS mail_stat ON campaign.mail_id = mail_stat.mail_id';

        if (!acym_isAdmin()) {
            $filters[] = 'mail.creator_id = '.intval(acym_currentUserId());
        }

        if (!empty($settings['tag'])) {
            $tagJoin = ' JOIN #__acym_tag AS tag ON campaign.mail_id = tag.id_element';
            $query .= $tagJoin;
            $queryCount .= $tagJoin;
            $filters[] = 'tag.name = '.acym_escapeDB($settings['tag']);
            $filters[] = 'tag.type = "mail"';
        }

        if (!empty($settings['search'])) {
            $filters[] = 'mail.name LIKE '.acym_escapeDB('%'.$settings['search'].'%');
        }

        if ($settings['status'] != 'generated') {
            $operator = $settings['element_tab'] == 'campaigns_auto' ? '=' : '!=';
            if ($settings['element_tab'] == 'campaigns_auto') {
                $filters[] = 'campaign.sending_type '.$operator.' '.acym_escapeDB(self::SENDING_TYPE_AUTO);
            } elseif ($settings['element_tab'] == 'campaigns') {
                $filters[] = 'campaign.sending_type IN ('.acym_escapeDB(self::SENDING_TYPE_NOW).', '.acym_escapeDB(self::SENDING_TYPE_SCHEDULED).')';
            } elseif (!empty($settings['element_tab'])) {
                acym_trigger('onAcymCampaignAddFiltersSpecificListing', [&$filters, $settings['element_tab']]);
            }
        }

        if (!empty($filters)) {
            $query .= ' WHERE ('.implode(') AND (', $filters).')';
            $queryCount .= ' WHERE ('.implode(') AND (', $filters).')';
        }

        $statusRequests = [
            'all' => '(campaign.parent_id IS NULL OR campaign.parent_id = 0)',
            'scheduled' => 'campaign.sending_type = '.acym_escapeDB(self::SENDING_TYPE_SCHEDULED).' AND (campaign.parent_id IS NULL OR campaign.parent_id = 0)',
            'sent' => 'campaign.sent = 1 AND (campaign.parent_id IS NULL OR campaign.parent_id = 0)',
            'draft' => 'campaign.draft = 1 AND (campaign.parent_id IS NULL OR campaign.parent_id = 0)',
        ];

        if ($settings['element_tab'] == 'campaigns_auto') {
            $statusRequests['generated'] = 'campaign.sending_type = '.acym_escapeDB(self::SENDING_TYPE_NOW).' AND campaign.parent_id  > 0';
        }

        if (empty($settings['status'])) $settings['status'] = 'all';
        if (!empty($statusRequests[$settings['status']])) {
            $query .= empty($filters) ? ' WHERE ' : ' AND ';
            $query .= $statusRequests[$settings['status']];
        }

        if (!empty($settings['ordering']) && !empty($settings['ordering_sort_order'])) {
            $table = in_array($settings['ordering'], ['name', 'creation_date']) ? 'mail' : 'campaign';
            $query .= ' ORDER BY '.$table.'.'.acym_secureDBColumn($settings['ordering']).' '.acym_secureDBColumn(strtoupper($settings['ordering_sort_order']));
        }

        if (empty($settings['offset']) || $settings['offset'] < 0) {
            $settings['offset'] = 0;
        }

        if (empty($settings['elementsPerPage']) || $settings['elementsPerPage'] < 1) {
            $pagination = new PaginationHelper();
            $settings['elementsPerPage'] = $pagination->getListLimit();
        }

        $results['elements'] = $this->decode(acym_loadObjectList($query, '', $settings['offset'], $settings['elementsPerPage']));

        foreach ($results['elements'] as $oneCampaign) {
            array_push($mailIds, $oneCampaign->mail_id);
            $oneCampaign->tags = '';
        }

        $tags = $tagClass->getAllTagsByTypeAndElementIds('mail', $mailIds);
        $lists = $mailClass->getAllListsWithCountSubscribersByMailIds($mailIds);

        $isMultilingual = acym_isMultilingual();

        $urlClickClass = new UrlClickClass();
        foreach ($results['elements'] as $i => $oneCampaign) {
            $results['elements'][$i]->tags = [];
            $results['elements'][$i]->lists = [];
            $results['elements'][$i]->automation_id = null;

            foreach ($tags as $tag) {
                if ($oneCampaign->id == $tag->id_element) {
                    $results['elements'][$i]->tags[] = $tag;
                }
            }

            foreach ($lists as $list) {
                if ($oneCampaign->mail_id == $list->mail_id) {
                    array_push($results['elements'][$i]->lists, $list);
                }
            }

            if ($settings['element_tab'] == 'campaigns_auto' && $settings['status'] != 'generated') {
                $this->getStatsCampaignAuto($results['elements'][$i], $urlClickClass);
            } else {
                if ($isMultilingual) {
                    $this->prepareStatsCampaign($results['elements'][$i]);
                }
                $this->getStatsCampaign($results['elements'][$i], $urlClickClass);
            }
        }

        $results['total'] = acym_loadObjectList($queryCount);

        return $results;
    }

    private function prepareStatsCampaign(&$element)
    {
        $query = 'SELECT SUM(mailstat.sent) AS subscribers, SUM(mailstat.open_unique) AS open_unique FROM #__acym_mail_stat AS mailstat
                  LEFT JOIN #__acym_mail AS mail ON mail.id = mailstat.mail_id WHERE mail.parent_id = '.intval($element->mail_id);
        $stats = acym_loadObject($query);
        $element->subscribers += $stats->subscribers;
        $element->open_unique += $stats->open_unique;
    }

    public function getStatsCampaign(&$element, $urlClickClass)
    {
        $element->open = 0;
        if (!empty($element->subscribers)) {
            $element->open = number_format($element->open_unique / $element->subscribers * 100, 2);

            $clicksNb = $urlClickClass->getNumberUsersClicked($element->mail_id);
            $element->click = number_format($clicksNb / $element->subscribers * 100, 2);
        }

        //Tracking sales
        $element->sale = 0;
        $element->currency = '';
        if (!acym_isTrackingSalesActive()) return;

        if (acym_isMultilingual()) {
            $trackingSales = acym_loadObject(
                'SELECT SUM(tracking_sale) as sale, currency FROM #__acym_user_stat WHERE mail_id IN (SELECT id FROM #__acym_mail WHERE id = '.intval(
                    $element->mail_id
                ).' OR parent_id = '.intval($element->mail_id).') AND currency IS NOT NULL'
            );
        } else {
            $trackingSales = acym_loadObject(
                'SELECT SUM(tracking_sale) as sale, currency FROM #__acym_user_stat WHERE mail_id = '.intval($element->mail_id).' AND currency IS NOT NULL'
            );
        }
        $this->formatSaleTracking($element, $trackingSales);
    }

    private function getStatsCampaignAuto(&$element, $urlClickClass)
    {
        $generatedMailsStats = acym_loadObjectList(
            'SELECT mail_stat.* FROM #__acym_mail AS mail JOIN #__acym_mail_stat AS mail_stat ON mail.id = mail_stat.mail_id WHERE mail.id IN (SELECT mail_id FROM #__acym_campaign WHERE parent_id = '.intval(
                $element->id
            ).')'
        );
        $element->open = 0;
        $element->click = 0;
        $element->subscribers = 0;
        if (empty($generatedMailsStats)) return;

        foreach ($generatedMailsStats as $key => $mailsStat) {
            $element->open += $mailsStat->open_unique;
            $element->click += $urlClickClass->getNumberUsersClicked($element->id);
            $element->subscribers += $mailsStat->sent;
        }

        if (!empty($element->subscribers)) {
            $element->open = number_format($element->open / $element->subscribers * 100, 2);
            $element->click = number_format($element->click / $element->subscribers * 100, 2);
        }

        //Tracking sales
        if (!acym_isTrackingSalesActive()) return;

        $trackingSales = acym_loadObject(
            'SELECT SUM(user_stat.tracking_sale) as sale, user_stat.currency 
            FROM #__acym_user_stat AS user_stat 
            WHERE mail_id IN (SELECT mail_id FROM #__acym_campaign WHERE parent_id = '.intval($element->id).') AND currency IS NOT NULL'
        );
        $this->formatSaleTracking($element, $trackingSales);
    }

    private function formatSaleTracking(&$element, $trackingSales)
    {
        $element->sale = $trackingSales->sale;
        if (empty($element->currency)) $element->currency = '';
        acym_trigger('getCurrency', [&$element->currency]);
    }

    public function getOneById($id)
    {
        return $this->decode(acym_loadObject('SELECT campaign.* FROM #__acym_campaign AS campaign WHERE campaign.id = '.intval($id)));
    }

    public function getOneByIdWithMail($id)
    {
        $query = 'SELECT campaign.*, mail.name, mail.subject, mail.body, mail.from_name, mail.from_email, mail.reply_to_name, mail.reply_to_email, mail.bcc, mail.links_language, mail.tracking, mail.translation
                FROM #__acym_campaign AS campaign
                JOIN #__acym_mail AS mail ON campaign.mail_id = mail.id
                WHERE campaign.id = '.intval($id);

        return $this->decode(acym_loadObject($query));
    }

    public function get($identifier, $column = 'id')
    {
        return $this->decode(acym_loadObject('SELECT campaign.* FROM #__acym_campaign AS campaign WHERE campaign.'.acym_secureDBColumn($column).' = '.acym_escapeDB($identifier)));
    }

    public function getAllCampaignsNameMailId()
    {
        $query = 'SELECT m.id, m.name 
                FROM #__acym_campaign AS c 
                LEFT JOIN #__acym_mail AS m ON c.mail_id = m.id';

        return $this->decode(acym_loadObjectList($query));
    }

    public function getOneCampaignByMailId($mailId)
    {
        return $this->decode(acym_loadObject('SELECT * FROM #__acym_campaign WHERE mail_id = '.intval($mailId)));
    }

    public function getAutoCampaignFromGeneratedMailId($mailId)
    {
        $queryCampaign = 'SELECT * FROM #__acym_campaign WHERE id = (SELECT parent_id FROM #__acym_campaign WHERE mail_id = '.intval($mailId).')';

        return $this->decode(acym_loadObject($queryCampaign));
    }

    public function manageListsToCampaign($listsIds, $mailId, $unselectedListIds = [])
    {
        if (!empty($unselectedListIds)) {
            acym_arrayToInteger($unselectedListIds);
            acym_query('DELETE FROM #__acym_mail_has_list WHERE mail_id = '.intval($mailId).' AND list_id IN ('.implode(', ', $unselectedListIds).')');
        }

        acym_arrayToInteger($listsIds);
        if (empty($listsIds)) return false;

        $values = [];
        $listsIds = array_unique($listsIds);
        foreach ($listsIds as $id) {
            array_push($values, '('.intval($mailId).', '.intval($id).')');
        }

        if (!empty($values)) {
            acym_query('INSERT IGNORE INTO #__acym_mail_has_list (`mail_id`, `list_id`) VALUES '.implode(',', $values));
        }

        return true;
    }

    /**
     * @param $campaign
     *
     * @return bool|mixed
     */
    public function save($campaignToSave)
    {
        $campaign = clone $campaignToSave;
        if (isset($campaign->tags)) {
            $tags = $campaign->tags;
            unset($campaign->tags);
        }

        foreach ($campaign as $oneAttribute => $value) {
            if (in_array($oneAttribute, $this->encodedColumns)) {
                $campaign->$oneAttribute = json_encode(empty($value) ? [] : $value);
            } else {
                if (empty($value)) continue;
                $campaign->$oneAttribute = strip_tags($value);
            }
        }

        $campaignID = parent::save($campaign);

        if (!empty($campaignID) && isset($tags)) {
            $tagClass = new TagClass();
            $tagClass->setTags('mail', $campaign->mail_id, $tags);
        }

        return $campaignID;
    }

    public function onlyManageableCampaigns(&$elements)
    {
        if (acym_isAdmin()) return;

        $idCurrentUser = acym_currentUserId();
        if (empty($idCurrentUser)) return;

        $manageable = acym_loadResultArray(
            'SELECT campaign.id 
            FROM #__acym_campaign AS campaign 
            JOIN #__acym_mail AS mail ON campaign.mail_id = mail.id 
            WHERE mail.creator_id = '.intval($idCurrentUser)
        );
        $elements = array_intersect($elements, $manageable);
    }

    /**
     * Delete a campaign. Needs to delete the tag associated with the campaign and the mail attached to the campaign.
     * Deleting a mail of a campaign needs to clean the association table mail_has_list and its tags
     *
     * @param $elements
     *
     * @return bool|int|null
     */
    public function delete($elements)
    {
        if (!is_array($elements)) $elements = [$elements];
        acym_arrayToInteger($elements);
        $this->onlyManageableCampaigns($elements);

        if (empty($elements)) return 0;

        $mailsToDelete = [];
        foreach ($elements as $id) {
            $mailsToDelete[] = acym_loadResult('SELECT mail_id FROM #__acym_campaign WHERE id = '.intval($id));
            acym_query('UPDATE #__acym_campaign SET mail_id = NULL WHERE id = '.intval($id));
        }

        $mailClass = new MailClass();
        $mailClass->delete($mailsToDelete);

        return parent::delete($elements);
    }

    public function countUsersCampaign($campaignID)
    {
        $campaign = $this->getOneById($campaignID);
        if (empty($campaign)) return 0;

        $mailClass = new MailClass();
        $lists = $mailClass->getAllListsByMailId($campaign->mail_id);
        $listsIds = [];
        foreach ($lists as $list) {
            $listsIds[] = $list->id;
        }

        $automationHelperBase = new AutomationHelper();

        $filters = $this->getFilterCampaign($campaign->sending_params);

        $automationHelpers = [];

        foreach ($filters as $or => $orValues) {
            $automationHelpers[$or] = new AutomationHelper();
            foreach ($orValues as $and => $andValues) {
                $and = intval($and);
                foreach ($andValues as $filterName => $options) {
                    acym_trigger('onAcymProcessFilter_'.$filterName, [&$automationHelpers[$or], &$options, &$and]);
                }
            }
        }

        $join = $this->config->get('require_confirmation', 1) == 1 ? ' AND user.confirmed = 1' : '';

        $userIds = [];
        if (empty($automationHelpers)) {
            $automationHelperBase->join['user_list'] = ' #__acym_user_has_list AS user_list ON user_list.user_id = user.id AND user_list.list_id IN ('.implode(
                    ',',
                    $listsIds
                ).') and user_list.status = 1 '.$join;
            $userIds = acym_loadResultArray($automationHelperBase->getQuery(['user.id']));
        } else {
            foreach ($automationHelpers as $key => $automationHelper) {
                $automationHelper->join['user_list'] = ' #__acym_user_has_list AS user_list ON user_list.user_id = user.id AND user_list.list_id IN ('.implode(
                        ',',
                        $listsIds
                    ).') and user_list.status = 1 '.$join;
                $userIds = array_merge($userIds, acym_loadResultArray($automationHelper->getQuery(['user.id'])));
            }
            $userIds = array_unique($userIds);
        }


        $count = count($userIds);

        return $count;
    }

    public function getFilterCampaign($sendingParams)
    {
        $filters = [0 => []];
        if (!empty($sendingParams['segment'])) {
            if (!empty($sendingParams['segment']['filters'])) {
                $filters = $sendingParams['segment']['filters'];
            } else {
                $segmentClass = new SegmentClass();
                $segment = $segmentClass->getOneById($sendingParams['segment']['segment_id']);
                if (!empty($segment)) $filters = $segment->filters;
            }
        }

        return $filters;
    }

    public function send($campaignID, $result = 0)
    {
        // Make sure the email we're trying to send exists
        $campaign = $this->getOneById($campaignID);

        if (empty($campaign->mail_id)) {
            $this->errors[] = acym_translation('ACYM_EMAIL_NOT_FOUND');

            return false;
        }

        $filters = $this->getFilterCampaign($campaign->sending_params);

        foreach ($filters as $key => $filter) {
            acym_trigger('onAcymSendCampaignSpecial', [$campaign, &$filters[$key]]);
        }

        // Make sure some receivers have been selected
        $lists = acym_loadResultArray('SELECT list_id FROM #__acym_mail_has_list WHERE mail_id = '.intval($campaign->mail_id));
        if (empty($lists)) {
            $this->errors[] = acym_translation('ACYM_NO_LIST_SELECTED');

            return false;
        }
        acym_arrayToInteger($lists);

        $date = acym_date('now', 'Y-m-d H:i:s', false);
        if (empty($result)) {
            $conditions = [
                '`user`.`active` = 1',
                '`ul`.`status` = 1',
                '`ul`.`list_id` IN ('.implode(',', $lists).')',
            ];
            if ($this->config->get('require_confirmation', 1) == 1) $conditions[] = '`user`.`confirmed` = 1';

            $automationHelper = new AutomationHelper();
            $automationHelper->join['ul'] = ' #__acym_user_has_list AS ul ON ul.user_id = user.id AND ul.list_id IN ('.implode(',', $lists).') AND ul.status = 1 ';
            if (acym_isMultilingual()) {
                $select = ['IF(mail.id IS NULL, '.intval($campaign->mail_id).', `mail`.`id`)', 'ul.`user_id`', acym_escapeDB($date)];
                $automationHelper->leftjoin['mail'] = '`#__acym_mail` AS mail ON `mail`.`language` = `user`.language AND `mail`.`parent_id` = '.intval($campaign->mail_id);
            } else {
                $select = [intval($campaign->mail_id), 'ul.`user_id`', acym_escapeDB($date)];
            }

            if (!empty($campaign->sending_params['resendTarget']) && 'new' === $campaign->sending_params['resendTarget']) {
                $automationHelper->leftjoin['us'] = '`#__acym_user_stat` AS `us` ON `us`.`user_id` = `user`.`id` AND `us`.`mail_id` = '.intval($campaign->mail_id);
                $conditions[] = '`us`.`user_id` IS NULL';
            }

            $automationHelper->where = $conditions;

            $segmentsController = new SegmentsController();
            $automationHelper->removeFlag($segmentsController::FLAG_USERS);

            $automationHelpers = [];

            foreach ($filters as $or => $orValues) {
                $automationHelpers[$or] = clone $automationHelper;
                foreach ($orValues as $and => $andValues) {
                    $and = intval($and);
                    foreach ($andValues as $filterName => $options) {
                        acym_trigger('onAcymProcessFilter_'.$filterName, [&$automationHelpers[$or], &$options, &$and]);
                    }
                }
            }

            if (empty($automationHelpers)) {
                $insertQuery = 'INSERT IGNORE INTO `#__acym_queue` (`mail_id`, `user_id`, `sending_date`) '.$automationHelper->getQuery($select);
                $result = acym_query($insertQuery);
            } else {
                foreach ($automationHelpers as $oneAutomationHelper) {
                    $insertQuery = 'INSERT IGNORE INTO `#__acym_queue` (`mail_id`, `user_id`, `sending_date`) '.$oneAutomationHelper->getQuery($select);
                    $result += acym_query($insertQuery);
                }
            }
        }

        if ($campaign->sending_type == self::SENDING_TYPE_NOW) {
            $campaign->sending_date = $date;
            $campaign->draft = 0;
            $this->save($campaign);
        }

        $mailStatClass = new MailStatClass();
        $mailStat = $mailStatClass->getOneRowByMailId($campaign->mail_id);

        if (empty($mailStat)) {
            $mailStat = [];
            $mailStat['mail_id'] = intval($campaign->mail_id);
            $mailStat['total_subscribers'] = 0;
        } else {
            $mailStat = get_object_vars($mailStat);
        }

        $mailStat['total_subscribers'] += intval($result);
        $mailStat['send_date'] = $date;

        if (!empty($mailStat['sent'])) unset($mailStat['sent']);

        $mailStatClass->save($mailStat);

        if ($result === 0) {
            $this->errors[] = acym_translation('ACYM_NO_SUBSCRIBERS_FOUND');

            return false;
        }

        acym_query('UPDATE `#__acym_campaign` SET `sent` = 1, `active` = 1 WHERE `mail_id` = '.intval($campaign->mail_id));

        return $result;
    }

    public function getCampaignForDashboard()
    {
        $query = 'SELECT campaign.*, mail.name as name FROM #__acym_campaign as campaign LEFT JOIN #__acym_mail as mail ON campaign.mail_id = mail.id WHERE `active` = 1 AND `sending_type` = '.acym_escapeDB(
                self::SENDING_TYPE_SCHEDULED
            ).' AND `sent` = 0 LIMIT 3';

        return $this->decode(acym_loadObjectList($query));
    }

    public function getOpenRateOneCampaign($mail_id)
    {
        $query = 'SELECT sent, open_unique FROM #__acym_mail_stat 
                    WHERE mail_id = '.intval($mail_id);

        return acym_loadObject($query);
    }

    public function getOpenRateAllCampaign()
    {
        $query = 'SELECT SUM(sent) as sent, SUM(open_unique) as open_unique FROM #__acym_mail_stat';

        return acym_loadObject($query);
    }

    public function getBounceRateAllCampaign()
    {
        $query = 'SELECT SUM(sent) as sent, SUM(bounce_unique) as bounce_unique FROM #__acym_mail_stat';

        return acym_loadObject($query);
    }


    public function getBounceRateOneCampaign($mail_id)
    {
        $query = 'SELECT sent, bounce_unique FROM #__acym_mail_stat 
                    WHERE mail_id = '.intval($mail_id);

        return acym_loadObject($query);
    }

    public function getOpenByMonth($mail_id = '', $start = '', $end = '')
    {
        $query = 'SELECT COUNT(user_id) as open, DATE_FORMAT(open_date, \'%Y-%m\') as open_date FROM #__acym_user_stat WHERE open > 0';
        $query .= empty($mail_id) ? '' : ' AND  `mail_id`='.intval($mail_id);
        $query .= ' AND `open_date` > "0000-00-00"';
        $query .= empty($start) ? '' : ' AND `open_date` >= '.acym_escapeDB($start);
        $query .= empty($end) ? '' : ' AND `open_date` <= '.acym_escapeDB($end);
        $query .= ' GROUP BY MONTH(open_date), YEAR(open_date) ORDER BY open_date';

        return acym_loadObjectList($query);
    }

    public function getOpenByWeek($mail_id = '', $start = '', $end = '')
    {
        $query = 'SELECT COUNT(user_id) as open, DATE_FORMAT(open_date, \'%Y-%m-%d\') as open_date FROM #__acym_user_stat WHERE open > 0';
        $query .= empty($mail_id) ? '' : ' AND  `mail_id`='.intval($mail_id);
        $query .= ' AND `open_date` > "0000-00-00"';
        $query .= empty($start) ? '' : ' AND `open_date` >= '.acym_escapeDB($start);
        $query .= empty($end) ? '' : ' AND `open_date` <= '.acym_escapeDB($end);
        $query .= ' GROUP BY WEEK(open_date), YEAR(open_date) ORDER BY open_date';

        return acym_loadObjectList($query);
    }

    public function getOpenByDay($mail_id = '', $start = '', $end = '')
    {
        $query = 'SELECT COUNT(user_id) as open, DATE_FORMAT(open_date, \'%Y-%m-%d\') as open_date FROM #__acym_user_stat WHERE open > 0';
        $query .= empty($mail_id) ? '' : ' AND  `mail_id`='.intval($mail_id);
        $query .= ' AND `open_date` > "0000-00-00"';
        $query .= empty($start) ? '' : ' AND `open_date` >= '.acym_escapeDB($start);
        $query .= empty($end) ? '' : ' AND `open_date` <= '.acym_escapeDB($end);
        $query .= ' GROUP BY DAYOFYEAR(open_date), YEAR(open_date) ORDER BY open_date';

        return acym_loadObjectList($query);
    }

    public function getOpenByHour($mail_id = '', $start = '', $end = '')
    {
        $query = 'SELECT COUNT(user_id) as open, DATE_FORMAT(open_date, \'%Y-%m-%d %H:00:00\') as open_date FROM #__acym_user_stat WHERE open > 0';
        $query .= empty($mail_id) ? '' : ' AND  `mail_id`='.intval($mail_id);
        $query .= ' AND `open_date` > "0000-00-00 00:00:00"';
        $query .= empty($start) ? '' : ' AND `open_date` >= '.acym_escapeDB($start);
        $query .= empty($end) ? '' : ' AND `open_date` <= '.acym_escapeDB($end);
        $query .= ' GROUP BY HOUR(open_date), DAYOFYEAR(open_date), YEAR(open_date) ORDER BY open_date';

        return acym_loadObjectList($query);
    }

    public function getDevicesWithCountByMailId($mailId = '')
    {
        $query = 'SELECT device, COUNT(*) as number FROM #__acym_user_stat WHERE `open` > 0';
        if (!empty($mailId)) $query .= ' AND mail_id = '.intval($mailId);
        $query .= ' GROUP BY device';

        return acym_loadObjectList($query);
    }

    public function getLastNewsletters($params)
    {
        $mailClass = new MailClass();

        // Init select elements
        $querySelect = 'SELECT mail.*, campaign.sending_date ';
        $queryCountSelect = 'SELECT COUNT(*) FROM (SELECT DISTINCT mail.id ';

        // Form the query
        $query = 'FROM #__acym_campaign AS campaign
                  JOIN #__acym_mail AS mail ON campaign.mail_id = mail.id ';

        // We may need some joins depending on the selected options
        if (isset($params['userId']) || isset($params['lists'])) {
            $query .= 'JOIN #__acym_mail_has_list AS maillist ON mail.id = maillist.mail_id ';
            if (isset($params['userId'])) $query .= 'JOIN #__acym_user_has_list AS userlist ON maillist.list_id = userlist.list_id ';
        }

        // Make sure we display only active campaigns
        $where = 'WHERE campaign.active = 1 AND campaign.sent = 1 AND mail.type = '.acym_escapeDB($mailClass::TYPE_STANDARD).' AND campaign.visible = 1 ';

        // If we want an archive of some specific lists
        if (isset($params['lists'])) {
            acym_arrayToInteger($params['lists']);
            $where .= 'AND maillist.list_id IN ('.implode(', ', $params['lists']).') ';
        }

        // If we want an archive for a specific user
        if (isset($params['userId'])) {
            $where .= 'AND userlist.user_id = '.intval($params['userId']).' ';
        }

        // If the user search for a newsletter
        if (isset($params['search'])) {
            $search = acym_escapeDB('%'.utf8_encode($params['search']).'%');
            $where .= 'AND (mail.subject LIKE '.$search.' OR mail.name LIKE '.$search.')';
        }

        $query .= $where;

        // Make sure we display campaigns only once
        $endQuerySelect = 'GROUP BY mail.id ';
        $endQuerySelect .= 'ORDER BY campaign.sending_date DESC';

        // Init the pagination
        $page = isset($params['page']) ? $params['page'] : 0;
        $numberPerPage = isset($params['numberPerPage']) ? $params['numberPerPage'] : 0;
        $lastNewsletters = isset($params['limit']) ? $params['limit'] : 0;

        if (!empty($page) && !empty($numberPerPage)) {
            if (!empty($lastNewsletters)) {
                $limit = $page * $numberPerPage > $lastNewsletters ? fmod($lastNewsletters, $numberPerPage) : $numberPerPage;
            } else {
                $limit = $numberPerPage;
            }

            $offset = ($params['page'] - 1) * $numberPerPage;
            $endQuerySelect .= ' LIMIT '.intval($offset).', '.intval($limit);
        } elseif (!empty($lastNewsletters)) {
            $limit = $lastNewsletters;

            $endQuerySelect .= ' LIMIT '.intval($limit);
        }

        $return = [];

        $return['count'] = acym_loadResult($queryCountSelect.$query.') AS r ');
        $return['matchingNewsletters'] = $this->decode(acym_loadObjectList($querySelect.$query.$endQuerySelect));

        $userClass = new UserClass();
        $userEmail = acym_currentUserEmail();
        $user = $userClass->getOneByEmail($userEmail);

        foreach ($return['matchingNewsletters'] as $i => $oneNewsletter) {
            acym_trigger('replaceContent', [&$oneNewsletter, false]);
            acym_trigger('replaceUserInformation', [&$oneNewsletter, &$user, false]);

            $return['matchingNewsletters'][$i] = $oneNewsletter;
        }

        return $return;
    }

    public function getListsForCampaign($mailId)
    {
        $query = 'SELECT list_id FROM #__acym_mail_has_list WHERE mail_id = '.intval($mailId);

        return acym_loadResultArray($query);
    }

    public function triggerAutoCampaign()
    {
        $activeAutoCampaigns = acym_loadObjectList(
            'SELECT campaign.*, mail.name 
            FROM #__acym_campaign AS campaign 
            JOIN #__acym_mail AS mail ON campaign.`mail_id` = mail.`id` 
            WHERE `active` = 1 AND `sending_type` = '.acym_escapeDB(self::SENDING_TYPE_AUTO)
        );
        $activeAutoCampaigns = $this->decode($activeAutoCampaigns);

        if (empty($activeAutoCampaigns)) return;

        $mailClass = new MailClass();
        $time = time();

        foreach ($activeAutoCampaigns as $campaign) {
            // Check the start date
            if (!empty($campaign->sending_params['start_date']) && (int)acym_getTime(acym_date($campaign->sending_params['start_date'], 'Y-m-d H:i')) > $time) continue;

            //check if we trigger the campaign
            $step = new \stdClass();
            $step->triggers = $campaign->sending_params;
            $step->last_execution = $campaign->last_generated;
            $step->next_execution = $campaign->next_trigger;

            $data = ['time' => $time];
            $execute = !empty($step->next_execution) && $step->next_execution <= $data['time'];
            acym_trigger('onAcymExecuteTrigger', [&$step, &$execute, &$data], 'plgAcymTime');
            $campaign->next_trigger = $step->next_execution;
            if (!$execute) {
                $this->save($campaign);
                continue;
            }

            //update the campaign
            $campaignMail = $mailClass->getOneById($campaign->mail_id);

            $lastGenerated = $campaign->last_generated;
            $shouldGenerate = $this->_updateAutoCampaign($campaign, $campaignMail, $time);
            $this->save($campaign);

            if (!$shouldGenerate) continue;

            //We generate the new campaign
            $generatedCampaign = $this->_generateCampaign($campaign, $campaignMail, $lastGenerated, $mailClass);

            //We send it if needed
            if (empty($campaign->sending_params['need_confirm_to_send'])) $this->send($generatedCampaign->id);
        }
    }

    private function shouldGenerateCampaign($campaign, $campaignMail)
    {
        // The generateByCategory function is the only one that can stop a campaign generation, with min number of items
        $results = acym_trigger(
            'generateByCategory',
            [&$campaignMail],
            null,
            function ($plugin) {
                $plugin->generateCampaignResult->status = true;
            }
        );

        // If one of the return statuses is "false", we won't generate the campaign
        foreach ($results as $oneResult) {
            if (isset($oneResult->status) && !$oneResult->status) {
                $this->messages[] = acym_translationSprintf('ACYM_CAMPAIGN_NOT_GENERATED', $campaign->name, $oneResult->message);

                return false;
            }
        }

        return true;
    }

    private function _updateAutoCampaign(&$campaign, $campaignMail, $time)
    {
        if (!$this->shouldGenerateCampaign($campaign, $campaignMail)) return false;

        if (empty($campaign->sending_params['number_generated'])) {
            $campaign->sending_params['number_generated'] = 1;
        } else {
            $campaign->sending_params['number_generated']++;
        }
        $campaign->last_generated = $time;

        return true;
    }

    private function _generateCampaign($campaign, $campaignMail, $lastGenerated, $mailClass)
    {
        $newMail = $this->_generateMailAutoCampaign($campaignMail, $campaign->sending_params['number_generated']);
        $newCampaign = new \stdClass();
        $newCampaign->mail_id = $newMail->id;
        $newCampaign->parent_id = $campaign->id;
        $newCampaign->active = 1;
        $newCampaign->draft = 1;
        $newCampaign->sending_type = self::SENDING_TYPE_NOW;
        $newCampaign->sent = 0;
        $newCampaign->last_generated = $lastGenerated;
        $newCampaign->sending_params = empty($campaign->sending_params['segment']) ? '' : ['segment' => $campaign->sending_params['segment']];

        $newCampaign->id = $this->save($newCampaign);

        // Replace content in the generated mail. MUST be done after campaign has been saved
        acym_trigger('replaceContent', [&$newMail, false]);
        $mailClass->save($newMail);

        return $newCampaign;
    }

    private function _generateMailAutoCampaign($newMail, $generatedMail)
    {
        $mailId = $newMail->id;
        unset($newMail->id);
        $newMail->name .= ' #'.$generatedMail;

        $mailClass = new MailClass();
        $newMail->id = $mailClass->save($newMail);
        $this->_setListToGeneratedCampaign($mailId, $newMail->id);

        if (acym_isMultilingual()) $this->generateMailAutoCampaignMultilingual($mailId, $generatedMail, $newMail->id);

        return $newMail;
    }

    private function generateMailAutoCampaignMultilingual($mailId, $generatedMail, $newParentId)
    {
        $mailClass = new MailClass();
        $mails = $mailClass->getTranslationsById($mailId, true);

        foreach ($mails as $mail) {
            unset($mail->id);
            $mail->name .= ' #'.$generatedMail;
            $mail->parent_id = $newParentId;

            $mailClass->save($mail);
        }
    }

    private function _setListToGeneratedCampaign($parentMailId, $newMailId)
    {
        $mailClass = new MailClass();
        $lists = $mailClass->getAllListsByMailId($parentMailId);
        $listIds = [];
        foreach ($lists as $list) {
            $listIds[] = $list->id;
        }

        return $this->manageListsToCampaign($listIds, $newMailId);
    }

    public function getLastGenerated($mailId)
    {
        return acym_loadResult(
            'SELECT `last_generated` 
            FROM #__acym_campaign 
            WHERE `mail_id` = '.intval($mailId)
        );
    }

    public function getAllCampaignsGenerated()
    {
        $query = 'SELECT id FROM #__acym_campaign WHERE parent_id IS NOT NULL AND sending_type = '.acym_escapeDB(
                self::SENDING_TYPE_NOW
            ).' AND draft = 1 AND active = 1 AND sent = 0';

        return acym_loadObjectList($query);
    }

    public function getCampaignsByTypes($campaignTypes, $onlyActives = false)
    {
        if (empty($campaignTypes)) return [];
        $campaignTypes = array_map('acym_escapeDB', $campaignTypes);
        $query = 'SELECT campaign.* FROM #__acym_campaign AS campaign WHERE campaign.sending_type IN ('.implode(',', $campaignTypes).')';
        if ($onlyActives) $query .= ' AND campaign.active = 1';

        return $this->decode(acym_loadObjectList($query), false);
    }
}
