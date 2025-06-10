<?php

namespace AcyMailing\Controllers\Lists;

use AcyMailing\Classes\HistoryClass;
use AcyMailing\Classes\ListClass;
use AcyMailing\Classes\MailClass;
use AcyMailing\Classes\MailStatClass;
use AcyMailing\Classes\TagClass;
use AcyMailing\Classes\UrlClickClass;
use AcyMailing\Controllers\ListsController;
use AcyMailing\Helpers\EntitySelectHelper;
use AcyMailing\Helpers\WorkflowHelper;

trait Edition
{
    public function settings(): void
    {
        acym_setVar('layout', 'settings');

        $tab = acym_getVar('string', 'step', ListsController::LIST_EDITION_TABS_GENERAL);

        // In the front with the SEF activated, the tab is not always set
        if (empty($tab)) {
            $tab = ListsController::LIST_EDITION_TABS_GENERAL;
        }

        $data = [];
        $data['svg'] = acym_loaderLogo(false);
        $data['workflowHelper'] = new WorkflowHelper();
        $data['currentTab'] = $tab;
        $data['tabs'] = [
            'general' => ListsController::LIST_EDITION_TABS_GENERAL,
            'subscribers' => ListsController::LIST_EDITION_TABS_SUBSCRIBERS,
            'unsubscriptions' => ListsController::LIST_EDITION_TABS_UNSUBSCRIPTIONS,
        ];

        $listId = acym_getVar('int', 'listId', 0);

        if (!$this->prepareListSettings($data, $listId)) return;

        if ($tab === ListsController::LIST_EDITION_TABS_GENERAL) {
            $this->prepareTagsSettings($data, $listId);
            $this->prepareListStat($data, $listId);
            $this->prepareListStatEvolution($data, $listId);
            $this->prepareWelcomeUnsubData($data);
            $this->prepareMultilingualOption($data);
        } elseif ($tab === ListsController::LIST_EDITION_TABS_SUBSCRIBERS) {
            $this->prepareSubscribersEntitySelect($data, $listId);
            $this->prepareSubscribersSettings($data, $listId);
        } elseif ($tab === ListsController::LIST_EDITION_TABS_UNSUBSCRIPTIONS) {
            $this->prepareUnsubReasons($data);
        }

        parent::display($data);
    }

    private function prepareListSettings(array &$data, int $listId): bool
    {
        if (empty($listId)) {
            $listInformation = new \stdClass();
            $listInformation->id = '';
            $listInformation->name = '';
            $listInformation->frontLabel = '';
            $listInformation->description = '';
            $listInformation->active = 1;
            $listInformation->visible = 1;
            $randColor = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9', 'a', 'b', 'c', 'd', 'e', 'f'];
            $listInformation->color = '#'.$randColor[rand(0, 15)].$randColor[rand(0, 15)].$randColor[rand(0, 15)].$randColor[rand(0, 15)].$randColor[rand(0, 15)].$randColor[rand(
                    0,
                    15
                )];
            $listInformation->welcome_id = '';
            $listInformation->unsubscribe_id = '';
            $listInformation->access = [];
            $listInformation->tracking = 1;
            $listInformation->translation = [];
            $listInformation->display_name = '';

            $this->breadcrumb[acym_translation('ACYM_NEW_LIST')] = acym_completeLink('lists&task=settings');
        } else {
            $listClass = new ListClass();
            $listInformation = $listClass->getOneById($listId);
            if (is_null($listInformation)) {
                acym_enqueueMessage(acym_translation('ACYM_LIST_DOESNT_EXIST'), 'error');
                $this->listing();

                return false;
            }


            $subscribersCount = $listClass->getSubscribersCountPerStatusByListId([$listId]);

            $this->breadcrumb[acym_escape($listInformation->name)] = acym_completeLink('lists&task=settings&listId='.$listId);

            $listInformation->access = empty($listInformation->access) ? [] : explode(',', $listInformation->access);

            $currentUser = acym_currentUserId();
            if (!acym_isAdmin() && ($listInformation->cms_user_id != $currentUser)) {
                $userGroups = acym_getGroupsByUser($currentUser);
                $canAccess = false;

                foreach ($userGroups as $group) {
                    if (in_array($group, $listInformation->access)) $canAccess = true;
                }

                if (!$canAccess) {
                    acym_enqueueMessage(acym_translation('ACYM_YOU_DONT_HAVE_ACCESS_TO_THIS_LIST'), 'error');

                    $this->listing();

                    return false;
                }
            }
        }

        $listInformation->subscribers = [
            'unsubscribed_users' => 0,
            'sendable_users' => 0,
            'unconfirmed_users' => 0,
            'inactive_users' => 0,
        ];
        if (!empty($subscribersCount)) {
            $listStats = array_shift($subscribersCount);
            $listInformation->subscribers = [
                'unsubscribed_users' => $listStats->unsubscribed_users,
                'sendable_users' => $listStats->sendable_users,
                'unconfirmed_users' => $listStats->unconfirmed_users,
                'inactive_users' => $listStats->inactive_users,
            ];
        }

        $data['listInformation'] = $listInformation;

        return true;
    }

    private function prepareTagsSettings(array &$data, int $listId): void
    {
        $tagClass = new TagClass();
        $data['allTags'] = $tagClass->getAllTagsByType(TagClass::TYPE_LIST);
        $data['listTagsName'] = [];
        $listsTags = $tagClass->getAllTagsByElementId(TagClass::TYPE_LIST, $listId);
        foreach ($listsTags as $oneTag) {
            $data['listTagsName'][] = $oneTag;
        }
    }

    private function prepareSubscribersSettings(array &$data, int $listId): void
    {
        $data['ordering'] = acym_getVar('string', 'users_ordering', 'id');
        $data['orderingSortOrder'] = acym_getVar('string', 'users_ordering_sort_order', 'desc');
        $data['classSortOrder'] = $data['orderingSortOrder'] == 'asc' ? 'acymicon-sort-amount-asc' : 'acymicon-sort-amount-desc';
        $listClass = new ListClass();
        $data['subscribers'] = $listClass->getSubscribersForList(
            [
                'listIds' => [$listId],
                'offset' => 0,
                'limit' => 500,
                'status' => 1,
                'orderBy' => $data['ordering'],
                'orderBySort' => $data['orderingSortOrder'],
            ]
        );
        foreach ($data['subscribers'] as &$oneSub) {
            if ($oneSub->subscription_date == '0000-00-00 00:00:00') continue;
            $oneSub->subscription_date = acym_date(strtotime($oneSub->subscription_date), acym_translation('ACYM_DATE_FORMAT_LC2'));
        }
    }

    private function prepareSubscribersEntitySelect(array &$data, int $listId): void
    {
        if (empty($listId)) {
            $data['subscribersEntitySelect'] = '';

            return;
        }

        $entityHelper = new EntitySelectHelper();

        $data['subscribersEntitySelect'] = acym_modal(
            acym_translation('ACYM_MANAGE_SUBSCRIBERS'),
            $entityHelper->entitySelect(
                'user',
                ['join' => 'join_list-'.$listId],
                $entityHelper->getColumnsForUser('userlist.user_id'),
                ['text' => acym_translation('ACYM_CONFIRM'), 'action' => 'saveSubscribers'],
                true,
                '',
                'subscriber'
            ),
            'acym__lists__settings__subscribers__entity__modal',
            '',
            'class="cell medium-6 large-shrink button button-secondary"'
        );
    }

    private function prepareListStat(array &$data, int $listId): void
    {
        $data['listStats'] = ['deliveryRate' => 0, 'openRate' => 0, 'clickRate' => 0, 'failRate' => 0, 'bounceRate' => 0];
        if (empty($listId)) return;
        $listClass = new ListClass();
        $mails = $listClass->getMailsByListId($listId);
        if (empty($mails)) return;

        $mailStatClass = new MailStatClass();
        $mailsStat = $mailStatClass->getCumulatedStatsByMailIds($mails);

        if (intval($mailsStat->sent) + intval($mailsStat->fails) === 0) return;

        $totalSent = intval($mailsStat->sent) + intval($mailsStat->fails);
        if (empty($mailsStat->open)) $mailsStat->open = 0;
        if (empty($mailsStat->fails)) $mailsStat->fails = 0;
        if (empty($mailsStat->bounces)) $mailsStat->bounces = 0;

        $data['listStats']['openRate'] = number_format($mailsStat->open / $totalSent * 100, 2);
        $data['listStats']['deliveryRate'] = number_format(($mailsStat->sent - $mailsStat->bounces) / $totalSent * 100, 2);
        $data['listStats']['failRate'] = number_format($mailsStat->fails / $totalSent * 100, 2);
        $data['listStats']['bounceRate'] = number_format($mailsStat->bounces / $totalSent * 100, 2);

        $urlClickClass = new UrlClickClass();
        $nbClicks = $urlClickClass->getClickRateByMailIds($mails);
        $data['listStats']['clickRate'] = number_format($nbClicks / $totalSent * 100, 2);
    }

    private function prepareListStatEvolution(array &$data, int $listId): void
    {
        $data['evol'] = [];
        $listClass = new ListClass();
        $subEvolStat = $listClass->getYearSubEvolutionPerList($listId);
        if (empty($subEvolStat['subscribers']) && empty($subEvolStat['unsubscribers'])) return;

        // Init tables ordered by month number starting on month from one year ago
        $firstMonth = date('n') + 1;
        $zeroReached = false;
        $evolSub = [];
        $evolUnsub = [];
        for ($i = 0 ; $i < 12 ; $i++) {
            $month = ($firstMonth + $i) % 13;
            if ($month == 0) $zeroReached = true;
            if ($zeroReached) $month += 1;
            $evolSub[$month] = $month.'_0';
            $evolUnsub[$month] = $month.'_0';
        }

        foreach ($subEvolStat['subscribers'] as $unit => $monthData) {
            $evolSub[$monthData->monthSub] = $monthData->monthSub.'_'.$monthData->nbUser;
        }

        foreach ($subEvolStat['unsubscribers'] as $unit => $monthData) {
            $evolUnsub[$monthData->monthUnsub] = $monthData->monthUnsub.'_'.$monthData->nbUser;
        }

        foreach ($evolSub as $month => $oneEvol) {
            $data['evol'][0][] = $oneEvol;
            $data['evol'][1][] = $evolUnsub[$month];
        }
    }

    protected function prepareWelcomeUnsubData(array &$data): void
    {
        $data['tmpls'] = [];
        if (empty($data['listInformation']->id)) return;
        $listClass = new ListClass();
        $mailClass = new MailClass();

        foreach ([MailClass::TYPE_WELCOME => 'welcome', MailClass::TYPE_UNSUBSCRIBE => 'unsub'] as $full => $short) {
            $mailId = acym_getVar('int', $short.'mailid', 0);
            if (empty($data['listInformation']->{$full.'_id'}) && !empty($mailId)) {
                $data['listInformation']->{$full.'_id'} = $mailId;
                $listInfoSave = clone $data['listInformation'];
                unset($listInfoSave->subscribers);
                if (!$listClass->save($listInfoSave)) acym_enqueueMessage(acym_translation('ACYM_ERROR_SAVE_LIST'), 'error');
            }

            $returnLink = acym_completeLink('lists&task=settings&listId='.$data['listInformation']->id.'&edition=1&'.$short.'mailid={mailid}');
            $favoriteTemplate = $this->config->get('favorite_template', 0);
            $startFrom = empty($favoriteTemplate) ? '' : '&from='.$favoriteTemplate;

            if (empty($data['listInformation']->{$full.'_id'})) {
                $data['tmpls'][$short.'TmplUrl'] = acym_completeLink(
                    'mails&task=edit&step=editEmail&type='.$full.'&type_editor=acyEditor&list_id='.$data['listInformation']->id.'&return='.urlencode(
                        base64_encode($returnLink)
                    ).$startFrom
                );
            } else {
                $data['tmpls'][$short.'TmplUrl'] = acym_completeLink(
                    'mails&task=edit&id='.$data['listInformation']->{$full.'_id'}.'&type='.$full.'&list_id='.$data['listInformation']->id.'&return='.urlencode(
                        base64_encode($returnLink)
                    ).$startFrom
                );
            }

            $data['tmpls'][$full] = !empty($data['listInformation']->{$full.'_id'}) ? $mailClass->getOneById($data['listInformation']->{$full.'_id'}) : '';
        }
    }

    public function unsetMail(string $type): void
    {
        $listClass = new ListClass();
        $id = acym_getVar('int', 'listId', 0);
        $list = $listClass->getOneById($id);

        if (empty($list)) {
            acym_enqueueMessage(acym_translation('ACYM_ERROR_SAVE_LIST'), 'error');
            $this->listing();

            return;
        }

        if (!$listClass->hasUserAccess($id)) {
            die('Access denied for list '.acym_escape($id));
        }

        $list->$type = null;

        if ($listClass->save($list)) {
            acym_setVar('listId', $id);
            $this->settings();
        } else {
            acym_enqueueMessage(acym_translation('ACYM_ERROR_SAVE_LIST'), 'error');
            $this->listing();
        }
    }

    public function unsetWelcome(): void
    {
        $this->unsetMail('welcome_id');
    }

    public function unsetUnsubscribe(): void
    {
        $this->unsetMail('unsubscribe_id');
    }

    public function apply(): void
    {
        $this->save(false);
    }

    public function save(bool $goToListing = true): void
    {
        acym_checkToken();

        $step = acym_getVar('string', 'step');
        if ($step === self::LIST_EDITION_TABS_GENERAL) {

            $listClass = new ListClass();
            $formData = (object)acym_getVar('array', 'list', []);

            $listId = acym_getVar('int', 'listId', 0);
            if (!empty($listId)) {
                $formData->id = $listId;
            }

            $allowedFields = acym_getColumns('list');
            $listInformation = new \stdClass();
            if (empty($formData->welcome_id)) unset($formData->welcome_id);
            if (empty($formData->unsubscribe_id)) unset($formData->unsubscribe_id);
            foreach ($formData as $name => $data) {
                if (!in_array($name, $allowedFields)) {
                    continue;
                }
                $listInformation->{$name} = $data;
            }

            $listInformation->tags = acym_getVar('array', 'list_tags', []);

            if (acym_isAdmin()) {
                $listInformation->access = empty($listInformation->access) ? '' : ','.implode(',', $listInformation->access).',';
            } elseif (!empty($formData->id) && !$listClass->hasUserAccess($formData->id)) {
                die('Cannot save list '.acym_escape($formData->id));
            }

            $listId = $listClass->save($listInformation);

            if (!empty($listId)) {
                acym_setVar('listId', $listId);
                acym_enqueueMessage(acym_translationSprintf('ACYM_LIST_IS_SAVED', $listInformation->name), 'success');
                $this->_saveSubscribersTolist();
            } else {
                acym_enqueueMessage(acym_translation('ACYM_ERROR_SAVING'), 'error');
                if (!empty($listClass->errors)) {
                    acym_enqueueMessage($listClass->errors, 'error');
                }
            }
        }

        if ($goToListing) {
            $this->listing();
        } else {
            $this->settings();
        }
    }

    private function _saveSubscribersTolist(): bool
    {
        $usersIds = json_decode(acym_getVar('string', 'acym__entity_select__selected', '[]'));
        $usersIdsUnselected = json_decode(acym_getVar('string', 'acym__entity_select__unselected', '[]'));
        $listId = acym_getVar('int', 'listId', 0);

        $listClass = new ListClass();
        if (empty($listId) || !$listClass->hasUserAccess($listId)) return false;

        acym_arrayToInteger($usersIdsUnselected);
        if (!empty($usersIdsUnselected)) {
            acym_query(
                'UPDATE #__acym_user_has_list 
                SET status = 0, unsubscribe_date = '.acym_escapeDB(acym_date(time(), 'Y-m-d H:i:s')).' 
                WHERE list_id = '.intval($listId).' 
                    AND user_id IN ('.implode(', ', $usersIdsUnselected).')'
            );
        }

        acym_arrayToInteger($usersIds);
        if (!empty($usersIds)) {
            acym_query(
                'INSERT IGNORE #__acym_user_has_list (`user_id`, `list_id`, `status`, `subscription_date`) (SELECT id, '.intval($listId).', 1, '.acym_escapeDB(
                    acym_date(time(), 'Y-m-d H:i:s')
                ).' FROM #__acym_user AS user WHERE user.id IN ('.implode(', ', $usersIds).')) ON DUPLICATE KEY UPDATE status = 1'
            );
        }

        return true;
    }

    public function saveSubscribers(): void
    {
        acym_checkToken();

        $this->_saveSubscribersTolist();
        $listId = acym_getVar('int', 'listId', 0);
        acym_setVar('listId', $listId);

        $this->settings();
    }

    private function sortDataByList(array $data): array
    {
        $sortedData = [];

        foreach ($data as $entry) {
            $pattern = '/(?<!EXECUTED_BY::)\b\d+\b/';
            preg_match_all($pattern, $entry->data, $listNumbers);

            foreach ($listNumbers[0] as $listNumber) {
                if (!isset($sortedData[$listNumber])) {
                    $sortedData[$listNumber] = [];
                }

                $sortedData[$listNumber][] = $entry->unsubscribe_reason;
            }
        }

        return $sortedData;
    }

    private function prepareUnsubReasons(array &$data): void
    {
        $historyClass = new HistoryClass();
        $allReasonsData = $historyClass->getAllUnsubReasons();

        $sortedData = $this->sortDataByList($allReasonsData);
        $listId = acym_getVar('int', 'listId', 0);
        $currentListData = $sortedData[$listId] ?? [];
        $reasonCounts = array_count_values($currentListData);

        $allAnswers = $historyClass->getAllMainLanguageUnsubReasons();
        $newReasonCounts = [];
        foreach ($reasonCounts as $key => $value) {
            if (is_numeric($key) && isset($allAnswers[$key - 1])) {
                $newReasonCounts[$allAnswers[$key - 1]] = $value;
            } else {
                $newReasonCounts[$key] = $value;
            }
        }

        arsort($newReasonCounts);

        $data['unsubReasons'] = $newReasonCounts;
    }
}
